<?php
/**
 * Plugin Name: AI Editorial Calendar
 * Plugin URI: https://github.com/kristinaquiones/wp-ai-cal
 * Description: A lightweight editorial calendar with personalized AI content suggestions. Connect your own AI account (OpenAI, Anthropic, Google, or xAI Grok).
 * Version: 1.1.0
 * Author: Kristina Quinones
 * License: GPL v2 or later
 * Text Domain: ai-editorial-calendar
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AIEC_VERSION', '1.1.0');
define('AIEC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIEC_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once AIEC_PLUGIN_DIR . 'includes/class-aiec-settings.php';
require_once AIEC_PLUGIN_DIR . 'includes/class-aiec-ai-client.php';
require_once AIEC_PLUGIN_DIR . 'includes/class-aiec-prompt-builder.php';

/**
 * Main plugin class: WordPress integration layer.
 *
 * Wires hooks, renders admin UI, and handles AJAX. Delegates settings/access
 * policy to AIEC_Settings, provider calls to AIEC_AI_Client, and prompt text to
 * AIEC_Prompt_Builder.
 */
class AI_Editorial_Calendar {

    private static $instance = null;

    // Upper bound on posts loaded into the calendar view at once (see audit S5).
    const CALENDAR_MAX_POSTS = 500;

    /** @var AIEC_AI_Client */
    private $ai_client;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->ai_client = new AIEC_AI_Client();

