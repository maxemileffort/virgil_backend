<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function practice_tests_plugin_menu() {
    add_menu_page('Practice Tests Settings', 'Dashboard', 'manage_options', 'practice-tests-plugin', 'practice_tests_dashboard_page');

    // Other submenus
    add_submenu_page('practice-tests-plugin', 'Add Question', 'Add Question', 'manage_options', 'practice-tests-add-question', 'practice_tests_add_question_page');
    // ... add other submenus ...
}

// Function to display the plugin admin page
function practice_tests_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h2>Practice Tests</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <!-- Single Question Addition -->
            <h3>Add a Single Question</h3>
            <p>
                <label for="question">Question:</label>
                <input type="text" name="question" id="question">
            </p>
            <p>
                <label for="answer">Answer:</label>
                <input type="text" name="answer" id="answer">
            </p>
            <input type="submit" name="submit_single_question" value="Add Question">

            <!-- CSV Upload -->
            <h3>Upload Questions via CSV</h3>
            <p>
                <input type="file" name="questions_csv" id="questions_csv">
            </p>
            <input type="submit" name="submit_csv" value="Upload CSV">
        </form>
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
