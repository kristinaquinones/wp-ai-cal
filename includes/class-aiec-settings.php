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

        // Keep the stored API key out of the autoloaded options cache. Run this only
        // when the key is actually written, not on every admin_init, so we never pay
        // a wp_load_alloptions() scan on unrelated admin pages.
        add_action('add_option_aiec_api_key', [self::class, 'ensure_api_key_not_autoloaded']);
        add_action('update_option_aiec_api_key', [self::class, 'ensure_api_key_not_autoloaded']);
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

    /**
     * Canonical AI provider roster: provider key => display label.
     *
     * Single source of truth for which providers exist and what they are called.
     * Used by sanitize_provider(), the settings dropdown, the provider-name lookup,
     * and the AI client dispatch, so a provider is added/renamed in exactly one place.
     *
     * @return array<string,string>
     */
    public static function get_providers() {
        return [
            'openai'    => 'OpenAI',
            'anthropic' => 'Anthropic',
            'google'    => 'Google',
            'grok'      => 'xAI Grok',
        ];
    }

    public static function sanitize_provider($value) {
        return array_key_exists($value, self::get_providers()) ? $value : 'openai';
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
     * Transient key holding the current user's rate-limit window.
     *
     * @return string
     */
    private static function rate_limit_key() {
        return 'aiec_rl_' . get_current_user_id();
    }

    /**
     * Read the current user's rate-limit window, normalizing legacy/expired data.
     *
     * The window is a fixed (non-sliding) hour: it stores the start timestamp and
     * the number of calls made since then. Once the hour from `start` elapses the
     * window is treated as empty.
     *
     * @return array{start:int,count:int}
     */
    private static function get_rate_limit_window() {
        $stored = get_transient(self::rate_limit_key());
        $now = time();

        if (!is_array($stored) || !isset($stored['start'], $stored['count'])) {
            return ['start' => $now, 'count' => 0];
        }

        // Fixed window: if the hour from start has elapsed, start fresh.
        if (($now - (int) $stored['start']) >= HOUR_IN_SECONDS) {
            return ['start' => $now, 'count' => 0];
        }

        return ['start' => (int) $stored['start'], 'count' => (int) $stored['count']];
    }

    /**
     * Whether the current user may make another paid AI call (read-only gate).
     *
     * Even trusted users shouldn't be able to loop a generation endpoint and run
     * up an unbounded bill on the owner's provider account, so each user gets a
     * capped budget per fixed hour. This only reads the counter; callers must
     * invoke record_ai_call() after a successful call so failed calls don't burn
     * quota. See audit S1.
     *
     * @return bool True if the call is allowed, false if the limit is reached.
     */
    public static function check_ai_rate_limit() {
        $window = self::get_rate_limit_window();
        return $window['count'] < self::AI_RATE_LIMIT_PER_HOUR;
    }

    /**
     * Charge one call against the current user's rate-limit window.
     *
     * Call this only after a paid provider call actually succeeds. The transient
     * TTL is anchored to the window start (not re-armed each call) so the hour
     * does not slide. WordPress transients are not atomic, so a tightly concurrent
     * burst can over-count slightly; the post-success ordering bounds the cost.
     */
    public static function record_ai_call() {
        $window = self::get_rate_limit_window();
        $window['count']++;

        // Anchor TTL to the fixed window end so the hour doesn't slide forward.
        $remaining = HOUR_IN_SECONDS - (time() - $window['start']);
        if ($remaining < 1) {
            $remaining = HOUR_IN_SECONDS;
        }

        set_transient(self::rate_limit_key(), $window, $remaining);
    }

    /**
     * Keep the API key out of the autoloaded options cache.
     *
     * The Settings API writes the option on options.php with autoload enabled,
     * which would pull the secret into memory on every request. We flip it off so
     * the key is only loaded when actually used. See audit S2.
     *
     * Public so it can be attached to the add_option/update_option hooks.
     */
    public static function ensure_api_key_not_autoloaded() {
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
