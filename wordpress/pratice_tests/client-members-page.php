<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function render_member_sidebar(){
    $sidebar_output = "<div id='member-sidebar'>";
    $sidebar_output .= "<ul id='member-sidebar-list'>";
    $sidebar_output .= "<li id='member-sidebar-list-item-1' class='clickable'>Tests</li>";
    $sidebar_output .= "<li id='member-sidebar-list-item-2' class='clickable'>Feedback</li>";
    $sidebar_output .= "<li id='member-sidebar-list-item-3' class='clickable'>Learning</li>";
    $sidebar_output .= "<li id='member-sidebar-list-item-4' class='clickable'>Profile</li>";
    $sidebar_output .= "<li id='member-sidebar-list-item-5' class='clickable'>Sign Out</li>";
    $sidebar_output .= "</ul>";
    $sidebar_output .= "</div>"; // close sidebar
    return $sidebar_output;
}

function render_member_nav(){
    $nav_output = "<div id='member-nav' >";
    $nav_output .= "<ul id='member-nav-list' style='width:100%;display:flex;flex-direction:row;justify-content:space-evenly;'>";
    $nav_output .= "<li id='member-nav-list-item-1' class='clickable'>Take Test</li>";
    $nav_output .= "<li id='member-nav-list-item-2' class='clickable'>See Results</li>";
    $nav_output .= "<li id='member-nav-list-item-3' class='clickable'>Upgrade</li>";
    $nav_output .= "<li id='member-nav-list-item-4' class='clickable'>Add Student</li>";
    $nav_output .= "</ul>";
    $nav_output .= "</div>"; // close nav
    return $nav_output;
}

function render_member_display(){
    $display_output = "<div id='member-display' >";
    $display_output .= "<div id='member-display-row-1'>"; 
    $display_output .= "<p>Display Row 1 - There will be test selection items here</p>";
    $display_output .= "</div>"; // close display row 1
    $display_output .= "<div id='member-display-row-2'>"; 
    $display_output .= "<p>Display Row 2 - There will be things like timer settings and some other things here.</p>";
    $display_output .= "</div>"; // close display row 2
    $display_output .= "</div>"; // close display
    return $display_output;
}

function practice_tests_members_page(){
    $output = "<div id='members-area' style='width:100%;display:flex;flex-direction:row;justify-content:space-evenly;'>";
    $output .= "<div id='members-sidebar-container' style='width:10%'>";
    $output .= render_member_sidebar();
    $output .= "</div>"; // close sidebar container
    $output .= "<div id='members-main-container' style='width:60%'>";
    $output .= render_member_nav();
    $output .= render_member_display();
    $output .= "</div>"; // close main container
    $output .= "</div>"; // close member's area div
    return $output;
}


add_shortcode('practice_tests_members', 'practice_tests_members_page');