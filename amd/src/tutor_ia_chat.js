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
 * Tutor-IA Chat - Drawer and chat functionality (based on aiplacement_courseassist)
 *
 * @module     local_dttutor/tutor_ia_chat
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/pubsub',
    'core/str',
    'local_dttutor/error_modal'
], function (
    $,
    Ajax,
    Notification,
    PubSub,
    Str,
    ErrorModal
) {
    'use strict';

    const SELECTORS = {
        TOGGLE_BTN: '[data-action="tutor-ia-toggle"]',
        DRAWER: '.tutor-ia-drawer',
        CLOSE_BTN: '.tutor-ia-close-button',
        MESSAGES: '[data-region="tutor-ia-messages"]',
        INPUT: '[data-region="tutor-ia-input"]',
        SEND_BTN: '[data-action="send-message"]',
        EDIT_MESSAGE: '[data-action="edit-message"]',
        PAGE: '#page',
        JUMP_TO: '#jump-to',
        BODY: 'body'
    };

    /**
     * TutorIAChat - Main chat drawer and messaging functionality.
     *
     * @class TutorIAChat
     */
    class TutorIAChat {
        /**
         * Constructor.
         *
         * @param {HTMLElement} root - The root element for the chat
         * @param {string} uniqueId - Unique identifier for the instance
         * @param {number} courseId - Course ID
         * @param {number} cmId - Course module ID
         * @param {number} userId - User ID
         */
        constructor(root, uniqueId, courseId, cmId, userId) {
            this.root = $(root);
            this.uniqueId = uniqueId;
            this.courseId = courseId;
            this.cmId = cmId;
            this.userId = userId;
            this.streaming = false;
            this.currentEventSource = null;
            this.currentAbortController = null;
            this.currentSessionId = null;
            this.currentAIMessageEl = null;
            this.currentAIMessageContainer = null;
            this.currentAIMessageRawText = '';
            this.markdownRenderFrameId = null;
            this.markdownRenderScheduled = false;
            this.editingMessageEl = null;

            // Text selection state.
            this.selectedText = '';
            this.selectionLineCount = 0;
            this.selectionCharCount = 0;

            // Bound functions for proper event listener removal.
            this.boundHandleTextSelectionMouseUp = null;
            this.boundHandleTextSelectionKeyUp = null;
            this.textSelectionListenersActive = false;
            this.textSelectionDebounceTimer = null;

            // Cached DOM elements for performance.
            this.cachedSelectionIndicator = null;
            this.cachedSelectionCount = null;

            // History pagination state.
            this.historyOffset = 0;
            this.historyLimit = 20;
            this.isLoadingHistory = false;
            this.hasMoreHistory = true;
            this.historyLoaded = false;

            // Language strings (loaded asynchronously).
            this.strings = {};
            this.stringsLoaded = false;

            this.welcomeMessage = root.getAttribute('data-welcomemessage') || '';

            this.drawerElement = document.querySelector(SELECTORS.DRAWER);
            this.pageElement = document.querySelector(SELECTORS.PAGE);
            this.bodyElement = document.querySelector(SELECTORS.BODY);
            this.toggleButton = document.querySelector(SELECTORS.TOGGLE_BTN);
            this.closeButton = document.querySelector(SELECTORS.CLOSE_BTN);
            this.jumpTo = document.querySelector(SELECTORS.JUMP_TO);

            this.position = this.drawerElement ? this.drawerElement.getAttribute('data-position') || 'right' : 'right';
            this.pageClass = this.position === 'left' ? 'show-drawer-left' : 'show-drawer-right';
            // Used for footer-popover positioning via CSS.
            this.bodyClass = this.position === 'left' ? 'tutor-ia-drawer-open-left' : 'tutor-ia-drawer-open-right';

            this.pageContext = this.detectPageContext();
            this.adjustDrawerTopPosition();
            this.init();
        }

        /**
         * Adjusts the drawer top position based on the navbar height.
         * Ensures compatibility with different Moodle themes that may have different navbar heights.
         */
        adjustDrawerTopPosition() {
            if (!this.drawerElement) {
                return;
            }

            const navbarSelectors = [
                '.navbar.fixed-top',
                '.fixed-top.navbar',
                '#page-header.fixed-top',
                'nav.fixed-top',
                '.navbar-fixed-top'
            ];

            let navbarHeight = 60;

            for (const selector of navbarSelectors) {
                const navbar = document.querySelector(selector);
                if (navbar) {
                    navbarHeight = navbar.offsetHeight;
                    if (navbarHeight > 0) {
                        break;
                    }
                }
            }

            this.drawerElement.style.setProperty('--tutor-ia-drawer-top', navbarHeight + 'px');

            window.addEventListener('resize', () => {
                const navbar = document.querySelector(navbarSelectors[0]);
                if (navbar) {
                    const newHeight = navbar.offsetHeight;
                    this.drawerElement.style.setProperty('--tutor-ia-drawer-top', newHeight + 'px');
                }
            });
        }

        /**
         * Detects the current page context (page type and relevant parameters).
         *
         * @returns {Object} Object with page contextual information
         */
        detectPageContext() {
            const context = {};

            if (typeof M !== 'undefined' && M.cfg && M.cfg.pagetype) {
                context.pagetype = M.cfg.pagetype;
            }

            // Fallback: extract from body id (format: "page-mod-forum-discuss").
            if (!context.pagetype) {
                const bodyId = document.body.id;
                if (bodyId) {
                    context.pagetype = bodyId.replace('page-', '');
                }
            }

            // Fallback: extract from body classes.
            if (!context.pagetype) {
                const bodyClasses = document.body.className;
                const pathMatch = bodyClasses.match(/path-([\w-]+)/);
                if (pathMatch) {
                    context.pagetype = pathMatch[1];
                }
            }

            const urlParams = new URLSearchParams(window.location.search);

            if (context.pagetype && context.pagetype.includes('forum')) {
                if (urlParams.has('d')) {
                    context.discussionid = parseInt(urlParams.get('d'), 10);
                }
                if (urlParams.has('f')) {
                    context.forumid = parseInt(urlParams.get('f'), 10);
                }
            } else if (context.pagetype && context.pagetype.includes('quiz')) {
                if (urlParams.has('attempt')) {
                    context.attemptid = parseInt(urlParams.get('attempt'), 10);
                }
            } else if (context.pagetype && context.pagetype.includes('assign')) {
                if (urlParams.has('id')) {
                    context.assignid = parseInt(urlParams.get('id'), 10);
                }
            } else if (context.pagetype && context.pagetype.includes('wiki')) {
                if (urlParams.has('pageid')) {
                    context.pageid = parseInt(urlParams.get('pageid'), 10);
                }
            }

            return context;
        }

        init() {
            this.loadStrings();
            this.registerEventListeners();
            window.addEventListener('beforeunload', () => this.cleanup());
        }

        /**
         * Load language strings asynchronously.
         */
        loadStrings() {
            Str.get_strings([
                { key: 'line', component: 'local_dttutor' },
                { key: 'lines', component: 'local_dttutor' },
                { key: 'char', component: 'local_dttutor' },
                { key: 'chars', component: 'local_dttutor' },
                { key: 'selected', component: 'local_dttutor' },
                { key: 'yesterday', component: 'local_dttutor' },
                { key: 'loading', component: 'local_dttutor' },
                { key: 'error_invalid_message', component: 'local_dttutor' },
                { key: 'error_message_too_long', component: 'local_dttutor' },
                { key: 'error_no_credits', component: 'local_dttutor' },
                { key: 'error_no_credits_short', component: 'local_dttutor' },
                { key: 'error_internal', component: 'local_dttutor' },
                { key: 'connection_interrupted', component: 'local_dttutor' },
                { key: 'error_establish_sse_connection', component: 'local_dttutor' },
                { key: 'error_unexpected', component: 'local_dttutor' },
                { key: 'error_unknown', component: 'local_dttutor' },
                { key: 'error_attempt_later', component: 'local_dttutor' },
                { key: 'error_license_fallback', component: 'local_dttutor' },
                { key: 'error_license_fallback_short', component: 'local_dttutor' },
                { key: 'error_no_credits_fallback', component: 'local_dttutor' },
                { key: 'error_insufficient_tokens_short', component: 'local_dttutor' },
                { key: 'edit_message', component: 'local_dttutor' },
                { key: 'editing_message', component: 'local_dttutor' },
                { key: 'edit_save', component: 'local_dttutor' },
                { key: 'edit_cancel', component: 'local_dttutor' }
            ]).then((strings) => {
                this.strings = {
                    line: strings[0],
                    lines: strings[1],
                    char: strings[2],
                    chars: strings[3],
                    selected: strings[4],
                    yesterday: strings[5],
                    loading: strings[6],
                    errorInvalidMessage: strings[7],
                    errorMessageTooLong: strings[8],
                    errorNoCredits: strings[9],
                    errorNoCreditsShort: strings[10],
                    errorInternal: strings[11],
                    connectionInterrupted: strings[12],
                    errorEstablishSse: strings[13],
                    errorUnexpected: strings[14],
                    errorUnknown: strings[15],
                    errorAttemptLater: strings[16],
                    errorLicenseFallback: strings[17],
                    errorLicenseFallbackShort: strings[18],
                    errorNoCreditssFallback: strings[19],
                    errorInsufficientTokensShort: strings[20],
                    editMessage: strings[21],
                    editingMessage: strings[22],
                    editSave: strings[23],
                    editCancel: strings[24]
                };
                this.stringsLoaded = true;
                return;
            }).catch(() => {
                // Fallback to English if strings fail to load.
                this.strings = {
                    line: 'line',
                    lines: 'lines',
                    char: 'char',
                    chars: 'chars',
                    selected: 'selected',
                    yesterday: 'Yesterday',
                    loading: 'Loading...',
                    errorInvalidMessage: 'Please enter a valid message',
                    errorMessageTooLong: '[Error] Message is too long. Maximum 4000 characters.',
                    errorNoCredits: 'Insufficient AI credits available.',
                    errorNoCreditsShort: 'No Credits Available',
                    errorInternal: 'Internal error: {$a}',
                    connectionInterrupted: '[Connection interrupted]',
                    errorEstablishSse: '[Error] Could not establish SSE connection',
                    errorUnexpected: 'An unexpected error occurred. Please try again.',
                    errorUnknown: 'An unknown error occurred. Please try again.',
                    errorAttemptLater: 'An error occurred. Please try again later.',
                    errorLicenseFallback: 'License error: {$a}',
                    errorLicenseFallbackShort: 'License Error',
                    errorNoCreditssFallback: 'Insufficient credits: {$a}',
                    errorInsufficientTokensShort: 'Insufficient Credits',
                    editMessage: 'Edit message',
                    editingMessage: 'Editing message',
                    editSave: 'Save',
                    editCancel: 'Cancel'
                };
                this.stringsLoaded = true;
            });
        }

        /**
         * Register all event listeners for the chat functionality.
         */
        registerEventListeners() {
            if (this.toggleButton) {
                this.toggleButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleDrawer();
                });
            }

            if (this.closeButton) {
                this.closeButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.closeDrawer();
                });
            }

            this.root.find(SELECTORS.SEND_BTN).on('click', () => {
                this.sendMessage();
            });

            const input = this.root.find(SELECTORS.INPUT);
            input.on('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            input.on('input', function () {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });

            const messagesContainer = this.root.find(SELECTORS.MESSAGES);
            messagesContainer.on('scroll', () => {
                this.handleHistoryScroll();
            });

            messagesContainer.on('click', SELECTORS.EDIT_MESSAGE, (e) => {
                e.preventDefault();
                this.startEditingMessage($(e.currentTarget).closest('.tutor-ia-message.user'));
            });

            document.addEventListener('keydown', (e) => {
                if (this.isDrawerOpen() && e.key === 'Escape') {
                    this.closeDrawer();
                }
            });

            // Close drawer if Moodle's message drawer opens (prevent conflict).
            PubSub.subscribe('core_message/drawer_shown', () => {
                if (this.isDrawerOpen()) {
                    this.closeDrawer();
                }
            });

            if (this.jumpTo) {
                this.jumpTo.addEventListener('focus', () => {
                    if (this.closeButton) {
                        this.closeButton.focus();
                    }
                });
            }

            // Text selection listeners are dynamically added/removed when drawer opens/closes.
            this.root.find('[data-action="clear-selection"]').on('click', () => {
                this.clearSelection();
                if (window.getSelection) {
                    window.getSelection().removeAllRanges();
                }
            });
        }

        /**
         * Attaches text selection event listeners to the document.
         * Called when the drawer opens. Uses debouncing to prevent excessive processing.
         */
        attachTextSelectionListeners() {
            if (this.textSelectionListenersActive) {
                return;
            }

            this.boundHandleTextSelectionMouseUp = () => {
                this.debouncedHandleTextSelection();
            };

            this.boundHandleTextSelectionKeyUp = (e) => {
                if (e.shiftKey || e.ctrlKey || e.metaKey) {
                    this.debouncedHandleTextSelection();
                }
            };

            document.addEventListener('mouseup', this.boundHandleTextSelectionMouseUp);
            document.addEventListener('keyup', this.boundHandleTextSelectionKeyUp);

            this.textSelectionListenersActive = true;
        }

        /**
         * Detaches text selection event listeners from the document.
         * Called when the drawer closes to eliminate performance overhead.
         */
        detachTextSelectionListeners() {
            if (!this.textSelectionListenersActive) {
                return;
            }

            if (this.boundHandleTextSelectionMouseUp) {
                document.removeEventListener('mouseup', this.boundHandleTextSelectionMouseUp);
                this.boundHandleTextSelectionMouseUp = null;
            }

            if (this.boundHandleTextSelectionKeyUp) {
                document.removeEventListener('keyup', this.boundHandleTextSelectionKeyUp);
                this.boundHandleTextSelectionKeyUp = null;
            }

            if (this.textSelectionDebounceTimer) {
                clearTimeout(this.textSelectionDebounceTimer);
                this.textSelectionDebounceTimer = null;
            }

            this.textSelectionListenersActive = false;
        }

        /**
         * Debounced version of handleTextSelection.
         * Waits 150ms before processing to prevent excessive DOM operations.
         */
        debouncedHandleTextSelection() {
            if (this.textSelectionDebounceTimer) {
                clearTimeout(this.textSelectionDebounceTimer);
            }

            this.textSelectionDebounceTimer = setTimeout(() => {
                this.handleTextSelection();
                this.textSelectionDebounceTimer = null;
            }, 150);
        }

        /**
         * Handles text selection on the page.
         * IMPORTANT: This method ONLY reads the selection - it does NOT modify the page DOM.
         * Selection persists until explicitly cleared by user action (X button, send message, or new selection).
         */
        handleTextSelection() {
            const selection = window.getSelection();
            const selectedText = selection ? selection.toString().trim() : '';

            // Only update if there is actual new text selected.
            if (selectedText && selectedText.length > 0) {
                this.selectedText = selectedText;
                this.selectionLineCount = selectedText.split('\n').length;
                this.selectionCharCount = selectedText.length;
                this.updateSelectionIndicator();
            }
        }

        /**
         * Caches selection indicator DOM elements for performance.
         */
        cacheSelectionIndicatorElements() {
            this.cachedSelectionIndicator = this.root.find('[data-region="selection-indicator"]');
            this.cachedSelectionCount = this.root.find('[data-region="selection-count"]');
        }

        /**
         * Updates the selection indicator in the chat UI.
         */
        updateSelectionIndicator() {
            const indicator = this.cachedSelectionIndicator || this.root.find('[data-region="selection-indicator"]');
            const countElement = this.cachedSelectionCount || this.root.find('[data-region="selection-count"]');

            if (!indicator.length || !countElement.length) {
                return;
            }

            if (this.selectedText && this.selectedText.length > 0 && this.selectionLineCount > 0) {
                const lineText = this.selectionLineCount === 1 ? this.strings.line : this.strings.lines;
                const charText = this.selectionCharCount === 1 ? this.strings.char : this.strings.chars;
                const selectionText = this.selectionLineCount + ' ' + lineText + ', ' +
                    this.selectionCharCount + ' ' + charText + ' ' + this.strings.selected;
                countElement.text(selectionText);
                indicator.show();
            } else {
                indicator.hide();
            }
        }

        /**
         * Clears the text selection state.
         */
        clearSelection() {
            this.selectedText = '';
            this.selectionLineCount = 0;
            this.selectionCharCount = 0;
            this.updateSelectionIndicator();
        }

        /**
         * Check if the drawer is currently open.
         *
         * @returns {boolean} True if drawer is open
         */
        isDrawerOpen() {
            return this.drawerElement && this.drawerElement.classList.contains('show');
        }

        /**
         * Opens the chat drawer.
         */
        openDrawer() {
            if (!this.drawerElement) {
                return;
            }

            // Close Moodle's message drawer to prevent conflict.
            PubSub.publish('core_message/hide', {});

            this.drawerElement.classList.add('show');
            this.drawerElement.setAttribute('tabindex', '0');

            if (this.toggleButton) {
                this.toggleButton.setAttribute('aria-expanded', 'true');
            }

            if (this.pageElement && !this.pageElement.classList.contains(this.pageClass)) {
                this.pageElement.classList.add(this.pageClass);
            }

            if (this.bodyElement && !this.bodyElement.classList.contains(this.bodyClass)) {
                this.bodyElement.classList.add(this.bodyClass);
            }

            if (this.jumpTo) {
                this.jumpTo.setAttribute('tabindex', 0);
                this.jumpTo.focus();
            }

            this.loadChatHistory();

            this.attachTextSelectionListeners();
            this.cacheSelectionIndicatorElements();
        }

        /**
         * Closes the chat drawer.
         */
        closeDrawer() {
            if (!this.drawerElement) {
                return;
            }

            this.drawerElement.classList.remove('show');
            this.drawerElement.setAttribute('tabindex', '-1');

            if (this.toggleButton) {
                this.toggleButton.setAttribute('aria-expanded', 'false');
            }

            if (this.pageElement && this.pageElement.classList.contains(this.pageClass)) {
                this.pageElement.classList.remove(this.pageClass);
            }

            if (this.bodyElement && this.bodyElement.classList.contains(this.bodyClass)) {
                this.bodyElement.classList.remove(this.bodyClass);
            }

            if (this.jumpTo) {
                this.jumpTo.setAttribute('tabindex', -1);
            }
            if (this.toggleButton) {
                this.toggleButton.focus();
            }

            this.detachTextSelectionListeners();
        }

        /**
         * Toggles the drawer open/closed state.
         */
        toggleDrawer() {
            if (this.isDrawerOpen()) {
                this.closeDrawer();
            } else {
                this.openDrawer();
            }
        }

        /**
         * Load chat history from API.
         */
        loadChatHistory() {
            if (this.isLoadingHistory || !this.hasMoreHistory) {
                return;
            }

            this.isLoadingHistory = true;

            const messagesContainer = this.root.find(SELECTORS.MESSAGES);
            const scrollHeightBefore = messagesContainer[0].scrollHeight;
            const scrollTopBefore = messagesContainer[0].scrollTop;

            this.showHistoryLoading();

            const requests = Ajax.call([{
                methodname: "local_dttutor_get_chat_history",
                args: {
                    courseid: parseInt(this.courseId, 10),
                    cmid: this.cmId ? parseInt(this.cmId, 10) : null,
                    limit: this.historyLimit,
                    offset: this.historyOffset
                },
            }]);

            requests[0]
                .then((data) => {
                    this.hideHistoryLoading();

                    if (data.success && data.messages && data.messages.length > 0) {
                        const isInitialLoad = this.historyOffset === 0;

                        this.displayHistoryMessages(data.messages, isInitialLoad);
                        this.historyOffset += data.messages.length;
                        this.hasMoreHistory = data.pagination.has_more;

                        if (isInitialLoad) {
                            this.scrollToBottom();
                        } else {
                            // Maintain scroll position when loading older messages.
                            const scrollHeightAfter = messagesContainer[0].scrollHeight;
                            const scrollDiff = scrollHeightAfter - scrollHeightBefore;
                            messagesContainer[0].scrollTop = scrollTopBefore + scrollDiff;
                        }
                    } else {
                        this.hasMoreHistory = false;
                    }

                    this.historyLoaded = true;
                    this.isLoadingHistory = false;
                    return data;
                })
                .catch((err) => {
                    this.hideHistoryLoading();
                    this.isLoadingHistory = false;

                    ErrorModal.showGeneralError(this.getFriendlyErrorMessage(err));
                });
        }

        /**
         * Handle scroll event for infinity scroll.
         */
        handleHistoryScroll() {
            const messagesContainer = this.root.find(SELECTORS.MESSAGES);
            const scrollTop = messagesContainer[0].scrollTop;

            if (scrollTop < 100 && !this.isLoadingHistory && this.hasMoreHistory) {
                this.loadChatHistory();
            }
        }

        /**
         * Display history messages in the chat.
         *
         * @param {Array} messages - Array of message objects (ordered DESC by timestamp from backend)
         * @param {boolean} isInitialLoad - True if first load (append), false for older messages (prepend)
         */
        displayHistoryMessages(messages, isInitialLoad) {
            const messagesContainer = this.root.find(SELECTORS.MESSAGES);

            if (isInitialLoad && this.welcomeMessage) {
                messagesContainer.find('.tutor-ia-message.ai:contains("' + this.welcomeMessage + '")').remove();
            }

            if (isInitialLoad) {
                // Initial load: reverse to get chronological order.
                for (let i = messages.length - 1; i >= 0; i--) {
                    const messageDiv = this.createMessageElement(messages[i]);
                    messagesContainer.append(messageDiv);
                }
            } else {
                // Loading older: prepend to place above existing messages.
                messages.forEach(msg => {
                    const messageDiv = this.createMessageElement(msg);
                    messagesContainer.prepend(messageDiv);
                });
            }
        }

        /**
         * Create a message element.
         *
         * @param {Object} msg - Message object
         * @returns {jQuery} Message element
         */
        createMessageElement(msg) {
            const messageDiv = $('<div>')
                .addClass('tutor-ia-message')
                .addClass(msg.role === 'user' ? 'user' : 'ai')
                .attr('data-message-id', msg.id)
                .attr('data-message-role', msg.role === 'user' ? 'user' : 'assistant')
                .attr('data-message-content', msg.content || '');

            const contentDiv = $('<div>')
                .addClass('message-content');

            if (msg.role === 'user') {
                contentDiv.text(msg.content);
            } else {
                contentDiv.html(this.renderMarkdown(msg.content || ''));
            }

            const timestampDiv = $('<div>')
                .addClass('message-timestamp')
                .text(this.formatTimestamp(msg.timestamp));

            messageDiv.append(contentDiv);
            messageDiv.append(timestampDiv);

            if (msg.role === 'user') {
                messageDiv.append(this.createEditButton());
            }

            return messageDiv;
        }

        /**
         * Create the edit button for user messages.
         *
         * @returns {jQuery} Edit button element
         */
        createEditButton() {
            return $('<button type="button"></button>')
                .addClass('tutor-ia-edit-message')
                .attr('data-action', 'edit-message')
                .attr('aria-label', this.strings.editMessage || 'Edit message')
                .attr('title', this.strings.editMessage || 'Edit message')
                .text('✎');
        }

        /**
         * Format Unix timestamp to readable date and time.
         *
         * @param {number} timestamp - Unix timestamp
         * @returns {string} Formatted date and time string
         */
        formatTimestamp(timestamp) {
            const date = new Date(timestamp * 1000);
            const today = new Date();

            const messageDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
            const todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            const yesterday = new Date(todayDate);
            yesterday.setDate(yesterday.getDate() - 1);

            const hours = date.getHours().toString().padStart(2, '0');
            const minutes = date.getMinutes().toString().padStart(2, '0');
            const time = `${hours}:${minutes}`;

            if (messageDate.getTime() === todayDate.getTime()) {
                return time;
            }

            if (messageDate.getTime() === yesterday.getTime()) {
                return `${this.strings.yesterday} ${time}`;
            }

            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year} ${time}`;
        }

        /**
         * Show loading indicator at top of messages.
         */
        showHistoryLoading() {
            const messagesContainer = this.root.find(SELECTORS.MESSAGES);
            if (!messagesContainer.find('.history-loading').length) {
                const loadingDiv = $('<div>')
                    .addClass('history-loading')
                    .text(this.strings.loading);
                messagesContainer.prepend(loadingDiv);
            }
        }

        /**
         * Hide loading indicator.
         */
        hideHistoryLoading() {
            this.root.find('.history-loading').remove();
        }

        /**
         * Send a message to the AI tutor.
         */
        sendMessage() {
            const input = this.root.find(SELECTORS.INPUT);
            const sendBtn = this.root.find(SELECTORS.SEND_BTN);
            const messageText = input.val().trim();

            if (!messageText || this.streaming) {
                return;
            }

            // Sending a new message cancels any active inline editing.
            this.clearEditingState();

            if (messageText === '.') {
                this.addMessage('[Error] ' + this.strings.errorInvalidMessage, 'ai');
                return;
            }

            if (messageText.length > 4000) {
                this.addMessage(this.strings.errorMessageTooLong, 'ai');
                return;
            }

            try {
                this.closeCurrentStream();
                sendBtn.prop('disabled', true);

                this.addMessage(messageText, 'user');
                const payloadMessages = this.buildConversationMessages();

                input.val('');
                input.css('height', 'auto');
                this.scrollToBottom();
                this.showTypingIndicator();

                // Build page context for the proxy.
                const context = {
                    course_id: parseInt(this.courseId, 10) || 0,
                    activity_id: parseInt(this.cmId, 10) || 0,
                    page_url: window.location.href,
                    page_title: document.title,
                    pagetype: this.pageContext.pagetype || '',
                };

                // Build payload for chatproxy.php.
                const payload = {
                    messages: payloadMessages,
                    model: this.getModelName(),
                    sesskey: M.cfg.sesskey || '',
                    context: context,
                };

                const proxyUrl = this.getChatProxyUrl();

                // Use AbortController so closeCurrentStream() can cancel the fetch.
                this.currentAbortController = new AbortController();
                const signal = this.currentAbortController.signal;
                this.streaming = true;
                let firstTokenReceived = false;

                fetch(proxyUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    signal: signal,
                })
                    .then((response) => {
                        if (!response.ok) {
                            return response.json().then((errData) => {
                                throw new Error(errData.error || 'HTTP ' + response.status);
                            });
                        }
                        return this._readChatProxyStream(response, sendBtn, firstTokenReceived);
                    })
                    .then(() => {
                        // Stream completed successfully.
                        this.finalizeStream(sendBtn);
                        this.clearSelection();
                    })
                    .catch((err) => {
                        if (err.name === 'AbortError') {
                            return; // Cancelled by closeCurrentStream.
                        }
                        this.hideTypingIndicator();
                        sendBtn.prop('disabled', false);
                        ErrorModal.showGeneralError(this.strings.errorUnexpected);
                    });
            } catch (error) {
                this.hideTypingIndicator();
                sendBtn.prop('disabled', false);
                ErrorModal.showGeneralError(this.strings.errorInternal.replace('{$a}', error.message));
            }
        }

        /**
         * Read and parse SSE events from the chatproxy.php response stream.
         *
         * The proxy emits 'token' events with {"t":"..."} and a 'done' event
         * when complete. This replicates the EventSource handling from startSSE()
         * but uses fetch() + ReadableStream for POST support.
         *
         * @param {Response} response - The fetch Response object.
         * @param {jQuery} sendBtn - Send button element.
         * @param {boolean} firstTokenReceived - Reference boolean (mutated by reference in closure).
         * @returns {Promise<void>}
         */
        _readChatProxyStream(response, sendBtn, firstTokenReceived) {
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            let messageCompleted = false;
            let that = this;

            return new Promise((resolve, reject) => {
                /**
                 * Process each chunk from the stream reader.
                 * @return {void}
                 */
                function processChunk() {
                    reader.read().then(({ done, value }) => {
                        if (done) {
                            if (!messageCompleted) {
                                // If no done event was received, finalize anyway.
                                resolve();
                            }
                            return;
                        }

                        buffer += decoder.decode(value, { stream: true });

                        // SSE events are separated by \n\n.
                        const parts = buffer.split('\n\n');
                        // Keep the last (potentially incomplete) part in the buffer.
                        buffer = parts.pop() || '';

                        for (const part of parts) {
                            if (!part.trim()) {
                                continue;
                            }

                            const lines = part.split('\n');
                            let eventType = '';
                            let dataStr = '';

                            for (const line of lines) {
                                const trimmed = line.trim();
                                if (trimmed.startsWith('event: ')) {
                                    eventType = trimmed.substring(7).trim();
                                } else if (trimmed.startsWith('data: ')) {
                                    dataStr = trimmed.substring(6).trim();
                                }
                            }

                            if (eventType === 'token' && dataStr) {
                                try {
                                    const payload = JSON.parse(dataStr);
                                    const text = payload.t || payload.content || '';
                                    if (text) {
                                        if (!firstTokenReceived) {
                                            firstTokenReceived = true;
                                            that.ensureAIMessageEl();
                                            that.hideTypingIndicator();
                                        }
                                        that.appendToAIMessage(text);
                                    }
                                } catch (e) {
                                    // Invalid JSON in token data, skip.
                                }
                            } else if (eventType === 'done' || eventType === 'message_completed') {
                                messageCompleted = true;
                                resolve();
                                return;
                            } else if (eventType === 'error' && dataStr) {
                                try {
                                    const errorData = JSON.parse(dataStr);
                                    that.handleStreamError(errorData, sendBtn);
                                    reject(new Error('Stream error'));
                                    return;
                                } catch (e) {
                                    // Non-JSON error, fall through.
                                }
                            }
                        }

                        // Continue reading.
                        processChunk();
                    }).catch((err) => {
                        if (err.name === 'AbortError') {
                            resolve(); // Cancelled, resolve cleanly.
                        } else {
                            reject(err);
                        }
                    });
                }

                processChunk();
            });
        }

        /**
         * Get the chatproxy.php URL.
         *
         * @returns {string}
         */
        getChatProxyUrl() {
            // Use a data attribute on the root element if available,
            // otherwise compute from the current Moodle wwwroot.
            const proxyUrl = this.root.attr('data-chatproxy-url');
            if (proxyUrl) {
                return proxyUrl;
            }
            // Fallback: derive from the current page URL.
            // This works because chatproxy.php is in the same Moodle instance.
            const baseUrl = M.cfg.wwwroot || window.location.origin;
            return baseUrl + '/local/dttutor/chatproxy.php';
        }

        /**
         * Get the model name to use.
         *
         * The model is a placeholder — the Datacurso AI proxy decides the
         * actual model server-side (Gemini, OpenAI, etc.).
         *
         * @returns {string}
         */
        getModelName() {
            return 'gemini-2.5-flash';
        }

        /**
         * Start Server-Sent Events stream for AI response.
         *
         * @param {string} streamUrl - URL for the SSE stream
         * @param {jQuery} sendBtn - Send button element to re-enable on completion
         */
        startSSE(streamUrl, sendBtn) {
            try {
                const es = new EventSource(streamUrl);
                this.currentEventSource = es;
                this.streaming = true;
                let firstToken = true;
                let messageCompleted = false;

                es.addEventListener('token', (ev) => {
                    try {
                        const payload = JSON.parse(ev.data);
                        const text = payload.t || payload.content || '';

                        if (firstToken) {
                            firstToken = false;
                            this.ensureAIMessageEl();
                            this.hideTypingIndicator();
                        }
                        this.appendToAIMessage(text);
                    } catch (e) {
                        // Invalid token data, skip.
                    }
                });

                es.addEventListener('done', () => {
                    messageCompleted = true;
                    this.finalizeStream(sendBtn);
                });

                // Backward compatibility with 'message_completed' event.
                es.addEventListener('message_completed', () => {
                    messageCompleted = true;
                    this.finalizeStream(sendBtn);
                });

                es.addEventListener('error', (ev) => {
                    if (ev.data) {
                        try {
                            const errorData = JSON.parse(ev.data);
                            this.handleStreamError(errorData, sendBtn);
                            return;
                        } catch (e) {
                            // Not JSON, continue with generic error handling.
                        }
                    }

                    // Only show error if message did NOT complete (error after 'done' is expected).
                    if (!messageCompleted) {
                        this.appendToAIMessage('\n' + this.strings.connectionInterrupted);
                        this.finalizeStream(sendBtn);
                    }
                });
            } catch (error) {
                this.addMessage(this.strings.errorEstablishSse, 'ai');
                this.finalizeStream(sendBtn);
            }
        }

        /**
         * Ensures an AI message element exists for streaming content.
         *
         * @returns {HTMLElement} The message content element
         */
        ensureAIMessageEl() {
            if (this.currentAIMessageEl) {
                return this.currentAIMessageEl;
            }

            const messages = this.root.find(SELECTORS.MESSAGES);
            let messageContainer;

            const typingEl = messages.find('.tutor-ia-typing');
            if (typingEl.length) {
                messageContainer = typingEl;
                messageContainer.removeClass('tutor-ia-typing');
                messageContainer.addClass('tutor-ia-message ai');
                messageContainer.html('');
            } else {
                messageContainer = $('<div class="tutor-ia-message ai"></div>');
                messages.append(messageContainer);
            }

            messageContainer
                .attr('data-message-role', 'assistant')
                .attr('data-message-content', '');

            const contentDiv = $('<div>')
                .addClass('message-content');
            messageContainer.append(contentDiv);

            this.currentAIMessageEl = contentDiv[0];
            this.currentAIMessageContainer = messageContainer[0];
            this.currentAIMessageRawText = '';
            return this.currentAIMessageEl;
        }

        /**
         * Appends text to the current AI message element.
         *
         * @param {string} text - Text to append
         */
        appendToAIMessage(text) {
            if (!this.currentAIMessageEl) {
                this.ensureAIMessageEl();
            }
            if (!this.currentAIMessageEl || typeof text !== 'string') {
                return;
            }

            const currentText = this.currentAIMessageRawText || '';
            const maxLength = 10000;

            let chunk = text;

            if (currentText.length + chunk.length > maxLength) {
                const remaining = maxLength - currentText.length;
                if (remaining <= 0) {
                    return;
                }
                chunk = chunk.substring(0, remaining) + '...';
            }

            this.currentAIMessageRawText += chunk;
            this.scheduleStreamingRender();
        }

        /**
         * Batch markdown rendering to avoid full DOM re-render on each chunk.
         */
        scheduleStreamingRender() {
            if (this.markdownRenderScheduled) {
                return;
            }

            this.markdownRenderScheduled = true;
            this.markdownRenderFrameId = window.requestAnimationFrame(() => {
                this.markdownRenderScheduled = false;
                this.markdownRenderFrameId = null;

                if (!this.currentAIMessageEl) {
                    return;
                }

                this.currentAIMessageEl.innerHTML = this.renderMarkdown(this.currentAIMessageRawText);
                this.scrollToBottom();
            });
        }

        /**
         * Flush any pending markdown render immediately.
         */
        flushPendingStreamingRender() {
            if (this.markdownRenderFrameId) {
                window.cancelAnimationFrame(this.markdownRenderFrameId);
                this.markdownRenderFrameId = null;
                this.markdownRenderScheduled = false;
            }

            if (!this.currentAIMessageEl) {
                return;
            }

            this.currentAIMessageEl.innerHTML = this.renderMarkdown(this.currentAIMessageRawText);
            this.scrollToBottom();
        }

        /**
         * Escape HTML special characters.
         *
         * @param {string} text - Raw text
         * @returns {string} Escaped text
         */
        escapeHtml(text) {
            if (typeof text !== 'string') {
                return '';
            }

            return text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        /**
         * Render inline markdown (bold, italic, links, code).
         *
         * @param {string} text - Escaped text
         * @returns {string} HTML string
         */
        renderMarkdownInline(text) {
            if (!text) {
                return '';
            }

            let html = text;

            // Links (http/https only).
            html = html.replace(
                /\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g,
                '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
            );

            // Inline code.
            html = html.replace(/`([^`\n]+)`/g, '<code>$1</code>');

            // Bold.
            html = html.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');
            html = html.replace(/__([^_\n]+)__/g, '<strong>$1</strong>');

            // Italic (after bold).
            html = html.replace(/(^|[^*])\*([^*\n]+)\*(?!\*)/g, '$1<em>$2</em>');

            return html;
        }

        /**
         * Render basic markdown to safe HTML for AI responses.
         *
         * @param {string} text - Raw markdown text
         * @returns {string} Safe HTML
         */
        renderMarkdown(text) {
            const escaped = this.escapeHtml((text || '').replace(/\r\n?/g, '\n'));
            if (!escaped.trim()) {
                return '';
            }

            const blocks = escaped.split(/\n{2,}/);
            const htmlBlocks = blocks.map((block) => {
                const trimmedBlock = block.trim();
                if (!trimmedBlock) {
                    return '';
                }

                const lines = trimmedBlock.split('\n');
                const segments = [];
                const paragraphLines = [];
                let listType = null;
                let listItems = [];

                const flushParagraph = () => {
                    if (!paragraphLines.length) {
                        return;
                    }

                    const paragraph = paragraphLines.join('\n').trim();
                    if (paragraph) {
                        segments.push('<p>' + this.renderMarkdownInline(paragraph).replace(/\n/g, '<br>') + '</p>');
                    }
                    paragraphLines.length = 0;
                };

                const flushList = () => {
                    if (!listItems.length || !listType) {
                        return;
                    }

                    const openTag = listType === 'ol' ? '<ol>' : '<ul>';
                    const closeTag = listType === 'ol' ? '</ol>' : '</ul>';
                    const itemsHtml = listItems
                        .map((item) => '<li>' + this.renderMarkdownInline(item.trim()) + '</li>')
                        .join('');

                    segments.push(openTag + itemsHtml + closeTag);
                    listType = null;
                    listItems = [];
                };

                lines.forEach((line) => {
                    const headingMatch = line.match(/^\s*(#{1,6})\s+(.+)$/);
                    const orderedMatch = line.match(/^\s*\d+\.\s+(.+)$/);
                    const unorderedMatch = line.match(/^\s*[-*+]\s+(.+)$/);

                    if (headingMatch) {
                        flushParagraph();
                        flushList();
                        const level = headingMatch[1].length;
                        const headingContent = this.renderMarkdownInline(headingMatch[2].trim());
                        segments.push('<h' + level + '>' + headingContent + '</h' + level + '>');
                        return;
                    }

                    if (orderedMatch) {
                        flushParagraph();
                        if (listType !== 'ol') {
                            flushList();
                            listType = 'ol';
                        }
                        listItems.push(orderedMatch[1]);
                        return;
                    }

                    if (unorderedMatch) {
                        flushParagraph();
                        if (listType !== 'ul') {
                            flushList();
                            listType = 'ul';
                        }
                        listItems.push(unorderedMatch[1]);
                        return;
                    }

                    flushList();
                    paragraphLines.push(line);
                });

                flushParagraph();
                flushList();

                return segments.join('');
            }).filter((html) => html.length > 0);

            return htmlBlocks.join('');
        }

        /**
         * Adds a complete message to the chat.
         *
         * @param {string} text - Message text
         * @param {string} type - Message type ('user' or 'ai')
         */
        addMessage(text, type) {
            if (!text || typeof text !== 'string') {
                return;
            }

            const messages = this.root.find(SELECTORS.MESSAGES);

            const messageEl = $('<div></div>')
                .addClass('tutor-ia-message')
                .addClass(type)
                .attr('data-message-role', type === 'user' ? 'user' : 'assistant')
                .attr('data-message-content', text.substring(0, 10000));

            const contentDiv = $('<div>')
                .addClass('message-content');

            if (type === 'ai') {
                contentDiv.html(this.renderMarkdown(text.substring(0, 10000)));
            } else {
                contentDiv.text(text.substring(0, 10000));
            }

            const currentTimestamp = Math.floor(Date.now() / 1000);
            const timestampDiv = $('<div>')
                .addClass('message-timestamp')
                .text(this.formatTimestamp(currentTimestamp));

            messageEl.append(contentDiv);
            messageEl.append(timestampDiv);

            if (type === 'user') {
                messageEl.append(this.createEditButton());
            }

            messages.append(messageEl);
            this.scrollToBottom();

            return messageEl;
        }

        /**
         * Start inline editing of a user message.
         *
         * Replaces the message content with a textarea and save/cancel buttons.
         *
         * @param {jQuery} messageEl User message element being edited.
         */
        startEditingMessage(messageEl) {
            if (!messageEl || !messageEl.length || this.streaming) {
                return;
            }

            this.clearEditingState();
            this.editingMessageEl = messageEl[0];

            const text = messageEl.attr('data-message-content') || messageEl.find('.message-content').text();
            const contentDiv = messageEl.find('.message-content');

            // Save original HTML for cancel.
            this.editingOriginalContent = contentDiv.html();

            const editTextarea = $('<textarea class="form-control edit-inline-textarea"></textarea>')
                .val(text)
                .on('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.saveEditing();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        this.cancelEditing();
                    }
                })
                .on('input', function () {
                    this.style.height = 'auto';
                    this.style.height = this.scrollHeight + 'px';
                });

            contentDiv.hide();
            contentDiv.after(
                $('<div class="edit-inline-container"></div>').append(editTextarea)
            );

            // Initial auto-resize now that the textarea is in the DOM.
            editTextarea.each(function () {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });

            // Wrap message + actions in a shared container so they share the same left edge.
            messageEl.wrap('<div class="edit-wrapper"></div>');
            messageEl.parent('.edit-wrapper').append(
                $('<div class="edit-inline-actions"></div>').append(
                    $('<button type="button" class="btn btn-primary btn-sm edit-inline-save"></button>')
                        .text(this.strings.editSave || 'Save')
                        .on('click', () => this.saveEditing()),
                        $('<button type="button" class="btn btn-secondary btn-sm edit-inline-cancel"></button>')
                        .text(this.strings.editCancel || 'Cancel')
                        .on('click', () => this.cancelEditing())
                )
            );

            messageEl.addClass('editing');
            messageEl.find(SELECTORS.EDIT_MESSAGE).hide();
            messageEl.find('.edit-inline-textarea').focus();
        }

        /**
         * Save the inline edit: truncate conversation and send a corrected message.
         */
        saveEditing() {
            if (!this.editingMessageEl) {
                return;
            }

            const textarea = $(this.editingMessageEl).find('.edit-inline-textarea');
            const newText = textarea.val().trim();

            if (!newText || this.streaming) {
                return;
            }

            // Collect messages before the edited one.
            const previousMessages = this.buildConversationMessages(this.editingMessageEl);

            // Truncate DOM from edited message onward (remove wrapper, then subsequent messages).
            const wrapper = $(this.editingMessageEl).parent('.edit-wrapper');
            wrapper.nextAll('.tutor-ia-message').remove();
            wrapper.remove();

            this.clearEditingState();

            // Add corrected user message.
            this.addMessage(newText, 'user');
            this.showTypingIndicator();
            this.scrollToBottom();

            // Build full conversation: history + new message.
            const allMessages = previousMessages.concat([{
                role: 'user',
                content: this.sanitizeString(newText.substring(0, 4000)),
            }]);

            // Send with session reset.
            this._sendConversation(allMessages);
        }

        /**
         * Cancel the inline edit and restore the original message content.
         */
        cancelEditing() {
            this.clearEditingState();
        }

        /**
         * Clear edit state and restore any active inline editor.
         *
         * If a message has an inline textarea open, restores its original content
         * before clearing the edit state. This handles the case where the user
         * clicks edit on a different message while another is still being edited.
         */
        clearEditingState() {
            if (this.editingMessageEl) {
                const el = $(this.editingMessageEl);
                el.removeClass('editing');
                el.find(SELECTORS.EDIT_MESSAGE).show();

                // Restore original content if inline editor is open.
                if (el.find('.edit-inline-container').length) {
                    el.find('.edit-inline-container').remove();
                    el.siblings('.edit-inline-actions').remove();
                    el.unwrap();
                    const contentDiv = el.find('.message-content');
                    if (this.editingOriginalContent !== undefined) {
                        contentDiv.html(this.editingOriginalContent);
                    }
                    contentDiv.show();
                }
            }
            this.editingMessageEl = null;
            this.editingOriginalContent = undefined;
        }

        /**
         * Send a full conversation array to chatproxy.php with session reset.
         *
         * Used by saveEditing() to resend the corrected branch.
         *
         * @param {Array} conversationMessages Array of {role, content} objects.
         */
        _sendConversation(conversationMessages) {
            const sendBtn = this.root.find(SELECTORS.SEND_BTN);
            sendBtn.prop('disabled', true);

            const context = {
                course_id: parseInt(this.courseId, 10) || 0,
                activity_id: parseInt(this.cmId, 10) || 0,
                page_url: window.location.href,
                page_title: document.title,
                pagetype: this.pageContext.pagetype || '',
            };

            const payload = {
                messages: conversationMessages,
                model: this.getModelName(),
                sesskey: M.cfg.sesskey || '',
                context: context,
                reset_session: true,
            };

            const proxyUrl = this.getChatProxyUrl();

            this.currentAbortController = new AbortController();
            const signal = this.currentAbortController.signal;
            this.streaming = true;
            let firstTokenReceived = false;

            fetch(proxyUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
                signal: signal,
            })
                .then((response) => {
                    if (!response.ok) {
                        return response.json().then((errData) => {
                            throw new Error(errData.error || 'HTTP ' + response.status);
                        });
                    }
                    return this._readChatProxyStream(response, sendBtn, firstTokenReceived);
                })
                .then(() => {
                    this.finalizeStream(sendBtn);
                    this.clearSelection();
                })
                .catch((err) => {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    this.hideTypingIndicator();
                    sendBtn.prop('disabled', false);
                    ErrorModal.showGeneralError(this.strings.errorUnexpected);
                });
        }

        /**
         * Build chronological conversation messages from the visible chat.
         *
         * @param {HTMLElement|null} stopBefore Optional message element where collection stops.
         * @returns {Array} Messages for chatproxy.php.
         */
        buildConversationMessages(stopBefore = null) {
            const builtMessages = [];

            this.root.find(SELECTORS.MESSAGES).find('.tutor-ia-message').each((index, element) => {
                if (stopBefore && element === stopBefore) {
                    return false;
                }
                if ($(element).hasClass('tutor-ia-typing')) {
                    return;
                }

                const role = $(element).attr('data-message-role');
                if (role !== 'user' && role !== 'assistant') {
                    return;
                }

                let content = $(element).attr('data-message-content');
                if (!content) {
                    content = $(element).find('.message-content').text();
                }
                content = (content || '').trim();
                if (!content || (this.welcomeMessage && content === this.welcomeMessage)) {
                    return;
                }

                builtMessages.push({
                    role: role,
                    content: this.sanitizeString(content.substring(0, 10000)),
                });
            });

            return builtMessages;
        }

        /**
         * Shows the typing indicator.
         */
        showTypingIndicator() {
            const messages = this.root.find(SELECTORS.MESSAGES);
            if (messages.find('.tutor-ia-typing').length) {
                return;
            }

            const typing = $('<div class="tutor-ia-message ai tutor-ia-typing"></div>')
                .html('<span class="dot"></span><span class="dot"></span><span class="dot"></span>');
            messages.append(typing);
            this.scrollToBottom();
        }

        /**
         * Hides the typing indicator.
         */
        hideTypingIndicator() {
            this.root.find('.tutor-ia-typing').remove();
        }

        /**
         * Show no credits warning in chat.
         *
         * @param {string} errorHtml - HTML error message from provider
         */
        showNoCreditsWarning(errorHtml) {
            const messages = this.root.find(SELECTORS.MESSAGES);

            messages.find('.tutor-ia-no-credits-warning').remove();

            const warningDiv = $('<div class="tutor-ia-no-credits-warning"></div>');
            const alertDiv = $('<div class="alert alert-danger"></div>');
            alertDiv.html(
                '<i class="fa fa-exclamation-circle"></i> ' +
                '<div class="warning-content">' +
                '<strong>' + this.strings.errorNoCreditsShort + '</strong>' +
                '<p>' + errorHtml + '</p>' +
                '</div>'
            );

            warningDiv.append(alertDiv);
            messages.append(warningDiv);
            this.scrollToBottom();
        }

        /**
         * Scrolls the messages container to the bottom.
         */
        scrollToBottom() {
            const messages = this.root.find(SELECTORS.MESSAGES);
            messages.scrollTop(messages[0].scrollHeight);
        }

        /**
         * Closes the current SSE stream.
         */
        closeCurrentStream() {
            this.flushPendingStreamingRender();

            if (this.currentEventSource) {
                try {
                    this.currentEventSource.close();
                } catch (e) {
                    // Ignore close errors.
                }
            }
            this.currentEventSource = null;

            if (this.currentAbortController) {
                try {
                    this.currentAbortController.abort();
                } catch (e) {
                    // Ignore abort errors.
                }
            }
            this.currentAbortController = null;

            this.streaming = false;
            this.currentAIMessageEl = null;
            this.currentAIMessageContainer = null;
            this.currentAIMessageRawText = '';
            this.hideTypingIndicator();
        }

        /**
         * Finalizes the stream and re-enables the send button.
         *
         * @param {jQuery} sendBtn - Send button element
         */
        finalizeStream(sendBtn) {
            this.flushPendingStreamingRender();

            if (this.currentAIMessageContainer) {
                const currentTimestamp = Math.floor(Date.now() / 1000);
                const timestampDiv = $('<div>')
                    .addClass('message-timestamp')
                    .text(this.formatTimestamp(currentTimestamp));
                $(this.currentAIMessageContainer)
                    .attr('data-message-content', this.currentAIMessageRawText || '')
                    .append(timestampDiv);

                this.currentAIMessageContainer = null;
            }

            this.closeCurrentStream();
            if (sendBtn) {
                sendBtn.prop('disabled', false);
            }
        }

        /**
         * Handle structured error data from SSE stream.
         * Detects license and insufficient tokens errors and shows user-friendly modals.
         *
         * @param {Object} errorData - Error data object from SSE event
         * @param {jQuery} sendBtn - Send button element to re-enable
         */
        handleStreamError(errorData, sendBtn) {
            this.hideTypingIndicator();
            this.finalizeStream(sendBtn);

            if (errorData && errorData.detail && errorData.detail.status === 'error') {
                const errorMessage = errorData.detail.detail || '';

                if (errorMessage.toLowerCase().includes('license not allowed')) {
                    var self = this;
                    Str.get_strings([
                        { key: 'error_license_not_allowed', component: 'local_dttutor' },
                        { key: 'error_license_not_allowed_short', component: 'local_dttutor' }
                    ]).then(function (strings) {
                        ErrorModal.showGeneralError(strings[0], strings[1]);
                        return;
                    }).catch(function () {
                        ErrorModal.showGeneralError(
                            self.strings.errorLicenseFallback.replace('{$a}', errorMessage),
                            self.strings.errorLicenseFallbackShort
                        );
                    });
                    return;
                }

                if (errorMessage.toLowerCase().includes('insufficient tokens')) {
                    var selfTokens = this;
                    Str.get_strings([
                        { key: 'error_insufficient_tokens', component: 'local_dttutor' },
                        { key: 'error_insufficient_tokens_short', component: 'local_dttutor' }
                    ]).then(function (strings) {
                        ErrorModal.showGeneralError(strings[0], strings[1]);
                        return;
                    }).catch(function () {
                        ErrorModal.showGeneralError(
                            selfTokens.strings.errorNoCreditssFallback.replace('{$a}', errorMessage),
                            selfTokens.strings.errorInsufficientTokensShort
                        );
                    });
                    return;
                }

                ErrorModal.showGeneralError(errorMessage);
            } else {
                ErrorModal.showGeneralError(this.strings.errorUnexpected);
            }
        }

        /**
         * Sanitizes a string by removing angle brackets.
         *
         * @param {string} str - String to sanitize
         * @returns {string} Sanitized string
         */
        sanitizeString(str) {
            if (typeof str !== 'string') {
                return '';
            }
            return str.replace(/[<>]/g, '');
        }

        /**
         * Check if error is related to insufficient AI credits.
         *
         * @param {Object} err - Error object
         * @returns {boolean} True if no credits error
         */
        isNoCreditsError(err) {
            if (!err || !err.message) {
                return false;
            }
            const message = err.message.toLowerCase();
            return message.includes('notenoughtokens') ||
                message.includes('insufficient ai credits') ||
                message.includes('no credits') ||
                message.includes('out of credits');
        }

        /**
         * Get friendly error message from exception.
         *
         * @param {Object} err - Error object
         * @returns {string} Friendly error message
         */
        getFriendlyErrorMessage(err) {
            if (!err) {
                return this.strings.errorUnknown;
            }

            if (err.message) {
                return err.message;
            }

            if (err.error) {
                return err.error;
            }

            return this.strings.errorAttemptLater;
        }

        /**
         * Cleanup resources on page unload.
         */
        cleanup() {
            this.closeCurrentStream();
            this.detachTextSelectionListeners();
        }

        /**
         * Destroys the chat instance and releases all resources.
         */
        destroy() {
            this.cleanup();
            this.cachedSelectionIndicator = null;
            this.cachedSelectionCount = null;
        }
    }

    return {
        init: function (root, uniqueId, courseId, cmId, userId) {
            return new TutorIAChat(root, uniqueId, courseId, cmId, userId);
        }
    };
});
