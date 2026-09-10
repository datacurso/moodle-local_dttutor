# AI Tutor Chat for Moodle

An intelligent conversational assistant that integrates seamlessly into your Moodle courses, providing real-time AI-powered support to students and teachers through a floating chat interface.

## What does it do?

AI Tutor Chat adds a floating avatar button to course pages that opens an AI-powered chat drawer. Students and teachers can interact with the AI assistant to:

- **Get instant help** with course content and questions
- **Receive personalized guidance** based on their role (student/teacher)
- **Experience real-time responses** with streaming text display
- **Access contextual assistance** aware of the current course and activity

The chat interface features:
- Customizable avatar with 10 built-in options
- Flexible positioning (bottom-right or bottom-left corner)
- Real-time streaming responses using Server-Sent Events (SSE)
- Automatic role detection for personalized interactions
- Keyboard shortcuts (Enter to send, Escape to close)
- Mobile-responsive design

## Screenshots

### Admin Settings

![Admin Settings - Chat Configuration](pix/screenshots/1.png)

**Configuration panel** showing the global chat enable toggle and the avatar selection with 10 built-in avatar options.

---

![Avatar Position Configuration](pix/screenshots/2.png)

**Avatar positioning** with preset corner positions (bottom-right/bottom-left), custom positioning options, drawer side selection, and live preview showing how the avatar will appear on course pages.

---

![Tutor Customization](pix/screenshots/3.png)

**Tutor customization settings** allowing you to personalize the welcome message, tutor name, and custom AI behavior prompts. Supports placeholders like {teachername}, {coursename}, {username}, and {firstname}.

---

### Chat Interface

![Chat Drawer in Action](pix/screenshots/4.png)

**Live chat interface** showing the AI tutor drawer open on a course page, with real-time conversation support, role detection (Teacher), and personalized welcome message.

## Explore the suite

AI Tutor Chat is part of the **Datacurso AI Suite**, a collection of intelligent tools designed to enhance the Moodle learning experience:

- **[Course Creator AI](https://github.com/industria-elearning/moodle-local_coursegen)** - Generate complete courses automatically using AI
- **[Ranking Activities AI](https://github.com/industria-elearning/moodle-local_ranking)** - Analyze student feedback with AI-powered insights
- **[Forum AI](https://github.com/industria-elearning/moodle-local_forumgrade)** - Enhance discussion engagement with intelligent moderation
- **[Assign AI](https://github.com/industria-elearning/moodle-local_assigngrade)** - Streamline assignment review with AI assistance
- **AI Tutor Chat** - Provide real-time conversational AI support (this plugin)

All plugins in the suite require the **Datacurso AI Provider** to function.

## Pre-requisites

Before installing AI Tutor Chat, ensure your system meets these requirements:

1. **Moodle 4.5 or later** - This plugin requires Moodle version 4.5 or higher
2. **Datacurso AI Provider plugin** - Must be installed and configured
   - Download the free AI Provider plugin from [Moodle Plugins Directory](https://moodle.org/plugins/aiprovider_datacurso)
   - Install the AI Provider plugin
   - Configure your license key
   - **This plugin will not function unless the Datacurso AI Provider plugin is installed and licensed**
3. **Valid License Key** - Configure your license in the Datacurso AI Provider settings

## Installation

### Method 1: Upload via Moodle Admin Panel

1. Download the plugin ZIP file
2. Go to **Site administration > Plugins > Install plugins**
3. Upload the ZIP file
4. Click **Install plugin from the ZIP file**
5. Follow the on-screen installation prompts

### Method 2: Manual Installation

1. Extract the plugin files to your Moodle installation:
   ```bash
   cd /path/to/moodle/local
   unzip dttutor.zip
   # or use git clone
   ```

2. Run the Moodle upgrade process:
   ```bash
   php admin/cli/upgrade.php --non-interactive
   ```

3. Complete the installation by following any additional prompts

## Configuration

After installation, configure the plugin:

1. Navigate to **Site administration > Plugins > Local plugins > AI Tutor**

2. **Enable the Chat**:
   - Check "Enable Chat" to activate the floating chat globally

3. **Customize Appearance**:
   - **Avatar**: Choose from 10 available avatars (01-10) or upload a custom image
   - **Avatar Position**: Pick the bottom-right or bottom-left corner, or the *Custom position* preset and drag the avatar in the live preview to the exact spot; the drawer side (left/right) is configured independently

4. **Tutor Customization**:
   - **Welcome message**: The first message shown when the chat opens; supports the `{teachername}`, `{coursename}`, `{username}` and `{firstname}` placeholders
   - **Tutor name**: The name shown in the chat header (`{teachername}` shows the course teacher's name)
   - **Custom prompt**: Institutional instructions appended to the tutor's system prompt

5. **Send the student's grades to the AI tutor** (`include_grades`, disabled by default): when enabled, the student's own grades for the course are added to the context sent to the Datacurso AI service so the tutor can answer questions about them

6. The plugin automatically uses your Datacurso AI Provider configuration for API connectivity

### Supported Languages

AI Tutor Chat is available in 7 languages:
- Spanish (es)
- English (en)
- German (de)
- French (fr)
- Portuguese (pt)
- Indonesian (id)
- Russian (ru)

## Usage

### For Students and Teachers

1. **Open the chat**: Click the floating avatar button in the corner of any course page

2. **Type your message**: Enter your question or message in the text field
   - Press `Enter` to send
   - Press `Shift+Enter` for a new line
   - Maximum 4,000 characters per message

3. **Receive AI response**: Watch the AI assistant respond in real-time with streaming text

4. **Close the chat**:
   - Click the X button in the drawer header
   - Click the floating avatar button again
   - Press `Escape` key

### Features

- **Auto-scroll**: Chat automatically scrolls as new content arrives
- **Typing indicator**: Visual feedback while AI processes your message
- **Error handling**: Clear messages if connection issues occur
- **Role-aware**: AI adapts responses based on whether you're a student or teacher
- **Context-aware**: AI knows which course and activity you're viewing

## Data processed and transferred

AI Tutor Chat processes personal data both inside Moodle and in the external Datacurso AI service. Privacy officers can review and act on it through the standard Moodle Privacy API (**Site administration > Users > Privacy and policies**).

### Stored in Moodle

| Where | What | Why |
|---|---|---|
| `local_dttutor_course_config` | The per-course enablement flag and the ID of the user who last edited it | Enable the tutor per course |
| `local_dttutor_session` | One handle per user, course and activity of the chat session opened in the Datacurso AI service (no message content) | Reuse the remote session and be able to delete it later |
| Cache `local_dttutor/sessions` | The same session handles, for fast lookup | Avoid creating a new remote session on every request |

### Sent to the Datacurso AI service

Every request made through the Datacurso AI Provider carries the site URL, an anonymous site identifier, the user ID, the user's language and timezone. On top of that, the tutor sends:

- the chat messages written by the user and the tutor's previous answers;
- the course structure visible to that user (activities, sections, dates, maximum grades) and the URL of the page the message was sent from;
- the ID of the activity being viewed and any text the user selected on the page to ask about;
- the institutional custom prompt configured by the administrator;
- the student's own grades in the course, **only** when the administrator enables *Send the student's grades to the AI tutor* (disabled by default).

Nothing else from the client (for example the user's name or arbitrary page metadata) is forwarded: the plugin builds the context server-side and only accepts an allowlisted set of keys from the browser.

### Export and deletion

- **Privacy API export** returns, per course, the user's stored session handles and the course configuration entries they edited.
- **Privacy API deletion** (per user, per course, or for a set of users in a course) deletes the stored session rows and requests the deletion of each remote session from the Datacurso AI service by its stored identifier. The reference to the last editor of the course configuration is cleared; the course configuration itself is kept because it belongs to the course, not to a person.
- **Course deletion** removes the course configuration and the course's sessions (locally and remotely).
- **User deletion** removes the user's sessions (locally and remotely).

Remote deletion is best effort: a failure on the Datacurso side is logged and never blocks the Moodle deletion. Because remote sessions are deleted one by one using the stored identifiers, sessions created before this version (which were never recorded) cannot be enumerated from Moodle. A per-user bulk deletion endpoint on the Datacurso AI backend is a pending dependency that would make erasure exhaustive; until it is available, requests concerning such sessions are handled through Datacurso support.

## Troubleshooting

### The floating button doesn't appear

**Check these settings:**
1. Verify the chat is enabled in plugin settings
2. Confirm you're on a course page (not the site homepage)
3. Clear Moodle caches: `php admin/cli/purge_caches.php`

### Chat drawer doesn't open when clicking the button

**Possible causes:**
1. JavaScript conflict with another plugin - check browser console (F12) for errors
2. Missing compiled JavaScript - verify `amd/build/tutor_ia_chat.min.js` exists
3. Cache issue - clear both Moodle and browser caches

### "Session not ready" error

**Solution:**
1. Verify your Datacurso AI Provider license is valid and active
2. Check that the AI Provider plugin is properly configured
3. Review Moodle error logs for API connectivity issues

### Avatar not displaying

**Fix:**
1. Verify avatar image files exist in `pix/avatars/` directory
2. Check file permissions allow web server to read the images
3. Try selecting a different avatar in settings and save

## Development

### Build JavaScript

To modify and rebuild the AMD JavaScript modules:

```bash
cd /path/to/moodle
grunt amd --root=local/dttutor
```

For active development with automatic rebuilds:

```bash
grunt watch --root=local/dttutor
```

### Clear Caches

After making changes:

```bash
php admin/cli/purge_caches.php
```

## Credits

- **Developer**: Datacurso
- **License**: GNU GPL v3 or later
- **Based on**: Moodle's core patterns for drawer interfaces

## Support

For support and questions:
- **Email**: info@industriaelearning.com
- **Issues**: [GitHub Issues](https://github.com/industria-elearning/moodle-local_dttutor/issues)

## License

Copyright (C) 2025 Datacurso

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.