        add_action('init', [$this, 'load_textdomain']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', ['AIEC_Settings', 'register']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_aiec_get_posts', [$this, 'ajax_get_posts']);
        add_action('wp_ajax_aiec_get_all_posts', [$this, 'ajax_get_all_posts']);
        add_action('wp_ajax_aiec_get_suggestions', [$this, 'ajax_get_suggestions']);
        add_action('wp_ajax_aiec_update_post_date', [$this, 'ajax_update_post_date']);
        add_action('admin_post_aiec_uninstall', [$this, 'handle_uninstall']);
        add_action('wp_ajax_aiec_create_draft', [$this, 'ajax_create_draft']);
        add_action('wp_ajax_aiec_generate_outline', [$this, 'ajax_generate_outline']);
        add_action('wp_ajax_aiec_trash_post', [$this, 'ajax_trash_post']);
        add_action('wp_ajax_aiec_check_model_health', [$this, 'ajax_check_model_health']);
        add_action('wp_ajax_aiec_dismiss_notice', [$this, 'ajax_dismiss_notice']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_action_links']);
        add_action('admin_bar_menu', [$this, 'add_admin_bar_link'], 100);
        add_action('post_submitbox_misc_actions', [$this, 'add_editor_return_link']);
        add_filter('get_sample_permalink_html', [$this, 'add_view_post_return_link'], 10, 5);
        add_action('add_meta_boxes', [$this, 'add_ai_suggestion_meta_box']);
        add_action('admin_notices', [$this, 'add_editor_return_notice']);
        add_action('edit_form_top', [$this, 'add_editor_return_notice_edit_form']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_assets']);
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
    }

    /**
     * Load translations. The text domain still differs from the install slug;
     * that rename is a separate change tracked for any WordPress.org submission.
     */
    public function load_textdomain() {
        load_plugin_textdomain('ai-editorial-calendar', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_admin_menu() {
        add_menu_page(
            __('AI Editorial Calendar', 'ai-editorial-calendar'),
            __('AI Editorial Calendar', 'ai-editorial-calendar'),
            'edit_posts',
            'ai-editorial-calendar',
            [$this, 'render_calendar_page'],
            'dashicons-calendar-alt',
            26
        );

        add_submenu_page(
            'ai-editorial-calendar',
            __('Settings', 'ai-editorial-calendar'),
            __('Settings', 'ai-editorial-calendar'),
            'manage_options',
            'aiec-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * API key accessor. Delegates to AIEC_Settings; kept here so templates and
     * internal callers can use $plugin->get_api_key().
     *
     * @return string
     */
    public function get_api_key() {
        return AIEC_Settings::get_api_key();
    }

    /**
     * Whether the current user may trigger paid AI calls. Delegates to
     * AIEC_Settings; kept here for template/internal use.
     *
     * @return bool
     */
    public function user_can_use_ai() {
        return AIEC_Settings::user_can_use_ai();
    }

    /**
     * Get the calendar page URL
     *
     * @return string Calendar admin URL
     */
    private function get_calendar_url() {
        return admin_url('admin.php?page=ai-editorial-calendar');
    }

    /**
     * Get provider display name
     *
     * @param string $provider Provider key (openai, anthropic, google, grok)
     * @return string Provider display name
     */
    private function get_provider_name($provider) {
        $provider_names = [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic',
            'google' => 'Google',
            'grok' => 'xAI Grok'
        ];
        return $provider_names[$provider] ?? 'OpenAI';
    }

    /**
     * Get primary category for a post
     *
     * @param int $post_id Post ID
     * @return string Primary category name or empty string
     */
    private function get_primary_category($post_id) {
        $primary_category = '';
        $categories = get_the_category($post_id);

        if (!empty($categories)) {
            // Use first category as primary, or check for Yoast primary category
            $primary_category = $categories[0]->name;
            $yoast_primary = get_post_meta($post_id, '_yoast_wpseo_primary_category', true);
            if ($yoast_primary) {
                $cat = get_category($yoast_primary);
                if ($cat && !is_wp_error($cat)) {
                    $primary_category = $cat->name;
                }
            }
        }

        return $primary_category;
    }

    /**
     * Build post event data array
     *
     * @param WP_Post $post Post object
     * @return array Post event data
     */
    private function build_post_event($post) {
        return [
            'id' => $post->ID,
            'title' => $post->post_title ?: __('(no title)', 'ai-editorial-calendar'),
            'date' => $post->post_date,
            'status' => $post->post_status,
            'editUrl' => get_edit_post_link($post->ID, 'raw'),
            'type' => $post->post_type,
            'category' => $this->get_primary_category($post->ID),
        ];
    }

    private function validate_date_time($date_string) {
        if (empty($date_string)) {
            return false;
        }

        // Validate date format (YYYY-MM-DD HH:MM:SS)
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date_string)) {
            return false;
        }

        // Validate it's a real date
        $date_parts = date_parse($date_string);
        if ($date_parts['error_count'] > 0 || $date_parts['warning_count'] > 0) {
            return false;
        }

        return true;
    }

    public function enqueue_assets($hook) {
        // Shared helper script; auto-enqueued wherever it's listed as a dependency.
        wp_register_script('aiec-utils', AIEC_PLUGIN_URL . 'assets/js/utils.js', [], AIEC_VERSION, true);

        // Enqueue for calendar and settings pages
        if (strpos($hook, 'ai-editorial-calendar') !== false || strpos($hook, 'aiec-settings') !== false) {
            // Self-hosted fonts, no external CDN call from wp-admin (see audit S3)
            wp_enqueue_style('aiec-fonts', AIEC_PLUGIN_URL . 'assets/css/fonts.css', [], AIEC_VERSION);
            wp_enqueue_style('dashicons');
            wp_enqueue_style('aiec-styles', AIEC_PLUGIN_URL . 'assets/css/calendar.css', ['aiec-fonts', 'dashicons'], AIEC_VERSION);
            wp_enqueue_script('aiec-calendar', AIEC_PLUGIN_URL . 'assets/js/calendar.js', ['jquery', 'aiec-utils'], AIEC_VERSION, true);

            wp_localize_script('aiec-calendar', 'aiecData', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'adminUrl' => admin_url('admin.php'),
                'newPostUrl' => admin_url('post-new.php'),
                'nonce' => wp_create_nonce('aiec_nonce'),
                'hasApiKey' => !empty($this->get_api_key()),
                'canUseAi' => $this->user_can_use_ai(),
                'strings' => [
                    'getSuggestions' => __('Get AI Suggestions', 'ai-editorial-calendar'),
                    'loading' => __('Loading...', 'ai-editorial-calendar'),
                    'noApiKey' => __('Please configure your AI API key in Settings.', 'ai-editorial-calendar'),
                    'months' => [
                        __('January', 'ai-editorial-calendar'),
                        __('February', 'ai-editorial-calendar'),
                        __('March', 'ai-editorial-calendar'),
                        __('April', 'ai-editorial-calendar'),
                        __('May', 'ai-editorial-calendar'),
                        __('June', 'ai-editorial-calendar'),
                        __('July', 'ai-editorial-calendar'),
                        __('August', 'ai-editorial-calendar'),
                        __('September', 'ai-editorial-calendar'),
                        __('October', 'ai-editorial-calendar'),
                        __('November', 'ai-editorial-calendar'),
                        __('December', 'ai-editorial-calendar'),
                    ],
                ],
            ]);
        }

        // Enqueue for post editor pages
        if (in_array($hook, ['post.php', 'post-new.php'])) {
            global $post;

            // Only enqueue for calendar-created posts when the user may spend AI credits
            if ($post && $this->user_can_use_ai() && get_post_meta($post->ID, '_aiec_from_calendar', true)) {
                wp_enqueue_script('aiec-meta-box', AIEC_PLUGIN_URL . 'assets/js/meta-box.js', ['jquery', 'aiec-utils'], AIEC_VERSION, true);

                $provider = get_option('aiec_ai_provider', 'openai');
                $provider_name = $this->get_provider_name($provider);

                // Check if post already has content
                $has_content = !empty($post->post_content);

                wp_localize_script('aiec-meta-box', 'aiecMetaBox', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('aiec_generate_outline'),
                    'postId' => $post->ID,
                    'providerName' => $provider_name,
                    'hasContent' => $has_content,
                    'strings' => [
                        'generating' => __('Generating outline...', 'ai-editorial-calendar'),
                        'success' => __('Outline generated successfully!', 'ai-editorial-calendar'),
                        'error' => __('Error generating outline. Please try again.', 'ai-editorial-calendar'),
                        'confirmRegenerate' => sprintf(
                            /* translators: %s: AI provider name (OpenAI, Anthropic, Google, xAI Grok) */
                            __('Are you sure? You\'re using %s credits for each request. 👀', 'ai-editorial-calendar'),
                            $provider_name
                        ),
                    ]
                ]);
            }
        }
    }

    public function enqueue_block_editor_assets() {
        // Only enqueue on post editor pages
        global $pagenow;
        if (!in_array($pagenow, ['post.php', 'post-new.php'], true)) {
            return;
        }

        if (!current_user_can('edit_posts')) {
            return;
        }

        $calendar_url = $this->get_calendar_url();

        // Enqueue the editor notice script
        wp_enqueue_script(
            'aiec-editor-notice',
            AIEC_PLUGIN_URL . 'assets/js/editor-notice.js',
            [],
            AIEC_VERSION,
            true
        );

        // Localize script with data
        wp_localize_script('aiec-editor-notice', 'aiecEditorNotice', [
            'calendarUrl' => $calendar_url,
            'strings' => [
                'returnToCalendar' => __('Open Editorial Calendar', 'ai-editorial-calendar'),
            ]
        ]);
    }

    public function render_calendar_page() {
        include AIEC_PLUGIN_DIR . 'templates/calendar.php';
    }

    public function render_settings_page() {
        include AIEC_PLUGIN_DIR . 'templates/settings.php';
    }

    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=aiec-settings')) . '">' . __('Settings', 'ai-editorial-calendar') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function add_admin_bar_link($wp_admin_bar) {
        if (!current_user_can('edit_posts')) {
            return;
        }

        $wp_admin_bar->add_node([
            'id' => 'aiec-calendar',
            'title' => __('Editorial Calendar', 'ai-editorial-calendar'),
            'href' => $this->get_calendar_url(),
            'parent' => false,
        ]);
    }

