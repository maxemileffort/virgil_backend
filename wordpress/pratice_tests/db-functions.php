<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// Function to create tables
function create_plugin_database_tables() {
    global $wpdb;

    // Define table names
    $table_questions = $wpdb->prefix . 'practice_test_questions';
    $table_subs = $wpdb->prefix . 'practice_test_subs';

    // SQL to create your tables
    $sql_questions = "CREATE TABLE $table_questions (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        testtype text not null,
        passage text not null,
        question text NOT NULL,
        answercorrect text NOT NULL,
        answerchoices1 text NOT NULL,
        answerchoices2 text NOT NULL,
        answerchoices3 text NOT NULL,
        answerchoices4 text NOT NULL,
        answerchoices5 text NOT NULL,
        explanation text NOT NULL,
        subjectarea text NOT NULL,
        skill text NOT NULL,
        PRIMARY KEY  (id)
    );";

    $sql_subs = "CREATE TABLE $table_subs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        username text not null,
        email text NOT NULL,
        password text NOT NULL,
        status text NOT NULL,
        tests_taken int(5) NOT NULL,
        questions_answered int(5) NOT NULL,
        best_subject text NOT NULL,
        worst_subject text NOT NULL,
        PRIMARY KEY  (id)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_questions);
    dbDelta($sql_subs);
}

// Function to add a single question to the database
function practice_tests_add_single_question($question, $answer) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_test_questions'; // Change to your table name

    // Sanitize data
    $question = sanitize_text_field($question);
    $answer = sanitize_text_field($answer);

    // Insert into database
    $wpdb->insert(
        $table_name,
        array('question' => $question, 'answer' => $answer),
        array('%s', '%s')
    );
}

// Function to add a single user to the database
function practice_tests_add_single_user($question, $answer) {
    # TODO - match user form
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_test_subs'; // Change to your table name

    // Sanitize data
    $question = sanitize_text_field($question);
    $answer = sanitize_text_field($answer);

    // Insert into database
    $wpdb->insert(
        $table_name,
        array('question' => $question, 'answer' => $answer),
        array('%s', '%s')
    );
}

// Function to handle CSV upload
function practice_tests_handle_csv_upload($file) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_test_questions'; // Change to your table name

    // Check for file upload errors and validate file type (CSV)
    if ($file['error'] != UPLOAD_ERR_OK) {
        echo "Error uploading file.";
        return;
    }

    // Read the file
    if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Assuming the CSV columns are question, answer
            $question = sanitize_text_field($data[0]);
            $answer = sanitize_text_field($data[1]);

            // Insert into database
            $wpdb->insert(
                $table_name,
                array('question' => $question, 'answer' => $answer),
                array('%s', '%s')
            );
        }
        fclose($handle);
    }
}

// Function to handle CSV upload
function practice_tests_handle_users_csv_upload($file) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_test_questions'; // Change to your table name

    // Check for file upload errors and validate file type (CSV)
    if ($file['error'] != UPLOAD_ERR_OK) {
        echo "Error uploading file.";
        return;
    }

    // Read the file
    if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Assuming the CSV columns are question, answer
            $question = sanitize_text_field($data[0]);
            $answer = sanitize_text_field($data[1]);

            // Insert into database
            $wpdb->insert(
                $table_name,
                array('question' => $question, 'answer' => $answer),
                array('%s', '%s')
            );
        }
        fclose($handle);
    }
}

global $wpdb;

/**
 * Create Tables for Managing Questions
 */
function create_questions_table() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $table_name = $wpdb->prefix . 'practice_questions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        question_type varchar(50) NOT NULL,
        question_content text NOT NULL,
        answer_options text,
        correct_answer varchar(255),
        explanation text,
        subject_area varchar(100),
        PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta($sql);
}

/**
 * Add a New Question
 */
function add_question($question_type, $question_content, $answer_options, $correct_answer, $explanation, $subject_area) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_questions';

    $wpdb->insert(
        $table_name,
        array(
            'question_type' => $question_type,
            'question_content' => $question_content,
            'answer_options' => maybe_serialize($answer_options),
            'correct_answer' => $correct_answer,
            'explanation' => $explanation,
            'subject_area' => $subject_area
        )
    );
}

/**
 * Get a Specific Question by ID
 */
function get_question($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_questions';

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
}

/**
 * Update a Question
 */
function update_question($id, $question_type, $question_content, $answer_options, $correct_answer, $explanation, $subject_area) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_questions';

    $wpdb->update(
        $table_name,
        array(
            'question_type' => $question_type,
            'question_content' => $question_content,
            'answer_options' => maybe_serialize($answer_options),
            'correct_answer' => $correct_answer,
            'explanation' => $explanation,
            'subject_area' => $subject_area
        ),
        array('id' => $id)
    );
}

/**
 * Delete a Question
 */
function delete_question($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_questions';

    $wpdb->delete($table_name, array('id' => $id));
}

/**
 * Create Tables for Managing Tests and Users
 */
function create_tests_users_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Creating table for Tests
    $tests_table_name = $wpdb->prefix . 'practice_tests';
    $tests_sql = "CREATE TABLE $tests_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        test_name varchar(255) NOT NULL,
        test_description text,
        test_type varchar(50) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Creating table for Users
    $users_table_name = $wpdb->prefix . 'practice_users';
    $users_sql = "CREATE TABLE $users_table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_email varchar(100) NOT NULL UNIQUE,
        user_password varchar(255) NOT NULL,
        user_role varchar(50) NOT NULL,
        user_status varchar(50) NOT NULL,
        registration_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    dbDelta($tests_sql);
    dbDelta($users_sql);
}

/**
 * Add a New Test
 */
function add_test($test_name, $test_description, $test_type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_tests';

    $wpdb->insert(
        $table_name,
        array(
            'test_name' => $test_name,
            'test_description' => $test_description,
            'test_type' => $test_type
        )
    );
}

/**
 * Get a Specific Test by ID
 */
function get_test($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_tests';

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
}

/**
 * Update a Test
 */
function update_test($id, $test_name, $test_description, $test_type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_tests';

    $wpdb->update(
        $table_name,
        array(
            'test_name' => $test_name,
            'test_description' => $test_description,
            'test_type' => $test_type
        ),
        array('id' => $id)
    );
}

/**
 * Delete a Test
 */
function delete_test($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_tests';

    $wpdb->delete($table_name, array('id' => $id));
}

/**
 * Add a New User
 */
function add_user($user_email, $user_password, $user_role, $user_status) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_users';

    $wpdb->insert(
        $table_name,
        array(
            'user_email' => $user_email,
            'user_password' => wp_hash_password($user_password),
            'user_role' => $user_role,
            'user_status' => $user_status
        )
    );
}

/**
 * Get a Specific User by ID
 */
function get_user($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_users';

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
}

/**
 * Update a User
 */
function update_user($id, $user_email, $user_password, $user_role, $user_status) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_users';

    $wpdb->update(
        $table_name,
        array(
            'user_email' => $user_email,
            'user_password' => wp_hash_password($user_password),
            'user_role' => $user_role,
            'user_status' => $user_status
        ),
        array('id' => $id)
    );
}

/**
 * Delete a User
 */
function delete_user($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_users';

    $wpdb->delete($table_name, array('id' => $id));
}