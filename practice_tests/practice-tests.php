<?php
/*
Plugin Name: Practice Tests
Description: A plugin to create practice tests for ACT and SAT.
Version: 1.0
Author: Max Wood
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Include file necessary for activation hook
include_once plugin_dir_path(__FILE__) . 'db-functions.php';

// Activation/Deactivation Hooks
register_activation_hook(__FILE__, 'create_plugin_database_tables');
register_deactivation_hook( __FILE__, 'deactivate' ); // Ensure 'deactivate' function exists in db-functions.php or elsewhere included globally

/**
 * Initialize the plugin - load files and add hooks.
 * Hooked to 'plugins_loaded'.
 */
function practice_tests_plugin_init() {

    // Load core functions, utilities, and frontend page definitions
    include_once plugin_dir_path(__FILE__) . 'core-functions.php';
    include_once plugin_dir_path(__FILE__) . 'utilities.php';
    include_once plugin_dir_path(__FILE__) . 'client-register-page.php';
    include_once plugin_dir_path(__FILE__) . 'client-plans-page.php';
    include_once plugin_dir_path(__FILE__) . 'client-login-page.php';
    include_once plugin_dir_path(__FILE__) . 'client-members-page.php';

    // --- Admin Area Specific ---
    if ( is_admin() ) {
        include_once plugin_dir_path(__FILE__) . 'admin-menu.php'; // Include admin menu file
        add_action('admin_menu', 'practice_tests_plugin_menu'); // Hook the admin menu setup

        // Admin-specific form submission handling (POST requests usually handled before plugins_loaded, consider admin_init or specific page hooks)
        // For simplicity, keeping basic checks here, but ideally move to admin_init or specific action hooks.
        // Note: Define Test submission is handled via admin_init hook in admin-menu.php

        if (isset($_POST['submit_single_question'])) {
            if (function_exists('practice_tests_add_single_question')) {
                // Basic nonce check example (add nonces to your forms)
                // if (isset($_POST['pt_add_question_nonce']) && wp_verify_nonce($_POST['pt_add_question_nonce'], 'pt_add_question_action')) {
                    practice_tests_add_single_question(
                        isset($_POST['testtype']) ? sanitize_text_field($_POST['testtype']) : '',
                        isset($_POST['passage']) ? sanitize_textarea_field($_POST['passage']) : '',
                        isset($_POST['question']) ? sanitize_textarea_field($_POST['question']) : '',
                        isset($_POST['answerchoices1']) ? sanitize_text_field($_POST['answerchoices1']) : '',
                        isset($_POST['answerchoices2']) ? sanitize_text_field($_POST['answerchoices2']) : '',
                        isset($_POST['answerchoices3']) ? sanitize_text_field($_POST['answerchoices3']) : '',
                        isset($_POST['answerchoices4']) ? sanitize_text_field($_POST['answerchoices4']) : '',
                        isset($_POST['answerchoices5']) ? sanitize_text_field($_POST['answerchoices5']) : '',
                        isset($_POST['answercorrect']) ? sanitize_text_field($_POST['answercorrect']) : '',
                        isset($_POST['explanation']) ? sanitize_textarea_field($_POST['explanation']) : '',
                        isset($_POST['subjectarea']) ? sanitize_text_field($_POST['subjectarea']) : '',
                        isset($_POST['skill']) ? sanitize_text_field($_POST['skill']) : ''
                    );
                // }
            }
        }

        if (isset($_POST['submit_questions_csv']) && isset($_FILES['questions_csv'])) {
            if (function_exists('practice_tests_handle_csv_upload')) {
                 // Add nonce check
                practice_tests_handle_csv_upload($_FILES['questions_csv']);
            }
        }

         if (isset($_POST['submit_single_user'])) {
            if (function_exists('practice_tests_add_single_user')) {
                 // Add nonce check
                 // Note: Redirect in this function might still be problematic in admin context.
                practice_tests_add_single_user(
                    isset($_POST['user_name']) ? sanitize_text_field($_POST['user_name']) : '',
                    isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '',
                    isset($_POST['user_password']) ? $_POST['user_password'] : '',
                    isset($_POST['useraction']) ? sanitize_key($_POST['useraction']) : 'add',
                    isset($_POST['userstatus']) ? sanitize_key($_POST['userstatus']) : 'active',
                    isset($_POST['user_role']) ? sanitize_key($_POST['user_role']) : 'student'
                );
            }
        }

        if (isset($_POST['submit_users_csv']) && isset($_FILES['users_csv'])) {
            if (function_exists('practice_tests_handle_users_csv_upload')) {
                 // Add nonce check
                practice_tests_handle_users_csv_upload($_FILES['users_csv']);
            }
        }

    } // end is_admin()

    // --- Frontend Specific / Global ---

    // Login handling (can happen outside admin, often before plugins_loaded on form submission)
    // This check might be better placed outside the init function or hooked earlier if needed immediately on POST.
    // However, the function it calls needs db-functions.php included.
    if (isset($_POST['login_user_email']) && isset($_POST['login_user_password'])) {
        if (function_exists('practice_tests_custom_login')) {
            practice_tests_custom_login($_POST['login_user_email'], $_POST['login_user_password']);
        }
    }

    // Enqueue scripts (can be hooked here or later like wp_enqueue_scripts)
    add_action('wp_enqueue_scripts', 'enqueue_email_validation_scripts');
    add_action('wp_enqueue_scripts', 'enqueue_custom_scripts'); // For redirect

    // AJAX Actions (need to be hooked for both logged-in and non-logged-in users if applicable)
    add_action('wp_ajax_check_email', 'check_email_existence');
    add_action('wp_ajax_nopriv_check_email', 'check_email_existence');

    // AJAX actions from core-functions (Quiz interaction & Member area loading)
    // Ensure these functions are defined in core-functions.php before adding hooks
    if (function_exists('practice_test_save_answer_callback')) {
        add_action('wp_ajax_save_practice_test_answer', 'practice_test_save_answer_callback');
    }
    if (function_exists('practice_test_pause_test_callback')) {
        add_action('wp_ajax_pause_practice_test', 'practice_test_pause_test_callback');
    }
    if (function_exists('practice_test_submit_test_callback')) {
        add_action('wp_ajax_submit_practice_test', 'practice_test_submit_test_callback');
    }
    if (function_exists('practice_test_load_member_content_callback')) {
        add_action('wp_ajax_load_member_content', 'practice_test_load_member_content_callback');
    }
     if (function_exists('practice_test_link_student_account_callback')) {
        add_action('wp_ajax_link_student_account', 'practice_test_link_student_account_callback');
    }

}
add_action('plugins_loaded', 'practice_tests_plugin_init');


