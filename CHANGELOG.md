# Changelog

All notable changes to the Tutor-IA plugin (local_dttutor) will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.9] - 2026-09-08

Remediation of the MindFree security assessment of 2.0.7 (findings SEC-001 to SEC-005) plus the defects it left out.

### Security

#### SEC-001 — Web service bridge executed as the service bot / admin
- **Web service bridge removed**: The AI model can no longer call Moodle web services. The `ws_search`, `ws_describe` and `call_webservice` tools, the tool loop, the `external_functions` indexer and the `schema_cache` cache are gone; the tutor answers only from the pre-loaded course knowledge.
- **Service bot retired**: The `tutoriabot_datacurso` user is no longer created or enrolled. The upgrade step unenrols it from every course, suspends it and removes the `serviceuserid` setting (the account is kept so that existing logs still resolve; an administrator may delete it).
- **System-level grants revoked**: The upgrade step removes `moodle/course:view` from the teacher archetypes at system context and the system-level `local/dttutor:use` assignment that previous upgrades had added; the capability is now checked only in the course context.

#### SEC-002 — Chat proxy trusted the client-supplied course
- **Course-level authorisation in `chatproxy.php`**: The requested course is resolved server-side (`request_guard`): login and enrolment in that course, `local/dttutor:use` in the course context, the site and per-course enablement toggles, and a course module that belongs to the course and is visible to the user. Any mismatch is refused before anything is pre-loaded or persisted.
- **Prompt input hardening**: Only `user`/`assistant` messages with string content are accepted, with limits on message count, message length and request body size; the client can no longer inject `system` or `tool` messages.
- **Course knowledge filtered per user**: The pre-loaded structure honours `uservisible` and is cached per course and user.
- **Widget only for authorised users**: The floating chat is rendered only for users holding `local/dttutor:use` in the course, the same check the proxy enforces; visitors without a course role no longer see a widget whose requests would be refused.

#### SEC-003 — No Privacy API
- **Privacy API implemented**: Metadata for `local_dttutor_course_config`, `local_dttutor_session` and every field transferred to the Datacurso AI service; per-user export and deletion; user and course deletion observers.

#### SEC-004 — Debug logging of conversation content
- **Metadata-only logging**: Log lines carry event names, counts, lengths, HTTP status and cURL error codes only. Message previews, provider response bodies and answer previews are no longer logged, and exception messages are logged by class name only.
- **No debug output in the SSE stream**: `chatproxy.php` declares `NO_DEBUG_DISPLAY`.

#### SEC-005 — Default HMAC secret
- **SPT validator removed**: The unused `spt_validator` class and its default `dttutor-spt-secret` are deleted.

### Added

- **Privacy API provider** (`classes/privacy/provider.php`) with `privacy:metadata:*` strings in the seven language packs.
- **Session table** `local_dttutor_session`: one handle per user, course and module of the remote chat session, so that sessions can be enumerated and deleted from Moodle.
- **Event observers** (`db/events.php`): `course_deleted` removes the course configuration and its sessions; `user_deleted` removes the user's sessions locally and remotely.
- **Setting `include_grades`** (disabled by default): the student's own grades are sent to the AI tutor only when the administrator opts in.
- **Cache `sessions`** declared in `db/caches.php`.
- **PHPUnit suite** (`tests/`): request guard, context pre-loader, system message, handler payload and logging, HTTP client session bookkeeping, privacy provider, observers, upgrade steps and `delete_chat_session`.

### Changed

- **System prompt**: Instructs the model to answer only from the COURSE KNOWLEDGE block, to admit when information is not available, and no longer describes web service tools.
- **Client metadata**: the user's full name is no longer sent by the client nor placed in the prompt; the chat proxy builds the context server-side.
- **HTTP client**: `tutoria_api` accepts an injected AI client and is resolved through `\core\di`; `delete_chat_session` now extends `core_external\external_api`.
- **Provider integration isolated**: the AI provider is reached only through the `ai_client` port and the `datacurso_ai_client` adapter (`client_factory` resolves the DI binding or the adapter); the test suite no longer requires `aiprovider_datacurso` to be installed. The rate limit notice now uses the plugin's own `error_ratelimit_exceeded` string.
- **README**: New *Data processed and transferred* section.
- **Legacy external API include removed**: `save_course_config` no longer `require_once`s the legacy `externallib` (it already extended `core_external\external_api`) and is now covered by PHPUnit tests.
- **Chat proxy testable seams**: `handler::build_request_headers()` and `handler::classify_response()` are extracted from `call_ai_api_buffer()` (pure extraction, the outgoing request is unchanged) so that the request headers and the rate-limit notice / error / SSE-text classification of the AI response are covered by PHPUnit without a network call.

### Fixed

