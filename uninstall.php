<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin tables
global $wpdb;
$tables = array(
    $wpdb->prefix . 'wp_agency_agency'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Delete plugin options
delete_option('wp_agency_version');
delete_option('wp_agency_db_version');
