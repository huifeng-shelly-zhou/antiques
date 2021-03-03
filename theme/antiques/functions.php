<?php

add_theme_support( 'post-thumbnails' );

//add lib
if (file_exists( dirname(__FILE__) .'/lib/anq_conversion.php')) {
	require_once ('lib/anq_conversion.php');
}

//User custom
if (file_exists( dirname(__FILE__) .'/includes/admin/sub-function-user.php')) {
	require_once ('includes/admin/sub-function-user.php');
}

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

add_action('init', 'antique_rewrite_permastruct');
function antique_rewrite_permastruct() {
    global $wp_rewrite;
	
	// add post url rule
	add_rewrite_rule('article/([0-9]+)/?','index.php?p=$matches[1]','top');	
	
	// rewrite author url
	$wp_rewrite->author_base = 'author';
	$wp_rewrite->author_structure = '/' . $wp_rewrite->author_base . '/%author_id%-%author_displayname%';	
	add_rewrite_rule('author/([0-9]+)-([^/]*)/?','index.php?author=$matches[1]&author_displayname=$matches[2]','top');
	add_rewrite_rule('author/([0-9]+)/?','index.php?author=$matches[1]','top');
	
	$wp_rewrite->flush_rules();		
}

add_filter('author_link', 'antique_author_link',10,3);
function antique_author_link($url,$author_id,$author_nicename) {	
	//var_dump($url);
	$url = str_replace( '/article', '', $url );	
	if ( false !== strpos( $url, '%author_id%' ) ) {
		if(!isset($author_id)){
			$author_id = 0;
		}
		$url = str_replace( '%author_id%', $author_id, $url );	
	
    }
	
	if ( false !== strpos( $url, '%author_displayname%' ) ) {
		$author_display_name = '';
		
		$author_obj = get_user_by('id', $author_id);
		if(is_a($author_obj, 'WP_User')){
			$author_display_name = urlencode($author_obj->display_name);			
		}
		$url = str_replace( '%author_displayname%', $author_display_name, $url );       
	}
	
    return $url;
}

?>