- **`$role` clobbered** in `chatproxy.php` while replaying messages after a session reset.
- **`delete_chat_session`** opened a new remote session (`/chat/start`) before deleting it and never cleared the cache key; it now deletes the stored session only, or returns `deleted: false` without any network call; when the provider client cannot be built (unconfigured or unreachable) it also returns `deleted: false` and drops the stored handle instead of throwing.
- **`sessions` cache** was used but never declared, so a new remote session was created on every request.
- **Exception messages** (including SQL from DML errors) are no longer sent to the browser or to the model.
- **SSE error framing**: a provider failure is now streamed as an explicit `event: error` frame; the previous bare `data:` line was ignored by the chat client, so the student saw nothing.
- **Provider failures are logged in production**: `AI_API_ERROR`, `AI_API_RATE_LIMITED`, `AI_API_RESPONSE_EMPTY`, session persistence and remote-deletion failures are written (metadata only) to the PHP error log in addition to `debugging()`, which is silent on production sites.
- **Per-course gate on the AJAX functions**: `get_chat_history` and `delete_chat_session` now apply the same checks as the chat proxy (tutor enabled for the course, course module belonging to the course and visible to the user). `get_chat_history` extends `core_external\external_api`, reads only sessions already stored (no remote session is opened by a read) and returns an empty history with a `notice` when the service is unavailable.
- **`course_materials.js`** read an `enrol_status` field that `save_course_config` no longer returns; the dead branch is removed and the AMD build regenerated.
- **`session_store::upsert()`** could throw a `dml_write_exception` when two concurrent requests opened the first session for the same user, course and module; the loser of the unique-index race now updates the winner's row (last writer wins).
- **Enablement toggle wiped the stored per-course prompt** (`course_materials.js` sent `custom_prompt: ''` on every toggle, so any prompt saved through the web service was silently erased). Resolved by removing the per-course prompt altogether (see *Removed*); the site-level prompt is the only custom prompt.
- **Location hint never reached the model**: the chat client sends the Moodle `pagetype` while the system message read a `location` key nobody set, so the "Location: …" hint (the `ctx_loc_*` strings) was unreachable. The chat proxy now derives the location server-side from the sanitised page type (`request_guard::location_from_pagetype()`); a client-supplied `location` is ignored.
- **Double initialisation on the course management page**: `manage.php` called `js_call_amd('local_dttutor/course_materials')` while `manage_course.mustache` also required the module, so each toggle triggered two `save_course_config` calls and two notifications. The template's `{{#js}}` block is now the only initialiser.
- **Error modal title**: `ErrorModal.showGeneralError()` accepted a single argument, so the short title passed by the chat (license and credit errors) was silently dropped; it now accepts an optional title.

### Removed

- `classes/agent/tool/*`, `classes/agent/tool_executor.php`, `classes/schema/ws_indexer.php`, `classes/proxy/spt_validator.php`, `db/install.php`.
- `local_dttutor_get_tool_definitions()`, `local_dttutor_resolve_ws_classname()` and `local_dttutor_get_page_context()` from `lib.php`.
- The `tool_*` and `servicebot_*` language strings.
- **Course materials feature** (dead since the removal of the indexing backend): the `local_dttutor_upload_course_material`, `local_dttutor_delete_course_material` and `local_dttutor_get_course_materials` web services, the `course_materials` file area served by `local_dttutor_pluginfile()`, its privacy `core_files` link and the `course_materials`, `material_uploaded`, `material_deleted` strings. The tutor uses only the pre-loaded course knowledge.
- **`local_dttutor_create_chat_message` web service**: the chat posts to `chatproxy.php`; the v1 `/chat/start` and `/chat/message` client methods (`start_session()`, `send_message()`, `get_stream_url()`), the legacy session cache key and the `error_empty_message`, `error_metadata_too_large`, `error_selected_text_too_large`, `sessionnotready` strings go with it.
- **Off-topic detection settings** (`off_topic_detection_enabled`, `off_topic_strictness`): their only reader was `create_chat_message`, so they had no effect on the chat.
- **Dead schema columns** `last_indexed_at`, `indexing_status`, `indexing_task_id`, `indexing_error` and the `indexing_status` index of `local_dttutor_course_config` (upgrade step 2026090900). `indexing_enabled` remains as the per-course enablement flag.
- **Per-course `custom_prompt`**: the `local_dttutor_course_config.custom_prompt` column, the `custom_prompt` parameter of `local_dttutor_save_course_config`, `course_config::get_custom_prompt()` and the course section of the system message. No UI exposed it and the enablement toggle wiped it; the prompt is configured site-wide only.
- **Legacy EventSource path and unused client code** in `tutor_ia_chat.js`: `startSSE()`, `currentEventSource`, the `message_completed` event and `payload.content` fallback (the proxy only emits `token`, `done` and `error` with `{"t": …}`), `showNoCreditsWarning()`, `isNoCreditsError()`, `destroy()`, `getModelName()` and the client-side `model` payload key (the proxy decides the model), the unused `core/notification` dependency, the `userId` constructor argument, the forum/quiz/assign/wiki URL parameters that were detected but never sent, and the `data-courseid`/`data-cmid`/`data-userid` attributes of the drawer (the module receives course and module ids as init arguments).
- `js/avatar_selector_admin.js` (non-AMD duplicate of `amd/src/avatar_selector.js`, never loaded).
- Dead CSS: `.tutor-ia-warning-message`, `.tutor-ia-no-credits-warning` and `.tutor-ia-debug-*` blocks in `styles.css`.
- The legacy `avatar_position` setting fallback in `chat_hook::get_position_data()` (only `avatar_position_data` exists).
- Orphan language strings (all seven packs): `configuration_error`, `connection_interrupted`, `editing_message`, `error_establish_sse_connection`, `error_no_credits`, `error_no_credits_short`, `welcomemessage`.

