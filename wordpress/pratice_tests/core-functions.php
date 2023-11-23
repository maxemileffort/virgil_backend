<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function practice_test_quiz_shortcode() {
    global $wpdb; // Access the global WordPress database object

    // SQL to get quiz data
    $sql = "SELECT * FROM wp_practice_test_questions WHERE testtype = 'ACT'"; // Replace with your table name and conditions
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
            $choice_col = 'answerchoice' . $i;
            if (!empty($question->$choice_col)) {
                $output .= '<label><input type="radio" name="question' . esc_attr($question->id) . '" value="' . $i . '"> ' . esc_html($question->$choice_col) . '</label><br>';
            }
        }

        // Optionally, display the explanation (if you want it visible)
        // $output .= '<p class="explanation">' . esc_html($question->explanation) . '</p>';

        $output .= '</div>'; // Close the quiz-question div
    }

    $output .= '</div>'; // Close the quiz-container div

    return $output;
}
add_shortcode('practice_test_quiz', 'practice_test_quiz_shortcode');
