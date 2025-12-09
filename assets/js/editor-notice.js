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

        // Find the editor header area
        const editorHeader = document.querySelector('.edit-post-header');
        if (!editorHeader) {
            // Try again after a short delay if header not found
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
        notice.style.cssText = 'border-left-color: #0066ff; padding: 12px; margin: 0 0 15px 0; display: flex; align-items: center; gap: 8px;';
        
        notice.innerHTML = `
            <span class="dashicons dashicons-calendar-alt" style="color: #0066ff; font-size: 20px; width: 20px; height: 20px; flex-shrink: 0;"></span>
            <strong style="flex: 1;">${aiecEditorNotice.strings.quickAccess}</strong>
            <a href="${aiecEditorNotice.calendarUrl}" class="button button-primary" style="margin-left: 12px; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px; line-height: 1.5;"></span>
                ${aiecEditorNotice.strings.returnToCalendar}
            </a>
            <button type="button" class="notice-dismiss" style="position: absolute; top: 0; right: 1px; border: none; margin: 0; padding: 9px; background: none; cursor: pointer; color: #787c82;">
                <span class="screen-reader-text">Dismiss this notice.</span>
                <span class="dashicons dashicons-dismiss" style="font-size: 16px; width: 16px; height: 16px;"></span>
            </button>
        `;

        // Insert notice at the top of the editor header
        const headerToolbar = editorHeader.querySelector('.edit-post-header__toolbar');
        if (headerToolbar && headerToolbar.parentNode) {
            // Insert before the toolbar
            headerToolbar.parentNode.insertBefore(notice, headerToolbar);
        } else {
            // Fallback: insert at the beginning of header
            editorHeader.insertBefore(notice, editorHeader.firstChild);
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

