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

$string['accepted_files_pdf_only'] = 'Only PDF files are accepted (maximum 50 files)';
$string['avatar'] = 'Tutor-AI avatar';
$string['avatar_desc'] = 'Select the avatar to display on the Tutor-AI floating chat button. If none is selected or the file does not exist, Avatar 1 will be used by default.';
$string['avatar_position'] = 'Avatar position';
$string['avatar_position_desc'] = 'Configure where the Tutor-AI floating avatar button will be displayed. Choose a preset corner position or customize the exact X,Y coordinates. The live preview shows how it will appear.';
$string['cachedef_sessions'] = 'Cache for Tutor-AI chat sessions';
$string['char'] = 'char';
$string['chars'] = 'chars';
$string['choose_files'] = 'Choose Files';
$string['clear_selection'] = 'Clear selection';
$string['close'] = 'Close Tutor AI';
$string['configuration_error'] = 'Configuration error';
$string['configure_now'] = 'Configure Now';
$string['connection_interrupted'] = '[Connection interrupted]';
$string['course_custom_prompt'] = 'Course-specific Custom Prompt';
$string['course_custom_prompt_help'] = 'This custom prompt overrides the global setting for this course only. Leave empty to use the global prompt.';
$string['course_materials'] = 'Course Materials (PDFs)';
$string['course_materials_help'] = 'Upload additional PDF files that the AI tutor should reference when answering questions.';
$string['custom_prompt'] = 'Custom prompt';
$string['custom_prompt_desc'] = 'Custom instructions to control the AI tutor behavior. Use this field to provide specific guidelines, tone, or knowledge boundaries for the tutor.';
$string['customavatar'] = 'Custom avatar';
$string['customavatar_desc'] = 'Upload your own custom avatar image. This will override the selected predefined avatar.';
$string['customavatar_dimensions'] = 'Recommended dimensions: 200x200 pixels. Supported formats: PNG, JPG, JPEG, SVG. Maximum file size: 512KB.';
$string['drag_drop_upload'] = 'Drag & Drop PDF files here';
$string['drag_drop_upload_or_browse'] = 'or click to browse';
$string['drawer_side'] = 'Drawer opening side';
$string['drawer_side_help'] = 'Choose from which side the chat drawer will open. This is independent of the avatar button position.';
$string['drawer_side_left'] = 'Open from left';
$string['drawer_side_right'] = 'Open from right';
$string['dttutor:use'] = 'Use Tutor-AI';
$string['edit_message'] = 'Edit message';
$string['edit_save'] = 'Save';
$string['edit_cancel'] = 'Cancel';
$string['enable_tutor_for_course'] = 'Enable AI Tutor for this course';
$string['enable_tutor_for_course_help'] = 'When enabled, the AI Tutor will be available for students and teachers in this course. The global plugin setting must also be enabled.';
$string['enabled'] = 'Enable Chat';
$string['enabled_desc'] = 'Enable or disable the Tutor-AI chat globally';
$string['editing_message'] = 'Editing message';
$string['error_api_not_configured'] = 'API configuration is missing. Please check your settings.';
$string['error_api_request_failed'] = 'API request error: {$a}';
$string['error_attempt_later'] = 'An error occurred. Please try again later.';
$string['error_cache_unavailable'] = 'Chat service is temporarily unavailable. Please try refreshing the page.';
$string['error_empty_message'] = 'Message cannot be empty';
$string['error_establish_sse_connection'] = '[Error] Could not establish SSE connection';
$string['error_http_code'] = 'HTTP error {$a}';
$string['error_insufficient_tokens'] = 'There are not enough AI credits available to process your request. Please contact your administrator to add more credits to continue using the AI Tutor.';
$string['error_insufficient_tokens_short'] = 'Insufficient Credits';
$string['error_internal'] = 'Internal error: {$a}';
$string['error_invalid_api_response'] = 'Invalid API response';
$string['error_invalid_coordinates'] = 'Invalid coordinates. Please use valid CSS values (e.g., 10px, 2rem, 50%)';
$string['error_invalid_message'] = 'Please enter a valid message';
$string['error_invalid_position'] = 'Invalid position data';
$string['error_license_fallback'] = 'License error: {$a}';
$string['error_license_fallback_short'] = 'License Error';
$string['error_license_not_allowed'] = 'Your site license does not allow access to the AI Tutor service. Please contact your administrator to verify your license status or upgrade your plan.';
$string['error_license_not_allowed_short'] = 'License Error';
$string['error_message_too_long'] = '[Error] Message is too long. Maximum 4000 characters.';
$string['error_metadata_too_large'] = 'The metadata sent with your message is too large. Please try again.';
$string['error_no_credits'] = 'Insufficient AI credits available.';
$string['error_no_credits_fallback'] = 'Insufficient credits: {$a}';
$string['error_no_credits_short'] = 'No Credits Available';
$string['error_selected_text_too_large'] = 'The selected text is too large. Please select a smaller portion.';
$string['error_unexpected'] = 'An unexpected error occurred. Please try again.';
$string['error_unknown'] = 'An unknown error occurred. Please try again.';
$string['error_webservice_not_configured'] = 'The AI Tutor chat is not properly configured and is currently unavailable.';
$string['error_webservice_not_configured_action'] = 'Please contact your site administrator or report this issue to get the chat service activated.';
$string['error_webservice_not_configured_admin'] = 'The Datacurso AI Provider webservice needs to be configured before using the AI Tutor. <a href="{$a}" target="_blank">Click here to configure it now</a>.';
$string['error_webservice_not_configured_admin_inline'] = 'The Datacurso AI Provider webservice needs to be configured before using the AI Tutor.';
$string['error_webservice_not_configured_short'] = 'Chat Service Unavailable';
$string['last_indexed'] = 'Last synchronized: {$a}';
$string['line'] = 'line';
$string['lines'] = 'lines';
$string['loading'] = 'Loading...';
$string['manage_tutor'] = 'AI Tutor Management';
$string['material_deleted'] = 'Material deleted successfully';
$string['material_uploaded'] = 'Material uploaded successfully';
$string['off_topic_detection_enabled'] = 'Enable off-topic detection';
$string['off_topic_detection_enabled_desc'] = 'When enabled, the AI tutor will detect and respond to off-topic messages according to the strictness level configured below.';
$string['off_topic_strictness'] = 'Off-topic strictness';
$string['off_topic_strictness_desc'] = 'Control how strict the off-topic detection is. Permissive allows more flexibility, while strict enforces course-related conversations only.';
$string['off_topic_strictness_moderate'] = 'Moderate';
$string['off_topic_strictness_permissive'] = 'Permissive';
$string['off_topic_strictness_strict'] = 'Strict';
$string['open'] = 'Open Tutor AI';
$string['or_click_to_browse'] = 'or click to browse';
$string['pluginname'] = 'Tutor AI';
$string['position_custom'] = 'Custom position';
$string['position_left'] = 'Bottom left corner';
$string['position_preset'] = 'Position preset';
$string['position_right'] = 'Bottom right corner';
$string['position_x'] = 'Horizontal position (X)';
$string['position_x_help'] = 'Distance from left edge. Examples: 2rem, 20px, 5%. Use negative values to position from the right edge.';
$string['position_x_label'] = 'X: {$a->value} (from {$a->ref})';
$string['position_y'] = 'Vertical position (Y)';
$string['position_y_help'] = 'Distance from bottom edge. Examples: 6rem, 80px, 10%. Use negative values to position from the top edge.';
$string['position_y_label'] = 'Y: {$a->value} (from {$a->ref})';
$string['positiondisplay_corner'] = 'Position: {$a->preset} corner | Drawer: {$a->drawer}';
$string['positiondisplay_custom'] = 'Position: X: {$a->x}, Y: {$a->y} | Drawer: {$a->drawer}';
$string['preview'] = 'Live Preview';
$string['ref_bottom'] = 'Bottom';
$string['ref_left'] = 'Left';
$string['ref_right'] = 'Right';
$string['ref_top'] = 'Top';
$string['reference_edge_x'] = 'Horizontal reference edge';
$string['reference_edge_y'] = 'Vertical reference edge';
$string['selected'] = 'selected';
$string['selection_indicator'] = '{$a} lines selected';
$string['selectionformat'] = '{$a->lines} {$a->linetext}, {$a->chars} {$a->chartext} selected';
$string['sendmessage'] = 'Send message';
$string['sessionnotready'] = 'The Tutor-AI session is not ready. Please try again.';
$string['student'] = 'Student';
$string['teacher'] = 'Teacher';
$string['tutor_disabled_notice'] = 'The AI Tutor is currently disabled for this course. Students will not see the chat interface.';
$string['tutor_status'] = 'AI Tutor Status';
$string['tutorcustomization'] = 'Tutor Customization';
$string['tutorname_default'] = 'AI Tutor';
$string['tutorname_setting'] = 'Tutor name';
$string['tutorname_setting_desc'] = 'Configure the name to display in the chat header. You can use {teachername} to show the actual teacher\'s name from the course, or enter a custom name. Examples: "{teachername}" will show "John Doe", "AI Assistant" will show "AI Assistant".';
$string['typemessage'] = 'Type your message...';
$string['unauthorized'] = 'Unauthorized access';
$string['upload_files'] = 'Upload Files';
$string['upload_material'] = 'Upload PDF';
$string['welcomemessage'] = 'Hello! I\'m your AI assistant. How can I help you today?';
$string['welcomemessage_default'] = 'Hello! I\'m {teachername}, your AI assistant. How can I help you today?';
$string['welcomemessage_setting'] = 'Welcome message';
$string['welcomemessage_setting_desc'] = 'Customize the welcome message displayed when the chat is opened. You can use placeholders: {teachername}, {coursename}, {username}, {firstname}';
$string['yesterday'] = 'Yesterday';

