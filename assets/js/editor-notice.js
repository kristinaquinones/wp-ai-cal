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
        // Prevent duplicates
        if (document.querySelector('.aiec-calendar-toolbar-button')) {
            return true;
        }

        // Common header containers in WP 6.9
        const candidates = [
            '.edit-post-header__settings',
            '.edit-post-header__toolbar',
            '.interface-interface-skeleton__header .edit-post-header__settings',
            '.interface-interface-skeleton__header .edit-post-header__toolbar'
        ];

        const host = candidates.map(sel => document.querySelector(sel)).find(Boolean);
        if (!host) {
            return false;
        }

        const calendarButton = document.createElement('a');
        calendarButton.href = aiecEditorNotice.calendarUrl;
        calendarButton.className = 'aiec-calendar-toolbar-button components-button has-icon';
        calendarButton.setAttribute('aria-label', aiecEditorNotice.strings.returnToCalendar);
        calendarButton.setAttribute('title', aiecEditorNotice.strings.returnToCalendar);
        calendarButton.innerHTML = '<span class="dashicons dashicons-calendar-alt"></span>';

        // Try to place before Preview or Schedule buttons
        const previewButton = host.querySelector(
            '[aria-label*="Preview"], [aria-label*="preview"], .edit-post-header-preview__button-external, .edit-post-header-preview__button-toggle'
        );
        const scheduleButton = host.querySelector('[aria-label*="Schedule"], [aria-label*="Publish"], .editor-post-publish-button__button');

        if (previewButton && previewButton.parentNode) {
            previewButton.parentNode.insertBefore(calendarButton, previewButton);
            return true;
        }
        if (scheduleButton && scheduleButton.parentNode) {
            scheduleButton.parentNode.insertBefore(calendarButton, scheduleButton);
            return true;
        }

        // Fallback: append to host
        host.appendChild(calendarButton);
        return true;
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

