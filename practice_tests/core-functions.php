<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// Enqueue quiz handler script and localize data
function enqueue_quiz_handler_scripts() {
    // Only enqueue on pages where the shortcode might be used (or enqueue globally if simpler)
    // A better approach might be to enqueue only when the shortcode function runs, see below.
    wp_register_script('practice-test-quiz-handler', plugin_dir_url(__FILE__) . 'js/quiz-handler.js', array('jquery'), '1.0.0', true);

    // Create nonce here to be passed to script
    $quiz_nonce = wp_create_nonce('practice_test_quiz_nonce');

    wp_localize_script('practice-test-quiz-handler', 'practiceTestQuiz', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => $quiz_nonce
        // We will add attempt_id dynamically within the shortcode output if needed,
        // or potentially pass it here if we can determine it before enqueueing.
    ));

    // Register the members area handler script (will be enqueued specifically in the shortcode)
     wp_register_script('practice-test-members-handler', plugin_dir_url(__FILE__) . 'js/members-handler.js', array('jquery'), '1.0.0', true);

}
add_action('wp_enqueue_scripts', 'enqueue_quiz_handler_scripts');


function practice_test_quiz_shortcode($atts) {
    global $wpdb;

    // 1. Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to take the practice test.</p>';
    }
    $user_id = get_current_user_id();

    // 2. Parse Shortcode Attributes (Expecting test_id)
    $atts = shortcode_atts(array(
        'test_id' => 0, // Default to 0, indicating no test specified
    ), $atts, 'practice_test_quiz');

    $test_id = intval($atts['test_id']);

    if ($test_id <= 0) {
        return '<p>Error: No valid test ID specified.</p>';
    }

    // 3. Get Test Metadata (Optional, but good for context)
    $table_tests = $wpdb->prefix . 'practice_test_tests';
    $test_info = $wpdb->get_row($wpdb->prepare("SELECT test_name, test_type FROM $table_tests WHERE id = %d", $test_id));
    if (!$test_info) {
         return '<p>Error: Test not found.</p>';
    }

    // 4. Find or Create Test Attempt
    $table_attempts = $wpdb->prefix . 'practice_test_test_attempts';
    $attempt = $wpdb->get_row($wpdb->prepare(
        "SELECT id, attempt_status FROM $table_attempts WHERE test_id = %d AND user_id = %d AND attempt_status IN ('started', 'paused') ORDER BY start_time DESC LIMIT 1",
        $test_id, $user_id
    ));

    $attempt_id = 0;
    if ($attempt) {
        // Resume existing attempt
        $attempt_id = $attempt->id;
        // Optionally update status from 'paused' to 'started' if needed
        if ($attempt->attempt_status === 'paused') {
             $wpdb->update($table_attempts, ['attempt_status' => 'started'], ['id' => $attempt_id]);
        }
    } else {
        // Create new attempt
        $inserted = $wpdb->insert(
            $table_attempts,
            array(
                'test_id' => $test_id,
                'user_id' => $user_id,
                'attempt_status' => 'started',
                // start_time defaults to CURRENT_TIMESTAMP
            ),
            array('%d', '%d', '%s')
        );
        if ($inserted) {
            $attempt_id = $wpdb->insert_id;
        } else {
            return '<p>Error: Could not start the test attempt. Please try again.</p>';
        }
    }

    // 5. Fetch Ordered Question IDs for this Test
    $table_test_questions = $wpdb->prefix . 'practice_test_test_questions';
    $question_ids_ordered = $wpdb->get_col($wpdb->prepare(
        "SELECT question_id FROM $table_test_questions WHERE test_id = %d ORDER BY question_order ASC",
        $test_id
    ));

    if (empty($question_ids_ordered)) {
        return '<p>Error: No questions found for this test.</p>';
    }
    $total_questions = count($question_ids_ordered);

    // 6. Pagination Logic
    $items_per_page = 1; // Show one question per page for typical test flow
    $current_page = isset($_GET['qpage']) ? max(1, intval($_GET['qpage'])) : 1;
    $total_pages = ceil($total_questions / $items_per_page);
    $current_page = min($current_page, $total_pages); // Ensure current page doesn't exceed total pages
    $offset = ($current_page - 1) * $items_per_page;

    // Get the question IDs for the current page
    $current_page_question_ids = array_slice($question_ids_ordered, $offset, $items_per_page);

    if (empty($current_page_question_ids)) {
         return '<p>Error: Could not load questions for this page.</p>'; // Should not happen if logic is correct
    }

    // 7. Fetch Question Details for the Current Page
    $table_questions = $wpdb->prefix . 'practice_test_questions';
    $ids_placeholder = implode(',', array_fill(0, count($current_page_question_ids), '%d'));
    $sql = $wpdb->prepare(
        "SELECT * FROM $table_questions WHERE id IN ($ids_placeholder)",
        $current_page_question_ids
    );
    // Fetch questions indexed by their ID for easier lookup
    $questions_data = $wpdb->get_results($sql, OBJECT_K);

    // 8. Fetch Saved Answers for this Attempt (for questions on the current page)
    $table_responses = $wpdb->prefix . 'practice_test_test_resp';
    $sql_responses = $wpdb->prepare(
        "SELECT question_id, user_answer FROM $table_responses WHERE attempt_id = %d AND question_id IN ($ids_placeholder)",
        array_merge([$attempt_id], $current_page_question_ids) // Prepare arguments correctly
    );
    $saved_answers = $wpdb->get_results($sql_responses, OBJECT_K); // Indexed by question_id


    // Enqueue the script now that we know we need it and have the attempt ID
    wp_enqueue_script('practice-test-quiz-handler');
    // Pass attempt_id specifically for this quiz instance
    wp_add_inline_script(
        'practice-test-quiz-handler',
        'var currentPracticeTestAttemptId = ' . intval($attempt_id) . ';',
        'before' // Add before the main script execution
    );


    // 9. Build HTML Output
    $output = '<div class="practice-test-quiz-container" data-test-id="' . esc_attr($test_id) . '" data-attempt-id="' . esc_attr($attempt_id) . '">';
    $output .= '<h3>' . esc_html($test_info->test_name) . '</h3>';
    $output .= '<p>Question ' . esc_html($offset + 1) . ' of ' . esc_html($total_questions) . '</p>'; // Show current question number

    // Loop through the question IDs for the current page IN ORDER
    foreach ($current_page_question_ids as $q_id) {
        if (!isset($questions_data[$q_id])) continue; // Skip if question data wasn't fetched
        $question = $questions_data[$q_id];
        $saved_answer = isset($saved_answers[$q_id]) ? $saved_answers[$q_id]->user_answer : null;

        $output .= '<div class="quiz-question" data-question-id="' . esc_attr($question->id) . '">';

        // Display Passage if present
        if (!empty($question->passage) && trim($question->passage) !== '') {
             $output .= '<div class="quiz-passage">' . wp_kses_post($question->passage) . '</div>'; // Use wp_kses_post for safety if passage contains HTML
        }

        $output .= '<p class="quiz-question-text">' . esc_html($question->question) . '</p>'; // Display the question

        // Display the answer choices
        $output .= '<div class="quiz-answer-choices">';
        for ($i = 1; $i <= 5; $i++) {
            $choice_col = 'answerchoices' . $i;
            $choice_text = $question->$choice_col;
            if (!empty($choice_text)) {
                // Use a consistent value, e.g., the index '1', '2', ... or the choice text itself
                $choice_value = $i; // Or use $choice_text if preferred, ensure consistency with saving/grading
                $checked = ($saved_answer !== null && strval($saved_answer) === strval($choice_value)) ? ' checked' : '';
                $output .= '<label><input type="radio" name="question_' . esc_attr($question->id) . '" value="' . esc_attr($choice_value) . '"' . $checked . '> ' . esc_html($choice_text) . '</label><br>';
            }
        }
        $output .= '</div>'; // Close choices div

        // Explanation will be shown after submission/review, not during the test.

        $output .= '</div>'; // Close the quiz-question div
    }

    // Navigation buttons - Update links to use query parameter 'qpage'
    $output .= '<div class="quiz-navigation">';
    $base_url = get_permalink(); // Or the specific page URL where the shortcode is

    // Previous Button
    if ($current_page > 1) {
        $prev_page_url = add_query_arg('qpage', $current_page - 1, $base_url);
        $output .= '<a href="' . esc_url($prev_page_url) . '" class="button prev-page-link">Previous Question</a> ';
    } else {
         $output .= '<button class="button prev-page-link" disabled>Previous Question</button> ';
    }

    // Next/Submit Button
    if ($current_page < $total_pages) {
        $next_page_url = add_query_arg('qpage', $current_page + 1, $base_url);
        $output .= '<a href="' . esc_url($next_page_url) . '" class="button next-page-link">Next Question</a> ';
    } else {
        // On the last page, show a submit button instead of "Next"
        $output .= '<button class="button quiz-submit-button">Submit Test</button> ';
    }

    // Save and Exit (handled by JS/AJAX)
    $output .= '<button class="button save-exit-button">Save and Exit</button>';

    $output .= '</div>'; // Close the quiz-navigation div
    $output .= '</div>'; // Close the quiz-container div

    return $output;
}
add_shortcode('practice_test_quiz', 'practice_test_quiz_shortcode');


