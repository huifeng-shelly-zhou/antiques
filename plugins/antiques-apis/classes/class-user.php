<?php

class ANTIQUES_USER
{
	public $id = '';
	public $user_name = '';
	public $first_name = '';
	public $last_name = '';
	public $display_name = '';
	public $user_email = '';
	public $user_url = '';
	public $user_registered = '';
	public $avatar = '';
	public $description = '';
	public $token = '';
	public $token_expiration ='';	
	public $roles = array();	
	
	public function __construct(){ 
    }
	
	public function setUser($user){
		
		if ( is_a($user, 'WP_User') ){
			
			$this->id = $user->ID;
			$this->user_name = $user->user_login;
			$this->first_name =$user->first_name;
			$this->last_name =$user->last_name;
			$this->display_name = $user->data->display_name;
			$this->user_email = $user->user_email;			
			$this->roles = $user->roles;			
			
			$this->avatar = get_user_meta($user->ID, 'author_profile_picture', true);
			if ( empty($this->avatar) ){
				$this->avatar = esc_url( get_avatar_url( $user->ID ) );
			}
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
	
	
	public function validateToken(){		
			
		if (!isset($_POST['token']) || empty($_POST['token'])){
			return false;
		}			
		
		$token = trim($_POST['token']);
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
			'user' => null
		);
		
		if (!isset($post_params['authorize']) || empty($post_params['authorize'])){
		
			$result->success = false;
			$result->message = apiLang('Error on validating user login session.', $lang);
			$result->data = null;
			return $result;
		}
		
		list($email, $password)=explode(':',base64_decode($post_params['authorize']));
		
		// check email is verified
		$user = get_user_by( 'email', $email ); // return WP_User object on success, false on failure.
		if ( is_a($user, 'WP_User') && get_user_meta($user->ID, 'user-approved', true) != '1'){
			$result->success = false;
			$result->message = '<span>Please verify your account before login! <a href="/login/?action=send_verfication&email='.base64_encode($user->user_email).'">Resend Verification Link</a></span>';	
			$result->data = null;			
			return $result;
		}
		
		$user = wp_authenticate( $email , $password ); 
		
		if ( is_wp_error( $user ) ) {
			$result['message'] = $user->get_error_message();
			return $result;
		}
		
		
		$result['success'] = true;
		
		$this->setUser($user);
		
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
		else if (validatePassword($password) !== true){
			$result->message= '密码需多于八个字符!';
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
}

?>