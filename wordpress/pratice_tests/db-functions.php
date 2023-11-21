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