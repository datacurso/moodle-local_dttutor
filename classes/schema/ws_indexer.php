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
 * Web Service index — compact descriptions for RAG-style search.
 *
 * Uses external_api::external_function_info() to extract description,
 * parameter structure, and return structure for each WS function.
 * Results are stored per-function in schema_cache (24 h TTL).
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dttutor\schema;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/webservice/lib.php');

use core_external\external_api;
use core_external\external_description;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Indexes and searches Moodle web service functions.
 */
class ws_indexer {

    /**
     * Get compact info for one web service function.
     * Cached 24 h in schema_cache under key "ws_{wsname}".
     *
     * @param  string $wsname Function name (e.g. core_user_create_users).
     * @return array
     */
    public static function describe(string $wsname): array {
        global $DB;

        if (empty($wsname)) {
            return ['error' => 'wsname is required'];
        }

        $cache = \cache::make('local_dttutor', 'schema_cache');
        $cachekey = 'ws_' . $wsname;
        $cached = $cache->get($cachekey);
        if ($cached !== false) {
            return $cached;
        }

        $record = $DB->get_record('external_functions', ['name' => $wsname]);
        if (!$record) {
            return ['error' => "Web service function not found: {$wsname}"];
        }

        try {
            $info = external_api::external_function_info($record);
        } catch (\Throwable $e) {
            return ['error' => "Cannot load WS info: " . $e->getMessage()];
        }

        $result = self::build_compact_info($record, $info);
        $cache->set($cachekey, $result);
        return $result;
    }

    /**
     * Build compact metadata array from a full external_function_info object.
     *
     * @param  \stdClass $record Raw DB record from external_functions.
     * @param  \stdClass $info   Enriched info from external_api::external_function_info().
     * @return array
     */
    private static function build_compact_info(\stdClass $record, \stdClass $info): array {
        return [
            'name'        => $record->name,
            'description' => $info->description ?? '',
            'type'        => $info->type ?? 'unknown',
            'component'   => $record->component,
            'params'      => isset($info->parameters_desc)
                ? self::compact_params($info->parameters_desc)
                : '(none)',
            'returns'     => isset($info->returns_desc)
                ? self::compact_desc($info->returns_desc, 0)
                : 'void',
        ];
    }

    /**
     * Build (or refresh) the full WS index by caching compact info for every function.
     */
    private static function build_index(): void {
        global $DB;

        @set_time_limit(0);

        $records = $DB->get_records('external_functions');
        if (empty($records)) {
            return;
        }

        $cache = \cache::make('local_dttutor', 'schema_cache');

        foreach ($records as $record) {
            $cachekey = 'ws_' . $record->name;
            try {
                $info = external_api::external_function_info($record);
                $cache->set($cachekey, self::build_compact_info($record, $info));
            } catch (\Throwable $e) {
                continue;
            }
        }

        $cache->set('_ws_index_built_v2', time());
    }

    /**
     * Ensure the full index has been built within the last 24 hours.
     */
    private static function ensure_index(): void {
        $cache = \cache::make('local_dttutor', 'schema_cache');
        $built = $cache->get('_ws_index_built_v2');
        if ($built === false || (time() - $built) > DAYSECS) {
            self::build_index();
        }
    }

    /**
     * Search WS functions by keyword query, scored by relevance.
     *
     * @param  string $query   Natural language or keyword query.
     * @param  array  $wsnames Allowlist of function names (empty = all).
     * @param  int    $limit   Maximum results.
     * @return array           Top-N function info arrays, sorted by score descending.
     */
    public static function search(string $query, array $wsnames = [], int $limit = 8): array {
        if (empty($query)) {
            return [];
        }

        self::ensure_index();

        // No allowlist => search over all functions.
        if (empty($wsnames)) {
            global $DB;
            $wsnames = $DB->get_fieldset_sql('SELECT name FROM {external_functions}');
        }

        if (empty($wsnames)) {
            return [];
        }

        $expandedtokens = self::expand_query_tokens($query);
        if (empty($expandedtokens)) {
            return [];
        }

        $scored = [];
        foreach ($wsnames as $wsname) {
            $info = self::describe($wsname);
            if (isset($info['error'])) {
                continue;
            }

            $score = 0;
            $namelower = strtolower($wsname);
            $desclower = strtolower($info['description'] ?? '');
            $paramslower = strtolower($info['params'] ?? '');
            $returnslower = strtolower($info['returns'] ?? '');

            foreach ($expandedtokens as $tokeninfo) {
                $token = $tokeninfo['token'];
                $weight = (float)$tokeninfo['weight'];

                if (strpos($namelower, $token) !== false) {
                    $score += (10 * $weight);
                    if (preg_match('/(^|_)' . preg_quote($token, '/') . '(_|$)/', $namelower)) {
                        $score += (6 * $weight);
                    }
                }
                if (strpos($desclower, $token) !== false) {
                    $score += (4 * $weight);
                    if (preg_match('/\b' . preg_quote($token, '/') . '\b/', $desclower)) {
                        $score += (2 * $weight);
                    }
                }
                if (strpos($paramslower, $token) !== false) {
                    $score += (2 * $weight);
                }
                if (strpos($returnslower, $token) !== false) {
                    $score += (1 * $weight);
                }
            }

            if ($score > 0) {
                $scored[] = ['score' => $score, 'info' => $info];
            }
        }

        usort($scored, fn($a, $b) => $b['score'] - $a['score']);
        $top = array_slice($scored, 0, $limit);
        return array_map(fn($item) => $item['info'], $top);
    }

