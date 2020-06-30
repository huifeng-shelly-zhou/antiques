<?php

function emailNewAccount($user_id){
		
	// notify admin
	wp_new_user_notification($user_id);
	
	
	// notify vendor
	$user = new WP_User($user_id);
	$user_login = stripslashes($user->user_login);
	$user_display_name = $user->data->display_name;
	$user_email = $user->user_email;
	$site_name = get_bloginfo( 'name' );
	$lang = get_user_meta($user_id, 'my_lang', true);

	
	// Generate something random for a password reset key.
	$myDate = new DateTime("now", new DateTimeZone("Asia/Hong_Kong"));
	$myDate->modify('+24 hour');
	$expiration = $myDate->format( DateTime::ATOM ); 
	$key = wp_generate_password( 40, false );		
	
	add_user_meta($user_id, '_antiques_email_verify_key', array('key'=>$key, 'expiration'=>$expiration), true);
	
	$verify_link = get_home_url().'/login/?action=verify_account&user_login='.base64_encode($user_login).'&key='.$key;
	
	// get email html content
	$template = ABSPATH . 'wp-content/themes/antiques/templates/email_new_account.html';
	
	if (file_exists($template)){
		
		$subject = '您的'.$site_name.'帳戶已創建！';
		$headers = array();
		$headers[] = "Content-Type: text/html; charset=UTF-8";
		//$headers[] = "Cc: Shop <Shop@aitsolution.ca>";	
		
		$body = file_get_contents($template);
		// set user name
		$body = str_replace('[username]',$user_display_name, $body);
		// set verify link	
		$body = str_replace('[verify-link]',$verify_link, $body);
		// set site name	
		$body = str_replace('[site_name]',$site_name, $body);

		// set password
		if ($password == ''){
			$body = str_replace('[password]','', $body);
		}
		else{
			$password_words = '您的密碼是: '.$password;
			$body = str_replace('[password]',$password_words, $body);
		}
		
		if (strpos($lang, 'cn') !== false && function_exists('hk_to_cn')) {
			$subject = hk_to_cn($subject);
			$body = hk_to_cn($body);
		}
		
		return wp_mail($user_email,$subject, $body, $headers);
	}	

	return false;
}
	
	
function resendVerification($email){
	
	$user = get_user_by( 'email', $email );
	$template = ABSPATH . 'wp-content/themes/antiques/templates/email_new_verify_key.html';
	
	if (is_a($user, 'WP_User') && file_exists($template)){
		
		$user_login = stripslashes($user->user_login);
		$user_display_name = $user->data->display_name;
		$user_email = $user->user_email;
		$lang = get_user_meta($user->ID, 'my_lang', true);
		$site_name = get_bloginfo( 'name' );
		$subject = '新驗證碼';
		$headers = array();
		$headers[] = "Content-Type: text/html; charset=UTF-8";			
		
		// Generate something random for a password reset key.
		$myDate = new DateTime("now", new DateTimeZone("Asia/Hong_Kong"));
		$myDate->modify('+24 hour');
		$expiration = $myDate->format( DateTime::ATOM ); 
		$key = wp_generate_password( 40, false );
		
		update_user_meta($user->ID, '_antiques_email_verify_key', array('key'=>$key, 'expiration'=>$expiration));
	
		
		$verify_link = get_home_url().'/login/?action=verify_account&user_login='.base64_encode($user_login).'&key='.$key;
		
		// get email html content
		$body = file_get_contents($template);
		// set user name
		$body = str_replace('[username]',$user_display_name, $body);
		// set verify link	
		$body = str_replace('[verify-link]',$verify_link, $body);
		// set site name	
		$body = str_replace('[site_name]', $site_name, $body);
		
		$subject = antLang($subject, $lang);
		$body = antLang($body, $lang);		
		
		return wp_mail($user_email,$subject, $body, $headers);
	}
	
	return false;	
}

function sendNewPwToUser($name, $email, $pw){
	global $lang;
	
	$template = ABSPATH . 'wp-content/themes/antiques/templates/email_new_password.html';
	if (file_exists($template)){		
		
		$site_name = get_bloginfo( 'name' );
		$subject = "重置密碼成功";
		$headers = array();
		$headers[] = "Content-Type: text/html; charset=UTF-8";		
		
		// get email html content
		$body = file_get_contents($template);
		// set user name
		$body = str_replace('[username]',$name, $body);
		// set verify link	
		$body = str_replace('[email]',$email, $body);
		// set site name	
		$body = str_replace('[site_name]', $site_name, $body);
		
		if ($lang != null && strpos($lang, 'cn') !== false && function_exists('hk_to_cn')) {
			$subject = hk_to_cn($subject);
			$body = hk_to_cn($body);
		}
		
		return wp_mail($email,$subject, $body, $headers);//Returns (bool) Whether the email contents were sent successfully.
	}
	
	return false;
	
}

?>