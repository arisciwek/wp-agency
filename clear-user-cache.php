<?php
/**
 * Clear Agency User Info Cache
 *
 * Run this via browser or CLI to clear cached user info data
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Clear all agency_user_info cache
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_agency_user_info%'");
$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_timeout_agency_user_info%'");

echo "Cache cleared successfully!\n";
echo "Please refresh your admin page to see the updated permissions.\n";
