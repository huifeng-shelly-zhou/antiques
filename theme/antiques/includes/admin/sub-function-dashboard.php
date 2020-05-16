<?php
/**
 * Add/Remove a widget to the dashboard.
 *
 * This function is hooked into the 'wp_dashboard_setup', 'admin_init' action below.
 */

function ait_add_dashboard_widgets() {

	wp_add_dashboard_widget(
                 'ait_dashboard_contact_widget',         // Widget slug.
                 'Need Help? ',         // Title.
                 'ait_dashboard_contact_function' // Display function.
        );	
}
add_action( 'wp_dashboard_setup', 'ait_add_dashboard_widgets' );

/**
 * Create the function to output the contents of our Dashboard Widget.
 */
function ait_dashboard_contact_function() {
	echo '<p>For technical support please, <a href="mailto:support@aitsolution.ca"> click here email to support@aitsolution.ca </a></p>';
}


function remove_dashboard_meta() {
        remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');//since 3.8
}
add_action( 'admin_init', 'remove_dashboard_meta' );
?>