<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// Function to create tables
function create_plugin_database_tables() {
    global $wpdb;

    // Define table names
    $table_questions = $wpdb->prefix . 'practice_test_questions'; // Stores individual questions
    $table_subs = $wpdb->prefix . 'practice_test_subs'; // Stores user/subscriber data
    $table_tests = $wpdb->prefix . 'practice_test_tests'; // Defines specific tests (metadata)
    $table_test_questions = $wpdb->prefix . 'practice_test_test_questions'; // Links tests to questions (structure)
    $table_test_attempts = $wpdb->prefix . 'practice_test_test_attempts'; // Tracks user attempts at tests
    $table_test_resp = $wpdb->prefix . 'practice_test_test_resp'; // Stores user responses for each question in an attempt

    $charset_collate = $wpdb->get_charset_collate();

    // SQL to create Questions table
    $sql_questions = "CREATE TABLE IF NOT EXISTS $table_questions (
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
    ) $charset_collate;";

    // SQL to create Subscribers table
    $sql_subs = "CREATE TABLE IF NOT EXISTS $table_subs (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_name varchar(50) NOT NULL,
        user_email varchar(100) NOT NULL UNIQUE,
        user_password varchar(255) NOT NULL,
        user_role varchar(50) NOT NULL, -- 'guardian' or 'student'
        user_status varchar(50) NOT NULL DEFAULT 'active', -- 'active', 'paused', 'inactive'
        registration_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        plan varchar(50) NOT NULL DEFAULT 'free', -- e.g., 'free', 'premium'
        tests_taken int(11) DEFAULT 0 NOT NULL,
        questions_answered int(11) DEFAULT 0 NOT NULL,
        best_subject varchar(100) DEFAULT '' NOT NULL,
        worst_subject varchar(100) DEFAULT '' NOT NULL,
        fast_subject varchar(100) DEFAULT '' NOT NULL,
        slow_subject varchar(100) DEFAULT '' NOT NULL,
        PRIMARY KEY  (id),
        KEY user_email (user_email)
    ) $charset_collate;";

    // SQL to create Tests table (metadata)
    $sql_tests = "CREATE TABLE IF NOT EXISTS $table_tests (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        test_name varchar(255) NOT NULL,
        test_description text,
        test_type varchar(50) NOT NULL, -- e.g., 'ACT', 'SAT'
        date_created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        last_modified datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // SQL to create Test Questions table (linking tests to questions)
    $sql_test_questions = "CREATE TABLE IF NOT EXISTS $table_test_questions (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        test_id mediumint(9) NOT NULL,
        question_id mediumint(9) NOT NULL,
        question_order mediumint(9) NOT NULL, -- Order of the question within the test
        PRIMARY KEY  (id),
        KEY test_id (test_id),
        KEY question_id (question_id)
        -- Consider adding FOREIGN KEY constraints if InnoDB is guaranteed
        -- FOREIGN KEY (test_id) REFERENCES $table_tests(id) ON DELETE CASCADE,
        -- FOREIGN KEY (question_id) REFERENCES $table_questions(id) ON DELETE CASCADE
    ) $charset_collate;";

    // SQL to create Test Attempts table
    $sql_test_attempts = "CREATE TABLE IF NOT EXISTS $table_test_attempts (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        test_id mediumint(9) NOT NULL,
        user_id mediumint(9) NOT NULL,
        attempt_status varchar(50) NOT NULL DEFAULT 'started', -- 'started', 'paused', 'completed'
        start_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        end_time datetime,
        score decimal(5,2), -- e.g., percentage score
        PRIMARY KEY  (id),
        KEY test_id (test_id),
        KEY user_id (user_id)
        -- Consider adding FOREIGN KEY constraints
        -- FOREIGN KEY (test_id) REFERENCES $table_tests(id) ON DELETE CASCADE,
        -- FOREIGN KEY (user_id) REFERENCES $table_subs(id) ON DELETE CASCADE
    ) $charset_collate;";

    // SQL to create Test Responses table
    $sql_test_resp = "CREATE TABLE IF NOT EXISTS $table_test_resp (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        attempt_id mediumint(9) NOT NULL,
        question_id mediumint(9) NOT NULL,
        user_answer text, -- Store the selected answer choice (e.g., 'A', 'B', or the text)
        is_correct tinyint(1), -- 1 for correct, 0 for incorrect, NULL if not graded/applicable
        response_time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY attempt_id (attempt_id),
        KEY question_id (question_id)
        -- Consider adding FOREIGN KEY constraints
        -- FOREIGN KEY (attempt_id) REFERENCES $table_test_attempts(id) ON DELETE CASCADE,
        -- FOREIGN KEY (question_id) REFERENCES $table_questions(id) ON DELETE CASCADE
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_questions);
    dbDelta($sql_subs);
    dbDelta($sql_tests);
    dbDelta($sql_test_questions);
    dbDelta($sql_test_attempts);
    dbDelta($sql_test_resp);
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
                                        $user_status,$user_role) {
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
        wp_redirect(home_url('/login-success'));
        exit;
    }

    
}

