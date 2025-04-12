<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function render_member_sidebar(){
    // Added data-action attributes for JS targeting
    // Added basic href="#" to make them look like links
    $sidebar_output = "<div id='member-sidebar'>";
    $sidebar_output .= "<ul id='member-sidebar-list'>";
    $sidebar_output .= "<li><a href='#' class='member-nav-link' data-action='view_tests'>Tests</a></li>"; // Renamed for clarity
    $sidebar_output .= "<li><a href='#' class='member-nav-link' data-action='view_performance'>Performance</a></li>"; // Changed from Feedback
    $sidebar_output .= "<li><a href='#' class='member-nav-link' data-action='view_learning'>Learning</a></li>"; // Placeholder
    $sidebar_output .= "<li><a href='#' class='member-nav-link' data-action='view_profile'>Profile</a></li>"; // Placeholder
    $sidebar_output .= "<li><a href='" . wp_logout_url(home_url()) . "'>Sign Out</a></li>"; // Direct logout link
    $sidebar_output .= "</ul>";
    $sidebar_output .= "</div>"; // close sidebar
    return $sidebar_output;
}

function render_member_nav(){
     // Added data-action attributes for JS targeting
     // Added basic href="#" to make them look like links
    $nav_output = "<div id='member-nav' >";
    $nav_output .= "<ul id='member-nav-list' style='width:100%;display:flex;flex-direction:row;justify-content:space-evenly; list-style: none; padding: 0; margin: 10px 0;'>";
    $nav_output .= "<li><a href='#' class='member-nav-link button' data-action='view_tests'>Take Test</a></li>"; // Changed from Take Test
    $nav_output .= "<li><a href='#' class='member-nav-link button' data-action='view_results_history'>See Results History</a></li>"; // Changed from See Results
    $nav_output .= "<li><a href='#' class='member-nav-link button' data-action='view_upgrade'>Upgrade</a></li>"; // Placeholder
    // TODO: Conditionally show 'Add Student' based on user role (guardian)
    if (current_user_can('manage_students')) { // Example capability check - needs implementation
         $nav_output .= "<li><a href='#' class='member-nav-link button' data-action='add_student'>Add Student</a></li>"; // Placeholder
    }
    $nav_output .= "</ul>";
    $nav_output .= "</div>"; // close nav
    return $nav_output;
}

function render_member_display(){
    // Initial state is empty, content will be loaded via AJAX
    // Added a loading indicator
    $display_output = "<div id='member-display' style='border: 1px solid #ccc; padding: 15px; min-height: 300px;'>";
    $display_output .= "<div id='member-display-loading' style='display: none; text-align: center; padding: 50px;'>Loading...</div>";
    $display_output .= "<div id='member-display-content'></div>"; // Content will be loaded here
    $display_output .= "</div>"; // close display
    return $display_output;
}

function practice_tests_members_page(){
    // Enqueue the members area handler script only when this shortcode is used
    wp_enqueue_script('practice-test-members-handler');
    // Localize data needed by the members handler script
    wp_localize_script('practice-test-members-handler', 'practiceTestMembers', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('practice_test_members_nonce') // Create a specific nonce
    ));


    $output = "<div id='members-area' style='width:100%;display:flex;flex-direction:row;justify-content:space-evenly;'>";
    // Basic styling added for structure - replace with proper CSS later
    $output .= "<div id='members-sidebar-container' style='width:15%; padding-right: 15px;'>";
    $output .= render_member_sidebar();
    $output .= "</div>"; // close sidebar container
    $output .= "<div id='members-main-container' style='width:80%;'>";
    $output .= render_member_nav();
    $output .= render_member_display();
    $output .= "</div>"; // close main container
    $output .= "</div>"; // close member's area div
    return $output;
}
add_shortcode('practice_tests_members', 'practice_tests_members_page');