// --- AJAX Handlers for Quiz Interaction ---

// Handle saving a single answer
function practice_test_save_answer_callback() {
    // 1. Security Checks
    check_ajax_referer('practice_test_quiz_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.'], 403);
    }
    $user_id = get_current_user_id();

    // 2. Get and Sanitize Data
    $attempt_id = isset($_POST['attempt_id']) ? intval($_POST['attempt_id']) : 0;
    $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
    $user_answer = isset($_POST['answer']) ? sanitize_text_field($_POST['answer']) : ''; // Adjust sanitization based on answer format

    if ($attempt_id <= 0 || $question_id <= 0) {
        wp_send_json_error(['message' => 'Invalid attempt or question ID.'], 400);
    }

    global $wpdb;
    $table_responses = $wpdb->prefix . 'practice_test_test_resp';
    $table_attempts = $wpdb->prefix . 'practice_test_test_attempts';

    // 3. Verify the attempt belongs to the user and is active
    $attempt_owner = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $table_attempts WHERE id = %d AND attempt_status IN ('started', 'paused')",
        $attempt_id
    ));

    if (!$attempt_owner || intval($attempt_owner) !== $user_id) {
         wp_send_json_error(['message' => 'Invalid attempt or permission denied.'], 403);
    }

    // 4. Insert or Update the response
    $existing_response_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_responses WHERE attempt_id = %d AND question_id = %d",
        $attempt_id, $question_id
    ));

    $data = array(
        'attempt_id' => $attempt_id,
        'question_id' => $question_id,
        'user_answer' => $user_answer,
        // 'is_correct' will be calculated upon final submission/grading
        // 'response_time' defaults to CURRENT_TIMESTAMP
    );
    $format = array('%d', '%d', '%s'); // Adjust format based on answer type

    if ($existing_response_id) {
        // Update existing response
        $result = $wpdb->update($table_responses, $data, array('id' => $existing_response_id), $format, array('%d'));
    } else {
        // Insert new response
        $result = $wpdb->insert($table_responses, $data, $format);
    }

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to save answer.', 'db_error' => $wpdb->last_error], 500);
    } else {
        wp_send_json_success(['message' => 'Answer saved.']);
    }
}
add_action('wp_ajax_save_practice_test_answer', 'practice_test_save_answer_callback');


