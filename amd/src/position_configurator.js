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
 * Position configurator for Tutor-IA avatar placement settings
 *
 * @module     local_dttutor/position_configurator
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/str'], function(Str) {

    // Language strings (loaded asynchronously)
    var strings = {
        positionCorner: 'Position: {$a->preset} corner | Drawer: {$a->drawer}',
        positionCustom: 'Position: X: {$a->x}, Y: {$a->y} | Drawer: {$a->drawer}'
    };

    /**
     * Load language strings
     * @returns {Promise}
     */
    var loadStrings = function() {
        return Str.get_strings([
            {key: 'positiondisplay_corner', component: 'local_dttutor'},
            {key: 'positiondisplay_custom', component: 'local_dttutor'},
            {key: 'position_overlap_warning', component: 'local_dttutor'}
        ]).then(function(strs) {
            strings.positionCorner = strs[0];
            strings.positionCustom = strs[1];
            strings.overlapWarning = strs[2];
            return;
        }).catch(function() {
            // Keep fallback values
        });
    };

    /**
     * Initialize the position configurator
     */
    var init = function() {
        // Load language strings first
        loadStrings();

        const presetRadios = document.querySelectorAll('input[name="position_preset"]');
        const customCoordsDiv = document.getElementById('custom-coords');
        const xInput = document.getElementById('position_x');
        const yInput = document.getElementById('position_y');
        const xRefRadios = document.querySelectorAll('input[name="ref_x"]');
        const yRefRadios = document.querySelectorAll('input[name="ref_y"]');
        const drawerSideSelect = document.getElementById('drawer_side');
        const previewAvatar = document.getElementById('preview-avatar');
        const previewContainer = document.querySelector('.position-preview');
        const coordsDisplay = document.getElementById('coords-display');
        const hiddenInput = document.getElementById('id_s_local_dttutor_avatar_position_data');

        if (!previewAvatar || !previewContainer) {
            return;
        }

        let isDragging = false;
        let startX = 0;
        let startY = 0;
        let initialLeft = 0;
        let initialTop = 0;

        /**
         * Convert pixels to rem units
         * @param {number} pixels - Pixel value to convert
         * @returns {string} Value in rem units
         */
        function pixelsToRem(pixels) {
            const fontSize = parseFloat(getComputedStyle(document.documentElement).fontSize);
            return (pixels / fontSize).toFixed(2) + 'rem';
        }

        /**
         * Convert CSS value to pixels
         * @param {string} value - CSS value to convert
         * @param {number} containerSize - Container size for relative units
         * @returns {number} Value in pixels
         */
        function cssValueToPixels(value, containerSize) {
            if (value.endsWith('px')) {
                return parseFloat(value);
            } else if (value.endsWith('rem')) {
                const fontSize = parseFloat(getComputedStyle(document.documentElement).fontSize);
                return parseFloat(value) * fontSize;
            } else if (value.endsWith('em')) {
                const fontSize = parseFloat(getComputedStyle(previewAvatar).fontSize);
                return parseFloat(value) * fontSize;
            } else if (value.endsWith('%')) {
                return (parseFloat(value) / 100) * containerSize;
            } else if (value.endsWith('vh')) {
                return (parseFloat(value) / 100) * window.innerHeight;
            } else if (value.endsWith('vw')) {
                return (parseFloat(value) / 100) * window.innerWidth;
            }
            return 0;
        }

        /**
         * Update preview position based on current settings
         */
        function updatePreview() {
            const preset = document.querySelector('input[name="position_preset"]:checked').value;
            const drawerSide = drawerSideSelect.value;
            const xRef = document.querySelector('input[name="ref_x"]:checked')?.value || 'left';
            const yRef = document.querySelector('input[name="ref_y"]:checked')?.value || 'bottom';
            let x = xInput.value;
            let y = yInput.value;

            if (preset === 'custom') {
                customCoordsDiv.classList.add('active');
            } else {
                customCoordsDiv.classList.remove('active');
                if (preset === 'right') {
                    x = '2rem';
                    y = '6rem';
                } else if (preset === 'left') {
                    x = '2rem';
                    y = '6rem';
                }
            }

            if (preset === 'right') {
                previewAvatar.style.right = x;
                previewAvatar.style.left = 'auto';
                previewAvatar.style.bottom = y;
                previewAvatar.style.top = 'auto';
            } else if (preset === 'left') {
                previewAvatar.style.left = x;
                previewAvatar.style.right = 'auto';
                previewAvatar.style.bottom = y;
                previewAvatar.style.top = 'auto';
            } else {
                const rect = previewContainer.getBoundingClientRect();
                const avatarWidth = previewAvatar.offsetWidth;
                const avatarHeight = previewAvatar.offsetHeight;

                const xSide = x.startsWith('-') ? 'right' : 'left';
                const ySide = y.startsWith('-') ? 'top' : 'bottom';

                const xValue = x.replace('-', '');
                const yValue = y.replace('-', '');

                const xPx = cssValueToPixels(xValue, rect.width);
                const yPx = cssValueToPixels(yValue, rect.height);

                let leftPx, topPx;

                if (xSide === 'left') {
                    leftPx = xPx;
                } else {
                    leftPx = rect.width - xPx - avatarWidth;
                }

                if (ySide === 'bottom') {
                    topPx = rect.height - yPx - avatarHeight;
                } else {
                    topPx = yPx;
                }

                previewAvatar.style.left = leftPx + 'px';
                previewAvatar.style.top = topPx + 'px';
                previewAvatar.style.right = 'auto';
                previewAvatar.style.bottom = 'auto';
            }

            // Use language strings for position display
            var displayText;
            if (preset === 'custom') {
                displayText = strings.positionCustom
                    .replace('{$a->x}', x + ' (from ' + xRef + ')')
                    .replace('{$a->y}', y + ' (from ' + yRef + ')')
                    .replace('{$a->drawer}', drawerSide);
            } else {
                displayText = strings.positionCorner
                    .replace('{$a->preset}', preset)
                    .replace('{$a->drawer}', drawerSide);
            }
            coordsDisplay.textContent = displayText;
            warnAboutOverlap();

            const data = {
                preset: preset,
                x: x,
                y: y,
                drawerside: drawerSide,
                xref: xRef,
                yref: yRef
            };
            hiddenInput.value = JSON.stringify(data);

            document.querySelectorAll('.preset-option').forEach(opt => opt.classList.remove('selected'));
            const selectedOption = document.querySelector(`.preset-option[data-preset="${preset}"]`);
            if (selectedOption) {
                selectedOption.classList.add('selected');
            }
        }

        /**
         * Handle mouse move during drag
         * @param {MouseEvent} e - Mouse event
         */
        function handleMouseMove(e) {
            if (!isDragging) {
                return;
            }

            e.preventDefault();

            const point = e.touches && e.touches.length ? e.touches[0] : e;
            const rect = previewContainer.getBoundingClientRect();
            const deltaX = point.clientX - startX;
            const deltaY = point.clientY - startY;

            let newLeft = initialLeft + deltaX;
            let newTop = initialTop + deltaY;

            const avatarWidth = previewAvatar.offsetWidth;
            const avatarHeight = previewAvatar.offsetHeight;
            newLeft = Math.max(0, Math.min(newLeft, rect.width - avatarWidth));
            newTop = Math.max(0, Math.min(newTop, rect.height - avatarHeight));

            const fromRight = rect.width - newLeft - avatarWidth;
            const fromBottom = rect.height - newTop - avatarHeight;

            const xRef = document.querySelector('input[name="ref_x"]:checked')?.value || 'left';
            const yRef = document.querySelector('input[name="ref_y"]:checked')?.value || 'bottom';

            if (xRef === 'left') {
                xInput.value = pixelsToRem(newLeft);
            } else {
                xInput.value = '-' + pixelsToRem(fromRight);
            }

            if (yRef === 'bottom') {
                yInput.value = pixelsToRem(fromBottom);
            } else {
                yInput.value = '-' + pixelsToRem(newTop);
            }

            previewAvatar.style.left = newLeft + 'px';
            previewAvatar.style.top = newTop + 'px';
            previewAvatar.style.right = 'auto';
            previewAvatar.style.bottom = 'auto';

            // Use language strings for position display during drag
            var dragDisplayText = strings.positionCustom
                .replace('{$a->x}', xInput.value + ' (from ' + xRef + ')')
                .replace('{$a->y}', yInput.value + ' (from ' + yRef + ')')
                .replace('{$a->drawer}', drawerSideSelect.value);
            coordsDisplay.textContent = dragDisplayText;
        }

        /**
         * Warn when the button would sit where the platform keeps its own floating controls.
         *
         * Moodle puts the messaging drawer toggle and the footer buttons in the bottom right
         * corner, so a button placed there ends up on top of them, or under them.
         */
        function warnAboutOverlap() {
            const warning = document.getElementById('position-warning');
            if (!warning) {
                return;
            }

            const rect = previewContainer.getBoundingClientRect();
            const avatarRect = previewAvatar.getBoundingClientRect();

            // The reserved corner, in the same proportion the platform uses on a real page.
            const reserved = Math.min(rect.width, rect.height) * 0.25;
            const fromRight = rect.right - avatarRect.right;
            const fromBottom = rect.bottom - avatarRect.bottom;
            const overlaps = fromRight < reserved && fromBottom < reserved;

            warning.textContent = overlaps ? (strings.overlapWarning || '') : '';
            warning.style.display = overlaps ? 'block' : 'none';
        }

        /**
         * Handle mouse up to end drag
         */
        function handleMouseUp() {
            if (isDragging) {
                isDragging = false;
                previewAvatar.classList.remove('dragging');
                document.removeEventListener('mousemove', handleMouseMove);
                document.removeEventListener('mouseup', handleMouseUp);
                document.removeEventListener('touchmove', handleMouseMove);
                document.removeEventListener('touchend', handleMouseUp);
                updatePreview();
            }
        }

        /**
         * Start dragging the avatar, whatever was used to grab it.
         *
         * @param {Event} e The event that grabbed the avatar.
         */
        function startDragging(e) {
            const point = e.touches && e.touches.length ? e.touches[0] : e;
            e.preventDefault();
            isDragging = true;
            previewAvatar.classList.add('dragging');

            const rect = previewContainer.getBoundingClientRect();
            const avatarRect = previewAvatar.getBoundingClientRect();

            startX = point.clientX;
            startY = point.clientY;

            initialLeft = avatarRect.left - rect.left;
            initialTop = avatarRect.top - rect.top;

            switchToCustom();

            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
            document.addEventListener('touchmove', handleMouseMove, {passive: false});
            document.addEventListener('touchend', handleMouseUp);
        }

        /**
         * Put the configurator into custom position, which is where coordinates can be set.
         */
        function switchToCustom() {
            const customRadio = document.querySelector('input[name="position_preset"][value="custom"]');
            if (customRadio) {
                customRadio.checked = true;
                customCoordsDiv.classList.add('active');
            }
        }

        /**
         * Move the avatar with the arrow keys, for whoever does not use a pointer.
         *
         * @param {KeyboardEvent} e
         */
        function handleKeyDown(e) {
            const steps = {ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1]};
            const step = steps[e.key];
            if (!step) {
                return;
            }

            e.preventDefault();
            switchToCustom();

            const rect = previewContainer.getBoundingClientRect();
            const avatarRect = previewAvatar.getBoundingClientRect();
            const distance = e.shiftKey ? 10 : 1;

            const left = avatarRect.left - rect.left + (step[0] * distance);
            const top = avatarRect.top - rect.top + (step[1] * distance);

            moveAvatarTo(left, top);
        }

        /**
         * Place the avatar at a point of the preview and write the coordinates that describe it.
         *
         * @param {number} left Distance from the left edge of the preview, in pixels.
         * @param {number} top Distance from the top edge of the preview, in pixels.
         */
        function moveAvatarTo(left, top) {
            const rect = previewContainer.getBoundingClientRect();
            const avatarWidth = previewAvatar.offsetWidth;
            const avatarHeight = previewAvatar.offsetHeight;

            const newLeft = Math.max(0, Math.min(left, rect.width - avatarWidth));
            const newTop = Math.max(0, Math.min(top, rect.height - avatarHeight));

            const xRef = document.querySelector('input[name="ref_x"]:checked')?.value || 'left';
            const yRef = document.querySelector('input[name="ref_y"]:checked')?.value || 'bottom';

            xInput.value = xRef === 'left'
                ? pixelsToRem(newLeft)
                : '-' + pixelsToRem(rect.width - newLeft - avatarWidth);
            yInput.value = yRef === 'bottom'
                ? pixelsToRem(rect.height - newTop - avatarHeight)
                : '-' + pixelsToRem(newTop);

            updatePreview();
        }

        previewAvatar.addEventListener('mousedown', startDragging);
        previewAvatar.addEventListener('touchstart', startDragging, {passive: false});
        previewAvatar.addEventListener('keydown', handleKeyDown);
        previewAvatar.setAttribute('tabindex', '0');
        previewAvatar.setAttribute('role', 'application');

        presetRadios.forEach(radio => {
            radio.addEventListener('change', updatePreview);
        });

        xInput.addEventListener('input', updatePreview);
        yInput.addEventListener('input', updatePreview);
        drawerSideSelect.addEventListener('change', updatePreview);

        xRefRadios.forEach(radio => {
            radio.addEventListener('change', updatePreview);
        });

        yRefRadios.forEach(radio => {
            radio.addEventListener('change', updatePreview);
        });

        document.querySelectorAll('.preset-option').forEach(opt => {
            opt.addEventListener('click', function() {
                const preset = this.dataset.preset;
                document.querySelector(`input[name="position_preset"][value="${preset}"]`).checked = true;
                updatePreview();
            });
        });

        updatePreview();
    };

    return {
        init: init
    };
});
