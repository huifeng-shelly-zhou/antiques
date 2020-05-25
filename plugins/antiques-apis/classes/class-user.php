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
			$this->description = get_user_meta($user->ID, 'description', true);
			
			$this->avatar = get_user_meta($user->ID, 'author_profile_picture', true);
			if ( empty($this->avatar) ){
				$this->avatar = esc_url( get_avatar_url( $user->ID ) );
			}
			
			$this->approved = (get_user_meta($user->ID, '_user_approved', true) === 'true')? true:false;
		}
	}

	private function genToken() {
		
		if ( empty($this->id) ){
			return '';
		}
		$myDate = new DateTime("now", new DateTimeZone("Asia/Hong_Kong"));
		$myDate->modify('+24 hour');
		$this->token_expiration = $myDate->format( DateTime::ATOM ); 
		
		$this->token = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
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
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
		
		$this->token = $this->token.$this->id;
		
		return $this->token;
	}//genToken
	

	private function updateToken($user_id, $newToken, $newExpiration){
		
		$member_mapping = get_option( 'antiques_member_mapping', array()); //(mixed) Value set for the option.
		
		// add new record
		$member_mapping[$newToken] = array(
			'id' => $user_id,
			'expiration' => $newExpiration
		);
		
		
		// update mapping
		if (!update_option( 'antiques_member_mapping', $member_mapping )){		
			add_option( 'antiques_member_mapping', $member_mapping );
		}		
	}
	
	private function clearUserTokens($user_id){
		
		$member_mapping = get_option( 'antiques_member_mapping', array()); //(mixed) Value set for the option.
		
		$updated_member_mapping = array_filter( function ($values) {	
			return ( isset($values['id']) && $values['id'] != $user_id);
		}, $member_mapping);
		
		// update mapping
		if (!update_option( 'antiques_member_mapping', $updated_member_mapping )){		
			add_option( 'antiques_member_mapping', $updated_member_mapping );
		}
	}
	
	
	public function validateToken($post_params){		
			
		if (!isset($post_params['token']) || empty($post_params['token'])){
			return false;
		}			
		
		$token = trim($post_params['token']);
		$member_mapping = get_option( 'antiques_member_mapping', array()); //(mixed) Value set for the option.
		
		//invalid token
		if ( !isset($member_mapping[$token]) || !isset($member_mapping[$token]['id']) || !isset($member_mapping[$token]['expiration']) ){
			return false;
		}
		
		$user_id = $member_mapping[$token]['id'];
		$myDate = new DateTime("now", new DateTimeZone("Asia/Hong_Kong"));		
		$now = $myDate->format( DateTime::ATOM ); 
		
		
		$hasValidToken = false;
		$expiredTokens = array();
		foreach ($member_mapping as $token=>$member){
			
			if ( isset($member['id']) && $member['id'] == $user_id && isset($member['expiration'])){
				
				if ($member['expiration'] <= $now){
					$expiredTokens[] = $token;
				}
				else{
					$hasValidToken = true;
				}				
			}			
		}
		
		// remove expried tokens
		if ( count($expiredTokens)>0 ){
			foreach($expiredTokens as $t){
				unset($member_mapping[$t]);	
			}
			
			// save change
			if (!update_option( 'antiques_member_mapping', $member_mapping )){		
				add_option( 'antiques_member_mapping', $member_mapping );
			}
		}
		
		
		//all user tokens expried
		if ( $hasValidToken == false ){
			return false;
		}
		
		return $user_id;
		
	}//validateToken

	
	public function login($post_data){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'approved' => false,
			'user' => null
		);
		
		if (!isset($post_params['authorize']) || empty($post_params['authorize'])){
		
			$result['message'] = '验证用户登录信息出错。';
			
			return $result;
		}
		
		list($email, $password)=explode(':',base64_decode($post_params['authorize']));
		
		// check email is verified
		$user = get_user_by( 'email', $email ); // return WP_User object on success, false on failure.
		if ( is_a($user, 'WP_User') && get_user_meta($user->ID, 'user-approved', true) != '1'){
			$result['approved'] = false;
			$result['message'] = '请先验证您的邮件地址再登录！';	
			$result['resend_link'] = get_home_url().'/OX/api/user/send/verification?verify='.base64_encode($user->user_email);	
					
			return $result;
		}
		$result['approved'] = true;
		
		$user = wp_authenticate( $email , $password ); 
		
		if ( is_wp_error( $user ) ) {
			$result['message'] = $user->get_error_message();
			return $result;
		}
		
		
		$result['success'] = true;
		
		$this->populateUser($user);
		if (isset($post_data['firebase_token'])){
			
			if (!update_user_meta($this->id, 'firebase_token', $post_data['firebase_token'])){
				add_user_meta($this->id, 'firebase_token', $post_data['firebase_token'], true);
			}
		}
		
		//update user token		
		$newToken = $this->genToken();
		$this->updateToken($this->id, $newToken, $this->token_expiration);				
		$result['user'] = $this;
		
		return $result;
		
	}
	
	
	public function register($post_params){
		
		$result = array(
			'success'=> false,
			'message'=>'',
		);
		
		if (!isset($post_params['authorize']) || empty($post_params['authorize'])){		
			$result->data = null;
			$result->message = 'Error. No email and password.';		
			return $result;
		}
		
		list($email, $password)=explode(':',base64_decode($post_params['authorize']));
		
		if (is_email( $email ) == false){
			$result->message = '电子信箱格式不符!';
			$result->data = null;
			return $result;
		}	
		else if ( ($valid = validate_password($password)) !== true){
			$result->message= $valid;
			$result->data = null;		
			return $result;
		}	
		else if (email_exists($email)){
			$result->message = '此电子信箱已被注册, 请使用其他电子信箱或点击此处重置密码!';	
			$result->data = null;		
			return $result;
		}
		else {		
			
			$user_id = wp_create_user( $email, $password, $email );//When successful returns the user ID,In case of failure (username or email already exists) the function returns an error object;
			
			if (is_wp_error($user_id)){
				$result->message = $user_id->get_error_message();				
				return $result;
			}
			else if (is_int($user_id) && $user_id > 0){
				
				// update user first and last name
				$args = array(
					'ID' => $user_id,
				);
				$display_name = '';
				if ( isset($post_params['first_name']) && !empty($post_params['first_name']) ){
					$args['first_name'] = sanitize_text_field($post_params['first_name']);								
				}
				
				if ( isset($post_params['last_name']) && !empty($post_params['last_name']) ){
					$args['last_name'] = sanitize_text_field($post_params['last_name']);
				}	
				
				if ( isset($args['first_name']) && isset($args['last_name']) ){
					$args['display_name'] = $args['first_name'] . ' ' . $args['last_name'];
				}
				
				wp_update_user( $args ); //If successful, returns the user_id, otherwise returns a WP_Error object.
				
				
				// set user role to vendor
				$user_info  = get_userdata( $user_id );
				$user_info->set_role('antique_player');
				
				$result->success = true;				
				$result->message = '注册成功！请到您的电子邮箱查看您的验证电子邮件！';
				
				// send email to notify new user
				if (function_exists('emailNewAccount')){
					emailNewAccount($user_id);
				}			
			}
			else {
				$result->message = 'Registration is not success due to some reason. Please try again!';
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
	
		$result['display_name'] = $userdata->data->display_name;		
		$result['success'] = true;
		
		return $result;
	}

	
	public function profile($post_params){
		$result = array(
			'success'=> false,
			'message'=>'',
			'user' => null
		);
		
		// validate user has the right to create new user
		$user_id= $this->validateToken($post_params);
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		$user = get_userdata( $user_id );
		
		if ( is_wp_error( $user ) ) {
			$result['message'] = $user->get_error_message();
			return $result;
		}
		
		$this->populateUser($user);
		
		$result['user'] = $this;
		$result['success'] = true;
		
		return $result;
		
	}
	
	
	public function profile_update($post_params){
		$result = array(
			'success'=> false,
			'message'=>'',
			'user' => null
		);
		
		// validate user has the right to create new user
		$user_id= $this->validateToken($post_params);
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		$user = new WP_User( $user_id );

		if ( ! $user->exists() ) {
            $result['message'] = 'User with ID '.$user_id.' is not existed!';
			return $result;
        }
		
		
		// check required parameters
		if ( !isset($post_params['email']) || !is_email($post_params['email']) ){			
			$result['message'] = 'Invalid user email!';
			return $result;
		}
		// end check required parameters
		
		//$user->user_email = trim($post_params['email']);


		// check optional parameters
		if ( isset($post_params['display_name']) ){		
			$user->data->display_name = sanitize_text_field($post_params['display_name']);				
		}
		else{
			$user->data->display_name = '';
		}
		
		if ( isset($post_params['first_name']) ){			
			$user->first_name = sanitize_text_field($post_params['first_name']);
		}
		else{
			$user->first_name = '';
		}
		
		if ( isset($post_params['last_name']) ){			
			$user->last_name = sanitize_text_field($post_params['last_name']);			
		}
		else {
			$user->last_name = '';
		}		
		
		// description
		if ( isset($post_params['description']) ){			
			$description = sanitize_textarea_field($post_params['description']);			
		}
		else {
			$description = '';
		}				
		$updated = update_user_meta( $user_id, 'description', $description );		
		if ( $updated === false && $description != get_user_meta($user_id, 'description', true) ){
			$result['message'] = 'Error on updating user description!';
			return $result;	
		}
		// end description
		
		
		// avatar update
		$avatar = '';
		if ( isset($post_params['avatar']) ){			
			$avatar = sanitize_textarea_field($post_params['avatar']);			
		}		
		$avatar_updated = update_user_meta( $user_id, 'author_profile_picture', $avatar );
		if ( $avatar_updated === false && $avatar != get_user_meta($user_id, 'author_profile_picture', true) ){
			$result['message'] = 'Error on updating user avatar!';
			return $result;	
		}
		// end avatar update
		
		// end check optional parameters		
		
		
		$return_user_id = wp_update_user( $user );		
		
		if ( is_wp_error( $return_user_id ) ) {
			// There was an error; possibly this user doesn't exist.
			$result['message'] = $return_user_id->get_error_message();
			return $result;			
		}
		
		
		
		// password must be reset after user updated
		if ( isset($post_params['new_password']) && !empty($post_params['new_password']) ){
			
			$new_password = base64_decode(trim($post_params['new_password']));			
			
			if ( ($valid = validate_password($new_password)) !== true ){
				$result['message'] = $valid;
				return $result;	
			}			
			
			reset_password( $user, $new_password );
			$result['reset_password'] = true;
		}
		// end password updated
		
		
		$user_data = get_userdata( $return_user_id );
		$this->populateUser($user_data);
		
		$result['success'] = true;
		$result['user'] = $this;
		return $result;
	}


	public function password_reset($post_params){	

		$result = array(
			'success'=>false,
			'message'=>'重置密码失败！ 请稍后再试！'
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
					
					//clear user token
					$this->clearUserTokens($user->data->ID);
						
					$success = sendNewPwToUser($user->data->display_name, $email, $random_password);
					if ($success === true){
						$result['success'] = true;
						$result['message'] = '重设密码成功！ 请在电子邮件中查找您的新密码！';
					}
				}				
				
			}
			else {
				$result['message'] = '电子邮件地址不存在系统中！'; 
			}
		}	

		return $result;
	}


	public function verify_email($post_params){	
	
		$result = array(
			'success'=>false,
			'message'=>'验证电子邮件地址失败！',
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
				
				$result['success'] = true;
			}
			else if (isset($saved_key['key']) && isset($saved_key['expiration']) && !empty($saved_key['key']) && $saved_key['key'] == $key && $saved_key['expiration'] >= $now ){
				update_user_meta($user->ID, 'user-approved','1'); 
				update_user_meta($user->ID, '_antiques_email_verify_key', '');			
				$result['success'] = true;
				
			}
			else if (isset($saved_key['key']) && isset($saved_key['expiration']) && !empty($saved_key['key']) && $saved_key['key'] == $key && $saved_key['expiration'] < $now ){				
				
				$result['message'] .= ' 验证码已过期！';
				$result['resend_link'] = get_home_url().'/OX/api/user/send/verification?verify='.base64_encode($user->user_email);				
			}
			else {
				$result['resend_link'] = get_home_url().'/OX/api/user/send/verification?verify='.base64_encode($user->user_email);	
			}
		}
		
		if ($result['success'] == true){
			
			$result['message']= '您的帐户已通过验证！';
			
			$this->populateUser($user);
			if (isset($post_params['firebase_token'])){
				
				if (!update_user_meta($this->id, 'firebase_token', $post_params['firebase_token'])){
					add_user_meta($this->id, 'firebase_token', $post_params['firebase_token'], true);
				}
			}
			
			//update user token		
			$newToken = $this->genToken();
			$this->updateToken($this->id, $newToken, $this->token_expiration);				
			$result['user'] = $this;			
		}
		
		return $result;
		
	}
	
	
}

?>