// Function to handle User CSV upload (FIXED)
function practice_tests_handle_users_csv_upload($file) {
    global $wpdb;
    $table_subs = $wpdb->prefix . 'practice_test_subs'; // Corrected table name

    // Check for file upload errors and validate file type (CSV)
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        echo "No file uploaded or invalid upload.";
        return;
    }
    if ($file['error'] != UPLOAD_ERR_OK) {
        echo "Error uploading file: " . $file['error'];
        return;
    }

    // Basic MIME type check (can be spoofed, but better than nothing)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if ($mime_type !== 'text/plain' && $mime_type !== 'text/csv' && $mime_type !== 'application/csv') {
         echo "Invalid file type: " . $mime_type;
         return;
    }


    // Read the file
    if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
        $isHeaderRow = true; // Flag to skip header row
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) { // Assuming comma delimiter
             // Skip the header row
            if ($isHeaderRow) {
                $isHeaderRow = false;
                continue;
            }

            // Basic check for expected number of columns (adjust as needed)
            if (count($data) < 4) { // Expecting at least name, email, password, role
                echo "Skipping row due to insufficient columns: " . implode(',', $data);
                continue;
            }

            // Assuming the CSV columns are: user_name, user_email, user_password, user_role, [user_status], [plan]
            $user_name = sanitize_text_field($data[0]);
            $user_email = sanitize_email($data[1]);
            $user_password = wp_hash_password($data[2]); // Hash password
            $user_role = sanitize_text_field($data[3]);
            $user_status = isset($data[4]) ? sanitize_text_field($data[4]) : 'active'; // Default status
            $plan = isset($data[5]) ? sanitize_text_field($data[5]) : 'free'; // Default plan

            // Validate required fields
            if (empty($user_name) || empty($user_email) || empty($user_password) || empty($user_role)) {
                 echo "Skipping row due to missing required fields: " . implode(',', $data);
                 continue;
            }

            // Check if email already exists
            $existing_user = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_subs WHERE user_email = %s", $user_email));
            if ($existing_user) {
                echo "Skipping row: Email already exists - " . esc_html($user_email);
                continue;
            }

            // Prepare data for insertion
            $insert_data = array(
                'user_name' => $user_name,
                'user_email' => $user_email,
                'user_password' => $user_password,
                'user_role' => $user_role,
                'user_status' => $user_status,
                'plan' => $plan,
                'tests_taken' => 0, // Default values
                'questions_answered' => 0,
                'best_subject' => '',
                'worst_subject' => '',
                'fast_subject' => '',
                'slow_subject' => ''
            );

            // Insert into database
            $result = $wpdb->insert($table_subs, $insert_data);

            if ($result === false) {
                 echo "Failed to insert user: " . esc_html($user_email) . " - Error: " . $wpdb->last_error;
            } else {
                 echo "Successfully inserted user: " . esc_html($user_email);
            }
            echo "<br>"; // For readability in admin notice
        }
        fclose($handle);
        echo "User CSV processing complete.";
    } else {
        echo "Error opening uploaded file.";
    }
}

function practice_tests_custom_login($email, $password) {
    global $wpdb;
    require_once(ABSPATH . 'wp-includes/pluggable.php');

    $email = sanitize_email($email);
    $table_subs = $wpdb->prefix . 'practice_test_subs';

    // Query the database for the user
    $user = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_subs WHERE user_email = %s",
        $email
    ));

    if ($user) {
        // Check if the provided password matches the stored hash
        if (wp_check_password($password, $user->user_password)) {
            // Password is correct
            // You can set the user session or cookies here if needed
            echo 'Login successful!';
            wp_redirect(home_url('/login-success'));
            exit;
        } else {
            // Password is incorrect
            echo 'Incorrect password!';
        }
    } else {
        // No user found with that email
        echo 'No user found with that email!';
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

function deactivate(){
    // Access the global variable
    global $wpdb;

    // Define the table names
    $table_act = $wpdb->prefix . 'practice_test_questions';
    $table_subs = $wpdb->prefix . 'practice_test_subs';

    // SQL to drop tables
    $sql_questions = "DROP TABLE IF EXISTS $table_questions;";
    $sql_subs = "DROP TABLE IF EXISTS $table_subs;";

    // // Execute the SQL
    // $wpdb->query($sql_questions);
    // $wpdb->query($$sql_subs);
}
