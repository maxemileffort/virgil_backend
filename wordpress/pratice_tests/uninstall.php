<?php

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Access the global variable
global $wpdb;

// Define the table names
$table_act = $wpdb->prefix . 'practice_test_questions';
$table_subs = $wpdb->prefix . 'practice_test_subs';

// SQL to drop tables
$sql_questions = "DROP TABLE IF EXISTS $table_questions;";
$sql_subs = "DROP TABLE IF EXISTS $table_subs;";

// Execute the SQL
$wpdb->query($sql_questions);
$wpdb->query($$sql_subs);

// Optionally, you can also delete plugin options, post meta, etc.