// Service bot user.
$string['servicebot_firstname'] = 'Tutor';
$string['servicebot_lastname'] = 'AI';

// AI Proxy tool definitions.
$string['tool_ws_search_desc'] = 'Search Moodle web service functions by intent or keywords. Returns function name, component, description, params, returns.';
$string['tool_ws_search_query'] = 'Intent or keywords in ENGLISH (e.g. "create user", "enrol student", "get course grades").';
$string['tool_ws_search_limit'] = 'Maximum results (default: 20, max: 30).';
$string['tool_ws_describe_desc'] = 'Get full documentation for a specific Moodle web service function: description, parameters with types, and return structure.';
$string['tool_ws_describe_wsname'] = 'Exact web service function name (e.g. core_user_create_users, core_course_get_courses).';
$string['tool_call_webservice_desc'] = 'Call a Moodle web service function. The function will only work if the user has the required permissions in Moodle.';
$string['tool_call_webservice_function'] = 'The web service function name (e.g. core_user_get_users, core_course_get_courses).';
$string['tool_call_webservice_params'] = 'Parameters to pass to the function as key-value pairs.';

// Context / location labels for system message.
$string['ctx_loc_course'] = 'Location: Course';
$string['ctx_loc_activity'] = 'Location: Activity';
$string['ctx_loc_gradebook'] = 'Location: Gradebook';
$string['ctx_loc_admin'] = 'Location: Administration';
$string['ctx_loc_dashboard'] = 'Location: Dashboard';
$string['ctx_loc_messages'] = 'Location: Messages';
$string['ctx_loc_profile'] = 'Location: Profile';
$string['ctx_loc_calendar'] = 'Location: Calendar';
$string['ctx_loc_files'] = 'Location: Files';

// System message builder strings.
$string['system_message_greeting'] = 'You are an AI tutor integrated into Moodle ({$a}).';
$string['system_message_role'] = 'User role: {$a}.';
$string['system_message_goal'] = 'Goal: help the student with their course-related questions using available web service tools.';
$string['dblabel_mysql'] = 'MySQL/MariaDB';

// SPT/Proxy errors (retained for backward compatibility, no longer used).

// Proxy settings (removed — credentials are managed server-side via aiprovider_datacurso).