## [2.0.4] - 2026-02-02

### Changed

#### Individual Chat History Implementation
- **User-specific sessions**: Chat sessions are now tied to individual users instead of being course-wide.
- **Session cache keys**: Changed from `session_{courseid}` to `session_{courseid}_{userid}` for proper isolation.
- **API enhancement**: Added `user_id` parameter to `/chat/start` API endpoint.
- **Data privacy**: Each user now has their own private chat history that cannot be accessed by other users.
- **Breaking change**: Sessions created before this update will be invalidated and new sessions will be created per user.
- **Files modified**:
    - `classes/httpclient/tutoria_api.php` - Enhanced `start_session()` with userid parameter.
    - `classes/external/create_chat_message.php` - Pass current user ID to session creation.
    - `classes/external/get_chat_history.php` - Pass current user ID to history retrieval.

### Security Improvements
- **Session isolation**: Users can only access their own chat sessions.
- **Privacy enhancement**: Chat history is now private per user, not shared at course level.

## [2.0.3] - 2026-02-02

### Added

#### Course Synchronization Validation
- **Validation before enabling tutor**: Users cannot enable the AI Tutor until course content synchronization is completed
- **Frontend validation**: Toggle switch is disabled when `indexing_status !== 'completed'`
- **Backend validation**: `save_course_config` web service throws `moodle_exception` if enabling tutor without completed synchronization
- **User feedback**: Alert message shown explaining synchronization requirement
- **Files modified**:
  - `classes/external/save_course_config.php` - Added validation logic
  - `manage.php` - Added `can_enable_tutor` template variable
  - `templates/manage_course.mustache` - Added disabled state and info alert
  - `lang/en/local_dttutor.php` - Added new error string

### Technical Details
- Validation checks `indexing_status === 'completed'` before allowing `indexing_enabled = 1`
- Exception type: `moodle_exception('tutor_enable_requires_indexing', 'local_dttutor')`
- UI state: Toggle disabled with `{{^can_enable_tutor}}disabled{{/can_enable_tutor}}`

## [2.0.3] - 2026-02-02 (Continued)

### Changed

#### API Compatibility Improvements - Metadata String Conversion
- **Metadata string conversion**: All metadata values are now converted to strings for API compatibility
- **Boolean to string conversion**: Boolean values (`off_topic_detection_enabled`) are now sent as "true"/"false" strings instead of native booleans
- **Type safety**: Added `array_map()` function to ensure all metadata values are strings before sending to API
- **User ID conversion**: `userid` metadata is now explicitly cast to string
- **Module context support**: Added optional `cmid` parameter to session management for module-specific chat contexts
- **Session cache enhancement**: Session cache keys now include `cmid` when in module context (`session_{courseid}_{cmid}`)
- **API request structure**: `cmid` is sent as `module_id` string in API requests when present
- **Files modified**:
  - `classes/external/create_chat_message.php` - Added metadata string conversion and cmid support
  - `classes/httpclient/tutoria_api.php` - Enhanced session caching with cmid parameter

### Technical Details

**Metadata String Conversion:**
- Previous: Mixed types (bool, int, string) sent directly
- Now: All values converted to strings using `array_map()` before API call
- Boolean conversion: `value ? 'true' : 'false'`
- Numeric conversion: `(string)$value`

**Session Caching Enhancement:**
- Previous cache key: `session_{courseid}`
- New cache key: `session_{courseid}_{cmid}` (when cmid present)
- Allows separate chat sessions for course-level and module-level contexts
- Improves context isolation and relevance of AI responses

**API Compatibility:**
- `userid` → sent as string instead of integer
- `cmid` → sent as `module_id` string in API requests
- `off_topic_detection_enabled` → sent as "true"/"false" instead of boolean
- All other metadata values → explicitly converted to strings

**Version:**
- Plugin version: `2026012900`
- Release: `2.0.3`
- Maturity: `MATURITY_STABLE`