// Handle pausing the test (Save & Exit)
function practice_test_pause_test_callback() {
    // 1. Security Checks
    check_ajax_referer('practice_test_quiz_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.'], 403);
    }
    $user_id = get_current_user_id();

    // 2. Get and Sanitize Data
    $attempt_id = isset($_POST['attempt_id']) ? intval($_POST['attempt_id']) : 0;

    if ($attempt_id <= 0) {
        wp_send_json_error(['message' => 'Invalid attempt ID.'], 400);
    }

    global $wpdb;
    $table_attempts = $wpdb->prefix . 'practice_test_test_attempts';

    // 3. Verify the attempt belongs to the user and is 'started'
    $attempt_details = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id, attempt_status FROM $table_attempts WHERE id = %d",
        $attempt_id
    ));

    if (!$attempt_details || intval($attempt_details->user_id) !== $user_id) {
         wp_send_json_error(['message' => 'Invalid attempt or permission denied.'], 403);
    }

    if ($attempt_details->attempt_status !== 'started') {
         wp_send_json_error(['message' => 'Test is not currently active.'], 400);
    }

    // 4. Update attempt status to 'paused'
    $result = $wpdb->update(
        $table_attempts,
        ['attempt_status' => 'paused'],
        ['id' => $attempt_id],
        ['%s'],
        ['%d']
    );

     if ($result === false) {
        wp_send_json_error(['message' => 'Failed to pause test.', 'db_error' => $wpdb->last_error], 500);
    } else {
        // Optionally save the last answer before pausing if not done automatically by the JS
        wp_send_json_success(['message' => 'Test progress saved and paused.']);
    }
}
add_action('wp_ajax_pause_practice_test', 'practice_test_pause_test_callback');


// Handle submitting the test
function practice_test_submit_test_callback() {
     // 1. Security Checks
    check_ajax_referer('practice_test_quiz_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.'], 403);
    }
    $user_id = get_current_user_id();

    // 2. Get and Sanitize Data
    $attempt_id = isset($_POST['attempt_id']) ? intval($_POST['attempt_id']) : 0;

    if ($attempt_id <= 0) {
        wp_send_json_error(['message' => 'Invalid attempt ID.'], 400);
    }

    global $wpdb;
    $table_attempts = $wpdb->prefix . 'practice_test_test_attempts';

    // 3. Verify the attempt belongs to the user and is 'started' or 'paused'
    $attempt_details = $wpdb->get_row($wpdb->prepare(
        "SELECT user_id, attempt_status FROM $table_attempts WHERE id = %d",
        $attempt_id
    ));

    if (!$attempt_details || intval($attempt_details->user_id) !== $user_id) {
         wp_send_json_error(['message' => 'Invalid attempt or permission denied.'], 403);
    }

     if (!in_array($attempt_details->attempt_status, ['started', 'paused'])) {
         wp_send_json_error(['message' => 'Test cannot be submitted in its current state.'], 400);
    }

    // 4. Update attempt status to 'completed' and record end time
    $result = $wpdb->update(
        $table_attempts,
        [
            'attempt_status' => 'completed',
            'end_time' => current_time('mysql', 1) // GMT time
        ],
        ['id' => $attempt_id],
        ['%s', '%s'],
        ['%d']
    );

     if ($result === false) {
        wp_send_json_error(['message' => 'Failed to submit test.', 'db_error' => $wpdb->last_error], 500);
    } else {
        // Trigger grading process (Task 4)
        $grading_result = practice_test_grade_attempt($attempt_id);

        // Redirect URL for the results page (adjust as needed)
        $results_page_url = home_url('/test-results/?attempt=' . $attempt_id); // Example URL

        wp_send_json_success([
            'message' => 'Test submitted successfully!' . ($grading_result ? ' Grading complete.' : ' Grading failed.'),
            'redirect_url' => $results_page_url,
            'grading_status' => $grading_result
        ]);
    }
}
add_action('wp_ajax_submit_practice_test', 'practice_test_submit_test_callback');


// --- Grading Function ---

/**
 * Grades a completed test attempt.
 * Calculates overall score, marks individual answers, and updates user stats.
 *
 * @param int $attempt_id The ID of the test attempt to grade.
 * @return bool True on successful grading, false on failure.
 */
