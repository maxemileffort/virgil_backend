<?php

if ( ! defined( 'ABSPATH' ) ) exit;


function practice_tests_members_page(){
    $output = 'Members will go here.';
    return $output;
}


add_shortcode('practice_tests_members', 'practice_tests_members_page');