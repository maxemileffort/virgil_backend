<?php

if ( ! defined( 'ABSPATH' ) ) exit;


function practice_tests_login_page(){
    $output = 'Login will go here.';
    return $output;
}


add_shortcode('practice_tests_login', 'practice_tests_login_page');