function practice_test_grade_attempt($attempt_id) {
    global $wpdb;

    $attempt_id = intval($attempt_id);
    if ($attempt_id <= 0) {
        error_log("Practice Tests Grading Error: Invalid attempt ID {$attempt_id}");
        return false;
    }

    // Table names
    $table_attempts = $wpdb->prefix . 'practice_test_test_attempts';
    $table_responses = $wpdb->prefix . 'practice_test_test_resp';
    $table_questions = $wpdb->prefix . 'practice_test_questions';
    $table_subs = $wpdb->prefix . 'practice_test_subs';

    // 1. Get Attempt Details (including user_id and test_id)
    $attempt = $wpdb->get_row($wpdb->prepare("SELECT id, user_id, test_id, attempt_status FROM $table_attempts WHERE id = %d", $attempt_id));
    if (!$attempt || $attempt->attempt_status !== 'completed') {
        error_log("Practice Tests Grading Error: Attempt {$attempt_id} not found or not completed.");
        return false;
    }
    $user_id = $attempt->user_id;
    $test_id = $attempt->test_id;

    // 2. Get all user responses for this attempt
    $responses = $wpdb->get_results($wpdb->prepare(
        "SELECT id, question_id, user_answer FROM $table_responses WHERE attempt_id = %d",
        $attempt_id
    ), OBJECT_K); // Keyed by response ID for easy update later, need question_id too

    if (empty($responses)) {
        // No responses found, maybe update attempt score to 0 and return?
         $wpdb->update($table_attempts, ['score' => 0.00], ['id' => $attempt_id]);
         error_log("Practice Tests Grading Warning: No responses found for attempt {$attempt_id}. Score set to 0.");
         // Update user stats (tests_taken increment) even if no answers
         $wpdb->query($wpdb->prepare("UPDATE $table_subs SET tests_taken = tests_taken + 1 WHERE id = %d", $user_id));
         return true; // Consider this a success in terms of processing the submission
    }

    // 3. Get Correct Answers for all questions in the test
    $question_ids = array_keys($responses); // Get the IDs of questions answered
    $ids_placeholder = implode(',', array_fill(0, count($question_ids), '%d'));
    $correct_answers = $wpdb->get_results($wpdb->prepare(
        "SELECT id, answercorrect, subjectarea, skill FROM $table_questions WHERE id IN ($ids_placeholder)",
        $question_ids
    ), OBJECT_K); // Keyed by question ID

    // 4. Grade each response
    $total_questions_answered = count($responses);
    $correct_count = 0;
    $subject_stats = []; // ['SubjectName' => ['correct' => 0, 'total' => 0]]
    $skill_stats = [];   // ['SkillName' => ['correct' => 0, 'total' => 0]]

    foreach ($responses as $response) {
        $q_id = $response->question_id;
        $is_correct = 0; // Default to incorrect

        if (isset($correct_answers[$q_id])) {
            $correct_answer_val = $correct_answers[$q_id]->answercorrect;
            $subject = $correct_answers[$q_id]->subjectarea;
            $skill = $correct_answers[$q_id]->skill;

            // Comparison logic (needs refinement based on how answers are stored/compared)
            // Assuming user_answer stores the index ('1', '2', etc.) and answercorrect stores the index
            if (strval($response->user_answer) === strval($correct_answer_val)) {
                $is_correct = 1;
                $correct_count++;
            }

            // Update subject/skill stats
            if (!isset($subject_stats[$subject])) $subject_stats[$subject] = ['correct' => 0, 'total' => 0];
            if (!isset($skill_stats[$skill])) $skill_stats[$skill] = ['correct' => 0, 'total' => 0];
            $subject_stats[$subject]['total']++;
            $skill_stats[$skill]['total']++;
            if ($is_correct) {
                $subject_stats[$subject]['correct']++;
                $skill_stats[$skill]['correct']++;
            }

            // Update the response record in the database
            $wpdb->update(
                $table_responses,
                ['is_correct' => $is_correct],
                ['id' => $response->id], // Use the response ID as the key
                ['%d'],
                ['%d']
            );
        } else {
             error_log("Practice Tests Grading Warning: Correct answer not found for question ID {$q_id} in attempt {$attempt_id}.");
        }
    }

    // 5. Calculate Overall Score
    $overall_score = ($total_questions_answered > 0) ? round(($correct_count / $total_questions_answered) * 100, 2) : 0.00;

    // 6. Update the Attempt Score
    $wpdb->update(
        $table_attempts,
        ['score' => $overall_score],
        ['id' => $attempt_id],
        ['%f'], // Format as float
        ['%d']
    );

    // 7. Update User Aggregate Stats
    // For now, just increment tests_taken and questions_answered
    // More complex stats like best/worst subject require historical data analysis later
    $wpdb->query($wpdb->prepare(
        "UPDATE $table_subs
         SET tests_taken = tests_taken + 1,
             questions_answered = questions_answered + %d
         WHERE id = %d",
        $total_questions_answered,
        $user_id
    ));

    // TODO: Implement logic to update best/worst/fast/slow subject based on current and potentially past attempts.
    // This might involve storing subject/skill stats per attempt or querying historical data.

    error_log("Practice Tests Grading: Attempt {$attempt_id} graded. Score: {$overall_score}. Correct: {$correct_count}/{$total_questions_answered}.");

    return true;
}


// --- Results Display Shortcode ---

/**
 * Shortcode to display the results of a specific test attempt.
 * Expects an 'attempt' query parameter in the URL, e.g., /test-results/?attempt=123
 *
 * @param array $atts Shortcode attributes (not used directly here, relies on URL param).
 * @return string HTML output for the results page.
 */
