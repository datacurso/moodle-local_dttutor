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
 * Course materials management
 *
 * @module     local_dttutor/course_materials
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function ($, Ajax, Notification) {
    'use strict';

    var CourseMaterials = function (courseid) {
        this.courseid = courseid;
    };

    CourseMaterials.prototype.init = function () {
        this.attachEventHandlers();
    };

    CourseMaterials.prototype.attachEventHandlers = function () {
        var self = this;

        // Toggle tutor enabled/disabled.
        $('[data-action="toggle-tutor"]').on('change', function () {
            var enabled = $(this).is(':checked');
            self.toggleTutor(enabled);
        });

        // Note: Save config form and delete material handlers removed.
        // See REMOVED_FEATURES.md for restoration.
    };

    CourseMaterials.prototype.toggleTutor = function (enabled) {
        Ajax.call([{
            methodname: 'local_dttutor_save_course_config',
            args: {
                courseid: this.courseid,
                custom_prompt: '',
                enabled: enabled
            }
        }])[0].done(function (response) {
            if (response.success) {
                var msg = response.message;
                if (response.enrol_status) {
                    msg += ' (enrol: ' + response.enrol_status + ')';
                }
                Notification.addNotification({
                    message: msg,
                    type: 'success'
                });
                // Reload page to update UI state (show/hide warning alert).
                setTimeout(function () {
                    window.location.reload();
                }, 1000);
            } else {
                Notification.addNotification({
                    message: response.message,
                    type: 'error'
                });
            }
        }).fail(Notification.exception);
    };

    return {
        init: function (courseid) {
            var materials = new CourseMaterials(courseid);
            materials.init();
        }
    };
});