    public function add_editor_return_link() {
        if (!current_user_can('edit_posts')) {
            return;
        }

        $calendar_url = $this->get_calendar_url();
        echo '<div class="misc-pub-section aiec-return-link-section" style="padding-top: 12px; border-top: 1px solid #ddd; margin-top: 12px;">';
        echo '<a href="' . esc_url($calendar_url) . '" class="button button-primary aiec-return-button" style="width: 100%; text-align: center; margin-top: 5px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-weight: 500;">';
        echo '<span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px; line-height: 1;"></span>';
        echo esc_html__('Return to Editorial Calendar', 'ai-editorial-calendar');
        echo '</a>';
        echo '</div>';
    }

    public function add_editor_return_notice() {
        global $pagenow;

        if (!current_user_can('edit_posts')) {
            return;
        }

        // Only show on post editor pages (check both screen and pagenow for reliability)
        if (!in_array($pagenow, ['post.php', 'post-new.php'], true)) {
            return;
        }

        // Check if Gutenberg is active - if so, JavaScript will handle the notice
        // This prevents duplicate notices
        if (function_exists('use_block_editor_for_post_type') && use_block_editor_for_post_type('post')) {
            // Check if Classic Editor plugin is active and forcing classic editor
            $classic_editor_active = class_exists('Classic_Editor');
            $force_classic = false;

            if ($classic_editor_active) {
                // Check if user or post has classic editor forced
                global $post;
                if ($post) {
                    $editor_choice = get_post_meta($post->ID, 'classic-editor-remember', true);
                    if ($editor_choice === 'classic-editor') {
                        $force_classic = true;
                    }
                }

                // Check user preference
                $user_editor_choice = get_user_option('classic-editor-settings');
                if (isset($user_editor_choice['editor']) && $user_editor_choice['editor'] === 'classic') {
                    $force_classic = true;
                }
            }

            // Only show PHP notice if Classic Editor is forced
            if (!$force_classic) {
                return;
            }
        }

        $calendar_url = $this->get_calendar_url();

        // Always show the notice - prominent button design
        echo '<div class="aiec-classic-notice">';
        echo '<a href="' . esc_url($calendar_url) . '" class="aiec-editor-button button button-primary">';
        echo '<span class="dashicons dashicons-calendar-alt"></span>';
        echo '<span class="aiec-editor-button-text">' . esc_html__('Editorial Calendar', 'ai-editorial-calendar') . '</span>';
        echo '</a>';
        echo '</div>';
    }