function practice_test_results_shortcode($atts) {
    global $wpdb;

    // 1. Check Login & Get User ID
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your test results.</p>';
    }
    $user_id = get_current_user_id();

    // 2. Get Attempt ID from URL Parameter
    $attempt_id = isset($_GET['attempt']) ? intval($_GET['attempt']) : 0;
    if ($attempt_id <= 0) {
        return '<p>Invalid attempt ID specified.</p>';
    }

    // Table names
    $table_attempts = $wpdb->prefix . 'practice_test_test_attempts';
    $table_responses = $wpdb->prefix . 'practice_test_test_resp';
    $table_questions = $wpdb->prefix . 'practice_test_questions';
    $table_tests = $wpdb->prefix . 'practice_test_tests';

    // 3. Verify Attempt Belongs to User and is Completed
    $attempt = $wpdb->get_row($wpdb->prepare(
        "SELECT a.id, a.user_id, a.test_id, a.attempt_status, a.score, t.test_name
         FROM $table_attempts a
         JOIN $table_tests t ON a.test_id = t.id
         WHERE a.id = %d",
        $attempt_id
    ));

    if (!$attempt) {
        return '<p>Test attempt not found.</p>';
    }
    if (intval($attempt->user_id) !== $user_id) {
        return '<p>You do not have permission to view these results.</p>';
    }
    if ($attempt->attempt_status !== 'completed') {
        return '<p>This test attempt has not been completed yet.</p>';
    }

    // 4. Get Responses and Question Details for this Attempt
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT
            r.question_id, r.user_answer, r.is_correct,
            q.question, q.answercorrect, q.answerchoices1, q.answerchoices2, q.answerchoices3, q.answerchoices4, q.answerchoices5, q.explanation, q.passage, q.subjectarea, q.skill
         FROM $table_responses r
         JOIN $table_questions q ON r.question_id = q.id
         WHERE r.attempt_id = %d
         ORDER BY q.id ASC", // Ideally, order by the original question order in the test, requires joining test_questions table
        $attempt_id
    ));

    if (empty($results)) {
        return '<p>No responses found for this test attempt.</p>';
    }

    // 5. Build HTML Output
    $output = '<div class="practice-test-results-container">';
    $output .= '<h2>Results for: ' . esc_html($attempt->test_name) . '</h2>';
    $output .= '<p><strong>Your Score:</strong> ' . esc_html(number_format($attempt->score, 2)) . '%</p>';
    $output .= '<hr>';

    $question_number = 1;
    foreach ($results as $result) {
        $output .= '<div class="result-question" style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee;">';
        $output .= '<h4>Question ' . $question_number++ . '</h4>';

        // Display Passage if present
        if (!empty($result->passage) && trim($result->passage) !== '') {
             $output .= '<div class="result-passage" style="background-color: #f9f9f9; border: 1px solid #eee; padding: 10px; margin-bottom: 10px;">' . wp_kses_post($result->passage) . '</div>';
        }

        $output .= '<p><strong>Question:</strong> ' . esc_html($result->question) . '</p>';
        $output .= '<p><small><em>Subject: ' . esc_html($result->subjectarea) . ' | Skill: ' . esc_html($result->skill) . '</em></small></p>'; // Added Subject/Skill display

        // Display Choices, highlighting user answer and correct answer
        $output .= '<div class="result-choices" style="margin-left: 15px;">';
        $correct_answer_text = '';
        for ($i = 1; $i <= 5; $i++) {
            $choice_col = 'answerchoices' . $i;
            $choice_text = $result->$choice_col;
            if (!empty($choice_text)) {
                $is_user_answer = (strval($result->user_answer) === strval($i));
                $is_correct_answer = (strval($result->answercorrect) === strval($i));
                $style = '';
                $indicator = '';

                if ($is_correct_answer) {
                    $style .= ' font-weight: bold; color: green;';
                    $indicator .= ' (Correct Answer)';
                    $correct_answer_text = $choice_text; // Store correct answer text
                }
                if ($is_user_answer) {
                    $style .= ' border: 1px solid #0073aa; padding: 2px 5px; display: inline-block; margin-bottom: 3px;';
                    $indicator .= ' (Your Answer)';
                }

                $output .= '<p style="' . esc_attr($style) . '">' . esc_html($choice_text) . $indicator . '</p>';
            }
        }
         $output .= '</div>'; // Close choices

        // Display Result (Correct/Incorrect)
        if ($result->is_correct) {
            $output .= '<p style="color: green;"><strong>Result: Correct</strong></p>';
        } else {
            $output .= '<p style="color: red;"><strong>Result: Incorrect</strong></p>';
            // Show correct answer text if user was wrong
            if (!empty($correct_answer_text)) {
                 $output .= '<p><strong>Correct Answer Was:</strong> ' . esc_html($correct_answer_text) . '</p>';
            }
        }

        // Display Explanation
        if (!empty($result->explanation)) {
            $output .= '<p><strong>Explanation:</strong> ' . esc_html($result->explanation) . '</p>';
        }

        $output .= '</div>'; // Close result-question
    }


    $output .= '</div>'; // Close results container

    return $output;
}
add_shortcode('practice_test_results', 'practice_test_results_shortcode');


// --- Performance Breakdown Functionality ---

/**
 * Calculates performance statistics (accuracy) grouped by subject and skill for a given user.
 *
 * @param int $user_id The ID of the user.
 * @return array An array containing two elements: 'subjects' and 'skills',
 *               each being an associative array mapping category name to
 *               ['correct' => count, 'total' => count, 'accuracy' => percentage].
 *               Returns empty array if no completed attempts or data found.
 */
function practice_test_get_user_performance_breakdown($user_id) {
    global $wpdb;
    $user_id = intval($user_id);
    if ($user_id <= 0) {
        return ['subjects' => [], 'skills' => []];
    }

    // Table names
    $table_attempts = $wpdb->prefix . 'practice_test_test_attempts';
    $table_responses = $wpdb->prefix . 'practice_test_test_resp';
    $table_questions = $wpdb->prefix . 'practice_test_questions';

    // 1. Get all completed attempt IDs for the user
    $completed_attempt_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM $table_attempts WHERE user_id = %d AND attempt_status = 'completed'",
        $user_id
    ));

    if (empty($completed_attempt_ids)) {
        return ['subjects' => [], 'skills' => []]; // No completed tests
    }

    // 2. Get all responses for these attempts, joined with question data
    $ids_placeholder = implode(',', array_fill(0, count($completed_attempt_ids), '%d'));
    $performance_data = $wpdb->get_results($wpdb->prepare(
        "SELECT
            r.is_correct,
            q.subjectarea,
            q.skill
         FROM $table_responses r
         JOIN $table_questions q ON r.question_id = q.id
         WHERE r.attempt_id IN ($ids_placeholder) AND r.is_correct IS NOT NULL", // Only consider graded questions
        $completed_attempt_ids
    ));

    if (empty($performance_data)) {
        return ['subjects' => [], 'skills' => []]; // No graded responses found
    }

    // 3. Aggregate statistics
    $subject_stats = [];
    $skill_stats = [];

    foreach ($performance_data as $data) {
        $subject = trim($data->subjectarea);
        $skill = trim($data->skill);
        $is_correct = intval($data->is_correct);

        // Aggregate by Subject
        if (!empty($subject)) {
            if (!isset($subject_stats[$subject])) $subject_stats[$subject] = ['correct' => 0, 'total' => 0];
            $subject_stats[$subject]['total']++;
            if ($is_correct) {
                $subject_stats[$subject]['correct']++;
            }
        }

        // Aggregate by Skill
        if (!empty($skill)) {
             if (!isset($skill_stats[$skill])) $skill_stats[$skill] = ['correct' => 0, 'total' => 0];
            $skill_stats[$skill]['total']++;
            if ($is_correct) {
                $skill_stats[$skill]['correct']++;
            }
        }
    }

    // 4. Calculate Accuracy
    foreach ($subject_stats as $subject => &$stats) {
        $stats['accuracy'] = ($stats['total'] > 0) ? round(($stats['correct'] / $stats['total']) * 100, 1) : 0;
    }
    unset($stats); // Unset reference

    foreach ($skill_stats as $skill => &$stats) {
        $stats['accuracy'] = ($stats['total'] > 0) ? round(($stats['correct'] / $stats['total']) * 100, 1) : 0;
    }
    unset($stats); // Unset reference


    // Sort by accuracy (descending) or name (ascending) - Optional
     uasort($subject_stats, function($a, $b) { return $b['accuracy'] <=> $a['accuracy']; }); // Sort subjects by accuracy desc
     uasort($skill_stats, function($a, $b) { return $b['accuracy'] <=> $a['accuracy']; });   // Sort skills by accuracy desc


    return ['subjects' => $subject_stats, 'skills' => $skill_stats];
}


