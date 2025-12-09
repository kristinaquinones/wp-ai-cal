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

    <div class="aiec-view-toggle">
        <button type="button" class="aiec-btn aiec-view-btn aiec-view-calendar aiec-btn-primary" data-view="calendar">
            <?php esc_html_e('Calendar', 'ai-editorial-calendar'); ?>
        </button>
        <button type="button" class="aiec-btn aiec-view-btn aiec-view-list" data-view="list">
            <?php esc_html_e('List', 'ai-editorial-calendar'); ?>
        </button>
    </div>

    <div class="aiec-calendar-card aiec-view-container" data-view="calendar">
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

    <div class="aiec-list-card aiec-view-container" data-view="list" style="display: none;">
        <div class="aiec-list-toolbar">
            <div class="aiec-list-filters">
                <input type="text" class="aiec-search-input" placeholder="<?php esc_attr_e('Search posts...', 'ai-editorial-calendar'); ?>">
                <select class="aiec-status-filter">
                    <option value=""><?php esc_html_e('All Statuses', 'ai-editorial-calendar'); ?></option>
                    <option value="publish"><?php esc_html_e('Published', 'ai-editorial-calendar'); ?></option>
                    <option value="draft"><?php esc_html_e('Draft', 'ai-editorial-calendar'); ?></option>
                    <option value="pending"><?php esc_html_e('Pending', 'ai-editorial-calendar'); ?></option>
                    <option value="future"><?php esc_html_e('Scheduled', 'ai-editorial-calendar'); ?></option>
                </select>
            </div>
            <div class="aiec-list-actions">
                <button type="button" class="aiec-btn aiec-btn-primary aiec-new-post-list" title="<?php esc_attr_e('Create a new post without AI suggestions', 'ai-editorial-calendar'); ?>"><?php esc_html_e('New Post', 'ai-editorial-calendar'); ?></button>
                <?php if ($has_api_key): ?>
                    <button type="button" class="aiec-btn aiec-get-suggestions-list" title="<?php esc_attr_e('Get AI suggestions for this date', 'ai-editorial-calendar'); ?>"><?php esc_html_e('Get AI Suggestions', 'ai-editorial-calendar'); ?></button>
                <?php endif; ?>
            </div>
        </div>
        <div class="aiec-list-table-wrapper">
            <table class="aiec-list-table">
                <thead>
                    <tr>
                        <th class="aiec-col-drag"></th>
                        <th class="aiec-col-date"><?php esc_html_e('Date', 'ai-editorial-calendar'); ?></th>
                        <th class="aiec-col-title"><?php esc_html_e('Title', 'ai-editorial-calendar'); ?></th>
                        <th class="aiec-col-status"><?php esc_html_e('Status', 'ai-editorial-calendar'); ?></th>
                        <th class="aiec-col-category"><?php esc_html_e('Category', 'ai-editorial-calendar'); ?></th>
                        <th class="aiec-col-actions"><?php esc_html_e('Actions', 'ai-editorial-calendar'); ?></th>
                    </tr>
                </thead>
                <tbody class="aiec-list-tbody">
                    <!-- Posts will be loaded here -->
                </tbody>
            </table>
        </div>
        <div class="aiec-list-pagination"></div>
        <div class="aiec-list-suggestions" style="display: none;">
            <h4><?php esc_html_e('AI Suggestions', 'ai-editorial-calendar'); ?></h4>
            <div class="aiec-list-suggestions-content"></div>
        </div>
    </div>

    <div id="aiec-modal" class="aiec-modal" style="display: none;">
        <div class="aiec-modal-content">
            <span class="aiec-modal-close">&times;</span>
            <h3 class="aiec-modal-title"></h3>
            <div class="aiec-modal-body">
                <div class="aiec-modal-posts"></div>
                <div class="aiec-modal-actions">
                    <a href="#" class="aiec-btn aiec-btn-primary aiec-new-post" title="<?php esc_attr_e('Create a new post without AI suggestions', 'ai-editorial-calendar'); ?>"><?php esc_html_e('New Post', 'ai-editorial-calendar'); ?></a>
                    <?php if ($has_api_key): ?>
                        <button type="button" class="aiec-btn aiec-get-suggestions" title="<?php esc_attr_e('Get AI suggestions for this date', 'ai-editorial-calendar'); ?>"><?php esc_html_e('Get AI Suggestions', 'ai-editorial-calendar'); ?></button>
                    <?php endif; ?>
                </div>
                <div class="aiec-suggestions" style="display: none;">
                    <h4><?php esc_html_e('AI Suggestions', 'ai-editorial-calendar'); ?></h4>
                    <div class="aiec-suggestions-content"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="aiec-confirm-modal" class="aiec-modal" style="display: none;">
        <div class="aiec-modal-content aiec-confirm-modal-content">
            <h3 class="aiec-confirm-title"><?php esc_html_e('Confirm Action', 'ai-editorial-calendar'); ?></h3>
            <div class="aiec-confirm-body">
                <p class="aiec-confirm-message"></p>
            </div>
            <div class="aiec-confirm-actions">
                <button type="button" class="aiec-btn aiec-btn-secondary aiec-confirm-cancel"><?php esc_html_e('Cancel', 'ai-editorial-calendar'); ?></button>
                <button type="button" class="aiec-btn aiec-btn-primary aiec-confirm-ok"><?php esc_html_e('Confirm', 'ai-editorial-calendar'); ?></button>
            </div>
        </div>
    </div>

    <div class="aiec-attribution">
        <p>
            <?php
            printf(
                esc_html__('AI Editorial Calendar by %s', 'ai-editorial-calendar'),
                '<a href="https://github.com/kristinaquiones/wp-ai-cal" target="_blank" rel="noopener noreferrer">KQ</a>'
            );
            ?>
        </p>
    </div>
</div>
