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
        // Check if notice should be shown (not dismissed)
        if (aiecEditorNotice.dismissed === '1') {
            return;
        }

        // Find a better insertion point - look for the editor layout container
        const editorLayout = document.querySelector('.edit-post-layout');
        const editorHeader = document.querySelector('.edit-post-header');
        
        if (!editorLayout && !editorHeader) {
            // Try again after a short delay if elements not found
            setTimeout(initNotice, 500);
            return;
        }

        // Check if notice already exists (prevent duplicates)
        if (document.querySelector('.aiec-gutenberg-notice')) {
            return;
        }

        // Create notice element
        const notice = document.createElement('div');
        notice.className = 'aiec-gutenberg-notice notice notice-info is-dismissible';
        notice.setAttribute('data-notice-id', aiecEditorNotice.noticeId);
        
        notice.innerHTML = `
            <span class="dashicons dashicons-calendar-alt"></span>
            <strong>${aiecEditorNotice.strings.quickAccess}</strong>
            <a href="${aiecEditorNotice.calendarUrl}" class="button button-primary">
                <span class="dashicons dashicons-calendar-alt"></span>
                ${aiecEditorNotice.strings.returnToCalendar}
            </a>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">Dismiss this notice.</span>
                <span class="dashicons dashicons-dismiss"></span>
            </button>
        `;

        // Insert notice above the editor header, not inside it
        // This prevents overlap with Gutenberg's native elements
        if (editorHeader && editorHeader.parentNode) {
            // Insert before the header element itself
            editorHeader.parentNode.insertBefore(notice, editorHeader);
        } else if (editorLayout) {
            // Fallback: insert at the beginning of the layout
            editorLayout.insertBefore(notice, editorLayout.firstChild);
        } else {
            // Last resort: insert at the beginning of body
            document.body.insertBefore(notice, document.body.firstChild);
        }

        // Handle dismissal
        const dismissButton = notice.querySelector('.notice-dismiss');
        if (dismissButton) {
            dismissButton.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Fade out and remove
                notice.style.transition = 'opacity 0.3s';
                notice.style.opacity = '0';
                
                setTimeout(function() {
                    notice.remove();
                }, 300);

                // Send AJAX request to dismiss
                if (typeof jQuery !== 'undefined') {
                    jQuery.post(aiecEditorNotice.ajaxUrl, {
                        action: 'aiec_dismiss_notice',
                        notice_id: aiecEditorNotice.noticeId,
                        nonce: aiecEditorNotice.nonce
                    });
                } else {
                    // Fallback to fetch API
                    fetch(aiecEditorNotice.ajaxUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'aiec_dismiss_notice',
                            notice_id: aiecEditorNotice.noticeId,
                            nonce: aiecEditorNotice.nonce
                        })
                    });
                }
            });
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