/**
 * Shortcode to display the user's performance breakdown by subject and skill.
 *
 * @param array $atts Shortcode attributes (unused).
 * @return string HTML output.
 */
function practice_test_performance_breakdown_shortcode($atts) {
     if (!is_user_logged_in()) {
        return '<p>Please log in to view your performance breakdown.</p>';
    }
    $user_id = get_current_user_id();

    $performance = practice_test_get_user_performance_breakdown($user_id);

    $output = '<div class="practice-test-performance-breakdown">';
    $output .= '<h3>Performance Breakdown</h3>';

    // Display Subject Stats
    $output .= '<h4>By Subject Area</h4>';
    if (empty($performance['subjects'])) {
        $output .= '<p>No performance data available by subject yet.</p>';
    } else {
        $output .= '<table class="wp-list-table widefat striped practice-test-performance-table">';
        $output .= '<thead><tr><th>Subject</th><th>Correct</th><th>Attempted</th><th>Accuracy</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($performance['subjects'] as $subject => $stats) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($subject) . '</td>';
            $output .= '<td>' . esc_html($stats['correct']) . '</td>';
            $output .= '<td>' . esc_html($stats['total']) . '</td>';
            $output .= '<td>' . esc_html($stats['accuracy']) . '%</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';
    }

     // Display Skill Stats
    $output .= '<h4 style="margin-top: 20px;">By Skill</h4>';
    if (empty($performance['skills'])) {
        $output .= '<p>No performance data available by skill yet.</p>';
    } else {
        $output .= '<table class="wp-list-table widefat striped practice-test-performance-table">';
        $output .= '<thead><tr><th>Skill</th><th>Correct</th><th>Attempted</th><th>Accuracy</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($performance['skills'] as $skill => $stats) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($skill) . '</td>';
            $output .= '<td>' . esc_html($stats['correct']) . '</td>';
            $output .= '<td>' . esc_html($stats['total']) . '</td>';
            $output .= '<td>' . esc_html($stats['accuracy']) . '%</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';
    }

    $output .= '</div>'; // Close container

    return $output;
}
add_shortcode('practice_test_performance_breakdown', 'practice_test_performance_breakdown_shortcode');


// --- AJAX Handler for Loading Member Area Content ---

/**
 * Central AJAX handler for loading different content sections in the members area.
 */
function practice_test_load_member_content_callback() {
    // 1. Security Checks
    check_ajax_referer('practice_test_members_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in.'], 403);
    }
    $user_id = get_current_user_id();

    // 2. Get Requested Action
    $sub_action = isset($_POST['sub_action']) ? sanitize_key($_POST['sub_action']) : '';

    // 3. Route to appropriate content generation function
    $html_content = '';
    switch ($sub_action) {
        case 'view_tests':
            $html_content = practice_test_render_available_tests($user_id);
            break;
        case 'view_performance':
            // Reuse the shortcode function's logic, but just get the data array
            $performance = practice_test_get_user_performance_breakdown($user_id);
            $html_content = practice_test_render_performance_html($performance); // New rendering function
            break;
        case 'view_results_history':
             $html_content = practice_test_render_results_history($user_id);
             break;
        // Add cases for other actions (profile, learning, upgrade, add_student) later
        case 'view_profile':
             $html_content = '<p>Profile management functionality coming soon.</p>';
             break;
        case 'view_learning':
             $html_content = '<p>Learning resources functionality coming soon.</p>';
             break;
        case 'view_upgrade':
             $html_content = '<p>Upgrade options functionality coming soon.</p>';
             // Potentially render the [practice_tests_plan] shortcode content here
             // $html_content = do_shortcode('[practice_tests_plan]');
             break;
         case 'add_student':
             // Check if current user is a guardian (add proper capability check later)
             $user_info = get_userdata($user_id);
             $user_roles = (array) $user_info->roles;
             if (in_array('guardian', $user_roles)) { // Simple role check for now
                 $html_content = practice_test_render_add_student_form($user_id);
             } else {
                 $html_content = '<p>This feature is only available for guardian accounts.</p>';
             }
             break;
        default:
            $html_content = '<p>Invalid request or content not available.</p>';
            break;
    }

    wp_send_json_success(['html' => $html_content]);
}
add_action('wp_ajax_load_member_content', 'practice_test_load_member_content_callback');


// --- Helper Functions for Rendering Member Area Content ---

/**
 * Renders HTML for the list of available tests.
 */
