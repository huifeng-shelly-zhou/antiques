<?php
header("Content-type: charset=utf-8");
require_once( $_SERVER["DOCUMENT_ROOT"] . '/wp-load.php' );

$redirect_url = '';
$token = '';

if (isset($_GET['redirect_url'])){
	
	$value = trim($_GET['redirect_url']);
	
	$jsonString = base64_decode($value);
	if ( $jsonString !== false ){
		$data = json_decode($jsonString, true);
		
		if ( isset($data) && isset($data['redirect_url']) ){
			$redirect_url = trim($data['redirect_url']);
		}
		
		if ( isset($data) && isset($data['token']) ){
			$token = trim($data['token']);
		}		
	}
}

//echo '<!--';
//var_dump($redirect_url);
//var_dump($token);
//echo '-->';

if (strlen($redirect_url) < 4 || substr($redirect_url, 0, 4) != 'http'){
	displayMessage('Invalid redirect url!');
	exit();
}

$user_id = validateToken($token);
if ($user_id == false){
	displayMessage('Invalid user token!');
	exit();
}



// auto login user
$user = get_user_by( 'id', $user_id );
if (is_a($user, 'WP_User')){
	clean_user_cache($user->ID);
	wp_clear_auth_cookie();
	
	wp_set_current_user( $user->ID, $user->user_login );
	wp_set_auth_cookie( $user->ID );
	update_user_caches($user);
}
// end auto login user

if (get_current_user_id() > 0){	
	//echo get_current_user_id();
	wp_safe_redirect( $redirect_url );
	exit;
}
else {	
	wp_safe_redirect( admin_url() );
	exit();
} 

function validateToken($token){
	$member_mapping = get_option( 'bp_centralized_member_mapping', array()); //(mixed) Value set for the option.
		
		//invalid token
		if ( !isset($member_mapping[$token]) || !isset($member_mapping[$token]['id']) || !isset($member_mapping[$token]['expiration']) ){
			return false;
		}
		
		$user_id = $member_mapping[$token]['id'];
		$myDate = new DateTime("now", new DateTimeZone("Asia/Hong_Kong"));		
		$now = $myDate->format( DateTime::ATOM ); 
		
		
		$hasValidToken = false;
		
		foreach ($member_mapping as $token=>$member){			
			if ( isset($member['id']) && $member['id'] == $user_id && isset($member['expiration'])){				
				if ($member['expiration'] > $now){	
					$hasValidToken = true;
					break;
				}								
			}			
		}
		
		//all user tokens expried
		if ( $hasValidToken == false ){
			return false;
		}
		
		return $user_id;
}

function displayMessage($msg) {
	if (!empty($msg)) {
		echo "<!DOCTYPE html>
		<html>
			<head>
				<meta charset=\"UTF-8\">
				<title>Authorize</title>
				<meta name=\"viewport\" content=\"width=device-width\">
				<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
					
				<style>
					body{
						background: #f1f1f1;
						color: #444;
						margin: 2em auto;						
						max-width: 700px;
						font-family: -apple-system, BlinkMacSystemFont, Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
						-webkit-box-shadow: 0 1px 3px rgba(0,0,0,0.13);
						box-shadow: 0 1px 3px rgba(0,0,0,0.13);
					}
				</style>
			</head>
			<body>
				<p style=\"font-size: 14px;line-height: 1.5;padding: 2em 1em;background: #fff;\">".$msg."</p>
			</body>
		</html>";
	}
}
?>