// --- Global Helper Functions (if any, keep minimal) ---

// Example: Moved script enqueue functions here, hooked within init

function enqueue_email_validation_scripts() {
    // Consider adding checks like is_page('registration') before enqueueing
    wp_enqueue_script('reg-email-validation', plugin_dir_url(__FILE__) .'email-validation.js', array('jquery'), '1.0', true);
    wp_localize_script('reg-email-validation', 'my_plugin_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
}

function check_email_existence() {
    global $wpdb;
    // Basic nonce check example - add nonce to your JS ajax call
    // check_ajax_referer('your_email_check_nonce', 'nonce');
    $email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
    if (empty($email)) {
        wp_send_json_error('Email not provided.');
    }

    $table_subs = $wpdb->prefix . 'practice_test_subs';
    $query = $wpdb->prepare("SELECT COUNT(*) FROM $table_subs WHERE user_email = %s", $email);
    $count = $wpdb->get_var($query);

    if ($count > 0) {
        wp_send_json_success('exists'); // Use wp_send_json_* for consistency
    } else {
        wp_send_json_success('not_exists');
    }
    // wp_die(); // wp_send_json_* handles this
}


function enqueue_custom_scripts() {
    if (is_page('login-success')) { // Check if it's the login-success page
        wp_enqueue_script(
            'custom-redirect',
            plugin_dir_url(__FILE__) . 'redirect.js',
            array(), // Dependencies
            '1.0.0', // Version
            true // In footer
        );
    }
}