function practice_test_render_available_tests($user_id) {
    global $wpdb;
    $table_tests = $wpdb->prefix . 'practice_test_tests';
    // TODO: Add filtering based on user plan/access later
    $available_tests = $wpdb->get_results("SELECT id, test_name, test_description, test_type FROM $table_tests ORDER BY test_name ASC");

    if (empty($available_tests)) {
        return '<p>No practice tests are currently available.</p>';
    }

    $output = '<h3>Available Tests</h3>';
    $output .= '<ul>';
    foreach ($available_tests as $test) {
        // Assume a page slug 'take-quiz' where the quiz shortcode is placed
        $quiz_page_url = home_url('/take-quiz/'); // Adjust this slug if needed
        $start_test_url = add_query_arg('test_id', $test->id, $quiz_page_url);

        $output .= '<li>';
        $output .= '<strong>' . esc_html($test->test_name) . '</strong> (' . esc_html($test->test_type) . ')';
        if (!empty($test->test_description)) {
            $output .= '<br><small>' . esc_html($test->test_description) . '</small>';
        }
        $output .= '<br><a href="' . esc_url($start_test_url) . '" class="button button-primary">Start Test</a>';
        $output .= '</li><hr>';
    }
    $output .= '</ul>';

    return $output;
}


/**
 * Renders the form for guardians to add/link a student account.
 */
function practice_test_render_add_student_form($guardian_user_id) {
    // Nonce for security
    $nonce = wp_create_nonce('pt_link_student_nonce_' . $guardian_user_id);

    $output = '<h3>Link a Student Account</h3>';
    $output .= '<p>Enter the email address of the student account you wish to link to your guardian account.</p>';
    $output .= '<form id="link-student-form" method="post">'; // JS will handle submission via AJAX
    $output .= '<input type="hidden" name="action" value="link_student_account">'; // WP AJAX action
    $output .= '<input type="hidden" name="nonce" value="' . esc_attr($nonce) . '">';
    $output .= '<p>';
    $output .= '<label for="student_email">Student Email:</label> ';
    $output .= '<input type="email" id="student_email" name="student_email" required>';
    $output .= '</p>';
    $output .= '<p><input type="submit" value="Link Student Account" class="button button-primary"></p>';
    $output .= '</form>';
    $output .= '<div id="link-student-message" style="margin-top: 10px;"></div>'; // For success/error messages

    // List currently linked students
    global $wpdb;
    $table_links = $wpdb->prefix . 'practice_test_guardian_student_links';
    $table_subs = $wpdb->prefix . 'practice_test_subs';

    $linked_students = $wpdb->get_results($wpdb->prepare(
        "SELECT s.id, s.user_name, s.user_email
         FROM $table_links l
         JOIN $table_subs s ON l.student_user_id = s.id
         WHERE l.guardian_user_id = %d AND l.link_status = 'active'
         ORDER BY s.user_name ASC",
        $guardian_user_id
    ));

    $output .= '<h4 style="margin-top: 25px;">Linked Student Accounts</h4>';
    if (empty($linked_students)) {
        $output .= '<p>You have not linked any student accounts yet.</p>';
    } else {
        $output .= '<ul>';
        foreach ($linked_students as $student) {
            // Add links/buttons to view student performance later
            $output .= '<li>';
            $output .= esc_html($student->user_name) . ' (' . esc_html($student->user_email) . ')';
            // Example link structure for viewing student data (requires JS/AJAX handling)
            $output .= ' - <a href="#" class="view-student-performance" data-student-id="' . esc_attr($student->id) . '">View Performance</a>';
            // Add unlinking functionality later
            $output .= '</li>';
        }
        $output .= '</ul>';
    }


    return $output;
}


// --- AJAX Handler for Linking Student Account ---

function practice_test_link_student_account_callback() {
    // 1. Security & Permissions Check
    $guardian_user_id = get_current_user_id();
    if (!$guardian_user_id) {
        wp_send_json_error(['message' => 'User not logged in.'], 403);
    }
     if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pt_link_student_nonce_' . $guardian_user_id)) {
        wp_send_json_error(['message' => 'Nonce verification failed.'], 403);
    }
    // Add capability check to ensure only guardians can do this
    // if (!current_user_can('link_students')) { ... }

    // 2. Get and Validate Input
    $student_email = isset($_POST['student_email']) ? sanitize_email($_POST['student_email']) : '';
    if (!is_email($student_email)) {
        wp_send_json_error(['message' => 'Invalid student email address provided.'], 400);
    }

    global $wpdb;
    $table_subs = $wpdb->prefix . 'practice_test_subs';
    $table_links = $wpdb->prefix . 'practice_test_guardian_student_links';

    // 3. Find Student User ID
    $student_user = $wpdb->get_row($wpdb->prepare(
        "SELECT id, user_role FROM $table_subs WHERE user_email = %s",
        $student_email
    ));

    if (!$student_user) {
        wp_send_json_error(['message' => 'No user found with that email address.'], 404);
    }
    if ($student_user->user_role !== 'student') {
         wp_send_json_error(['message' => 'The user found with that email is not registered as a student.'], 400);
    }
    $student_user_id = $student_user->id;

    // Prevent linking to self
    if ($guardian_user_id === $student_user_id) {
         wp_send_json_error(['message' => 'You cannot link your own account.'], 400);
    }

    // 4. Check if Link Already Exists
    $existing_link = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_links WHERE guardian_user_id = %d AND student_user_id = %d",
        $guardian_user_id, $student_user_id
    ));

    if ($existing_link) {
        wp_send_json_error(['message' => 'This student account is already linked.'], 409); // 409 Conflict
    }

    // 5. Create the Link
    $result = $wpdb->insert(
        $table_links,
        array(
            'guardian_user_id' => $guardian_user_id,
            'student_user_id' => $student_user_id,
            'link_status' => 'active' // Or 'pending' if confirmation is needed
        ),
        array('%d', '%d', '%s')
    );

    if ($result === false) {
         wp_send_json_error(['message' => 'Failed to link student account. Database error.', 'db_error' => $wpdb->last_error], 500);
    } else {
        wp_send_json_success(['message' => 'Student account linked successfully!']);
        // TODO: Add logic to list linked students after success
    }
}
add_action('wp_ajax_link_student_account', 'practice_test_link_student_account_callback');


