<?php

/**
 * Filter the new user notification email.
 *
 * @param $email array New user notification email parameters.
 * @return $email array New user notification email parameters.
 */
function antiques_new_user_notification_email_callback( $email, $user, $blogname ) {
	
	if (is_a($user, 'WP_User')){
		
		$user_id = $user->ID;
		$lang = get_user_meta($user_id, 'my_lang', true);
		$site_name = get_bloginfo( 'name' );
		$user_login = stripslashes($user->user_login);
		
		// Generate something random for a password reset key.
		$myDate = new DateTime("now", new DateTimeZone("Asia/Hong_Kong"));
		$myDate->modify('+24 hour');
		$expiration = $myDate->format( DateTime::ATOM ); 
		$key = wp_generate_password( 40, false );		
		
		add_user_meta($user_id, '_antiques_email_verify_key', array('key'=>$key, 'expiration'=>$expiration), true);
		
		$verify_link = get_home_url().'/login/?action=verify_account&user_login='.base64_encode($user_login).'&key='.$key;
		
		$body = "尊敬的 [username]，你好：\r\n\r\n";
		$body .= "非常感谢你注册成为[site_name]的用戶。\r\n";
		$body .= "为验证此邮箱地址属于你，请点击以下链接进行验证。\r\n";
		$body .= "[verify-link]。\r\n\r\n\r\n";		
		$body .= "此致\r\n\r\n";
		$body .= "[site_name]团队\r\n";

		// set user name
		$body = str_replace('[username]',$user->data->display_name, $body);
		// set verify link	
		$body = str_replace('[verify-link]',$verify_link, $body);
		// set site name	
		$body = str_replace('[site_name]',$site_name, $body);		
		
		$body = antLang($body, $lang);
		
		
		$email['subject'] = antLang('您的'.$blogname.'帐户已创建！', $lang);
		$email['message'] = $body;		
	}	

    return $email;
} 
add_filter( 'wp_new_user_notification_email', 'antiques_new_user_notification_email_callback', 10, 3);


function emailNewAccount($user_id){
		
	wp_new_user_notification($user_id, null, 'both');
	return true;	
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
		$subject = antLang('新驗證碼', $lang);
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
		// set site name	
		$body = str_replace('[password]', $pw, $body);
		
		$subject = antLang($subject, $lang);
		$body = antLang($body, $lang);
		
		return wp_mail($email,$subject, $body, $headers);//Returns (bool) Whether the email contents were sent successfully.
	}
	
	return false;
	
}

?>