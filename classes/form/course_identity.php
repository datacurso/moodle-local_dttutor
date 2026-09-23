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

namespace local_dttutor\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * How the tutor introduces itself in one course.
 *
 * Only the name and the greeting: the institutional instructions of tone and limits belong to the
 * site, so a course cannot loosen them.
 *
 * @package    local_dttutor
 * @copyright  2026 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_identity extends \moodleform {
    /**
     * Fields of the form.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id', $this->_customdata['courseid']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'tutorname', get_string('course_tutorname', 'local_dttutor'), ['size' => 48]);
        $mform->setType('tutorname', PARAM_TEXT);
        $mform->addHelpButton('tutorname', 'course_tutorname', 'local_dttutor');

        $mform->addElement(
            'textarea',
            'welcomemessage',
            get_string('course_welcomemessage', 'local_dttutor'),
            ['rows' => 3, 'cols' => 60]
        );
        $mform->setType('welcomemessage', PARAM_TEXT);
        $mform->addHelpButton('welcomemessage', 'course_welcomemessage', 'local_dttutor');

        $this->add_action_buttons(false, get_string('savechanges'));
    }
}