### Migration Notes

**Upgrading from v2.0.2:**
- No database changes required
- No configuration changes needed
- Simply replace plugin files
- Existing sessions will be regenerated with new cache key format
- All functionality remains backward compatible

## [2.0.2] - 2026-01-23

### Changed

#### Code Cleanup - Removed Redundant Permission Checks
- **Removed redundant capability checks**: Eliminated unnecessary `require_capability('moodle/course:view')` checks from external web services
- **Reason**: The `local/dttutor:use` capability check already validates course access in course context
- **Impact**: Cleaner, more maintainable code without functional changes
- **Files modified**:
  - `classes/external/create_chat_message.php` - Removed redundant `moodle/course:view` check
  - `classes/external/delete_chat_session.php` - Removed redundant `moodle/course:view` check
  - `classes/external/get_chat_history.php` - Removed redundant `moodle/course:view` check
  - `classes/external/get_course_materials.php` - Removed redundant `moodle/course:view` check

### Technical Details

**Permission Check Logic:**
- Previous: `require_capability('local/dttutor:use', $context)` + `require_capability('moodle/course:view', $context)`
- Now: `require_capability('local/dttutor:use', $context)` only
- Rationale: Having `local/dttutor:use` capability in a course context already implies course access

**Security:**
- No security reduction - permission validation remains robust
- All web services still validate:
  1. User authentication via `require_login()`
  2. Context validation via `self::validate_context()`
  3. Plugin permission via `require_capability('local/dttutor:use')`

**Version:**
- Plugin version: `2026012300`
- Release: `2.0.2`
- Maturity: `MATURITY_STABLE`

### Migration Notes

**Upgrading from v2.0.1:**
- No database changes required
- No configuration changes needed
- Simply replace plugin files
- Code refactoring only - no functional changes

## [2.0.1] - 2026-01-22

### Fixed

#### Security Vulnerability - Missing Authentication and Context Validation
- **Critical fix**: Added missing security validations in `create_chat_message` web service
- **Issue**: Users were receiving permission denied errors because security checks were incomplete
- **Root cause**: The web service was missing three critical validation steps:
  1. `require_login()` - User authentication check
  2. `self::validate_context($context)` - Course context validation
  3. `require_capability('local/dttutor:use', $context)` - Plugin permission check
- **Solution**: Added all three missing validations following Moodle's external service security checklist
- **Impact**: Fixes "Lo sentimos, pero no tiene los permisos para hacer esto (Ver cursos sin participación)" error
- **Files modified**: `classes/external/create_chat_message.php`

### Changed

#### Permission System - Extended to All Authenticated Users
- **Extended access**: Added 'user' archetype to `local/dttutor:use` capability
- **Reason**: Allow all authenticated users to use the tutor, not just enrolled students
- **Previous behavior**: Only students, teachers, editing teachers, and managers could use the chat
- **New behavior**: All authenticated users with `moodle/course:view` permission can use the chat
- **Automatic upgrade**: Added upgrade script (`db/upgrade.php`) to automatically assign capability to 'user' role when updating from v2.0.0
- **Files modified**:
  - `db/access.php` - Added 'user' archetype to capability definition
  - `db/upgrade.php` - Added version 2026012201 upgrade step to assign capability

### Technical Details

**Security Validation Order** (now properly implemented):
1. Parameter validation via `self::validate_parameters()`
2. Authentication check via `require_login()`
3. Plugin state check via `get_config()`
4. Webservice configuration check
5. Course context validation via `self::validate_context()`
6. Plugin capability check via `require_capability('local/dttutor:use')`
7. Course view capability check via `require_capability('moodle/course:view')`

**Permission Archetypes** (updated):
- `user` → CAP_ALLOW (NEW - authenticated users)
- `student` → CAP_ALLOW
- `teacher` → CAP_ALLOW
- `editingteacher` → CAP_ALLOW
- `manager` → CAP_ALLOW

**Upgrade Script**:
- Version check: `if ($oldversion < 2026012201)`
- Uses `get_archetype_roles()` to find all 'user' roles
- Uses `assign_capability()` to grant permission at system context
- Checks for existing capability to avoid duplicate assignments
- Idempotent - safe to run multiple times

### Migration Notes

**Upgrading from v2.0.0**:
1. Replace plugin files with new version
2. Access your Moodle site
3. Click "Update database" when prompted
4. Upgrade script will automatically assign capability to 'user' role
5. No manual configuration required

**New Installations**:
- 'user' archetype automatically included in capability definition
- No additional setup needed

## [2.0.0] - 2025-12-30

### Changed