    /**
     * Expand query tokens with domain synonyms to improve WS recall.
     *
     * @param string $query
     * @return array<int, array{token:string,weight:float}>
     */
    private static function expand_query_tokens(string $query): array {
        $query = \core_text::strtolower(trim($query));
        if ($query === '') {
            return [];
        }

        $expanded = [];
        $basetokens = array_values(array_filter(
            preg_split('/[\s_\-]+/', $query),
            static fn(string $token): bool => strlen($token) >= 2
        ));

        foreach ($basetokens as $token) {
            self::add_weighted_token($expanded, $token, 1.0);
        }

        $synonymmap = [
            'course' => ['courses', 'gradebook', 'grades', 'gradeitems'],
            'activity' => ['activities', 'module', 'modules', 'cmid'],
            'assignment' => ['assign', 'submissions', 'grading', 'rubric'],
            'assign' => ['assignment', 'submissions', 'grading', 'rubric'],
            'rubric' => ['grading', 'definitions', 'criteria', 'submissions'],
            'grade' => ['grades', 'gradebook', 'gradeitems', 'grademax'],
            'grades' => ['grade', 'gradebook', 'gradeitems', 'grademax'],
            'points' => ['grade', 'grademax', 'gradeitems', 'total'],
            'total' => ['sum', 'points', 'grademax', 'gradeitems'],
        ];

        foreach ($basetokens as $token) {
            if (!isset($synonymmap[$token])) {
                continue;
            }
            foreach ($synonymmap[$token] as $synonym) {
                self::add_weighted_token($expanded, $synonym, 0.65);
            }
        }

        if (strpos($query, 'total points') !== false || strpos($query, 'course total') !== false) {
            self::add_weighted_token($expanded, 'gradebook', 0.9);
            self::add_weighted_token($expanded, 'gradeitems', 0.9);
            self::add_weighted_token($expanded, 'grademax', 0.9);
        }

        return array_values($expanded);
    }

    /**
     * Add or upgrade weighted token in expansion map.
     *
     * @param array<string, array{token:string,weight:float}> $expanded
     * @param string $token
     * @param float $weight
     */
    private static function add_weighted_token(array &$expanded, string $token, float $weight): void {
        $token = \core_text::strtolower(trim($token));
        if ($token === '' || strlen($token) < 2) {
            return;
        }

        if (!isset($expanded[$token])) {
            $expanded[$token] = ['token' => $token, 'weight' => $weight];
            return;
        }

        if ($weight > $expanded[$token]['weight']) {
            $expanded[$token]['weight'] = $weight;
        }
    }

    /**
     * Compact top-level parameter structure.
     *
     * @param  external_single_structure $params
     * @return string
     */
    private static function compact_params(external_single_structure $params): string {
        $parts = [];
        foreach ($params->keys as $keyname => $keydesc) {
            $req = ($keydesc->required === VALUE_REQUIRED) ? '*' : '?';
            $parts[] = $keyname . $req . ':' . self::compact_desc($keydesc, 1);
        }
        return '{' . implode(', ', $parts) . '}';
    }

    /**
     * Recursively render an external_description as compact text.
     *
     * @param  external_description $desc
     * @param  int                  $depth Current nesting depth.
     * @return string
     */
    private static function compact_desc(external_description $desc, int $depth = 0): string {
        if ($desc instanceof external_multiple_structure) {
            return '[' . self::compact_desc($desc->content, $depth) . ']';
        }
        if ($desc instanceof external_single_structure) {
            if ($depth >= 3) {
                return '{...}';
            }
            $parts = [];
            $count = 0;
            $total = count($desc->keys);
            foreach ($desc->keys as $k => $v) {
                $req = ($v->required === VALUE_REQUIRED) ? '*' : '?';
                $parts[] = $k . $req . ':' . self::compact_desc($v, $depth + 1);
                $count++;
                if ($count >= 15 && $total > 15) {
                    $parts[] = '+' . ($total - 15) . ' more';
                    break;
                }
            }
            return '{' . implode(', ', $parts) . '}';
        }
        if ($desc instanceof external_value) {
            return self::value_type($desc);
        }
        return 'mixed';
    }

    /**
     * Map a PARAM_* constant to a short type name.
     *
     * @param  external_value $v
     * @return string
     */
    private static function value_type(external_value $v): string {
        switch ($v->type) {
            case PARAM_BOOL:
                return 'bool';
            case PARAM_INT:
                return 'int';
            case PARAM_FLOAT:
                return 'float';
            default:
                return 'str';
        }
    }
}
