<?php

if ( ! defined( 'ABSPATH' ) ) exit;


function practice_tests_plans_page(){
    $output = 'Plans will go here.';
    return $output;
}


add_shortcode('practice_tests_plan', 'practice_tests_plans_page');