#### User Interface Terminology
- **Indexing → Synchronization**: Changed all user-facing terminology from "indexing/indexed" to "synchronization/synchronized" across all 7 supported languages
- **Language updates**: Updated 19 strings per language × 7 languages = 133 total string changes
- **Terminology mapping**:
  - "Course Indexing" → "Course Synchronization"
  - "Indexed" → "Synchronized"
  - "Indexing in progress" → "Synchronization in progress"
  - "Start Indexing" → "Start Synchronization"
  - "Re-index Course" → "Re-synchronize Course"
  - "Last indexed" → "Last synchronized"
  - "Indexing failed" → "Synchronization failed"
  - "Not indexed" → "Not synchronized"
- **Code preservation**: Internal code (variable names, functions, classes) maintains "indexing" terminology to avoid breaking changes
- **All languages updated**:
  - English: Indexing → Synchronization
  - Spanish: Indexación → Sincronización
  - German: Indexierung → Synchronisierung
  - French: Indexation → Synchronisation
  - Portuguese: Indexação → Sincronização
  - Russian: Индексация → Синхронизация
  - Indonesian: Pengindeksan → Sinkronisasi

### Removed

#### Markdown Formatting Feature
- **Removed markdown rendering**: Eliminated markdown-to-HTML conversion feature that was causing display issues
- **Simplified message display**: Chat messages now display as plain text without formatting
- **Code cleanup**:
  - Removed `markdownToHtml()` function from `tutor_ia_chat.js`
  - Reverted `appendToAIMessage()` to use `textContent` instead of `innerHTML`
  - Reverted `addMessage()` to use `.text()` for all messages
  - Reverted `createMessageElement()` to use `.text()` for history messages
  - Removed CSS styles for markdown formatting (lists, bold, italic)
- **Reason**: Markdown rendering was not working reliably and causing inconsistent message display

#### Debug Mode
- **Removed debug mode feature**: Completely removed debug mode and force reindex functionality
- **Removed from settings**: Deleted debug mode checkbox from admin settings page
- **Removed from UI**: Deleted debug controls section from chat drawer template
- **Removed from hook**: Cleaned up debug-related variables from chat_hook.php
- **Language cleanup**: Removed debug-related strings from all 7 language files
  - Removed: `debug_mode`, `debug_mode_desc`, `debug_force_reindex`

### Technical Details

**Terminology Change Impact**:
- User-facing strings only (no code changes)
- No database migrations required
- No breaking changes to API or functionality
- Maintains backwards compatibility

**Files Modified**:
- `lang/en/local_dttutor.php` (English)
- `lang/es/local_dttutor.php` (Spanish)
- `lang/de/local_dttutor.php` (German)
- `lang/fr/local_dttutor.php` (French)
- `lang/pt/local_dttutor.php` (Portuguese)
- `lang/ru/local_dttutor.php` (Russian)
- `lang/id/local_dttutor.php` (Indonesian)
- `amd/src/tutor_ia_chat.js` (Markdown removal)
- `styles.css` (Markdown styles removal)
- `settings.php` (Debug mode removal)
- `templates/tutor_ia_drawer.mustache` (Debug controls removal)
- `classes/hook/chat_hook.php` (Debug variables removal)

## [1.9.0] - 2025-12-01

### Added

#### Debug Mode Feature
- **Debug mode setting**: New admin setting to enable debug options in the chat interface
- **Force reindex checkbox**: When debug mode is enabled, shows a checkbox to force context reindexing (only visible to site administrators)
- **Admin-only access**: Debug controls require both debug mode enabled AND site:config capability
- **Metadata transmission**: Checkbox state sent as `force_reindex=true` in message metadata
- **Visual design**: Debug controls styled with warning colors (yellow background) to clearly indicate debug functionality
- **Language support**: Debug strings added to all 7 supported languages

#### Error Handling Improvements
- **Stream error detection**: Enhanced SSE stream error handling to detect and parse JSON error responses
- **License error modal**: User-friendly modal for license validation errors with clear explanation
- **Insufficient credits modal**: User-friendly modal for insufficient AI tokens/credits errors
- **Language support**: Added error strings for license and credits errors in all 7 languages

#### Technical Details

**Debug Mode**:
- Admin setting: `local_dttutor/debug_mode` (default: disabled)
- Template variables: `debug_mode` and `is_debug_admin` passed to drawer template
- Capability check: Requires `moodle/site:config` capability to see debug controls
- Visibility: Debug checkbox only shown when BOTH conditions are met (debug_mode=true AND user is admin)
- JavaScript: Checks checkbox state and includes in metadata when checked
- CSS: Warning-styled debug controls with yellow background and amber border

**Error Handling**:
- Enhanced EventSource error listener to parse JSON error data from SSE stream
- Added `handleStreamError()` method to detect and categorize API errors
- Detects errors with structure: `{detail: {status: "error", detail: "message"}}`
- Shows appropriate error modal based on error type (license vs. credits)
- Graceful fallback for unexpected error structures

## [1.8.2] - 2025-12-01

### Changed

