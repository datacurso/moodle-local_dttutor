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

**Configuration panel** showing global chat enable toggle, off-topic detection settings, and avatar selection with 10 built-in avatar options.

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
   - **Avatar**: Choose from 10 available avatars (01-10)
   - **Avatar Position**: Select bottom-right or bottom-left corner

4. The plugin automatically uses your Datacurso AI Provider configuration for API connectivity

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
