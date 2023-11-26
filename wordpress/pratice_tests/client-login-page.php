<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function practice_tests_login_page(){
    $output = "<form action='#' method='post'>";
    $output .="<label for='login_user_email'>Email:</label>";
    $output .="<input type='email' id='login_user_email' name='login_user_email' required>";
    $output .= "<br>";

    $output .="<label for='login_user_password'>Password:</label>";
    $output .="<input type='password' id='login_user_password' name='login_user_password' required>";
    $output .= "<br>";

    $output .="<input type='submit' id='login_user_btn' name='login_user' value='Login'>";
    $output .="</form> ";
    $output .="<p>Dont have an account? <a href='registration-guardian/'>Register as a parent here.</a></p>";
    $output .="<p>Are you a student? <a href='registration-student/'>Register as a student here.</a></p>";
    return $output;
}

add_shortcode('practice_tests_login', 'practice_tests_login_page');