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
include_once plugin_dir_path(__FILE__) . 'client-plans-page.php';
include_once plugin_dir_path(__FILE__) . 'client-login-page.php';
include_once plugin_dir_path(__FILE__) . 'client-members-page.php';

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
                                    $_POST['user_role'],);
}

if (isset($_POST['submit_users_csv'])) {
    // Handle user CSV upload
    practice_tests_handle_users_csv_upload($_FILES['users_csv']);
}

// Code for checking emails on registration forms
function enqueue_email_validation_scripts() {
    wp_enqueue_script('reg-email-validation', plugin_dir_url(__FILE__) .'email-validation.js');
    wp_localize_script('reg-email-validation', 'my_plugin_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'enqueue_email_validation_scripts');

function check_email_existence() {
    global $wpdb;

    $email = $_POST['user_email'];
    // Replace 'your_custom_table' with the actual table name and adjust the query accordingly
    $query = $wpdb->prepare("SELECT COUNT(*) FROM " . $wpdb->prefix . 'practice_test_subs' . " WHERE user_email = %s", $email);
    $count = $wpdb->get_var($query);

    if ($count > 0) {
        echo 'exists';
    } else {
        echo 'not_exists';
    }
    wp_die();
}

add_action('wp_ajax_check_email', 'check_email_existence');
add_action('wp_ajax_nopriv_check_email', 'check_email_existence');

