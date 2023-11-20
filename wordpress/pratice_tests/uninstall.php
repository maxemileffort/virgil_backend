<?php

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Access the global variable
global $wpdb;

// Define the table names
$table_act = $wpdb->prefix . 'practice_test_act';
$table_sat = $wpdb->prefix . 'practice_test_sat';

// SQL to drop tables
$sql_act = "DROP TABLE IF EXISTS $table_act;";
$sql_sat = "DROP TABLE IF EXISTS $table_sat;";

// Execute the SQL
$wpdb->query($sql_act);
$wpdb->query($sql_sat);

// Optionally, you can also delete plugin options, post meta, etc.
