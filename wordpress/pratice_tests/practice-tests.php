<?php
/*
Plugin Name: Practice Tests
Description: A plugin to create practice tests for ACT and SAT.
Version: 1.0
Author: Max Wood
*/

include_once plugin_dir_path(__FILE__) . 'admin-menu.php';
include_once plugin_dir_path(__FILE__) . 'core-functions.php';
include_once plugin_dir_path(__FILE__) . 'db-functions.php';
include_once plugin_dir_path(__FILE__) . 'utilities.php';

// Activation Hook
register_activation_hook(__FILE__, 'create_plugin_database_tables');

// Hook for adding admin menus
add_action('admin_menu', 'practice_tests_plugin_menu');

// Check for form submissions
if (isset($_POST['submit_single_question'])) {
    // Handle single question addition
    practice_tests_add_single_question($_POST['question'], $_POST['answer']);
}

if (isset($_POST['submit_csv'])) {
    // Handle CSV upload
    practice_tests_handle_csv_upload($_FILES['questions_csv']);
}