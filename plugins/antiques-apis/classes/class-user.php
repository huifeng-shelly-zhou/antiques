<?php

class ANTIQUES_USER
{
	public $id = '';	
	public $first_name = '';
	public $last_name = '';
	public $display_name = '';
	public $email = '';
	public $user_url = '';
	public $approved = false;
	public $avatar = '';
	public $description = '';
	public $token = '';
	public $token_expiration ='';	
	public $roles = array();
	public $providers = array();
	
	public function __construct(){ 
    }
	
	private function populateUser($user){
		
		if ( is_a($user, 'WP_User') ){
			
			$this->id = $user->ID;			
			$this->first_name =$user->first_name;
			$this->last_name =$user->last_name;
			$this->display_name = empty($user->data->display_name)? $user->first_name:$user->data->display_name;
			$this->email = $user->user_email;			
			$this->roles = $user->roles;
			
			$user_meta = get_user_meta($user->ID);
			foreach($user_meta as $key=>$value){
				
				if ($key == 'description'){
					$this->description = $value[0];
				}
				else if($key == 'author_profile_picture'){
					$this->avatar = $value[0];
				}
				else if($key == 'login_providers'){
					$this->providers = unserialize($value[0]);
				}
				else if($key == 'user-approved'){					
					$this->approved = ($value[0] === '1')? 'true':'false'; 
				}				
			}		
			
		}
	}

