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
	
	
	public function login($post_data){
		
		global $ALLOWED_ROLES;		
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'user' => null
		);
		
		// check required parameters
		if ( !isset($_POST['username']) ){			
			$result['message'] = 'User name is not found!';
			return $result;
		}
		
		if ( !isset($_POST['password']) ){			
			$result['message'] = 'Password not found!';
			return $result;
		}
		// end check required parameters
		
		$username  = trim($_POST['username']);
		$password = trim($_POST['password']);		
		
		$user = wp_authenticate( $username , $password ); 
		
		if ( is_wp_error( $user ) ) {
			$result['message'] = $user->get_error_message();
			return $result;
		}
		
		
		// check user right		
		$allowed = false;
		foreach ($user->roles as $role){
			if (in_array($role, $ALLOWED_ROLES)){
				$allowed = true;
				break;
			}
		}
		
		if (!$allowed){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] ='You are unauthorized.';
			return $result;
		}
		// end check user right
		
		$result['success'] = true;
		
		$this->setUser($user);
		
		//update user token		
		$newToken = $this->genToken();
		$this->updateToken($this->id, $newToken, $this->token_expiration);				
		$result['user'] = $this;
		
		return $result;
		
	}
	
}

?>