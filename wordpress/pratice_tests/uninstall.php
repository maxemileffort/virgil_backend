<?php

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Access the global variable
global $wpdb;

// Define the table names (ensure these match db-functions.php)
$table_questions = $wpdb->prefix . 'practice_test_questions';
$table_subs = $wpdb->prefix . 'practice_test_subs';
$table_tests = $wpdb->prefix . 'practice_test_tests';
$table_test_questions = $wpdb->prefix . 'practice_test_test_questions';
$table_test_attempts = $wpdb->prefix . 'practice_test_test_attempts';
$table_test_resp = $wpdb->prefix . 'practice_test_test_resp';

// SQL to drop tables
$sql_resp = "DROP TABLE IF EXISTS $table_test_resp;";
$sql_attempts = "DROP TABLE IF EXISTS $table_test_attempts;";
$sql_test_questions = "DROP TABLE IF EXISTS $table_test_questions;";
$sql_tests = "DROP TABLE IF EXISTS $table_tests;";
$sql_subs = "DROP TABLE IF EXISTS $table_subs;";
$sql_questions = "DROP TABLE IF EXISTS $table_questions;"; // Note: $table_act was likely a typo for $table_questions

// Execute the SQL (in reverse order of potential dependencies if FOREIGN KEYs were used)
$wpdb->query($sql_resp);
$wpdb->query($sql_attempts);
$wpdb->query($sql_test_questions);
$wpdb->query($sql_tests);
$wpdb->query($sql_subs); // Corrected variable name from $$sql_subs
$wpdb->query($sql_questions);


// Optionally, delete plugin options, post meta, etc.
// delete_option('my_plugin_option_name');
// delete_post_meta_by_key('my_plugin_meta_key');
