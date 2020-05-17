<?php
/*
Plugin Name: Antiques Club Custom Fields
Plugin URI:  https://www.antiques-fun.club/
Description: Add custom fields to user and posts to meet Antiques fun club site required
Version:     1.0
Author:      Shelly Zhou
Author URI:  https://www.antiques-fun.club/
*/

require_once "classes/class-user-custom.php";


function antiques_club_custom(){
	
	new ANTIQUES_USER_CUSTOM();	
	
}

add_action( 'init', 'antiques_club_custom');

?>