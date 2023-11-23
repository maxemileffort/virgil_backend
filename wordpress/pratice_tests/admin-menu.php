<?php

if ( ! defined( 'ABSPATH' ) ) exit;

include_once plugin_dir_path(__FILE__) . 'utilities.php';

function practice_tests_plugin_menu() {
    add_menu_page('Practice Tests Settings', 'Practice Tests', 'manage_options', 'practice-tests-plugin', 'practice_tests_dashboard_page');

    // Other submenus
    add_submenu_page('practice-tests-plugin', 'Add Question', 'Add Question', 'manage_options', 'practice-tests-add-question', 'practice_tests_add_question_page');
    add_submenu_page('practice-tests-plugin', 'View/Edit Question', 'View/Edit Question', 'manage_options', 'practice-tests-view-edit-question', 'practice_tests_view_edit_question_page');
    add_submenu_page('practice-tests-plugin', 'Manage Users', 'Manage Users', 'manage_options', 'practice-tests-manage-users', 'practice_tests_manage_users_page');
    // ... add other submenus ...
}

function practice_tests_manage_users_page() {
    ?>
    <div class="wrap">
        <h2>User Management</h2>
            <form method="post" action="" enctype="multipart/form-data">
                <!-- Single User Addition -->
                <h3>Add a Single User</h3>
                <p>
                    <label for="username">Name:</label>
                    <input type="text" name="username" id="username">
                    <label for="useremail">Email:</label>
                    <input type="text" name="useremail" id="useremail">
                </p>
                
                <p>
                    <label for="userstatus">Status:</label>
                    <select name="userstatus" id="userstatus">
                        <option value="add">Active</option>
                        <option value="update">Paused</option>
                        <option value="---">-------</option>
                        <option value="delete">Delete</option>
                    </select>
                    <label for="useraction">Action:</label>
                    <select name="useraction" id="useraction">
                        <option value="add">Add</option>
                        <option value="update">Update</option>
                        <option value="reset">Reset</option>
                        <option value="---">-------</option>
                        <option value="delete">Delete</option>
                    </select>
                </p>
                <input type="submit" name="submit_single_user" value="Add user">

                <!-- User CSV Upload -->
                <h3>Upload Users via CSV</h3>
                <p>
                    <input type="file" name="users_csv" id="users_csv">
                </p>
                <input type="submit" name="submit_users_csv" value="Upload CSV">
            </form>
    </div>
    <?php
}

// Function to display the plugin admin page
function practice_tests_add_question_page() {
    ?>
    <div class="wrap">
        <h2>Practice Tests</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <!-- Single Question Addition -->
            <h3>Add a Single Question</h3>
            <p>
                <label for="testtype">Test Type:</label>
                <select name="testtype" id="testtype">
                        <option value="ACT">ACT</option>
                        <option value="SAT">SAT</option>
                    </select>
            </p>
            <p>
                <label for="passage">Passage:</label>
                <input type="text" name="passage" id="passage" value=" ">
            </p>
            <p>
                <label for="question">Question:</label>
                <input type="text" name="question" id="question">
            </p>
            <p>
                <label for="answerchoices">Answer Choices:
                    <input type="text" name="answerchoices1">
                    <input type="text" name="answerchoices2">
                    <input type="text" name="answerchoices3">
                    <input type="text" name="answerchoices4">
                    <input type="text" name="answerchoices5">
                </label>
            </p>
            <p>
                <label for="answercorrect">Correct Answer:</label>
                <input type="text" name="answercorrect" id="answercorrect">
            </p>
            <p>
                <label for="explanation">Explanation:</label>
                <input type="text" name="explanation" id="explanation">
            </p>
            <p>
                    <label for="subjectarea">Subject Area:</label>
                    <input type="text" name="subjectarea" id="subjectarea">
                    <label for="skill">Skill:</label>
                    <input type="text" name="skill" id="skill">
                </p>
            <input type="submit" name="submit_single_question" value="Add Question">

            <!-- CSV Upload -->
            <h3>Upload Questions via CSV</h3>
            <p>
                <input type="file" name="questions_csv" id="questions_csv">
            </p>
            <input type="submit" name="submit_questions_csv" value="Upload CSV">
        </form>
    </div>
    <?php
}

// Function to display the plugin admin page
function practice_tests_view_edit_question_page() {
    ?>
    <div class="wrap">
        <h2>View / Edit Questions</h2>
        <p>This page will have a dropdown to select tables with questions. The table will render and the questions, answers, correct answer, and explanations will all be editable.</p>
        <?php
            render_question_bank_table(); // inside utilities.php
        ?>
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
