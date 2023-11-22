<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function render_question_bank_table() {
    global $wpdb;

    // Table name
    $table_questions = $wpdb->prefix . 'practice_test_questions'; // Replace with your table name

    // Get current page and calculate offset
    $paged = isset($_GET['paged']) ? max(0, intval($_GET['paged']) - 1) : 0;
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
    $offset = $paged * $per_page;

    // Fetch total number of questions
    $total_questions = $wpdb->get_var("SELECT COUNT(1) FROM $table_questions");
    $total_pages = ceil($total_questions / $per_page);

    // Fetch questions
    $sql = "SELECT * FROM $table_questions LIMIT %d OFFSET %d";
    $questions = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset), ARRAY_A);

    // Start rendering table
    echo '<table>';
    echo '<tr>';
    echo '<th>ID</th><th>Test Type</th><th>Passage</th><th>Question</th>';
    echo '<th>Answer Choice 1</th><th>Answer Choice 2</th><th>Answer Choice 3</th>';
    echo '<th>Answer Choice 4</th><th>Answer Choice 5</th><th>Correct Answer</th>';
    echo '<th>Explanation</th><th>Subject</th><th>Skill</th>';
    echo '</tr>';

    foreach ($questions as $question) {
        echo '<tr>';
        echo '<td>' . esc_html($question['id']) . '</td>';
        echo '<td>' . esc_html($question['testtype']) . '</td>';
        echo '<td>' . esc_html($question['passage']) . '</td>';
        echo '<td>' . esc_html($question['question']) . '</td>';
        echo '<td>' . esc_html($question['answerchoices1']) . '</td>';
        echo '<td>' . esc_html($question['answerchoices2']) . '</td>';
        echo '<td>' . esc_html($question['answerchoices3']) . '</td>';
        echo '<td>' . esc_html($question['answerchoices4']) . '</td>';
        echo '<td>' . esc_html($question['answerchoices5']) . '</td>';
        echo '<td>' . esc_html($question['answercorrect']) . '</td>';
        echo '<td>' . esc_html($question['explanation']) . '</td>';
        echo '<td>' . esc_html($question['subjectarea']) . '</td>';
        echo '<td>' . esc_html($question['skill']) . '</td>';
        echo '</tr>';
    }

    echo '</table>';

    // Pagination
    for ($i = 1; $i <= $total_pages; $i++) {
        echo '<a href="?paged=' . $i . '&per_page=' . $per_page . '">' . $i . '</a> ';
    }

    // Options for per page
    $current_page = isset($_GET['page']) ? $_GET['page'] : '';
    echo '<form action="' . $_SERVER['REQUEST_URI'] . '" method="get">';
    echo '<input type="hidden" name="page" value="' . esc_attr($current_page) . '">';
    echo '<input type="hidden" name="paged" value="1">'; // Reset page to 1 on per_page change
    echo '<select name="per_page" onchange="this.form.submit()">';
    echo '<option value="10"' . selected($per_page, 10) . '>10</option>';
    echo '<option value="25"' . selected($per_page, 25) . '>25</option>';
    echo '<option value="50"' . selected($per_page, 50) . '>50</option>';
    echo '</select>';
    echo '</form>';

}