// --- AJAX Handler for Loading Linked Student Performance ---

function practice_test_load_student_performance_callback() {
    // 1. Security & Permissions Check
    $guardian_user_id = get_current_user_id();
     if (!$guardian_user_id) {
        wp_send_json_error(['message' => 'User not logged in.'], 403);
    }
     // Use the main members area nonce for this action as well
     if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'practice_test_members_nonce')) {
        wp_send_json_error(['message' => 'Nonce verification failed.'], 403);
    }
     // Add capability check if needed

    // 2. Get Student ID
    $student_user_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
    if ($student_user_id <= 0) {
         wp_send_json_error(['message' => 'Invalid student ID provided.'], 400);
    }

    // 3. Verify Guardian is Linked to this Student
    global $wpdb;
    $table_links = $wpdb->prefix . 'practice_test_guardian_student_links';
    $is_linked = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_links WHERE guardian_user_id = %d AND student_user_id = %d AND link_status = 'active'",
        $guardian_user_id, $student_user_id
    ));

    if (!$is_linked) {
         wp_send_json_error(['message' => 'You are not linked to this student account or permission denied.'], 403);
    }

    // 4. Get Student's Performance Data
    $student_performance = practice_test_get_user_performance_breakdown($student_user_id);

    // 5. Get Student Name for Display
    $student_info = get_userdata($student_user_id);
    $student_name = $student_info ? $student_info->display_name : 'Student';

    // 6. Render HTML
    $html_content = '<h3>Performance Breakdown for ' . esc_html($student_name) . '</h3>';
    $html_content .= practice_test_render_performance_html($student_performance); // Reuse existing render function

    wp_send_json_success(['html' => $html_content]);

}
add_action('wp_ajax_load_student_performance', 'practice_test_load_student_performance_callback');


/**
 * Renders HTML for the performance breakdown based on pre-calculated data.
 */
function practice_test_render_performance_html($performance) {
     $output = '<h3>Performance Breakdown</h3>';

    // Display Subject Stats
    $output .= '<h4>By Subject Area</h4>';
    if (empty($performance['subjects'])) {
        $output .= '<p>No performance data available by subject yet.</p>';
    } else {
        $output .= '<table class="wp-list-table widefat striped practice-test-performance-table">';
        $output .= '<thead><tr><th>Subject</th><th>Correct</th><th>Attempted</th><th>Accuracy</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($performance['subjects'] as $subject => $stats) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($subject) . '</td>';
            $output .= '<td>' . esc_html($stats['correct']) . '</td>';
            $output .= '<td>' . esc_html($stats['total']) . '</td>';
            $output .= '<td>' . esc_html($stats['accuracy']) . '%</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';
    }

     // Display Skill Stats
    $output .= '<h4 style="margin-top: 20px;">By Skill</h4>';
    if (empty($performance['skills'])) {
        $output .= '<p>No performance data available by skill yet.</p>';
    } else {
        $output .= '<table class="wp-list-table widefat striped practice-test-performance-table">';
        $output .= '<thead><tr><th>Skill</th><th>Correct</th><th>Attempted</th><th>Accuracy</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($performance['skills'] as $skill => $stats) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($skill) . '</td>';
            $output .= '<td>' . esc_html($stats['correct']) . '</td>';
            $output .= '<td>' . esc_html($stats['total']) . '</td>';
            $output .= '<td>' . esc_html($stats['accuracy']) . '%</td>';
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';
    }
    return $output;
}

/**
 * Renders HTML for the user's test results history.
 */
function practice_test_render_results_history($user_id) {
    global $wpdb;
    $table_attempts = $wpdb->prefix . 'practice_test_test_attempts';
    $table_tests = $wpdb->prefix . 'practice_test_tests';

    $completed_attempts = $wpdb->get_results($wpdb->prepare(
        "SELECT a.id, a.test_id, a.attempt_status, a.score, a.end_time, t.test_name
         FROM $table_attempts a
         JOIN $table_tests t ON a.test_id = t.id
         WHERE a.user_id = %d AND a.attempt_status = 'completed'
         ORDER BY a.end_time DESC",
        $user_id
    ));

     $output = '<h3>Test Results History</h3>';
     if (empty($completed_attempts)) {
        $output .= '<p>You have not completed any tests yet.</p>';
    } else {
        $output .= '<table class="wp-list-table widefat striped practice-test-history-table">';
        $output .= '<thead><tr><th>Test Name</th><th>Date Completed</th><th>Score</th><th>View Details</th></tr></thead>';
        $output .= '<tbody>';
        foreach ($completed_attempts as $attempt) {
             $results_page_url = home_url('/test-results/'); // Adjust slug if needed
             $details_url = add_query_arg('attempt', $attempt->id, $results_page_url);
             $completed_date = date_format(date_create($attempt->end_time), 'Y-m-d H:i'); // Format date

            $output .= '<tr>';
            $output .= '<td>' . esc_html($attempt->test_name) . '</td>';
            $output .= '<td>' . esc_html($completed_date) . '</td>';
            $output .= '<td>' . esc_html(number_format($attempt->score, 2)) . '%</td>';
            $output .= '<td><a href="' . esc_url($details_url) . '" class="button">View Results</a></td>';
            $output .= '</tr>';
        }
        $output .= '</tbody></table>';
    }
    return $output;
}