	private function genToken() {
		
		if ( empty($this->id) ){
			return '';
		}
		$myDate = new DateTime("now", new DateTimeZone("Asia/Hong_Kong"));
		$myDate->modify('+7 days');
		$this->token_expiration = $myDate->format( DateTime::ATOM ); 
		
		$this->token = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x-'.$this->id.'%04x',
			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,

			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			
			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff )
		);
		
		return $this->token;
	}//genToken
	

	private function updateToken($user_id, $newToken, $newExpiration){
		
		$tokens = get_user_meta($user_id, 'antiques_member_tokens', true);
		if(!isset($tokens) || !is_array($tokens)){
			$tokens = array();
		}
		
		// add new record
		$tokens[$newToken] = $newExpiration;		
		
		// update mapping
		if (!update_user_meta($user_id, 'antiques_member_tokens',$tokens)){
			add_user_meta($user_id, 'antiques_member_tokens',$tokens, true);
		}
	}
	
	
	public function validateToken($post_params, $check_token_expired = true){		
			
		if (!isset($post_params['token']) || empty($post_params['token'])){
			return false;
		}			
		
		$token = trim($post_params['token']);
		
		$token_splits = explode('-',$token);
		$user_id=end($token_splits);
		if (empty($user_id) || strlen($user_id) <= 4)
			return false;
		
		
		$length = strlen($user_id) - 4;
		$user_id = substr($user_id,0, $length);
		
		$user_tokens = get_user_meta($user_id, 'antiques_member_tokens', true);
		if(!isset($user_tokens) || !is_array($user_tokens)){
			$user_tokens = array();
		}
		
		//invalid token
		if ( !isset($user_tokens[$token]) ){
			return false;
		}
		
		if (!$check_token_expired){
			return $user_id;
		}

		// valide expiration date
		$myDate = new DateTime("now", new DateTimeZone("Asia/Hong_Kong"));		
		$now = $myDate->format( DateTime::ATOM ); 
		$expiration = $user_tokens[$token];
		
		if ($expiration <= $now){
			unset($user_tokens[$token]);
			update_user_meta($user_id, 'antiques_member_tokens',$user_tokens);
			
			return false;
		}	
		
		return $user_id;
		
	}//validateToken

	
	public function login($post_params, $lang='hk'){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'approved' => false,
			'user' => null,			
		);
		
		if (!isset($post_params['authorize']) || empty($post_params['authorize'])){
		
			$result['message'] = antLang('验证用户登入信息出错。', $lang);
			$result['message'] = ($lang == 'en')? 'Error on verifying user login information.':$result['message'];
			
			return $result;
		}
		
		list($email, $password)=explode(':',base64_decode($post_params['authorize']));
		
		// check email is verified
		$user = get_user_by( 'email', $email ); // return WP_User object on success, false on failure.
		if ( is_a($user, 'WP_User') && get_user_meta($user->ID, 'user-approved', true) != '1'){
			$result['approved'] = false;
			$result['message'] = antLang('请先验证您的邮件地址再登入！',  $lang);	
			$result['message'] = ($lang == 'en')? 'Please verify your email address before login!':$result['message'];
			$result['resend_link'] = get_home_url().'/OX/api/user/send/verification?verify='.base64_encode($user->user_email);	
					
			return $result;
		}
		$result['approved'] = true;
		
		$user = wp_authenticate( $email , $password ); 
		
		if ( is_wp_error( $user ) ) {
			$result['message'] = antLang('邮件地址或密碼不正確。',  $lang);
			$result['message'] = ($lang == 'en')? 'Incorrect email address or password.':$result['message'];	
			return $result;
		}
		
		
		$result['success'] = true;
		
		$this->populateUser($user);
		if (isset($post_params['firebase_token'])){
			
			if (!update_user_meta($this->id, 'firebase_token', $post_params['firebase_token'])){
				add_user_meta($this->id, 'firebase_token', $post_params['firebase_token'], true);
			}
		}
		
		// save user selected lang
		if (!update_user_meta($this->id, 'my_lang', $lang)){
			add_user_meta($this->id, 'my_lang', $lang, true);
		}
		
		//update user token		
		$newToken = $this->genToken();
		$this->updateToken($this->id, $newToken, $this->token_expiration);				
		$result['user'] = $this;
		
		
		return $result;
		
	}
	
	
	public function providerAccount($post_params, $lang = 'hk'){
		
		$result = array(
			'success'=> false,
			'message'=>'',			
			'user' => null,			
		);
		
		if (!isset($post_params['authorize']) || empty($post_params['authorize'])){		
			
			$result['message'] = 'No email and id.';		
			return $result;
		}
		
		if (!isset($post_params['provider']) || empty($post_params['provider'])){		
			
			$result['message'] = 'Provider not found!';		
			return $result;
		}
		
		list($email, $provider_account_id)=explode(':',base64_decode($post_params['authorize']));
		
		$provider = trim($post_params['provider']);		
		
		if (is_email( $email ) == false){
			$result['message'] = antLang('电子信箱格式不符!', $lang);
			$result['message'] = ($lang == 'en')? 'Invalid email address.':$result['message'];
			return $result;
		}
		
		$user = get_user_by( 'email', $email );
		if ( $user ){
			$user_id = $user->ID;
		}
		else{
			// create user if not exist
			$user_id = wp_create_user( $email, $provider_account_id, $email );
			if (is_wp_error($user_id)){
				$result['message'] = $user_id->get_error_message();				
				return $result;
			}
			
			$user = get_userdata( $user_id );
			$user->set_role('player');
			
			if (!update_user_meta($user_id, 'user-approved','1')){
				add_user_meta($user_id, 'user-approved','1', true);
			}
		}
		
		// update user		
		$user->user_email = $email;
		
		if ( isset($post_params['display_name']) && !empty($post_params['display_name']) ){
			$user->data->display_name = sanitize_text_field($post_params['display_name']);
		}
		
		wp_update_user( $user );
		
		if (isset($post_params['firebase_token'])){
			
			if (!update_user_meta($user_id, 'firebase_token', $post_params['firebase_token'])){
				add_user_meta($user_id, 'firebase_token', $post_params['firebase_token'], true);
			}
		}
		
		// save user selected lang
		if (!update_user_meta($user_id, 'my_lang', $lang)){
			add_user_meta($user_id, 'my_lang', $lang, true);
		}
		
		// save user login provider
		$user_providers =  get_user_meta($user_id, 'login_providers', true);
		if (empty($user_providers)){
			$user_providers = array();
		}
		else if (!is_array($user_providers)){
			$user_providers = unserialize($user_providers);
		}		
		if (!in_array($provider, $user_providers)){
			// if not the same provider, add the new provider to the account
			$user_providers[] = $provider;			
			update_user_meta($user_id, 'login_providers', $user_providers);
		}
		
		
		if (isset($post_params['avatar']) && strlen($post_params['avatar']) > 4 && substr($post_params['avatar'], 0, 4) == 'http'){
			
			$file_name = $provider.'_avatar_'.$user_id.'.png';
			$result['file_name'] = $file_name;
			$wp_upload_dir = wp_upload_dir();
			$path = isset($wp_upload_dir['path'])? $wp_upload_dir['path']:'';
			$url = isset($wp_upload_dir['url'])? $wp_upload_dir['url']:'';
			
			if (!empty($path)){
				
				$image = $path.'/'.$file_name;
				$image_url = $url.'/'.$file_name;
				
				file_put_contents($image, file_get_contents($post_params['avatar']));
				
				if (!update_user_meta($user_id, 'author_profile_picture', $image_url)){
					add_user_meta($user_id, 'author_profile_picture', $image_url, true);
				}
			}
		}
				
		
		// get user updated info
		$user = get_userdata( $user_id );
		$this->populateUser($user);
		
		
		//update user token		
		$newToken = $this->genToken();
		$this->updateToken($this->id, $newToken, $this->token_expiration);				
		$result['user'] = $this;
		$result['success'] = true;
		
		return $result;
		
	}
	
	
	public function register($post_params, $lang = 'hk'){
		
		$result = array(
			'success'=> false,
			'message'=>'',
		);
		
		if (!isset($post_params['authorize']) || empty($post_params['authorize'])){		
			
			$result['message'] = 'No email and password.';		
			return $result;
		}
		
		list($email, $password)=explode(':',base64_decode($post_params['authorize']));
		
		if (is_email( $email ) == false){
			$result['message'] = antLang('电子信箱格式不符!', $lang);
			$result['message'] = ($lang == 'en')? 'Invalid email address.':$result['message'];
			return $result;
		}	
		else if ( ($valid = validate_password($password, $lang)) !== true){
			$result['message']= $valid;		
			return $result;
		}	
		else if (email_exists($email)){
			
			$user = get_user_by( 'email', $email );
			$user_providers =  get_user_meta($user->ID, 'login_providers', true);
			if( is_array($user_providers) && count($user_providers) > 0 ){
				$result['message'] = antLang('此电子信箱是已'.implode(', ', $user_providers).'注册, 请使用你的'.implode(', ', $user_providers).'帳號登入!', $lang);
				$result['message'] = ($lang == 'en')? 'This email is already registered by '.implode(', ', $user_providers).' login. Please use your '.implode(', ', $user_providers).' account to login again!':$result['message'];
			}
			else{
				$result['message'] = antLang('此电子信箱已被注册, 请使用其他电子信箱或重置密码!', $lang);
				$result['message'] = ($lang == 'en')? 'This email has been used. Please use another email or reset your password!':$result['message'];
			}			
			
			return $result;
		}
		else {		
			
			$user_id = wp_create_user( $email, $password, $email );//When successful returns the user ID,In case of failure (username or email already exists) the function returns an error object;
			
			if (is_wp_error($user_id)){
				$result['message'] = $user_id->get_error_message();				
				return $result;
			}
			else if (is_int($user_id) && $user_id > 0){
				
				// update user first and last name
				$args = array(
					'ID' => $user_id,
				);
				
				if ( isset($post_params['display_name']) && !empty($post_params['display_name']) ){
					$args['display_name'] = sanitize_text_field($post_params['display_name']);
				}				
				
				wp_update_user( $args ); //If successful, returns the user_id, otherwise returns a WP_Error object.
				
				
				// set user role to vendor
				$user_info  = get_userdata( $user_id );
				$user_info->set_role('player');
				
				// set firebase notification if have
				if (isset($post_params['firebase_token'])){
			
					if (!update_user_meta($user_id, 'firebase_token', $post_params['firebase_token'])){
						add_user_meta($user_id, 'firebase_token', $post_params['firebase_token'], true);
					}
				}
				
				// save user selected lang
				if (!update_user_meta($user_id, 'my_lang', $lang)){
					add_user_meta($user_id, 'my_lang', $lang, true);
				}
				
				
				$result['success'] = true;				
				$result['message'] = antLang('注册成功！请到您的电子邮箱查看您的验证电子邮件！', $lang);
				$result['message'] = ($lang == 'en')? 'Registration success! Please check your verification email in your email address!':$result['message'];
				
				// send email to notify new user
				if (function_exists('emailNewAccount')){
					emailNewAccount($user_id);
				}			
			}
			else {
				$result['message'] = 'Registration is not success due to some reason. Please try again!';
			}
			
		}	
		
		return $result;
	}

	
	public function validate($post_params){
		$result = array(
			'success'=> false,
			'message'=>'',
			'display_name' => ''
		);
		
		$user_id = $this->validateToken($post_params);
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		$user=get_userdata($user_id);
		if ( is_wp_error( $user ) ) {
			$result['message'] = $user->get_error_message();
			return $result;
		}
	
		$result['display_name'] = $user->data->display_name;		
		$result['success'] = true;
		
		return $result;
	}

	
	public function profile($post_params, $lang = 'hk'){
		$result = array(
			'success'=> false,
			'message'=>'',
			'user' => null
		);
		
		// validate user has the right to create new user
		$user_id= $this->validateToken($post_params);
		if ($user_id==false){
			//header('HTTP/1.0 401 Unauthorized');
			$result['message'] = antLang('登入已失效. 請重新登入!', $lang);
			$result['message'] = ($lang == 'en')? 'Login has expired. Please login again!':$result['message'];
			return $result;			
		}
		
		$user = get_userdata( $user_id );
		
		if ( is_wp_error( $user ) ) {
			$result['message'] = antLang($user->get_error_message(), $lang);
			return $result;
		}
		
		$this->populateUser($user);
		
		$result['user'] = $this;
		$result['success'] = true;
		
		return $result;
		
	}
	
	
	public function profile_update($post_params, $lang = 'hk'){
		$result = array(
			'success'=> false,
			'message'=>'',
			'user' => null
		);
		
		// validate user has the right to create new user
		$user_id= $this->validateToken($post_params);
		if ($user_id==false){
			//header('HTTP/1.0 401 Unauthorized');
			$result['message'] = antLang('登入已失效. 請重新登入!', $lang);
			return $result;			
		}
		
		$user = new WP_User( $user_id );

		if ( ! $user->exists() ) {
            $result['message'] = 'User with ID '.$user_id.' is not existed!';
			return $result;
        }
		

		// check optional parameters
		if ( isset($post_params['display_name']) ){		
			$user->data->display_name = sanitize_text_field($post_params['display_name']);				
		}
		if (empty($user->data->display_name)){
			$user->data->display_name = explode('@', $user->user_email)[0];
		}		
		
	
		// description
		if ( isset($post_params['description']) ){			
			$description = sanitize_textarea_field($post_params['description']);			
		}
		else {
			$description = '';
		}				
		$updated = update_user_meta( $user_id, 'description', $description );		
		
		// end description		
		
		// end check optional parameters		
		
		
		$return_user_id = wp_update_user( $user );		
		
		if ( is_wp_error( $return_user_id ) ) {
			// There was an error; possibly this user doesn't exist.
			$result['message'] = antLang($return_user_id->get_error_message(), $lang);
			return $result;			
		}
		
		
		
		// password must be reset after user updated
		if ( isset($post_params['updated_password']) && !empty($post_params['updated_password']) ){
			
			$updated_password = base64_decode(trim($post_params['updated_password']));			
			
			if ( ($valid = validate_password($updated_password)) !== true ){
				$result['message'] = antLang($valid, $lang);
				return $result;	
			}			
			
			reset_password( $user, $updated_password );
			$result['reset_password'] = true;
		}
		// end password updated
		
		
		$user_data = get_userdata( $return_user_id );
		$this->populateUser($user_data);
		
		$result['success'] = true;
		$result['message'] = antLang('更新成功！', $lang);
		$result['user'] = $this;
		return $result;
	}


	public function password_reset($post_params, $lang='hk'){	

		$result = array(
			'success'=>false,
			'message'=>antLang('重置密码失败！ 请稍后再试！', $lang)
		);
		
		if (isset($post_params['email'])){
			
			$email = base64_decode(trim($post_params['email']));		
			
			
			$user = get_user_by( 'email', $email ); // return WP_User object on success, false on failure.
			
			if ($user && is_a($user, 'WP_User')){			
				
				//send email
				if (function_exists('sendNewPwToUser')){
					
					// gen new pw
					$random_password = wp_generate_password(16);
					
					// reset user pw
					reset_password( $user, $random_password );					
						
					$success = sendNewPwToUser($user->data->display_name, $email, $random_password);
					if ($success === true){
						$result['success'] = true;
						$result['message'] = antLang('重设密码成功！ 请在电子邮件中查找您的新密码！', $lang);
					}
				}				
				
			}
			else {
				$result['message'] = antLang('电子邮件地址不存在系统中！', $lang); 
			}
		}	

		return $result;
	}


	public function verify_email($post_params, $lang = 'hk'){	
	
		$result = array(
			'success'=>false,
			'message'=>antLang('验证电子邮件地址失败！', $lang),
			'resend_link'=>'',
			'user' => null,
		);
		
		$user_login = isset($post_params['user_login'])? base64_decode(trim($post_params['user_login'])):'';
		$key = isset($post_params['key'])? trim($post_params['key']):'';
		
		$user = get_user_by('login', $user_login);
		if ( $user ){
		
			$saved_key = get_user_meta($user->ID, '_antiques_email_verify_key', true);
			$approved = get_user_meta($user->ID, 'user-approved', true);
			
			$myDate = new DateTime("now", new DateTimeZone("Asia/Hong_Kong"));		
			$now = $myDate->format( DateTime::ATOM ); 
			
			if ($approved == '1'){
				
				$result['message'] = antLang('您的电子邮件地址已被验证过！', $lang);
			}
			else if (isset($saved_key['key']) && isset($saved_key['expiration']) && !empty($saved_key['key']) && $saved_key['key'] == $key && $saved_key['expiration'] >= $now ){
				update_user_meta($user->ID, 'user-approved','1'); 
				update_user_meta($user->ID, '_antiques_email_verify_key', '');			
				$result['success'] = true;
				
			}
			else if (isset($saved_key['key']) && isset($saved_key['expiration']) && !empty($saved_key['key']) && $saved_key['key'] == $key && $saved_key['expiration'] < $now ){				
				
				$result['message'] .= antLang(' 验证码已过期！', $lang);
				$result['resend_link'] = get_home_url().'/OX/api/user/send/verification?verify='.base64_encode($user->user_email);				
			}
			else {
				$result['resend_link'] = get_home_url().'/OX/api/user/send/verification?verify='.base64_encode($user->user_email);	
			}
		}
		
		if ($result['success'] == true){
			
			$result['message']= antLang('您的帐户已通过验证！', $lang);
			
			$this->populateUser($user);
			if (isset($post_params['firebase_token'])){
				
				if (!update_user_meta($this->id, 'firebase_token', $post_params['firebase_token'])){
					add_user_meta($this->id, 'firebase_token', $post_params['firebase_token'], true);
				}
			}
			
			// save user selected lang
			if (!update_user_meta($this->id, 'my_lang', $lang)){
				add_user_meta($this->id, 'my_lang', $lang, true);
			}
			
			//update user token		
			$newToken = $this->genToken();
			$this->updateToken($this->id, $newToken, $this->token_expiration);				
			$result['user'] = $this;			
		}
		
		return $result;
		
	}
	

	public function langUpdate($post_params, $lang){
		$result = array(
			'success'=> false,
			'message'=>'',			
		);
		
		// validate user has the right to create new user
		$user_id= $this->validateToken($post_params);
		if ($user_id==false){
			//header('HTTP/1.0 401 Unauthorized');
			$result['message'] = antLang('登入已失效. 請重新登入!', $lang);
			return $result;			
		}
		
		// save user selected lang
		if (!update_user_meta($user_id, 'my_lang', $lang)){
			add_user_meta($user_id, 'my_lang', $lang, true);
		}
		
		$result['success'] = true;
		return $result;		
	}
	
	
	public function deleteImage($post_params, $lang){
		
		$result = array(
			'success'=> false,
			'message'=>'',			
		);
		
		// validate user has the right to create new user
		$user_id= $this->validateToken($post_params, false);
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');			
			return $result;			
		}
		
		if ( !isset($post_params['image_id']) || empty($post_params['image_id']) || !is_numeric($post_params['image_id'])){
			$result['message'] = 'Invalid image Id!';
			return $result;
		}
		
		if ( !isset($post_params['antique_id']) || empty($post_params['antique_id']) || !is_numeric($post_params['antique_id'])){
			$result['message'] = 'Invalid antique Id!';
			return $result;
		}
		
		$image_id = intval($post_params['image_id']);
		$antique_id = intval($post_params['antique_id']);
		
		$return = remove_antique_gallery_images_id($antique_id, $image_id);
		
		if (is_a($return, 'WP_Post')){
			$result['success'] = true;
			$result['message'] = 'Delete image with id '.$image_id.' in antique '.$antique_id.' success';
		}
		else{
			$result['message'] = 'Delete image with id '.$image_id.' in antique '.$antique_id.' failure';
		}
		
		return $result;
	}

	
	public function swapImages($post_params, $lang){
		
		$result = array(
			'success'=> false,
			'message'=>'',			
		);
		
		// validate user has the right to create new user
		$user_id= $this->validateToken($post_params, false);
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');			
			return $result;			
		}
		
		if ( !isset($post_params['image_id_1']) || empty($post_params['image_id_1']) || !is_numeric($post_params['image_id_1'])){
			$result['message'] = 'Invalid first image Id!';
			return $result;
		}
		
		if ( !isset($post_params['image_id_2']) || empty($post_params['image_id_2']) || !is_numeric($post_params['image_id_2'])){
			$result['message'] = 'Invalid second image Id!';
			return $result;
		}
		
		if ( !isset($post_params['antique_id']) || empty($post_params['antique_id']) || !is_numeric($post_params['antique_id'])){
			$result['message'] = 'Invalid antique Id!';
			return $result;
		}
		
		$image_id_1 = intval($post_params['image_id_1']);
		$image_id_2 = intval($post_params['image_id_2']);
		$antique_id = intval($post_params['antique_id']);
		
		$result['success'] = swap_antique_gallery_images($antique_id, $image_id_1, $image_id_2);
		
		if ($result['success']){
			$result['message'] = 'swap success from server';
		}
		else{
			$result['message'] = 'swap failure from server';
		}
		
		return $result;
	}

	
	public function uploadImage($post_params, $lang){
		
		/*
		var_dump($_FILES);

		"array(1) {
		  ["image"]=>
		  array(5) {
			["name"]=>
			string(12) "1595536337902.jpeg"
			["type"]=>
			string(10) "image/jpeg"
			["tmp_name"]=>
			string(14) "/tmp/php9ydynS"
			["error"]=>
			int(0)
			["size"]=>
			int(614971)
		  }
		}
		*/

		$result = array(
			'success'=> false,
			'message'=>'',
			'attachment_id' => 0,
			'uploaded_url' => '',
		);
		
		// validate user has the right to create new user
		$user_id= $this->validateToken($post_params, false);
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');			
			return $result;			
		}
		
		
		$allowed_type = array('image/jpg','image/jpeg','image/png','image/gif');
		
		$file = isset($_FILES['image'])? $_FILES['image']:'';
		$antique_id = (isset($post_params['antique_id']) && !empty($post_params['antique_id']))? $post_params['antique_id']:0;
		$attachment_type = isset($post_params['attachment_type'])? $post_params['attachment_type']:'';
		
		if (empty($file) || empty($attachment_type)){
			$result['message'] = 'Invalid parameters!';
			return $result;
		}
		
		if ( !isset($file['type']) || !in_array($file['type'], $allowed_type)){
			$result['message'] = 'Invalid image type!';
			return $result;
		}
		
		
		// login user
		if ($user_id != get_current_user_id()){
			$user = get_user_by( 'id', $user_id );
			clean_user_cache($user_id);
			wp_clear_auth_cookie();
			
			wp_set_current_user( $user_id, $user->user_login );
			wp_set_auth_cookie( $user_id );
			update_user_caches($user);
		}
		
		if (isset($_FILES['image']['name'])){
			
			$_FILES['image']['name'] = str_replace('.', $user_id.mt_rand( 10, 1000 ).'.', $_FILES['image']['name']);
		}
		
		
		// These files need to be included as dependencies when on the front end.
		require_once( ABSPATH . '/wp-admin/includes/image.php' );
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		require_once( ABSPATH . '/wp-admin/includes/media.php' );
		
		// Check that the nonce is valid
		if ($file['size'] > 0 ){
			// The nonce was valid, it is safe to continue.	

			$overrides = array(
				/*
				 * Tells WordPress to not look for the POST form fields that would
				 * normally be present, default is true, we downloaded the file from
				 * a remote server, so there will be no form fields.
				 */
				'test_form' => false,
		 
				// Setting this to false lets WordPress allow empty files, not recommended.
				'test_size' => true,
		 
				// A properly uploaded file will pass this test. There should be no reason to override this one.
				'test_upload' => true,
				
				'action' => 'wp_handle_sideload',
			);
			// Let WordPress handle the upload.
			
				
			// Remember, 'my_image_upload' is the name of our file input in our form above.
			$attachment_id = media_handle_upload( 'image', $antique_id, array(), $overrides );
			
			if ( is_wp_error( $attachment_id ) ) {
				// There was an error uploading the image.
				return $result;
				
			} else {
				
				
				// The image was uploaded successfully!
				$image_attributes = wp_get_attachment_image_src($attachment_id, 'medium'); // medium, thumbnail, large, full
				if ($image_attributes && $image_attributes[1] == 1){
					$image_attributes = wp_get_attachment_image_src($attachment_id, 'large');
					$attachment_url = $image_attributes[0];						
				}
				else if ( $image_attributes ) {
					$attachment_url = $image_attributes[0];						
				}

				$result['success'] = true;
				$result['uploaded_url'] = $attachment_url;	
				$result['attachment_id'] = $attachment_id;
				
			}		
			
		}
		
		if (strtoupper($attachment_type) == 'AVATAR' && !empty($result['uploaded_url'])){
			if (!update_user_meta($user_id, 'author_profile_picture', $result['uploaded_url'])){
				add_user_meta($user_id, 'author_profile_picture', $result['uploaded_url'], true);
			}
		}
		
		
		if (strtoupper($attachment_type) == 'ANTIQUE' && !empty($result['uploaded_url'])){
			
			add_antique_gallery_images_id($antique_id, $attachment_id);			
		}
		
		if (strtoupper($attachment_type) == 'ANTIQUE_HEADLINE' && !empty($result['uploaded_url'])){
			
			set_antique_headline_image($antique_id, $attachment_id);
		}
		
		if (strtoupper($attachment_type) == 'ANTIQUE_PROMOTION' && !empty($result['uploaded_url'])){
			
			set_antique_promotion_image($antique_id, $attachment_id);	
		}
		
		return $result;	
	}
	
	
}

?>