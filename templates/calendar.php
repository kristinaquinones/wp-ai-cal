<?php
if (!defined('ABSPATH')) {
    exit;
}

$has_api_key = !empty(AI_Editorial_Calendar::get_instance()->get_api_key());
?>
<div class="wrap aiec-wrap">
    <div class="aiec-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p class="aiec-subtitle"><?php esc_html_e('Plan your content with AI-powered suggestions', 'ai-editorial-calendar'); ?></p>
    </div>

    <?php if (!$has_api_key): ?>
        <div class="notice notice-warning">
            <p>
                <?php
                printf(
                    esc_html__('Configure your AI API key in %sSettings%s to enable content suggestions.', 'ai-editorial-calendar'),
                    '<a href="' . esc_url(admin_url('admin.php?page=aiec-settings')) . '">',
                    '</a>'
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="aiec-calendar-card">
        <div class="aiec-calendar-toolbar">
            <div class="aiec-month-title"></div>
            <div class="aiec-nav-buttons">
                <button type="button" class="aiec-btn aiec-nav-prev"><?php esc_html_e('← Prev', 'ai-editorial-calendar'); ?></button>
                <button type="button" class="aiec-btn aiec-btn-primary aiec-nav-today"><?php esc_html_e('Today', 'ai-editorial-calendar'); ?></button>
                <button type="button" class="aiec-btn aiec-nav-next"><?php esc_html_e('Next →', 'ai-editorial-calendar'); ?></button>
            </div>
        </div>

        <div class="aiec-calendar-grid">
            <div class="aiec-weekdays">
                <div class="aiec-weekday"><?php esc_html_e('Sun', 'ai-editorial-calendar'); ?></div>
                <div class="aiec-weekday"><?php esc_html_e('Mon', 'ai-editorial-calendar'); ?></div>
                <div class="aiec-weekday"><?php esc_html_e('Tue', 'ai-editorial-calendar'); ?></div>
                <div class="aiec-weekday"><?php esc_html_e('Wed', 'ai-editorial-calendar'); ?></div>
                <div class="aiec-weekday"><?php esc_html_e('Thu', 'ai-editorial-calendar'); ?></div>
                <div class="aiec-weekday"><?php esc_html_e('Fri', 'ai-editorial-calendar'); ?></div>
                <div class="aiec-weekday"><?php esc_html_e('Sat', 'ai-editorial-calendar'); ?></div>
            </div>
            <div class="aiec-days"></div>
        </div>

        <div class="aiec-legend">
            <div class="aiec-legend-item">
                <div class="aiec-legend-dot aiec-legend-publish"></div>
                <span><?php esc_html_e('Published', 'ai-editorial-calendar'); ?></span>
            </div>
            <div class="aiec-legend-item">
                <div class="aiec-legend-dot aiec-legend-draft"></div>
                <span><?php esc_html_e('Draft', 'ai-editorial-calendar'); ?></span>
            </div>
            <div class="aiec-legend-item">
                <div class="aiec-legend-dot aiec-legend-pending"></div>
                <span><?php esc_html_e('Pending', 'ai-editorial-calendar'); ?></span>
            </div>
            <div class="aiec-legend-item">
                <div class="aiec-legend-dot aiec-legend-future"></div>
                <span><?php esc_html_e('Scheduled', 'ai-editorial-calendar'); ?></span>
            </div>
        </div>
    </div>

    <div id="aiec-modal" class="aiec-modal" style="display: none;">
        <div class="aiec-modal-content">
            <span class="aiec-modal-close">&times;</span>
            <h3 class="aiec-modal-title"></h3>
            <div class="aiec-modal-body">
                <div class="aiec-modal-posts"></div>
                <div class="aiec-modal-actions">
                    <a href="#" class="aiec-btn aiec-btn-primary aiec-new-post"><?php esc_html_e('New Post', 'ai-editorial-calendar'); ?></a>
                    <?php if ($has_api_key): ?>
                        <button type="button" class="aiec-btn aiec-get-suggestions"><?php esc_html_e('Get AI Suggestions', 'ai-editorial-calendar'); ?></button>
                    <?php endif; ?>
                </div>
                <div class="aiec-suggestions" style="display: none;">
                    <h4><?php esc_html_e('AI Suggestions', 'ai-editorial-calendar'); ?></h4>
                    <div class="aiec-suggestions-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>
