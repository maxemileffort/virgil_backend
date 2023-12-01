<?php

if ( ! defined( 'ABSPATH' ) ) exit;


function render_member_sidebar(){
    $sidebar_output = '<p>This will be the sidebar.</p>';
    return $sidebar_output;
}

function render_member_nav(){
    $nav_output = '<p>This will be navigation within sidebar items.</p>';
    return $nav_output;
}

function render_member_display(){
    $display_output = '<p>This will display nav items.</p>';
    return $display_output;
}

function practice_tests_members_page(){
    $output = "<div id='members-area' style='display:flex;flex-direction:row;justify-content:space-evenly;'>";
    $output .= "<div id='members-sidebar-container' style='width:10%'>";
    $output .= render_member_sidebar();
    $output .= "</div>"; // close sidebar container
    $output .= "<div id='members-main-container' style='width:50%'>";
    $output .= render_member_nav();
    $output .= render_member_display();
    $output .= "</div>"; // close main container
    $output .= "</div>"; // close member's area div
    return $output;
}


add_shortcode('practice_tests_members', 'practice_tests_members_page');