#### Code Quality Improvements
- **JavaScript code cleanup**: Reduced tutor_ia_chat.js from 1387 to 1330 lines by removing redundant comments and consolidating documentation
- **Improved JSDoc**: Added proper class and constructor documentation with @class and @param tags
- **Comment standardization**: Replaced verbose inline comments with concise, meaningful ones following Moodle coding standards
- **Removed unused variable**: Eliminated `highlightedRange` variable that was no longer used

#### CSS Cleanup
- **Removed unused styles**: Eliminated `.tutor-ia-text-highlight` CSS rules (19 lines) that were no longer used after text selection refactoring

### Fixed

#### Avatar Position Default
- **Fixed xref default value**: Changed default `xref` from 'left' to 'right' in position preview admin setting to match actual behavior
- **Updated settings default**: Added complete position object with `drawerside`, `xref`, and `yref` to default JSON value

### Technical Details

**Code Reduction**:
- JavaScript: 57 lines removed (4.1% reduction)
- CSS: 19 lines removed (unused text highlight styles)
- Focus on removing redundant comments while preserving essential documentation
- Improved code readability through better comment organization
- All functionality remains unchanged

## [1.8.1] - 2025-11-28

### Changed

#### Text Selection Performance Optimization
- **Lazy loading of event listeners**: Text selection event listeners are now only attached when the chat drawer is open, eliminating performance overhead when the chat is closed
- **Event listener cleanup**: Listeners are automatically removed when the drawer is closed, ensuring zero performance impact on page interactions outside of the chat
- **Debounced text selection handling**: Added 150ms debouncing to the `debouncedHandleTextSelection()` method to prevent excessive DOM operations during rapid text selection
- **DOM element caching**: Selection indicator DOM elements are now cached instead of being queried repeatedly, improving selection handling performance
- **New JavaScript methods**:
  - `attachTextSelectionListeners()`: Attaches mouseup and keyup event listeners when drawer opens
  - `detachTextSelectionListeners()`: Removes event listeners when drawer closes
  - `debouncedHandleTextSelection()`: Debounced version of text selection handler
  - `cacheSelectionIndicatorElements()`: Caches references to selection indicator DOM elements on first use

### Technical Details

**Performance Improvements**:
- Reduced memory footprint by eliminating constant DOM queries during text selection
- Prevented redundant event handling through debouncing (150ms threshold)
- Event listeners only active when user is actively using the chat drawer
- Zero performance impact when chat is not in use

**Implementation**:
- Lazy attachment in `openDrawer()` method calls `attachTextSelectionListeners()`
- Cleanup in `closeDrawer()` method calls `detachTextSelectionListeners()`
- Debouncing threshold configurable via class constant
- Compatible with all existing text selection functionality from version 1.8.0

### Performance Metrics

- **Memory**: Reduced by removing inactive event listeners from DOM
- **CPU**: Reduced through debouncing and element caching
- **Responsiveness**: Improved for pages with heavy DOM manipulation
- **User impact**: No change in user-facing functionality or visual behavior

## [1.8.0] - 2025-11-17

### Added

#### Text Selection Context Feature
- **Text selection detection**: Automatically captures text selected by the user on course pages and activities
- **Selection metadata**: Selected text is sent as context with chat messages to provide more relevant AI responses
- **Persistent visual highlighting**: Selected text remains visually highlighted with yellow background and subtle outline even after clicking chat input
- **Selection indicator badge**: Shows line count and character count in chat footer with clear button
- **Backend validation**: Robust validation and sanitization of selected text (100KB metadata limit, 50KB text limit)
- **Security**: XSS prevention through clean_param() sanitization and PARAM_TEXT filtering
- **Language support**: Translations for text selection feature added to all 7 supported languages (en, es, de, fr, pt, ru, id)
- **Error handling**: User-friendly error messages for oversized metadata or selected text
- **Documentation**: Complete backend specification document (BACKEND_TEXT_SELECTION_SPEC.md) with API integration guidelines

#### Backend Improvements
- Enhanced metadata validation in `create_chat_message.php` with size limits and type checking
- Server-side sanitization of user-selected content
- Improved debugging output for metadata validation errors

#### Frontend Enhancements
- Real-time text selection handling with mouseup and keyboard event listeners
- Internal state management for selected text, line count, and character count
- Persistent highlighting using DOM manipulation (wraps selected text in styled span element)
- Smart selection persistence - only updates on new selection, never auto-clears when clicking elsewhere
- Automatic selection clearing after message is sent or when clear button clicked
- Visual indicator badge with line count, character count, and clear button
- Smooth animations for badge appearance and highlight transitions
- Interactive highlight with hover effect

#### UX Improvements
- **Context clarity**: Users can clearly see what text they're asking about while typing their question
- **Selection persistence**: Text selection doesn't disappear when clicking chat input to type
- **Visual feedback**: Highlighted text uses soft yellow background with subtle outline shadow
- **Graceful cleanup**: Highlight properly removed when selection cleared or message sent

