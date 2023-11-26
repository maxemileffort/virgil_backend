<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// Function to create tables
function create_plugin_database_tables() {
    global $wpdb;

    // Define table names
    $table_questions = $wpdb->prefix . 'practice_test_questions';
    $table_subs = $wpdb->prefix . 'practice_test_subs';
    $table_tests = $wpdb->prefix . 'practice_test_tests';

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
        user_name varchar(50) NOT NULL,
        user_email varchar(100) NOT NULL UNIQUE,
        user_password varchar(255) NOT NULL,
        user_role varchar(50) NOT NULL,
        user_status varchar(50) NOT NULL,
        registration_date datetime DEFAULT CURRENT_TIMESTAMP,
        tests_taken int(5) NOT NULL,
        questions_answered int(5) NOT NULL,
        best_subject text NOT NULL,
        worst_subject text NOT NULL,
        fast_subject text NOT NULL,
        slow_subject text NOT NULL,
        plan text NOT NULL,
        PRIMARY KEY  (id)
    );";

    // Creating table for Tests
    $tests_sql = "CREATE TABLE $table_tests (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        test_name varchar(255) NOT NULL,
        test_description text,
        test_type varchar(50) NOT NULL,
        test_questions JSON default null,
        PRIMARY KEY  (id)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_questions);
    dbDelta($sql_subs);
    dbDelta($tests_sql);
}

// Function to add a single question to the database
function practice_tests_add_single_question($testtype,$passage,
                                            $question, $answerchoices1,
                                            $answerchoices2,$answerchoices3,
                                            $answerchoices4,$answerchoices5,
                                            $answercorrect,$explanation,
                                            $subjectarea,$skill,) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_test_questions'; // Change to your table name

    // Insert into database
    $wpdb->insert(
        $table_name,
        array('testtype'=>$testtype,'passage'=>$passage,
        'question'=>$question, 'answerchoices1'=>$answerchoices1,
        'answerchoices2'=>$answerchoices2,'answerchoices3'=>$answerchoices3,
        'answerchoices4'=>$answerchoices4,'answerchoices5'=>$answerchoices5,
        'answercorrect'=>$answercorrect,'explanation'=>$explanation,
        'subjectarea'=>$subjectarea,'skill'=>$skill)
    );
}

// Function to handle CSV upload
function practice_tests_handle_csv_upload($file) {
    // Check for file upload errors and validate file type (CSV)
    if ($file['error'] != UPLOAD_ERR_OK) {
        echo "Error uploading file.";
        return;
    }

    // Read the file
    if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
        $isHeaderRow = true; // Flag to check if it's the first row (header)
        while (($data = fgetcsv($handle, 1000, "|")) !== FALSE) {

            // Skip the header row
            if ($isHeaderRow) {
                $isHeaderRow = false;
                continue;
            }

            // Assuming the CSV columns are question, answer
            $testtype = $data[0];
            $passage = $data[1];
            $question = $data[2];
            $answerchoices1 = $data[3];
            $answerchoices2 = $data[4];
            $answerchoices3 = $data[5];
            $answerchoices4 = $data[6];
            $answerchoices5 = $data[7];
            $answercorrect = $data[8];
            $explanation = $data[9];
            $subjectarea = $data[10];
            $skill = $data[11];

            // Insert into database
            practice_tests_add_single_question($testtype,$passage,
                                            $question, $answerchoices1,
                                            $answerchoices2,$answerchoices3,
                                            $answerchoices4,$answerchoices5,
                                            $answercorrect,$explanation,
                                            $subjectarea,$skill);
        }
        fclose($handle);
    }
}

// Function to add a single user to the database
function practice_tests_add_single_user($user_name, $user_email,
                                        $user_password, $user_action,
                                        $user_status,$user_role,$plan) {
    global $wpdb;
    require_once(ABSPATH . 'wp-includes/pluggable.php');

    $username = sanitize_text_field($user_name);
    $email = sanitize_email($user_email);
    $password = wp_hash_password($user_password); // Securely hash the password
    $table_subs = $wpdb->prefix . 'practice_test_subs'; 

    // Prepare the data for insertion
    $data = array(
        'user_name' => $username,
        'user_email' => $email,
        'user_password' => $password,
        'user_role' => $user_role,
        'user_status' => 'active', // Set default values for other fields
        'tests_taken' => 0,
        'questions_answered' => 0,
        'best_subject' => '',
        'worst_subject' => '',
        'fast_subject' => '',
        'slow_subject' => '',
        'plan' => 'free' // Set the plan to Free
    );

    if ($user_action=='add'){
        // Insert the data into the custom table
        $wpdb->insert($table_subs, $data);

        // Redirect or display a success message
        echo 'Registration successful!';
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
 * Get a Specific Subscriber by ID
 */
function get_sub($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_subs';

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
}

/**
 * Update a Subscriber
 */
function update_sub($id, $sub_email, $sub_password, $sub_role, $sub_status) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_subs';

    $wpdb->update(
        $table_name,
        array(
            'sub_email' => $sub_email,
            'sub_password' => wp_hash_password($sub_password),
            'sub_role' => $sub_role,
            'sub_status' => $sub_status
        ),
        array('id' => $id)
    );
}

/**
 * Delete a Subscriber
 */
function delete_sub($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'practice_subs';

    $wpdb->delete($table_name, array('id' => $id));
}