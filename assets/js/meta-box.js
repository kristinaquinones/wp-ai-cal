(function($) {
    'use strict';

    $(document).ready(function() {
        const $generateBtn = $('#aiec-generate-outline');
        const $spinner = $('#aiec-outline-spinner');
        const $message = $('#aiec-outline-message');

        if (!$generateBtn.length) {
            return;
        }

        $generateBtn.on('click', function(e) {
            e.preventDefault();

            const postId = $(this).data('post-id');
            const $btn = $(this);

            // Check if post already has content and show confirmation
            if (aiecMetaBox.hasContent) {
                if (!confirm(aiecMetaBox.strings.confirmRegenerate)) {
                    return;
                }
            }

            // Disable button and show spinner
            $btn.prop('disabled', true);
            $spinner.css('visibility', 'visible');
            $message.hide().removeClass('notice-success notice-error');

            $.ajax({
                url: aiecMetaBox.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aiec_generate_outline',
                    nonce: aiecMetaBox.nonce,
                    post_id: postId
                },
                success: function(response) {
                    $spinner.css('visibility', 'hidden');
                    $btn.prop('disabled', false);

                    if (response.success) {
                        const outline = response.data.outline;
                        
                        // Try to update the editor UI (content is already saved server-side)
                        // Gutenberg editor - convert markdown-style headings to HTML blocks
                        if (window.wp && window.wp.data && window.wp.blocks) {
                            try {
                                // Convert markdown to HTML for block parsing
                                let htmlContent = outline
                                    .split('\n')
                                    .map(line => {
                                        line = line.trim();
                                        if (!line) return '';
                                        // Markdown headings
                                        if (line.match(/^##\s+(.+)$/)) {
                                            return '<h2>' + line.replace(/^##\s+/, '') + '</h2>';
                                        }
                                        if (line.match(/^###\s+(.+)$/)) {
                                            return '<h3>' + line.replace(/^###\s+/, '') + '</h3>';
                                        }
                                        // Bullet points
                                        if (line.match(/^-\s+(.+)$/)) {
                                            return '<li>' + line.replace(/^-\s+/, '') + '</li>';
                                        }
                                        // Regular text
                                        return '<p>' + line + '</p>';
                                    })
                                    .filter(line => line)
                                    .join('\n');
                                
                                // Wrap consecutive <li> tags in <ul>
                                htmlContent = htmlContent.replace(/(<li>.*?<\/li>\n?)+/g, function(match) {
                                    return '<ul>' + match.replace(/\n/g, '') + '</ul>';
                                });
                                
                                const blocks = window.wp.blocks.rawHandler({
                                    HTML: htmlContent
                                });
                                if (blocks && blocks.length > 0) {
                                    window.wp.data.dispatch('core/block-editor').resetBlocks(blocks);
                                }
                            } catch (e) {
                                // Gutenberg update failed - content is saved, just need refresh
                                console.log('Gutenberg update skipped - content saved server-side');
                            }
                        }
                        // Classic editor with TinyMCE
                        else if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                            tinyMCE.get('content').setContent(outline);
                            $('#content').val(outline).trigger('change');
                        }
                        // Classic editor textarea
                        else if ($('#content').length) {
                            $('#content').val(outline).trigger('change');
                        }

                        // Show success message
                        $message
                            .addClass('notice notice-success')
                            .html('<p><strong>' + aiecMetaBox.strings.success + '</strong></p>' +
                                  '<p style="margin: 8px 0 0; font-size: 12px;">' +
                                  'The outline has been added to your post content. ' +
                                  'If you don\'t see it, please refresh the page.</p>')
                            .show();

                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $message.offset().top - 100
                        }, 300);
                    } else {
                        // Show error message
                        $message
                            .addClass('notice notice-error')
                            .html('<p><strong>' + aiecMetaBox.strings.error + '</strong><br>' + (response.data || '') + '</p>')
                            .show();
                    }
                },
                error: function(xhr, status, error) {
                    $spinner.css('visibility', 'hidden');
                    $btn.prop('disabled', false);
                    
                    $message
                        .addClass('notice notice-error')
                        .html('<p><strong>' + aiecMetaBox.strings.error + '</strong><br>' + error + '</p>')
                        .show();
                }
            });
        });
    });

})(jQuery);

