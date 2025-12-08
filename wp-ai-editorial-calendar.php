<?php
/**
 * Plugin Name: AI Editorial Calendar
 * Plugin URI: https://github.com/kristinaquiones/wp-ai-cal
 * Description: A lightweight editorial calendar with personalized AI content suggestions. Connect your own AI account (OpenAI, Anthropic, or Google).
 * Version: 1.0.0
 * Author: Kristina Quinones
 * License: GPL v2 or later
 * Text Domain: ai-editorial-calendar
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AIEC_VERSION', '1.0.0');
define('AIEC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIEC_PLUGIN_URL', plugin_dir_url(__FILE__));

class AI_Editorial_Calendar {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_aiec_get_posts', [$this, 'ajax_get_posts']);
        add_action('wp_ajax_aiec_get_suggestions', [$this, 'ajax_get_suggestions']);
        add_action('wp_ajax_aiec_update_post_date', [$this, 'ajax_update_post_date']);
        add_action('admin_post_aiec_uninstall', [$this, 'handle_uninstall']);
        add_action('wp_ajax_aiec_create_draft', [$this, 'ajax_create_draft']);
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

    public function register_settings() {
        register_setting('aiec_settings', 'aiec_ai_provider', [
            'sanitize_callback' => [$this, 'sanitize_provider']
        ]);
        register_setting('aiec_settings', 'aiec_api_key', [
            'sanitize_callback' => [$this, 'sanitize_api_key']
        ]);
        register_setting('aiec_settings', 'aiec_site_context', [
            'sanitize_callback' => [$this, 'sanitize_context_field']
        ]);
        register_setting('aiec_settings', 'aiec_tone', [
            'sanitize_callback' => [$this, 'sanitize_short_field']
        ]);
        register_setting('aiec_settings', 'aiec_avoid', [
            'sanitize_callback' => [$this, 'sanitize_context_field']
        ]);
    }

    public function sanitize_context_field($value) {
        $value = sanitize_textarea_field($value);
        // Cap at 500 characters to keep tokens down
        return mb_substr($value, 0, 500);
    }

    public function sanitize_short_field($value) {
        $value = sanitize_text_field($value);
        // Cap at 100 characters
        return mb_substr($value, 0, 100);
    }

    public function sanitize_provider($value) {
        $allowed = ['openai', 'anthropic', 'google'];
        return in_array($value, $allowed, true) ? $value : 'openai';
    }

    public function sanitize_api_key($value) {
        // If empty, keep existing value
        if (empty($value)) {
            return get_option('aiec_api_key');
        }
        // Only trim whitespace - don't use sanitize_text_field as it can corrupt API keys
        return trim($value);
    }

    public function get_api_key() {
        return get_option('aiec_api_key', '');
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
        if (strpos($hook, 'ai-editorial-calendar') === false && strpos($hook, 'aiec-settings') === false) {
            return;
        }

        // Enqueue Google Fonts
        wp_enqueue_style(
            'aiec-fonts',
            'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Space+Mono:wght@400;700&display=swap',
            [],
            null
        );
        
        wp_enqueue_style('aiec-styles', AIEC_PLUGIN_URL . 'assets/css/calendar.css', ['aiec-fonts'], AIEC_VERSION);
        wp_enqueue_script('aiec-calendar', AIEC_PLUGIN_URL . 'assets/js/calendar.js', ['jquery'], AIEC_VERSION, true);

        wp_localize_script('aiec-calendar', 'aiecData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url('admin.php'),
            'newPostUrl' => admin_url('post-new.php'),
            'nonce' => wp_create_nonce('aiec_nonce'),
            'hasApiKey' => !empty($this->get_api_key()),
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
            ]
        ]);
    }

    public function render_calendar_page() {
        include AIEC_PLUGIN_DIR . 'templates/calendar.php';
    }

    public function render_settings_page() {
        include AIEC_PLUGIN_DIR . 'templates/settings.php';
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

        wp_safe_redirect(add_query_arg('aiec-deleted', 'true', admin_url('admin.php?page=aiec-settings')));
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
            'post_type' => ['post', 'page'],
            'post_status' => ['publish', 'draft', 'pending', 'future'],
            'date_query' => [
                [
                    'after' => $start,
                    'before' => $end,
                    'inclusive' => true,
                ]
            ],
            'posts_per_page' => -1,
        ]);

        $events = array_map(function($post) {
            return [
                'id' => $post->ID,
                'title' => $post->post_title ?: __('(no title)', 'ai-editorial-calendar'),
                'date' => $post->post_date,
                'status' => $post->post_status,
                'editUrl' => get_edit_post_link($post->ID, 'raw'),
                'type' => $post->post_type,
            ];
        }, $posts);

        wp_send_json_success($events);
    }

    public function ajax_update_post_date() {
        check_ajax_referer('aiec_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'ai-editorial-calendar'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
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

        // Build post content with hidden description
        $content = '';
        if (!empty($description)) {
            $content = '<!-- wp:paragraph {"className":"aiec-suggestion-note"} -->' . "\n";
            $content .= '<p class="aiec-suggestion-note" style="display:none;">' . esc_html($description) . '</p>' . "\n";
            $content .= '<!-- /wp:paragraph -->';
        }

        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'draft',
            'post_date' => $date,
            'post_date_gmt' => get_gmt_from_date($date),
        ]);

        if (is_wp_error($post_id)) {
            wp_send_json_error($post_id->get_error_message());
        }

        wp_send_json_success(['id' => $post_id]);
    }

    public function ajax_get_suggestions() {
        check_ajax_referer('aiec_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Unauthorized', 'ai-editorial-calendar'));
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

        $prompt = $this->build_prompt($context, $tone, $avoid, $recent_titles, $date);

        $suggestions = $this->call_ai_api($provider, $api_key, $prompt);

        if (is_wp_error($suggestions)) {
            wp_send_json_error($suggestions->get_error_message());
        }

        wp_send_json_success($suggestions);
    }

    private function build_prompt($context, $tone, $avoid, $recent_titles, $date) {
        // Sanitize date
        $date = sanitize_text_field($date);
        
        // Sanitize recent titles
        $sanitized_titles = array_map(function($title) {
            return sanitize_text_field($title);
        }, array_slice(array_values($recent_titles), 0, 5));
        
        $titles_list = implode(', ', $sanitized_titles);

        // Build prompt with sanitized inputs (context, tone, avoid are already sanitized via settings)
        $prompt = sprintf(
            'Suggest 3 blog posts for %s.',
            $date
        );

        if ($context) {
            $prompt .= sprintf(' Site: %s.', $context);
        }
        if ($tone) {
            $prompt .= sprintf(' Tone: %s.', $tone);
        }
        if ($titles_list) {
            $prompt .= sprintf(' Recent: %s.', $titles_list);
        }
        if ($avoid) {
            $prompt .= sprintf(' Avoid: %s.', $avoid);
        }

        $prompt .= ' Format: Title: X | Desc: Y (one line each, no duplicates)';

        return $prompt;
    }

    private function call_ai_api($provider, $api_key, $prompt) {
        $response = null;

        switch ($provider) {
            case 'openai':
                $response = $this->call_openai($api_key, $prompt);
                break;
            case 'anthropic':
                $response = $this->call_anthropic($api_key, $prompt);
                break;
            case 'google':
                $response = $this->call_google($api_key, $prompt);
                break;
            default:
                return new WP_Error('invalid_provider', __('Invalid AI provider', 'ai-editorial-calendar'));
        }

        return $response;
    }

    private function call_openai($api_key, $prompt) {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 500,
            ]),
            'timeout' => 30,
        ]);

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (is_wp_error($response)) {
            return $response;
        }

        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message'] ?? __('API error', 'ai-editorial-calendar'));
        }

        $content = $body['choices'][0]['message']['content'] ?? '';
        if (empty($content)) {
            return new WP_Error('api_error', __('Empty response from API', 'ai-editorial-calendar'));
        }

        return $content;
    }

    private function call_anthropic($api_key, $prompt) {
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ],
            'body' => json_encode([
                'model' => 'claude-3-5-haiku-latest',
                'max_tokens' => 500,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
            ]),
            'timeout' => 30,
        ]);

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (is_wp_error($response)) {
            return $response;
        }

        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message'] ?? __('API error', 'ai-editorial-calendar'));
        }

        $content = $body['content'][0]['text'] ?? '';
        if (empty($content)) {
            return new WP_Error('api_error', __('Empty response from API', 'ai-editorial-calendar'));
        }

        return $content;
    }

    private function call_google($api_key, $prompt) {
        $response = wp_remote_post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent', [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $api_key,
            ],
            'body' => json_encode([
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            return new WP_Error('api_error', $body['error']['message'] ?? __('API error', 'ai-editorial-calendar'));
        }

        if (empty($body['candidates'][0]['content']['parts'][0]['text'])) {
            return new WP_Error('api_error', __('Empty response from API', 'ai-editorial-calendar'));
        }

        return $body['candidates'][0]['content']['parts'][0]['text'];
    }
}

AI_Editorial_Calendar::get_instance();