### Technical Details
- Backend validation: 100KB max metadata, 50KB max selected text
- UTF-8 safe byte counting using `strlen()`
- Defense-in-depth security approach
- Compatible with Moodle 4.5+
- DOM manipulation: Uses Range.extractContents() and insertNode() for highlight wrapping
- Proper cleanup: Unwraps highlight span and normalizes parent nodes on removal
- Error handling: Try-catch blocks prevent failures in non-editable areas

## [1.0.0] - 2025-10-07

### Added

#### Core Functionality
- **Floating chat drawer**: Fixed drawer that redistributes page space (based on aiplacement_courseassist pattern)
- **Floating toggle button**: Avatar button in bottom corner (right/left configurable)
- **Real-time streaming**: Server-Sent Events (SSE) for token-by-token AI responses
- **Session management**: Intelligent caching with TTL validation
- **Role detection**: Automatically detects user role (Teacher/Student) in course context
- **Course context filtering**: Only shows in course and module pages (not frontpage)

#### Web Services
- `local_dttutor_create_chat_message`: Create chat message and get stream URL
- `local_dttutor_delete_chat_session`: Delete chat session

#### HTTP Client
- `tutoria_api` class for external API communication
- Session caching with configurable TTL
- Automatic session validation and refresh
- cURL-based HTTP requests with error handling

#### Hook System
- `before_footer_html_generation` hook for injecting drawer and toggle HTML
- Context-aware rendering (course/module only)
- Integration with Moodle's message drawer (auto-close on conflict)

#### User Interface
- **Drawer**: Header with avatar and close button, scrollable messages area, input footer
- **Messages**: User messages (blue bubbles, right-aligned), AI messages (white bubbles, left-aligned)
- **Typing indicator**: Animated dots while AI is processing
- **Auto-scroll**: Automatic scroll to bottom as tokens arrive
- **Keyboard shortcuts**: Enter to send, Shift+Enter for newline, Escape to close
- **Responsive**: Adapts to different screen sizes

#### Configuration
- **Enable/Disable**: Global toggle for chat functionality
- **Avatar selection**: 10 predefined avatars to choose from
- **Avatar position**: Right or left corner placement

#### Assets
- 9 avatar images (avatar_profesor_01.png through avatar_profesor_10.png)
- Complete CSS styling (~250 lines)
- AMD JavaScript module with full drawer management

#### Documentation
- Comprehensive README.md with installation, configuration, usage, API docs
- CLAUDE.md for Claude Code integration with development workflows
- CHANGELOG.md (this file)
- Inline code documentation (PHPDoc, JSDoc)

#### Developer Tools
- Migration script (`cli/migrate_from_datacurso.php`) for upgrading from local_datacurso
- Settings page with all configuration options
- Cache definitions for session storage
- Capability system (`local/dttutor:use`)

#### Internationalization
- English language strings (lang/en/local_dttutor.php)
- Spanish language strings (lang/es/local_dttutor.php)
- All UI elements translatable

#### Accessibility
- ARIA labels on all interactive elements
- Keyboard navigation support
- Screen reader compatible
- Focus management (jump-to functionality)
- Tab index management for drawer

### Fixed

#### SSE Stream Handling
- **Issue**: "[Conexión interrumpida]" message appearing after every completed message
- **Cause**: EventSource 'error' event fires on normal connection close
- **Solution**: Implemented `messageCompleted` flag that tracks 'done' event before showing errors
- **Result**: Error message only shows on actual connection interruptions, not normal completions

#### Footer Popover Positioning
- **Issue**: Moodle's footer-popover buttons (message drawer, communication) not moving when Tutor-IA drawer opens
- **Cause**: Moodle uses fixed width (315px) in compiled SCSS, Tutor-IA drawer is 380px
- **Solution**: Added body class (`tutor-ia-drawer-open-right/left`) and CSS overrides with !important
- **Result**: Footer buttons now move correctly with drawer width (calc(380px + 2rem))

#### Drawer Width Consistency
- **Issue**: Drawer width mismatch between drawer element and footer-popover repositioning
- **Cause**: Hard-coded values in different places
- **Solution**: Standardized to 380px with clear documentation in CSS comments
- **Result**: Consistent spacing and movement across all elements

### Changed

#### Namespace Migration
- **From**: `local_datacurso` (embedded Tutor-IA)
- **To**: `local_dttutor` (standalone plugin)
- **Reason**: Separation of concerns - decouple Tutor-IA from course/activity AI generation
- **Impact**: All PHP namespaces, web services, config keys, and string keys updated

#### Config Key Simplification
- `enablechat` → `enabled` (standard naming)
- `tutoria_avatar` → `avatar` (no ambiguity in dedicated plugin)
- `tutoria_avatar_position` → `avatar_position` (simpler)