    public function add_editor_return_notice_edit_form() {
        // Alternative hook for post editor - fires at top of edit form
        $this->add_editor_return_notice();
    }

    public function ajax_dismiss_notice() {
        check_ajax_referer('aiec_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'ai-editorial-calendar'));
        }

        $notice_id = sanitize_text_field(wp_unslash($_POST['notice_id'] ?? ''));
        if (empty($notice_id)) {
            wp_send_json_error(__('Invalid notice ID', 'ai-editorial-calendar'));
        }

        update_user_meta(get_current_user_id(), $notice_id, '1');
        wp_send_json_success();
    }

    public function add_view_post_return_link($return, $post_id, $new_title, $new_slug, $post) {
        if (!current_user_can('edit_posts')) {
            return $return;
        }

        $calendar_url = $this->get_calendar_url();
        $calendar_link = '<a href="' . esc_url($calendar_url) . '" class="button button-small" style="margin-left: 8px;">' . esc_html__('Return to Editorial Calendar', 'ai-editorial-calendar') . '</a>';

        // Add the link after the existing preview/permalink HTML
        return $return . $calendar_link;
    }

    public function add_ai_suggestion_meta_box() {
        global $post;

        // Only show meta box if this post was created from the AI Editorial Calendar
        // This includes posts created from both calendar view and list view, as both
        // use the same ajax_create_draft() method which sets the _aiec_from_calendar meta
        if (!$post || !get_post_meta($post->ID, '_aiec_from_calendar', true)) {
            return;
        }

        add_meta_box(
            'aiec-ai-suggestion',
            __('AI-Suggested Outline', 'ai-editorial-calendar'),
            [$this, 'render_ai_suggestion_meta_box'],
            'post',
            'side',
            'high'
        );
    }

    public function render_ai_suggestion_meta_box($post) {
        $suggestion = get_post_meta($post->ID, '_aiec_ai_suggestion', true);

        if (empty($suggestion)) {
            echo '<p style="color: #666; font-style: italic;">' . esc_html__('No AI suggestion available for this post.', 'ai-editorial-calendar') . '</p>';
            return;
        }

        echo '<div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 12px; margin-top: 8px;">';
        echo '<p style="margin: 0; color: #333; line-height: 1.6;">' . esc_html($suggestion) . '</p>';
        echo '</div>';

        $calendar_url = $this->get_calendar_url();
        echo '<div style="margin-top: 12px;">';

        // Only users who may spend AI credits get the generate control (see audit S1)
        if ($this->user_can_use_ai()) {
            echo '<button type="button" id="aiec-generate-outline" class="button button-primary" data-post-id="' . esc_attr($post->ID) . '">';
            echo esc_html__('Generate an Outline', 'ai-editorial-calendar');
            echo '</button>';
        }

        // Calendar link button
        echo '<a href="' . esc_url($calendar_url) . '" class="button" style="margin-left:6px;" title="' . esc_attr__('Open Editorial Calendar', 'ai-editorial-calendar') . '">';
        echo '<span class="dashicons dashicons-calendar"></span>';
        echo '</a>';
        echo '<span class="spinner" id="aiec-outline-spinner" style="float: none; margin-left: 8px; visibility: hidden;"></span>';
        echo '</div>';
        echo '<div id="aiec-outline-message" style="margin-top: 8px; display: none;"></div>';

        wp_nonce_field('aiec_generate_outline', 'aiec_outline_nonce');
    }

