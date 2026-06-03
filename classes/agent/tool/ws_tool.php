<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * WebService tool — calls Moodle external functions via the internal API.
 *
 * Uses direct reflection on the WS class method so no registered web service
 * or token is required. The current user's permissions are inherited via
 * Moodle's own capability system.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\agent\tool;

defined('MOODLE_INTERNAL') || die();

/**
 * Tool that calls any Moodle web service function via PHP reflection.
 */
class ws_tool implements agent_tool {

    /** @var int User ID to run permission checks as. */
    private int $userid;

    public function __construct(int $userid = 0) {
        $this->userid = $userid;
    }

    public function get_name(): string {
        return 'call_webservice';
    }

    public function get_definition(): array {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'call_webservice',
                'description' => 'Call a Moodle web service function. The function will only work if the user has the required permissions in Moodle. Returns the function result or an error message.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'function' => [
                            'type' => 'string',
                            'description' => 'The web service function name (e.g. core_user_get_users, core_course_get_courses).',
                        ],
                        'params' => [
                            'type' => 'object',
                            'description' => 'Parameters to pass to the function as key-value pairs.',
                        ],
                    ],
                    'required' => ['function'],
                ],
            ],
        ];
    }

    public function execute(\stdClass $params): string {
        global $DB;

        // Accept both 'function' and 'wsname' for flexibility.
        $function = trim($params->function ?? $params->wsname ?? '');
        if (empty($function)) {
            return json_encode(['error' => 'Empty function name']);
        }

        // Prepare params.
        $callerparams = self::normalize_params_value($params->params ?? []);
        if (!is_array($callerparams)) {
            $callerparams = [];
        }
        $callerparams = self::normalize_call_params($function, $callerparams);

        try {
            $funcrecord = $DB->get_record(
                'external_functions',
                ['name' => $function],
                'classname, classpath, component, methodname'
            );
            if (!$funcrecord) {
                return json_encode(['error' => "Web service function '{$function}' not found"]);
            }

            $classname = \local_dttutor_resolve_ws_classname($function);
            if (!$classname || !class_exists($classname)) {
                return json_encode(['error' => "Class '{$classname}' not found"]);
            }

            $methodname = $funcrecord->methodname;
            if (!method_exists($classname, $methodname)) {
                return json_encode(['error' => "Method '{$classname}::{$methodname}()' not found"]);
            }

            // Map params by name via reflection.
            $ref = new \ReflectionMethod($classname, $methodname);
            $callargs = [];
            foreach ($ref->getParameters() as $param) {
                $name = $param->getName();
                if (array_key_exists($name, $callerparams)) {
                    $callargs[] = $callerparams[$name];
                } elseif ($param->isDefaultValueAvailable()) {
                    $callargs[] = $param->getDefaultValue();
                } else {
                    return json_encode(['error' => "Missing required parameter: {$name}"]);
                }
            }

            // Temporarily override $USER for capability checks.
            global $USER;
            $origuser = $USER;

            $targetuserid = $this->userid;
            if ($targetuserid <= 0) {
                $targetuserid = (int)get_config('local_dttutor', 'serviceuserid');
            }
            if ($targetuserid <= 0) {
                $admin = \get_admin();
                $targetuserid = $admin ? (int)$admin->id : 2;
            }
            $USER = \core_user::get_user($targetuserid, '*', MUST_EXIST);

            try {
                $result = $ref->invokeArgs(null, $callargs);
            } finally {
                $USER = $origuser;
            }

            $result = self::format_timestamps($result);

            return json_encode([
                'success' => true,
                'data' => $result,
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            return json_encode([
                'error' => $e->getMessage(),
                'function' => $function,
            ]);
        }
    }

    /**
     * Normalize known WS params that frequently fail for the model.
     *
     * @param string $function WS function name.
     * @param array $callerparams Raw caller parameters.
     * @return array
     */
    private static function normalize_call_params(string $function, array $callerparams): array {
        if ($function !== 'core_grading_get_definitions') {
            return $callerparams;
        }

        $rawareaname = (string)($callerparams['areaname'] ?? '');
        $areaname = \core_text::strtolower(trim($rawareaname));

        if (in_array($areaname, ['assign', 'assignment', 'mod_assign'], true)) {
            $callerparams['areaname'] = 'submissions';
            return $callerparams;
        }

        if ($areaname === 'submissions') {
            return $callerparams;
        }

        $cmids = $callerparams['cmids'] ?? [];
        if (!is_array($cmids) || empty($cmids)) {
            return $callerparams;
        }

        $modulenames = self::get_module_names_by_cmids($cmids);
        if (empty($modulenames)) {
            return $callerparams;
        }

        $uniquemodules = array_values(array_unique($modulenames));
        if (count($uniquemodules) === 1 && $uniquemodules[0] === 'assign') {
            $callerparams['areaname'] = 'submissions';
        }

        return $callerparams;
    }

    /**
     * Resolve module names by course module IDs.
     *
     * @param array $cmids Course module IDs.
     * @return array<int, string> Map cmid => modname.
     */
    private static function get_module_names_by_cmids(array $cmids): array {
        global $DB;

        $normalized = [];
        foreach ($cmids as $cmid) {
            $cmid = (int)$cmid;
            if ($cmid > 0) {
                $normalized[] = $cmid;
            }
        }

        if (empty($normalized)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($normalized, SQL_PARAMS_NAMED);
        $sql = 'SELECT cm.id AS cmid, m.name AS modname
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.id ' . $insql;

        $records = $DB->get_records_sql($sql, $params);
        $modulenames = [];
        foreach ($records as $record) {
            $modulenames[(int)$record->cmid] = (string)$record->modname;
        }

        return $modulenames;
    }

    /**
     * Normalize tool-call params recursively to PHP native arrays/scalars.
     *
     * @param mixed $value Value from decoded JSON arguments.
     * @return mixed
     */
    private static function normalize_params_value($value) {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::normalize_params_value($item);
            }
            return $value;
        }

        if (is_object($value)) {
            $normalized = [];
            foreach ((array)$value as $key => $item) {
                $normalized[$key] = self::normalize_params_value($item);
            }
            return $normalized;
        }

        return $value;
    }

    /**
     * Recursively format Unix timestamps as human-readable dates.
     *
     * Converts integer fields whose name contains "time" or "date" and whose
     * value is in a valid Unix timestamp range (> 1000000000).
     *
     * @param mixed $data Result data to scan.
     * @return mixed Data with timestamps replaced by formatted date strings.
     */
    private static function format_timestamps($data) {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = self::format_timestamps_field($key, $value);
            }
            return $result;
        }

        if (is_object($data)) {
            $result = [];
            foreach ((array)$data as $key => $value) {
                $result[$key] = self::format_timestamps_field($key, $value);
            }
            return (object)$result;
        }

        return $data;
    }

    /**
     * Format a single field's value if it looks like a timestamp.
     *
     * @param string $key Field name.
     * @param mixed $value Field value.
     * @return mixed
     */
    private static function format_timestamps_field($key, $value) {
        $keylower = strtolower($key);

        if ((is_array($value) || is_object($value)) && !is_string($value)) {
            return self::format_timestamps($value);
        }

        if (is_int($value) || is_float($value)) {
            if ($value > 1000000000 && $value < 1999999999) {
                if (strpos($keylower, 'time') !== false || strpos($keylower, 'date') !== false) {
                    return userdate((int)$value);
                }
            }
        }

        return $value;
    }
}
