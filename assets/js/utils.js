(function (window) {
    'use strict';

    // Single source of truth for output escaping in the plugin's admin scripts,
    // so the same helper isn't copy-pasted across calendar.js and meta-box.js.
    window.aiecUtils = {
        // Escape text for safe insertion as element text/HTML content.
        escapeHtml: function (text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : text;
            return div.innerHTML;
        },

        // Escape a value for safe insertion into a double-quoted attribute.
        // escapeHtml leaves quotes intact, which is unsafe in attribute context.
        escapeAttr: function (text) {
            return String(text == null ? '' : text)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }
    };
})(window);
