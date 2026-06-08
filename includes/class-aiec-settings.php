<?php
/**
 * Settings registration and AI access policy for the AI Editorial Calendar.
 *
 * Owns the options API wiring (registration + sanitizers), API key access, and
 * the "may this user spend AI credits" policy plus its rate limit.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIEC_Settings {

    // Max paid AI calls a single user may make per rolling hour (see audit S1).
    const AI_RATE_LIMIT_PER_HOUR = 30;

    /**
     * Register settings and their sanitizers. Hooked on admin_init.
     */
    public static function register() {
        register_setting('aiec_settings', 'aiec_ai_provider', [
            'sanitize_callback' => [self::class, 'sanitize_provider']
        ]);
        register_setting('aiec_settings', 'aiec_api_key', [
            'sanitize_callback' => [self::class, 'sanitize_api_key']
        ]);
        register_setting('aiec_settings', 'aiec_site_context', [
            'sanitize_callback' => [self::class, 'sanitize_context_field']
        ]);
        register_setting('aiec_settings', 'aiec_tone', [
            'sanitize_callback' => [self::class, 'sanitize_short_field']
        ]);
        register_setting('aiec_settings', 'aiec_avoid', [
            'sanitize_callback' => [self::class, 'sanitize_context_field']
        ]);
        register_setting('aiec_settings', 'aiec_country', [
            'sanitize_callback' => [self::class, 'sanitize_locale_list']
        ]);
        register_setting('aiec_settings', 'aiec_region', [
            'sanitize_callback' => [self::class, 'sanitize_locale_list']
        ]);
        register_setting('aiec_settings', 'aiec_culture', [
            'sanitize_callback' => [self::class, 'sanitize_locale_list']
        ]);
        register_setting('aiec_settings', 'aiec_belief', [
            'sanitize_callback' => [self::class, 'sanitize_locale_list']
        ]);
        register_setting('aiec_settings', 'aiec_focus_type', [
            'sanitize_callback' => [self::class, 'sanitize_short_field']
        ]);

        // Make sure the stored API key never sits in the autoloaded options cache.
        self::ensure_api_key_not_autoloaded();
    }

    public static function sanitize_context_field($value) {
        $value = sanitize_textarea_field($value);
        // Cap at 500 characters to keep tokens down
        return mb_substr($value, 0, 500);
    }

    public static function sanitize_short_field($value) {
        $value = sanitize_text_field($value);
        // Cap at 100 characters
        return mb_substr($value, 0, 100);
    }

    public static function sanitize_locale_list($value) {
        if (is_array($value)) {
            $value = array_map('sanitize_text_field', $value);
            $value = array_filter($value, function ($item) {
                return !empty($item);
            });
            $value = array_slice($value, 0, 5); // cap to 5 selections
            $value = implode(', ', $value);
        } else {
            $value = self::sanitize_short_field($value);
        }
        // Cap at 150 chars to avoid overly long strings
        return mb_substr($value, 0, 150);
    }

    public static function sanitize_provider($value) {
        $allowed = ['openai', 'anthropic', 'google', 'grok'];
        return in_array($value, $allowed, true) ? $value : 'openai';
    }

    public static function sanitize_api_key($value) {
        // If empty, keep existing value
        if (empty($value)) {
            return get_option('aiec_api_key', '');
        }
        // Only trim whitespace - don't use sanitize_text_field as it can corrupt API keys
        return trim($value);
    }

    /**
     * @return string Stored API key, or empty string.
     */
    public static function get_api_key() {
        return get_option('aiec_api_key', '');
    }

    /**
     * Whether the current user may trigger paid AI calls.
     *
     * Gated on publish_posts (Authors/Editors/Admins) rather than edit_posts so
     * Contributors can't spend the site owner's API credits. See audit S1.
     *
     * @return bool
     */
    public static function user_can_use_ai() {
        return current_user_can('publish_posts');
    }

    /**
     * Per-user hourly throttle for paid AI endpoints.
     *
     * Even trusted users shouldn't be able to loop a generation endpoint and run
     * up an unbounded bill on the owner's provider account, so each user gets a
     * capped budget per rolling hour. Returns false once the budget is spent.
     *
     * @return bool True if the call is allowed, false if the limit is reached.
     */
    public static function check_ai_rate_limit() {
        $key = 'aiec_rl_' . get_current_user_id();
        $count = (int) get_transient($key);
        if ($count >= self::AI_RATE_LIMIT_PER_HOUR) {
            return false;
        }
        set_transient($key, $count + 1, HOUR_IN_SECONDS);
        return true;
    }

    /**
     * Keep the API key out of the autoloaded options cache.
     *
     * The Settings API writes the option on options.php with autoload enabled,
     * which would pull the secret into memory on every request. We flip it off so
     * the key is only loaded when actually used. See audit S2.
     */
    private static function ensure_api_key_not_autoloaded() {
        if (get_option('aiec_api_key', false) === false) {
            return; // Nothing stored yet.
        }

        // WP 6.4+ exposes a direct helper; it no-ops when already correct.
        if (function_exists('wp_set_option_autoload')) {
            wp_set_option_autoload('aiec_api_key', false);
            return;
        }

        $autoloaded_options = wp_load_alloptions();
        if (!is_array($autoloaded_options) || !array_key_exists('aiec_api_key', $autoloaded_options)) {
            return;
        }

        // Fallback for older cores: re-create the option with autoload off.
        $value = get_option('aiec_api_key');
        delete_option('aiec_api_key');
        add_option('aiec_api_key', $value, '', 'no');
    }
}