    public function add_dashboard_widget() {
        if (!current_user_can('edit_posts')) {
            return;
        }

        wp_add_dashboard_widget(
            'aiec_dashboard_widget',
            __('AI Editorial Calendar', 'ai-editorial-calendar'),
            [$this, 'render_dashboard_widget']
        );
    }

    public function render_dashboard_widget() {
        $calendar_url = $this->get_calendar_url();
        $new_post_url = admin_url('post-new.php');
        $has_api_key = !empty($this->get_api_key());
        $can_use_ai = $this->user_can_use_ai();

        echo '<div class="aiec-dashboard-widget">';
        echo '<div class="aiec-dashboard-actions">';

        // New Post button
        echo '<a href="' . esc_url($new_post_url) . '" class="button button-primary aiec-dashboard-btn" style="width: 100%; margin-bottom: 10px; text-align: center; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">';
        echo '<span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px; line-height: 1;"></span>';
        echo esc_html__('New Post', 'ai-editorial-calendar');
        echo '</a>';

        // Get AI Suggestions button (only if a key is set and the user may spend it)
        if ($has_api_key && $can_use_ai) {
            echo '<a href="' . esc_url($calendar_url) . '" class="button button-secondary aiec-dashboard-btn" style="width: 100%; margin-bottom: 10px; text-align: center; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">';
            echo '<span class="dashicons dashicons-lightbulb" style="font-size: 16px; width: 16px; height: 16px; line-height: 1;"></span>';
            echo esc_html__('Get AI Suggestions', 'ai-editorial-calendar');
            echo '</a>';
        }

        // View Calendar button
        echo '<a href="' . esc_url($calendar_url) . '" class="button button-secondary aiec-dashboard-btn" style="width: 100%; text-align: center; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">';
        echo '<span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px; line-height: 1;"></span>';
        echo esc_html__('View Calendar', 'ai-editorial-calendar');
        echo '</a>';

        echo '</div>';

        if (!$has_api_key) {
            echo '<p style="margin-top: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; color: #856404;">';
            echo '<strong>' . esc_html__('Tip:', 'ai-editorial-calendar') . '</strong> ';
            printf(
                esc_html__('Configure your AI API key in %sSettings%s to enable content suggestions.', 'ai-editorial-calendar'),
                '<a href="' . esc_url(admin_url('admin.php?page=aiec-settings')) . '">',
                '</a>'
            );
            echo '</p>';
        }

        echo '</div>';
    }

