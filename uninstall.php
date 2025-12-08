<?php
/**
 * Uninstall script for AI Editorial Calendar
 *
 * Removes all plugin options when the plugin is deleted.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('aiec_ai_provider');
delete_option('aiec_api_key');
delete_option('aiec_site_context');
delete_option('aiec_tone');
delete_option('aiec_avoid');
