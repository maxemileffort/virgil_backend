<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// function practice_test_quiz_shortcode($testtype) {
function practice_test_quiz_shortcode() {
    global $wpdb; // Access the global WordPress database object

    // // SQL to get quiz data
    // $sql = "SELECT * FROM wp_practice_test_questions WHERE testtype = 'ACT'"; // Replace with your table name and conditions
    // $quiz_data = $wpdb->get_results($sql);

     // Define default attributes for the shortcode and parse any passed
     $atts = shortcode_atts(array(
        'page' => 1, // Current page
        'testtype' => 'ACT', // Default test type, replace with your desired default
        // 'testtype' => $testtype, // Default test type, replace with your desired default
    ), $atts);

    $items_per_page = 4;
    $offset = ($atts['page'] - 1) * $items_per_page;

    // SQL to get quiz data with pagination
    $sql = $wpdb->prepare(
        "SELECT * FROM wp_practice_test_questions WHERE testtype = %s LIMIT %d OFFSET %d",
        $atts['testtype'], $items_per_page, $offset
    );
    $quiz_data = $wpdb->get_results($sql);

    if (empty($quiz_data)) {
        return 'No quiz data found.';
    }

    // Start building the quiz output
    $output = '<div class="quiz-container">';

    foreach ($quiz_data as $question) {
        $output .= '<div class="quiz-question">';
        $output .= '<p>' . esc_html($question->question) . '</p>'; // Display the question

        // Display the answer choices
        for ($i = 1; $i <= 5; $i++) {
            $choice_col = 'answerchoices' . $i;
            if (!empty($question->$choice_col)) {
                $output .= '<label><input type="radio" name="question' . esc_attr($question->id) . '" value="' . $i . '"> ' . esc_html($question->$choice_col) . '</label><br>';
            }
        }

        // Optionally, display the explanation (if you want it visible)
        // $output .= '<p class="explanation">' . esc_html($question->explanation) . '</p>';

        $output .= '</div>'; // Close the quiz-question div
    }

    // Navigation buttons
    $output .= '<div class="quiz-navigation">';
    $output .= '<button class="prev-page">Previous Page</button>';
    $output .= '<button class="next-page">Next Page</button>';
    $output .= '<button class="save-exit">Save and Exit</button>';
    $output .= '<button class="save-submit">Save and Submit</button>';
    $output .= '</div>'; // Close the quiz-navigation div

    $output .= '</div>'; // Close the quiz-container div

    return $output;
}
add_shortcode('practice_test_quiz', 'practice_test_quiz_shortcode');