    public function handle_uninstall() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'ai-editorial-calendar'));
        }

        check_admin_referer('aiec_uninstall', 'aiec_uninstall_nonce');

        delete_option('aiec_ai_provider');
        delete_option('aiec_api_key');
        delete_option('aiec_site_context');
        delete_option('aiec_tone');
        delete_option('aiec_avoid');
        delete_option('aiec_country');
        delete_option('aiec_region');
        delete_option('aiec_culture');
        delete_option('aiec_belief');
        delete_option('aiec_focus_type');

        // Use a transient instead of GET parameter for the success message
        set_transient('aiec_settings_deleted', true, 30);
        wp_safe_redirect(admin_url('admin.php?page=aiec-settings'));
        exit;
    }

    public function ajax_get_posts() {
        check_ajax_referer('aiec_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'ai-editorial-calendar'));
        }

        $start = sanitize_text_field(wp_unslash($_POST['start'] ?? ''));
        $end = sanitize_text_field(wp_unslash($_POST['end'] ?? ''));

        $posts = get_posts([
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'pending', 'future'],
            'date_query' => [
                [
                    'after' => $start,
                    'before' => $end,
                    'inclusive' => true,
                ]
            ],
            // Bounded instead of -1 so a busy month can't exhaust memory (see audit S5).
            'posts_per_page' => self::CALENDAR_MAX_POSTS,
        ]);

        $events = array_map([$this, 'build_post_event'], $posts);

        wp_send_json_success($events);
    }

    public function ajax_get_all_posts() {
        check_ajax_referer('aiec_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'ai-editorial-calendar'));
        }

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $search = sanitize_text_field(wp_unslash($_POST['search'] ?? ''));
        $status_filter = sanitize_text_field(wp_unslash($_POST['status'] ?? ''));

        $args = [
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'pending', 'future'],
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        if (!empty($search)) {
            $args['s'] = $search;
        }

        if (!empty($status_filter)) {
            $args['post_status'] = [$status_filter];
        }

        $query = new WP_Query($args);
        $posts = $query->posts;

        $events = array_map([$this, 'build_post_event'], $posts);

        wp_send_json_success([
            'posts' => $events,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $page,
        ]);
    }

    public function ajax_update_post_date() {
        check_ajax_referer('aiec_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'ai-editorial-calendar'));
        }

        $post_id = absint($_POST['post_id'] ?? 0);
        $new_date = sanitize_text_field(wp_unslash($_POST['new_date'] ?? ''));

        if (!$post_id || !$new_date) {
            wp_send_json_error(__('Invalid parameters', 'ai-editorial-calendar'));
        }

        if (!$this->validate_date_time($new_date)) {
            wp_send_json_error(__('Invalid date format', 'ai-editorial-calendar'));
        }

        $post = get_post($post_id);
        if (!$post || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('Cannot edit this post', 'ai-editorial-calendar'));
        }

        $updated = wp_update_post([
            'ID' => $post_id,
            'post_date' => $new_date,
            'post_date_gmt' => get_gmt_from_date($new_date),
        ]);

        if (is_wp_error($updated)) {
            wp_send_json_error($updated->get_error_message());
        }

        wp_send_json_success();
    }

    public function ajax_create_draft() {
        check_ajax_referer('aiec_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'ai-editorial-calendar'));
        }

        $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $date = sanitize_text_field(wp_unslash($_POST['date'] ?? ''));

        if (empty($title)) {
            wp_send_json_error(__('Title is required', 'ai-editorial-calendar'));
        }

        if (!$this->validate_date_time($date)) {
            wp_send_json_error(__('Invalid date format', 'ai-editorial-calendar'));
        }

        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_content' => '',
            'post_status' => 'draft',
            'post_type' => 'post',
            'post_date' => $date,
            'post_date_gmt' => get_gmt_from_date($date),
        ], true);

        if (is_wp_error($post_id)) {
            wp_send_json_error($post_id->get_error_message());
        }

        // Store AI suggestion description in post meta
        if (!empty($description)) {
            update_post_meta($post_id, '_aiec_ai_suggestion', sanitize_textarea_field($description));
        }

        // Flag that this draft was created from the AI Editorial Calendar
        update_post_meta($post_id, '_aiec_from_calendar', '1');

        wp_send_json_success([
            'id' => $post_id,
            'editUrl' => get_edit_post_link($post_id, 'raw')
        ]);
    }

    public function ajax_trash_post() {
        check_ajax_referer('aiec_nonce', 'nonce');

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'ai-editorial-calendar'));
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('Post not found', 'ai-editorial-calendar'));
        }

        if (!current_user_can('delete_post', $post_id)) {
            wp_send_json_error(__('You do not have permission to delete this post', 'ai-editorial-calendar'));
        }

        $result = wp_trash_post($post_id);

        if (!$result) {
            wp_send_json_error(__('Failed to trash post', 'ai-editorial-calendar'));
        }

        wp_send_json_success();
    }

    public function ajax_generate_outline() {
        check_ajax_referer('aiec_generate_outline', 'nonce');

        if (!$this->user_can_use_ai()) {
            wp_send_json_error(__('You do not have permission to use AI features.', 'ai-editorial-calendar'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'ai-editorial-calendar'));
        }

        // Verify user can edit this specific post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('You do not have permission to edit this post', 'ai-editorial-calendar'));
        }

        if (!AIEC_Settings::check_ai_rate_limit()) {
            wp_send_json_error(__('Rate limit reached. Please wait before generating more outlines.', 'ai-editorial-calendar'));
        }

        $api_key = $this->get_api_key();
        if (empty($api_key)) {
            wp_send_json_error(__('API key not configured', 'ai-editorial-calendar'));
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('Post not found', 'ai-editorial-calendar'));
        }

        // Get the AI suggestion description
        $suggestion = get_post_meta($post_id, '_aiec_ai_suggestion', true);
        if (empty($suggestion)) {
            wp_send_json_error(__('No AI suggestion found for this post', 'ai-editorial-calendar'));
        }

        $provider = get_option('aiec_ai_provider', 'openai');
        $context = get_option('aiec_site_context', '');
        $tone = get_option('aiec_tone', '');

        // Build the outline generation prompt
        $prompt = AIEC_Prompt_Builder::build_outline($post->post_title, $suggestion, $context, $tone);

        // Call AI API to generate outline (1500 tokens should be sufficient for detailed outline)
        $outline = $this->ai_client->generate($provider, $api_key, $prompt, 1500);

        if (is_wp_error($outline)) {
            wp_send_json_error($outline->get_error_message());
        }

        // Clean the outline: strip HTML, markdown, and extraneous characters
        $outline = AIEC_Prompt_Builder::clean_outline($outline);

        // Sanitize outline before saving (strip any remaining HTML/scripts)
        $outline = wp_kses_post($outline);

        // Update post content with the generated outline
        $updated = wp_update_post([
            'ID' => $post_id,
            'post_content' => $outline,
        ], true);

        if (is_wp_error($updated)) {
            wp_send_json_error($updated->get_error_message());
        }

        wp_send_json_success([
            'outline' => $outline,
            'message' => __('Outline generated successfully!', 'ai-editorial-calendar'),
        ]);
    }

    public function ajax_get_suggestions() {
        check_ajax_referer('aiec_nonce', 'nonce');

        if (!$this->user_can_use_ai()) {
            wp_send_json_error(__('You do not have permission to use AI features.', 'ai-editorial-calendar'));
        }

        if (!AIEC_Settings::check_ai_rate_limit()) {
            wp_send_json_error(__('Rate limit reached. Please wait before requesting more AI suggestions.', 'ai-editorial-calendar'));
        }

        $api_key = $this->get_api_key();
        if (empty($api_key)) {
            wp_send_json_error(__('API key not configured', 'ai-editorial-calendar'));
        }

        $provider = get_option('aiec_ai_provider', 'openai');
        $context = get_option('aiec_site_context', '');
        $tone = get_option('aiec_tone', '');
        $avoid = get_option('aiec_avoid', '');
        $date = sanitize_text_field(wp_unslash($_POST['date'] ?? ''));

        // Get recent posts for context (exclude default placeholder posts)
        $recent_posts = get_posts([
            'posts_per_page' => 10,
            'post_status' => ['publish', 'draft', 'future'],
        ]);

        $recent_titles = array_filter(
            array_map(fn($p) => $p->post_title, $recent_posts),
            fn($title) => !empty($title) && stripos($title, 'Hello World') === false
        );

        $prompt = AIEC_Prompt_Builder::build_suggestions($context, $tone, $avoid, $recent_titles, $date);

        $suggestions = $this->ai_client->generate($provider, $api_key, $prompt);

        if (is_wp_error($suggestions)) {
            wp_send_json_error($suggestions->get_error_message());
        }

        wp_send_json_success($suggestions);
    }

    /**
     * Check model health/availability
     *
     * @return array Health status for each provider
     */
    public function ajax_check_model_health() {
        check_ajax_referer('aiec_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'ai-editorial-calendar'));
        }

        $api_key = $this->get_api_key();
        if (empty($api_key)) {
            wp_send_json_error(__('API key not configured', 'ai-editorial-calendar'));
        }

        $provider = get_option('aiec_ai_provider', 'openai');
        $health_status = [
            'provider' => $provider,
            'status' => 'unknown',
            'message' => '',
        ];

        // Make a minimal test call to check model availability
        $test_prompt = 'Test';
        $test_response = $this->ai_client->generate($provider, $api_key, $test_prompt, 10);

        if (is_wp_error($test_response)) {
            $error_code = $test_response->get_error_code();
            $error_message = $test_response->get_error_message();

            // Check for model-specific errors
            if (strpos($error_message, 'model') !== false || strpos($error_message, 'not found') !== false) {
                $health_status['status'] = 'unavailable';
                $health_status['message'] = __('Model may be unavailable or deprecated', 'ai-editorial-calendar');
            } elseif (strpos($error_message, '401') !== false || strpos($error_message, '403') !== false) {
                $health_status['status'] = 'auth_error';
                $health_status['message'] = __('API key authentication failed', 'ai-editorial-calendar');
            } else {
                $health_status['status'] = 'error';
                $health_status['message'] = sanitize_text_field($error_message);
            }
        } else {
            $health_status['status'] = 'available';
            $health_status['message'] = __('Model is available and responding', 'ai-editorial-calendar');
        }

        wp_send_json_success($health_status);
    }
}

AI_Editorial_Calendar::get_instance();
