<?php

add_theme_support( 'post-thumbnails' );


//Post custom
if (file_exists( dirname(__FILE__) .'/includes/admin/sub-function-post-custom.php')) {
	require_once ('includes/admin/sub-function-post-custom.php');
}

//Share common functions
if (file_exists( dirname(__FILE__) .'/includes/admin/sub-functions.php')) {
	require_once ('includes/admin/sub-functions.php');
}

//Emails functions
if (file_exists( dirname(__FILE__) .'/includes/admin/sub-functions-emails.php')) {
	require_once ('includes/admin/sub-functions-emails.php');
}


//Making jQuery Google API
function modify_jquery() {
	if (!is_admin()) {
		// comment out the next two lines to load the local copy of jQuery
		wp_deregister_script('jquery');
		wp_register_script('jquery', get_template_directory_uri() . '/js/jquery.min.3.4.1.js', false, '1.0');
		wp_enqueue_script('jquery');
	}
}
//add_action('init', 'modify_jquery');


add_action( 'wp_enqueue_scripts', 'add_theme_scripts' );
function add_theme_scripts() { 
	
    wp_enqueue_style( 'bootstrap', get_template_directory_uri() . '/css/bootstrap.min.4.4.1.css', array(), '1.0', 'all');
	wp_enqueue_style( 'style', get_stylesheet_uri() );
	
	//wp_enqueue_script( 'jquery', get_template_directory_uri() . '/js/jquery.min.3.4.1.js', array (), '1.0', false);
	wp_enqueue_script( 'popper', get_template_directory_uri() . '/js/popper.min.1.16.0.js', array ('jquery'), '1.0', false);
    wp_enqueue_script( 'bootstrap', get_template_directory_uri() . '/js/bootstrap.min.4.4.1.js', array ('jquery'), '1.0', false);
	
}



?>