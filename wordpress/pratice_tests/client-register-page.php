<?php

if ( ! defined( 'ABSPATH' ) ) exit;

include_once plugin_dir_path(__FILE__) . 'practice-tests.php';

function practice_tests_register_new_guardian_user_page() {
    $output = "<form action='#' method='post'>";
    $output .= "<label for='user_name'>First Name:</label>";
    $output .= "<input type='text' id='user_name' name='user_name' required>";
    $output .= "<br>";

    $output .="<label for='user_email'>Email:</label>";
    $output .="<input type='email' id='user_email' name='user_email' required>";
    $output .= "<br>";

    $output .="<label for='user_password'>Password:</label>";
    $output .="<input type='password' id='user_password' name='user_password' required>";
    $output .= "<br>";

    $output .="<input type='hidden' name='user_action' value='add'>";
    $output .="<input type='hidden' name='user_status' value='active'>";
    $output .="<input type='hidden' name='user_role' value='guardian'>";

    $output .="<input type='submit' name='submit_single_user' value='Register'>";
    $output .="</form> ";

    return $output;
}

function practice_tests_register_new_student_user_page() {
    
    $output = "<form action='#' method='post'>";
    $output .= "<label for='user_name'>First Name:</label>";
    $output .= "<input type='text' id='user_name' name='user_name' required>";
    $output .= "<br>";

    $output .="<label for='user_email'>Email:</label>";
    $output .="<input type='email' id='user_email' name='user_email' required>";
    $output .= "<br>";

    $output .="<label for='user_password'>Password:</label>";
    $output .="<input type='password' id='user_password' name='user_password' required>";
    $output .= "<br>";

    $output .="<input type='hidden' name='user_action' value='add'>";
    $output .="<input type='hidden' name='user_status' value='active'>";
    $output .="<input type='hidden' name='user_role' value='student'>";

    $output .="<input type='submit' name='submit_single_user' value='Register'>";
    $output .="</form> ";

    return $output;
    
}

add_shortcode('practice_test_reg_guardian', 'practice_tests_register_new_guardian_user_page');
add_shortcode('practice_test_reg_student', 'practice_tests_register_new_student_user_page');