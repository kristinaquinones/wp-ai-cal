<?php
/**
 * Uninstall script for AI Editorial Calendar
 *
 * Removes all plugin options when the plugin is deleted.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
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

// Delete post meta for all posts
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_aiec_ai_suggestion', '_aiec_from_calendar')");

// Delete user meta (dismissed notices)
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'aiec_%'");
