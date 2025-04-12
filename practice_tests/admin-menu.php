<?php

if ( ! defined( 'ABSPATH' ) ) exit;

include_once plugin_dir_path(__FILE__) . 'utilities.php';

function practice_tests_plugin_menu() {
    add_menu_page('Practice Tests Settings', 'Practice Tests', 'manage_options', 'practice-tests-plugin', 'practice_tests_dashboard_page');

    // Other submenus
    add_submenu_page('practice-tests-plugin', 'Add Question', 'Add Question', 'manage_options', 'practice-tests-add-question', 'practice_tests_add_question_page');
    add_submenu_page('practice-tests-plugin', 'View/Edit Question', 'View/Edit Question', 'manage_options', 'practice-tests-view-edit-question', 'practice_tests_view_edit_question_page');
    add_submenu_page('practice-tests-plugin', 'Define Test', 'Define Test', 'manage_options', 'practice-tests-define-test', 'practice_tests_define_test_page'); // New page
    add_submenu_page('practice-tests-plugin', 'Manage Users', 'Manage Users', 'manage_options', 'practice-tests-manage-users', 'practice_tests_manage_users_page');
    // ... add other submenus ...
}

// Function to handle the form submission for defining a test
function practice_tests_handle_define_test_submission() {
    if (!isset($_POST['submit_define_test']) || !isset($_POST['practice_tests_define_test_nonce']) || !wp_verify_nonce($_POST['practice_tests_define_test_nonce'], 'practice_tests_define_test_action')) {
        return; // Nonce check failed or form not submitted
    }

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized user');
    }

    global $wpdb;
    $table_tests = $wpdb->prefix . 'practice_test_tests';
    $table_test_questions = $wpdb->prefix . 'practice_test_test_questions';

    // Sanitize and retrieve test metadata
    $test_name = isset($_POST['test_name']) ? sanitize_text_field($_POST['test_name']) : '';
    $test_description = isset($_POST['test_description']) ? sanitize_textarea_field($_POST['test_description']) : '';
    $test_type = isset($_POST['test_type']) ? sanitize_text_field($_POST['test_type']) : ''; // e.g., ACT, SAT
    $selected_questions = isset($_POST['selected_questions']) ? array_map('intval', $_POST['selected_questions']) : array(); // Array of question IDs

    if (empty($test_name) || empty($test_type) || empty($selected_questions)) {
        // Add an admin notice: error - missing fields
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>Error: Test Name, Test Type, and at least one Question must be provided.</p></div>';
        });
        return;
    }

    // 1. Insert into tests table
    $test_inserted = $wpdb->insert(
        $table_tests,
        array(
            'test_name' => $test_name,
            'test_description' => $test_description,
            'test_type' => $test_type
        ),
        array('%s', '%s', '%s')
    );

    if ($test_inserted === false) {
         add_action('admin_notices', function() use ($wpdb) {
            echo '<div class="notice notice-error is-dismissible"><p>Error creating test definition: ' . esc_html($wpdb->last_error) . '</p></div>';
        });
        return;
    }

    $test_id = $wpdb->insert_id; // Get the ID of the newly created test

    // 2. Insert into test_questions linking table
    $order = 1;
    $errors = array();
    foreach ($selected_questions as $question_id) {
        $question_inserted = $wpdb->insert(
            $table_test_questions,
            array(
                'test_id' => $test_id,
                'question_id' => $question_id,
                'question_order' => $order++
            ),
            array('%d', '%d', '%d')
        );
        if ($question_inserted === false) {
            $errors[] = "Error linking question ID " . esc_html($question_id) . ": " . esc_html($wpdb->last_error);
        }
    }

    // Display success or error messages
    if (empty($errors)) {
        add_action('admin_notices', function() use ($test_name) {
            echo '<div class="notice notice-success is-dismissible"><p>Test "' . esc_html($test_name) . '" defined successfully!</p></div>';
        });
    } else {
         add_action('admin_notices', function() use ($errors) {
            echo '<div class="notice notice-error is-dismissible"><p>Test definition saved, but with errors linking questions:</p><ul>';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul></div>';
        });
    }
}
// Hook the handler to admin_post_{action} or admin_init
add_action('admin_init', 'practice_tests_handle_define_test_submission');


