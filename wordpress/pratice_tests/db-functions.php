<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// Function to create tables
function create_plugin_database_tables() {
    global $wpdb;

    // Define table names
    $table_act = $wpdb->prefix . 'practice_test_act';
    $table_sat = $wpdb->prefix . 'practice_test_sat';

    // SQL to create your tables
    $sql_act = "CREATE TABLE $table_act (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        question text NOT NULL,
        answer text NOT NULL,
        PRIMARY KEY  (id)
    );";

    $sql_sat = "CREATE TABLE $table_sat (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        question text NOT NULL,
        answer text NOT NULL,
        PRIMARY KEY  (id)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_act);
    dbDelta($sql_sat);
}

// Function to add a single question to the database
function practice_tests_add_single_question($question, $answer) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_test_act'; // Change to your table name

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
    $table_name = $wpdb->prefix . 'practice_test_act'; // Change to your table name

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