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
include_once plugin_dir_path(__FILE__) . 'client-register-page.php';

// Activation Hook
register_activation_hook(__FILE__, 'create_plugin_database_tables');

// Hook for adding admin menus
add_action('admin_menu', 'practice_tests_plugin_menu');

// Check for form submissions
if (isset($_POST['submit_single_question'])) {
    // Handle single question addition
    practice_tests_add_single_question($_POST['testtype'], 
                                       $_POST['passage'], 
                                       $_POST['question'], 
                                       $_POST['answerchoices1'],
                                       $_POST['answerchoices2'],
                                       $_POST['answerchoices3'],
                                       $_POST['answerchoices4'],
                                       $_POST['answerchoices5'],
                                       $_POST['answercorrect'],
                                       $_POST['explanation'],
                                       $_POST['subjectarea'],
                                       $_POST['skill'],);
}

if (isset($_POST['submit_questions_csv'])) {
    // Handle CSV upload
    practice_tests_handle_csv_upload($_FILES['questions_csv']);
}

if (isset($_POST['submit_single_user'])) {
    // Handle single user addition
    practice_tests_add_single_user($_POST['user_name'], 
                                    $_POST['user_email'],
                                    $_POST['user_password'], 
                                    $_POST['user_action'],
                                    $_POST['user_status'],
                                    $_POST['user_role'],
                                    $_POST['plan']);
}

if (isset($_POST['submit_users_csv'])) {
    // Handle user CSV upload
    practice_tests_handle_users_csv_upload($_FILES['users_csv']);
}