// Function to display the Define Test page
function practice_tests_define_test_page() {
     global $wpdb;
     $table_questions = $wpdb->prefix . 'practice_test_questions';

     // Fetch all questions to allow selection
     // In a real application, add pagination/search/filtering here for performance
     $all_questions = $wpdb->get_results("SELECT id, question, testtype, subjectarea, skill FROM $table_questions ORDER BY id DESC");

    ?>
    <div class="wrap">
        <h2>Define New Practice Test</h2>
        <p>Create a new test by providing metadata and selecting questions from the bank.</p>

        <form method="post" action=""> <?php // Form submits to the current page, handled by admin_init hook ?>
            <?php wp_nonce_field('practice_tests_define_test_action', 'practice_tests_define_test_nonce'); ?>

            <h3>Test Details</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="test_name">Test Name:</label></th>
                    <td><input type="text" id="test_name" name="test_name" class="regular-text" required></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="test_description">Description:</label></th>
                    <td><textarea id="test_description" name="test_description" rows="4" class="regular-text"></textarea></td>
                </tr>
                 <tr valign="top">
                    <th scope="row"><label for="test_type">Test Type:</label></th>
                    <td>
                        <select name="test_type" id="test_type" required>
                            <option value="">-- Select Type --</option>
                            <option value="ACT">ACT</option>
                            <option value="SAT">SAT</option>
                            <option value="Other">Other</option> <?php // Add more types if needed ?>
                        </select>
                    </td>
                </tr>
            </table>

            <h3>Select Questions</h3>
            <p>Check the questions to include in this test. The order will be based on selection order (or implement drag/drop later).</p>
             <?php if (empty($all_questions)): ?>
                <p>No questions found in the bank. Please <a href="<?php echo admin_url('admin.php?page=practice-tests-add-question'); ?>">add questions</a> first.</p>
            <?php else: ?>
                <div style="max-height: 400px; overflow-y: scroll; border: 1px solid #ccc; padding: 10px; margin-bottom: 15px;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 5%;">Select</th>
                                <th style="width: 5%;">ID</th>
                                <th>Question Text</th>
                                <th>Type</th>
                                <th>Subject</th>
                                <th>Skill</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_questions as $question): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_questions[]" value="<?php echo esc_attr($question->id); ?>"></td>
                                    <td><?php echo esc_html($question->id); ?></td>
                                    <td><?php echo esc_html(wp_trim_words($question->question, 20, '...')); ?></td>
                                    <td><?php echo esc_html($question->testtype); ?></td>
                                    <td><?php echo esc_html($question->subjectarea); ?></td>
                                    <td><?php echo esc_html($question->skill); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                 <?php submit_button('Define Test', 'primary', 'submit_define_test'); ?>
            <?php endif; ?>
        </form>
    </div>
    <?php
}

function practice_tests_manage_users_page() {
    ?>
    <div class="wrap">
        <h2>User Management</h2>
            <form method="post" action="" enctype="multipart/form-data">
                <!-- Single User Addition -->
                <h3>Add a Single User</h3>
                <p>
                    <label for="user_name">Name:</label>
                    <input type="text" name="user_name" id="user_name">
                    <label for="user_email">Email:</label>
                    <input type="text" name="user_email" id="user_email">
                </p>
                
                <p>
                    <label for="userstatus">Status:</label>
                    <select name="userstatus" id="userstatus">
                        <option value="active">Active</option>
                        <option value="paused">Paused</option>
                    </select>
                    <label for="useraction">Action:</label>
                    <select name="useraction" id="useraction">
                        <option value="add">Add</option>
                        <option value="update">Update</option>
                        <option value="reset">Reset</option>
                        <option value="---">-------</option>
                        <option value="delete">Delete</option>
                    </select>
                </p>
                <input type="submit" name="submit_single_user" value="Add user">

                <!-- User CSV Upload -->
                <h3>Upload Users via CSV</h3>
                <p>
                    <input type="file" name="users_csv" id="users_csv">
                </p>
                <input type="submit" name="submit_users_csv" value="Upload CSV">
            </form>
            <?php
            render_users_table(); // inside utilities.php
            ?>
    </div>
    <?php
}

// Function to display the plugin admin page
function practice_tests_add_question_page() {
    ?>
    <div class="wrap">
        <h2>Practice Tests</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <!-- Single Question Addition -->
            <h3>Add a Single Question</h3>
            <p>
                <label for="testtype">Test Type:</label>
                <select name="testtype" id="testtype">
                        <option value="ACT">ACT</option>
                        <option value="SAT">SAT</option>
                    </select>
            </p>
            <p>
                <label for="passage">Passage:</label>
                <input type="text" name="passage" id="passage" value=" ">
            </p>
            <p>
                <label for="question">Question:</label>
                <input type="text" name="question" id="question">
            </p>
            <p>
                <label for="answerchoices">Answer Choices:
                    <input type="text" name="answerchoices1">
                    <input type="text" name="answerchoices2">
                    <input type="text" name="answerchoices3">
                    <input type="text" name="answerchoices4">
                    <input type="text" name="answerchoices5">
                </label>
            </p>
            <p>
                <label for="answercorrect">Correct Answer:</label>
                <input type="text" name="answercorrect" id="answercorrect">
            </p>
            <p>
                <label for="explanation">Explanation:</label>
                <input type="text" name="explanation" id="explanation">
            </p>
            <p>
                    <label for="subjectarea">Subject Area:</label>
                    <input type="text" name="subjectarea" id="subjectarea">
                    <label for="skill">Skill:</label>
                    <input type="text" name="skill" id="skill">
                </p>
            <input type="submit" name="submit_single_question" value="Add Question">

            <!-- CSV Upload -->
            <h3>Upload Questions via CSV</h3>
            <p>
                <input type="file" name="questions_csv" id="questions_csv">
            </p>
            <input type="submit" name="submit_questions_csv" value="Upload CSV">
        </form>
    </div>
    <?php
}

// Function to display the plugin admin page
function practice_tests_view_edit_question_page() {
    ?>
    <div class="wrap">
        <h2>View / Edit Questions</h2>
        <p>This page will have a dropdown to select tables with questions. The table will render and the questions, answers, correct answer, and explanations will all be editable.</p>
        <?php
            render_question_bank_table(); // inside utilities.php
        ?>
    </div>
    <?php
}

// Function to render the dashboard page
function practice_tests_dashboard_page() {
    ?>
    <div class="wrap">
        <h2>Practice Tests Dashboard</h2>
        <p>This is the dashboard page. Analytics and other information will be displayed here.</p>
    </div>
    <?php
}
