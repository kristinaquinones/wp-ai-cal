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

    // Wait for Gutenberg to fully initialize
    function initNotice() {
        // Find the editor header toolbar where preview buttons are
        const headerToolbar = document.querySelector('.edit-post-header__toolbar');
        const headerSettings = document.querySelector('.edit-post-header__settings');
        
        if (!headerToolbar && !headerSettings) {
            // Try again after a short delay if elements not found
            setTimeout(initNotice, 500);
            return;
        }

        // Check if calendar button already exists (prevent duplicates)
        if (document.querySelector('.aiec-calendar-toolbar-button')) {
            return;
        }

        // Create icon-only button for toolbar
        const calendarButton = document.createElement('a');
        calendarButton.href = aiecEditorNotice.calendarUrl;
        calendarButton.className = 'aiec-calendar-toolbar-button components-button';
        calendarButton.setAttribute('aria-label', aiecEditorNotice.strings.returnToCalendar);
        calendarButton.setAttribute('title', aiecEditorNotice.strings.returnToCalendar);
        
        calendarButton.innerHTML = `
            <span class="dashicons dashicons-calendar-alt"></span>
        `;

        // Insert into header settings area (where preview buttons are)
        // Try to insert before the preview button or at the end of settings
        if (headerSettings) {
            const previewButton = headerSettings.querySelector('[aria-label*="Preview"], [aria-label*="preview"], .edit-post-header-preview__button-external, .edit-post-header-preview__button-toggle');
            if (previewButton && previewButton.parentNode) {
                // Insert before preview button
                previewButton.parentNode.insertBefore(calendarButton, previewButton);
            } else {
                // Insert at the beginning of settings
                headerSettings.insertBefore(calendarButton, headerSettings.firstChild);
            }
        } else if (headerToolbar) {
            // Fallback: insert at the end of toolbar
            headerToolbar.appendChild(calendarButton);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for Gutenberg to fully initialize
            setTimeout(initNotice, 1000);
        });
    } else {
        // DOM already loaded
        setTimeout(initNotice, 1000);
    }

    // Also try when Gutenberg's editor is ready (if available)
    if (typeof wp !== 'undefined' && typeof wp.data !== 'undefined') {
        wp.domReady(function() {
            setTimeout(initNotice, 500);
        });
    }
})();

