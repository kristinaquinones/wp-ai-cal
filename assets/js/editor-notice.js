(function() {
    'use strict';

    // Wait for DOM and Gutenberg to be ready
    if (typeof wp === 'undefined' || typeof wp.data === 'undefined') {
        // Gutenberg not available, fall back to PHP notices
        return;
    }

    // Check if we're in the block editor
    const isBlockEditor = document.body.classList.contains('block-editor-page');
    if (!isBlockEditor) {
        return;
    }

    function insertCalendarButton() {
        // Look for modern Gutenberg header areas
        const headerSettings = document.querySelector('.edit-post-header__settings');
        const headerToolbar = document.querySelector('.edit-post-header__toolbar');

        if (!headerSettings && !headerToolbar) {
            return false;
        }

        // Prevent duplicates
        if (document.querySelector('.aiec-calendar-toolbar-button')) {
            return true;
        }

        const calendarButton = document.createElement('a');
        calendarButton.href = aiecEditorNotice.calendarUrl;
        calendarButton.className = 'aiec-calendar-toolbar-button components-button has-icon';
        calendarButton.setAttribute('aria-label', aiecEditorNotice.strings.returnToCalendar);
        calendarButton.setAttribute('title', aiecEditorNotice.strings.returnToCalendar);
        calendarButton.innerHTML = '<span class="dashicons dashicons-calendar-alt"></span>';

        // Try to insert before the Preview button; otherwise append
        if (headerSettings) {
            const previewButton = headerSettings.querySelector(
                '[aria-label*="Preview"], [aria-label*="preview"], .edit-post-header-preview__button-external, .edit-post-header-preview__button-toggle'
            );
            if (previewButton && previewButton.parentNode) {
                previewButton.parentNode.insertBefore(calendarButton, previewButton);
                return true;
            }
            headerSettings.insertBefore(calendarButton, headerSettings.firstChild);
            return true;
        }

        if (headerToolbar) {
            headerToolbar.appendChild(calendarButton);
            return true;
        }

        return false;
    }

    // Wait for Gutenberg to fully initialize
    function initNotice() {
        if (insertCalendarButton()) {
            return;
        }

        // Retry a few times in case the toolbar renders late
        let attempts = 0;
        const maxAttempts = 10;
        const interval = setInterval(() => {
            attempts += 1;
            if (insertCalendarButton() || attempts >= maxAttempts) {
                clearInterval(interval);
            }
        }, 500);

        // MutationObserver as a fallback for dynamic render
        const observer = new MutationObserver(() => {
            if (insertCalendarButton()) {
                observer.disconnect();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNotice);
    } else {
        initNotice();
    }

    // Also try when Gutenberg's editor is ready (if available)
    if (typeof wp !== 'undefined' && typeof wp.data !== 'undefined') {
        wp.domReady(function() {
            initNotice();
        });
    }
})();

