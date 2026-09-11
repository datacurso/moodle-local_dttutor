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
 * English language strings for Tutor-IA plugin.
 *
 * @package    local_dttutor
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['avatar'] = 'Tutor-AI avatar';
$string['avatar_desc'] = 'Select the avatar to display on the Tutor-AI floating chat button. If none is selected or the file does not exist, Avatar 1 will be used by default.';
$string['avatar_position'] = 'Avatar position';
$string['avatar_position_desc'] = 'Configure where the Tutor-AI floating avatar button will be displayed. Choose a preset corner position or customize the exact X,Y coordinates. The live preview shows how it will appear.';
$string['cachedef_course_knowledge'] = 'Cache for pre-loaded course knowledge used by the chat';
$string['cachedef_sessions'] = 'Cache for Tutor-AI chat session handles';
$string['char'] = 'char';
$string['chars'] = 'chars';
$string['clear_selection'] = 'Clear selection';
$string['close'] = 'Close Tutor AI';
$string['ctx_loc_activity'] = 'Location: Activity';
$string['ctx_loc_admin'] = 'Location: Administration';
$string['ctx_loc_calendar'] = 'Location: Calendar';
$string['ctx_loc_course'] = 'Location: Course';
$string['ctx_loc_dashboard'] = 'Location: Dashboard';
$string['ctx_loc_files'] = 'Location: Files';
$string['ctx_loc_gradebook'] = 'Location: Gradebook';
$string['ctx_loc_messages'] = 'Location: Messages';
$string['ctx_loc_profile'] = 'Location: Profile';
$string['custom_prompt'] = 'Custom prompt';
$string['custom_prompt_desc'] = 'Custom instructions to control the AI tutor behavior. Use this field to provide specific guidelines, tone, or knowledge boundaries for the tutor.';
$string['customavatar'] = 'Custom avatar';
$string['customavatar_desc'] = 'Upload your own custom avatar image. This will override the selected predefined avatar.';
$string['customavatar_dimensions'] = 'Recommended dimensions: 200x200 pixels. Supported formats: PNG, JPG, JPEG, SVG. Maximum file size: 512KB.';
$string['drawer_side'] = 'Drawer opening side';
$string['drawer_side_help'] = 'Choose from which side the chat drawer will open. This is independent of the avatar button position.';
$string['drawer_side_left'] = 'Open from left';
$string['drawer_side_right'] = 'Open from right';
$string['dttutor:use'] = 'Use Tutor-AI';
$string['edit_cancel'] = 'Cancel';
$string['edit_message'] = 'Edit message';
$string['edit_save'] = 'Save';
$string['enable_tutor_for_course'] = 'Enable AI Tutor for this course';
$string['enable_tutor_for_course_help'] = 'When enabled, the AI Tutor will be available for students and teachers in this course. The global plugin setting must also be enabled.';
$string['enabled'] = 'Enable Chat';
$string['enabled_desc'] = 'Enable or disable the Tutor-AI chat globally';
$string['error_api_not_configured'] = 'API configuration is missing. Please check your settings.';
$string['error_attempt_later'] = 'An error occurred. Please try again later.';
$string['error_history_unavailable'] = 'The previous conversation could not be loaded. You can keep chatting.';
$string['error_insufficient_tokens'] = 'There are not enough AI credits available to process your request. Please contact your administrator to add more credits to continue using the AI Tutor.';
$string['error_insufficient_tokens_short'] = 'Insufficient Credits';
$string['error_internal'] = 'Internal error: {$a}';
$string['error_invalid_coordinates'] = 'Invalid coordinates. Please use valid CSS values (e.g., 10px, 2rem, 50%)';
$string['error_invalid_message'] = 'Please enter a valid message';
$string['error_invalid_position'] = 'Invalid position data';
$string['error_license_fallback'] = 'License error: {$a}';
$string['error_license_fallback_short'] = 'License Error';
$string['error_license_not_allowed'] = 'Your site license does not allow access to the AI Tutor service. Please contact your administrator to verify your license status or upgrade your plan.';
$string['error_license_not_allowed_short'] = 'License Error';
$string['error_message_too_long'] = '[Error] Message is too long. Maximum 4000 characters.';
$string['error_no_credits_fallback'] = 'Insufficient credits: {$a}';
$string['error_ratelimit_exceeded'] = 'The allowed consumption limit has been exceeded. Please try again at {$a}.';
$string['error_tutor_not_available'] = 'The AI Tutor is not available for this course.';
$string['error_unexpected'] = 'An unexpected error occurred. Please try again.';
$string['error_unknown'] = 'An unknown error occurred. Please try again.';
$string['include_grades'] = 'Send the student\'s grades to the AI tutor';
$string['include_grades_desc'] = 'When enabled, the current student\'s own grades for the course are added to the context sent to the Datacurso AI service so the tutor can answer questions about them. Disabled by default to minimise the personal data transferred.';
$string['line'] = 'line';
$string['lines'] = 'lines';
$string['loading'] = 'Loading...';
$string['manage_tutor'] = 'AI Tutor Management';
$string['open'] = 'Open Tutor AI';
$string['pluginname'] = 'Tutor AI';
$string['position_custom'] = 'Custom position';
$string['position_left'] = 'Bottom left corner';
$string['position_preset'] = 'Position preset';
$string['position_right'] = 'Bottom right corner';
$string['position_x'] = 'Horizontal position (X)';
$string['position_x_help'] = 'Distance from left edge. Examples: 2rem, 20px, 5%. Use negative values to position from the right edge.';
$string['position_y'] = 'Vertical position (Y)';
$string['position_y_help'] = 'Distance from bottom edge. Examples: 6rem, 80px, 10%. Use negative values to position from the top edge.';
$string['positiondisplay_corner'] = 'Position: {$a->preset} corner | Drawer: {$a->drawer}';
$string['positiondisplay_custom'] = 'Position: X: {$a->x}, Y: {$a->y} | Drawer: {$a->drawer}';
$string['preview'] = 'Live Preview';
$string['privacy:export:course_config'] = 'Tutor course configuration';
$string['privacy:export:sessions'] = 'Chat sessions';
$string['privacy:metadata:datacurso_ai'] = 'Chat messages and course context are sent to the Datacurso AI service to generate the tutor\'s answers.';
$string['privacy:metadata:datacurso_ai:cmid'] = 'The ID of the course module the user was viewing when writing the message.';
$string['privacy:metadata:datacurso_ai:course_structure'] = 'The structure of the course visible to the user (activities, sections, dates and maximum grades).';
$string['privacy:metadata:datacurso_ai:custom_prompt'] = 'The institutional custom instructions configured by the administrator.';
$string['privacy:metadata:datacurso_ai:grades'] = 'The user\'s own grades in the course, only when the "Send the student\'s grades" setting is enabled.';
$string['privacy:metadata:datacurso_ai:lang'] = 'The user\'s current language.';
$string['privacy:metadata:datacurso_ai:messages'] = 'The chat messages written by the user and the tutor\'s previous answers.';
$string['privacy:metadata:datacurso_ai:page_url'] = 'The URL of the Moodle page from which the message was sent.';
$string['privacy:metadata:datacurso_ai:selected_text'] = 'Text the user selected on the page to ask about.';
$string['privacy:metadata:datacurso_ai:site_id'] = 'The anonymous identifier of this Moodle site.';
$string['privacy:metadata:datacurso_ai:site_url'] = 'The URL of this Moodle site.';
$string['privacy:metadata:datacurso_ai:timezone'] = 'The user\'s timezone.';
$string['privacy:metadata:datacurso_ai:userid'] = 'The ID of the user sending the message.';
$string['privacy:metadata:local_dttutor_course_config'] = 'Per-course configuration of the AI tutor.';
$string['privacy:metadata:local_dttutor_course_config:timemodified'] = 'When the configuration was last modified.';
$string['privacy:metadata:local_dttutor_course_config:usermodified'] = 'The ID of the user who last modified the configuration.';
$string['privacy:metadata:local_dttutor_session'] = 'Handles of the chat sessions a user opened with the Datacurso AI tutor, stored so they can be deleted remotely.';
$string['privacy:metadata:local_dttutor_session:cmid'] = 'The ID of the course module the session belongs to (0 for the whole course).';
$string['privacy:metadata:local_dttutor_session:courseid'] = 'The ID of the course the session belongs to.';
$string['privacy:metadata:local_dttutor_session:remotesessionid'] = 'The identifier of the session in the Datacurso AI service.';
$string['privacy:metadata:local_dttutor_session:timecreated'] = 'When the session was created.';
$string['privacy:metadata:local_dttutor_session:timemodified'] = 'When the session was last renewed.';
$string['privacy:metadata:local_dttutor_session:userid'] = 'The ID of the user who owns the session.';
$string['ref_bottom'] = 'Bottom';
$string['ref_left'] = 'Left';
$string['ref_right'] = 'Right';
$string['ref_top'] = 'Top';
$string['reference_edge_x'] = 'Horizontal reference edge';
$string['reference_edge_y'] = 'Vertical reference edge';
$string['selected'] = 'selected';
$string['sendmessage'] = 'Send message';
$string['student'] = 'Student';
$string['teacher'] = 'Teacher';
$string['tutor_disabled_notice'] = 'The AI Tutor is currently disabled for this course. Students will not see the chat interface.';
$string['tutor_status'] = 'AI Tutor Status';
$string['tutorcustomization'] = 'Tutor Customization';
$string['tutorname_default'] = 'AI Tutor';
$string['tutorname_setting'] = 'Tutor name';
$string['tutorname_setting_desc'] = 'Configure the name to display in the chat header. You can use {teachername} to show the actual teacher\'s name from the course, or enter a custom name. Examples: "{teachername}" will show "John Doe", "AI Assistant" will show "AI Assistant".';
$string['typemessage'] = 'Type your message...';
$string['welcomemessage_default'] = 'Hello! I\'m {teachername}, your AI assistant. How can I help you today?';
$string['welcomemessage_setting'] = 'Welcome message';
$string['welcomemessage_setting_desc'] = 'Customize the welcome message displayed when the chat is opened. You can use placeholders: {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Yesterday';