#### String Key Simplification
- `tutoruia` → `pluginname` (Moodle standard)
- `opentutoria` → `open` (simpler)
- `closetutoria` → `close` (simpler)
- Added error strings with proper formatting placeholders

#### Hook Implementation
- **Removed**: AI course/activity button injection (belongs in local_datacurso)
- **Removed**: Course session checking (belongs in local_datacurso)
- **Kept**: Only Tutor-IA chat functionality
- **Result**: Cleaner, focused hook with single responsibility

### Removed

- **Dependencies on datacurso classes**: No longer uses ai_course, streaming_helper, or other datacurso-specific classes
- **Activity/Course AI buttons**: Not part of Tutor-IA functionality
- **Legacy code**: Removed unused tutor_ia_drawer.js and tutor_ia_trigger.mustache references

### Security

- **Input sanitization**: All user input sanitized before sending to API (sanitizeString method)
- **Message length limits**: 4000 characters per user message, 10000 characters per AI response
- **Capability checks**: `local/dttutor:use` capability required for all web services
- **Token authentication**: Bearer token authentication for all API requests
- **XSS prevention**: Using .textContent instead of .innerHTML for message display
- **HTTPS enforcement**: PARAM_URL validation for API URL configuration

### Performance

- **Session caching**: Reduces API calls by caching sessions with intelligent TTL
- **Lazy loading**: Drawer only rendered on course pages, not globally
- **Efficient streaming**: EventSource reuses single connection for entire message
- **CSS animations**: Hardware-accelerated transforms for smooth drawer animations
- **Minimal DOM manipulation**: Efficient token appending using textContent

### Known Issues

- **Browser compatibility**: EventSource not supported in IE11 (use polyfill if needed)
- **Session persistence**: Sessions stored in application cache, cleared on cache purge
- **Conflict with datacurso**: Cannot run both Tutor-IA implementations simultaneously (disable one)

### Migration Notes

When upgrading from local_datacurso's embedded Tutor-IA:

1. **Disable old chat**: Set `local_datacurso/enablechat` to '0'
2. **Install plugin**: Extract to `local/dttutor/` and run upgrade
3. **Migrate settings**: Run `php local/dttutor/cli/migrate_from_datacurso.php`
4. **Purge caches**: Run `php admin/cli/purge_caches.php`
5. **Test**: Verify drawer opens and chat works in a course

### Dependencies

- **Moodle**: >= 4.5 (2024042200)
- **PHP**: >= 7.4
- **External API**: Tutor-IA API (Datacurso) with SSE streaming support
- **JavaScript**: ES6 EventSource API
- **Moodle Core Modules**: jquery, core/ajax, core/notification, core/pubsub

### Upgrade Path

**From local_datacurso embedded Tutor-IA**:
- Database: No migration needed (no custom tables)
- Files: Copy avatars, update references
- Settings: Use migration script
- Templates: Already migrated
- JavaScript: Already migrated
- Hooks: New hook registration, remove old one

**Future versions**:
- Version numbers follow YYYYMMDDXX format (Moodle standard)
- Minor updates: XX increment (e.g., 2025100701)
- Major updates: Date increment (e.g., 2025100800)

---

## Version History Summary

| Version | Date       | Description                                                |
|---------|------------|--------------------------------------------------------|
| 2.0.3   | 2026-02-02 | Course synchronization validation & API metadata fixes |
| 2.0.2   | 2026-01-23 | Code cleanup - removed redundant permission checks     |
| 2.0.1   | 2026-01-22 | Security fix and extended permissions to all users     |
| 2.0.0   | 2025-12-30 | Terminology change (indexing → synchronization), removed markdown & debug |
| 1.9.0   | 2025-12-01 | Debug mode and enhanced error handling for license/credits |
| 1.8.2   | 2025-12-01 | Code cleanup - reduced redundant comments in JS        |
| 1.8.1   | 2025-11-28 | Text selection performance optimization                |
| 1.8.0   | 2025-11-17 | Text selection context feature                         |
| 1.0.0   | 2025-10-07 | Initial release - migrated from local_datacurso        |

---

## Contributing

When adding entries to this changelog:

1. Follow the format: `### [Type]` where Type is Added/Changed/Deprecated/Removed/Fixed/Security
2. Use present tense ("Add feature" not "Added feature")
3. Include issue/PR numbers where applicable: `- Fixed drawer width (#123)`
4. Keep descriptions clear and concise
5. Add breaking changes to a `### Breaking Changes` section
6. Document upgrade notes under `### Migration Notes`

---

**Legend**:
- **Added**: New features
- **Changed**: Changes in existing functionality
- **Deprecated**: Soon-to-be removed features
- **Removed**: Removed features
- **Fixed**: Bug fixes
- **Security**: Vulnerability fixes
