<?php
error_reporting(E_ALL);


global $antiquea_apis;
$antiquea_apis = new ANTIQUES_APIS();


class ANTIQUES_APIS {
	
	private $name;
	private $version;
	private $controllers;
	
	public function __construct( $config = array() )
	{
		
		$this->load_dependencies();
		$this->init_hooks();
		
		
	} // __construct
	
	private function load_dependencies() {
	
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-antiques-wp-rewrite.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/setting.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-user.php';
		
	}
	
	public function init_hooks(){
		/**
		** add a rewrite rule to plugin page.
		**	
		** URL path reference:
		** 		http://domain.com/$name/$controller/$action/
		** 		http://domain.com/index.php?'.$name.'=$controller&action=$action
		
		example:
		/OX/api/categories : all category
		/OX/api/categories/<id> : post list on specific category		
		/OX/api/post/<postid> : post on default category
		/OX/api/user/<userid>
	
		**/
		
		$this->name='OX';
		$this->version='1.0.0';	
		$this->controllers = array('api');
				
		$plugin_rewrite = new Antiques_API_Rewrite( $this->name, $this->version );	
		add_action( 'init', array( &$plugin_rewrite, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( &$plugin_rewrite, 'rewrite_query_vars') );	
		add_action( 'template_redirect', array( &$this, 'action_redirect') );	
		
		/*
		**	assign exception handler
		*/
		//	set_exception_handler('aitExceptionHandler');
		
	} // init_hooks()
	
	public function action_redirect(){
		
		global $API_KEY, $API_SECRET;
		
	
		
		// validate controller
		$current_controller = get_query_var( $this->name );	
		if ( empty( $current_controller ) ) {
			return;
		}
		
		
		header('Content-Type: application/json');
		
		if ( !in_array(strtolower( $current_controller ), $this->controllers) ) {
			$arr = array('success'=> false,'message'=>'unsupported controller : '.$current_controller);
			die( json_encode($arr) );
		}
		
		
		// validate action	
		$action = get_query_var( 'action' );	
		if ( empty( $action ) ) {
			$arr = array('success'=> false,'message'=>'Action is required.');
			die( json_encode($arr) );
		}
		
		
		// validate key and secret
		$headers = $this->parseRequestHeaders();
		if($headers["Key"] != $API_KEY || $headers["Secret"] != $API_SECRET)
		{
			$arr = array('success'=> false,'message'=>'Incorrect key and secret pair.');
			die( json_encode($arr) );
		}
		
		
		
		$result = array(
			'success'=> false,
			'headers'=>$headers,
			'controller'=>$current_controller,
			'action'=> str_replace('/', ' ', $action),
			'message'=>''
			
		);
		
		$action = str_replace('/','_',$action);
		if (method_exists($this,$action)){
			$result = call_user_func(array($this,$action));			
		}
		else {
			$result['message'] = 'Action '.$action.' not exists';
		}
		
		die(json_encode($result));
		
	}
		
	private function parseRequestHeaders() {
	
		$headers = array();
		foreach($_SERVER as $key => $value) {
			if (substr($key, 0, 5) <> 'HTTP_') {
				continue;
			}
			$header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
			$headers[$header] = $value;
		}
		return $headers;
	}
		
	private function validateToken(){		
			
		if (!isset($_POST['token']) || empty($_POST['token'])){
			return false;
		}			
		
		$token = trim($_POST['token']);
		$member_mapping = get_option( 'bp_centralized_member_mapping', array()); //(mixed) Value set for the option.
		
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
			if (!update_option( 'bp_centralized_member_mapping', $member_mapping )){		
				add_option( 'bp_centralized_member_mapping', $member_mapping );
			}
		}
		
		
		//all user tokens expried
		if ( $hasValidToken == false ){
			return false;
		}
		
		return $user_id;
		
	}//validateToken
	
	private function updateToken($user_id, $newToken, $newExpiration){
		
		$member_mapping = get_option( 'bp_centralized_member_mapping', array()); //(mixed) Value set for the option.
		
		// add new record
		$member_mapping[$newToken] = array(
			'id' => $user_id,
			'expiration' => $newExpiration
		);
		
		
		// update mapping
		if (!update_option( 'bp_centralized_member_mapping', $member_mapping )){		
			add_option( 'bp_centralized_member_mapping', $member_mapping );
		}
		
	}

	private function getSource(){
		global $SOURCE_LIST;
		
		$source = $_SERVER['HTTP_HOST'];
		if (isset($SOURCE_LIST[$source])){
			$source = $SOURCE_LIST[$source];
		}
		
		return $source;
	}

	

	private function savePostAutoSetting( $post, $auto_social_share, $auto_facebook_post ) {	
		if ( !is_a($post, 'WP_Post') ){
			return;
		}
		
		if ( isset( $auto_social_share ) ) {
			if ( ($auto_social_share === true || $auto_social_share == 'true' || $auto_social_share == '1') ) {
				update_post_meta( $post->ID, '_auto_social_share', 'pending_social_share' );
			} else if ( metadata_exists( $post->post_type, $post->ID, '_auto_social_share' ) ) {
				delete_post_meta( $post->ID, '_auto_social_share' );	
			}
		}
		
		if ( $post->post_status == 'publish' && isset( $auto_facebook_post ) && ($auto_facebook_post === true || $auto_facebook_post == 'true' || $auto_facebook_post == '1') ) {
			update_post_meta( $post->ID, '_auto_facebook_post', 'pending_facebook_post' );			
		}
		else if ( metadata_exists( $post->post_type, $post->ID, '_auto_facebook_post' ) ) {
			delete_post_meta( $post->ID, '_auto_facebook_post' );
		}		
	}
	
	private function updatePostCategories( $post_id, $ids ) {	
		
		$cat_ids = wp_set_post_categories($post_id, $ids);
		//return (boolean|mixed)  array of category IDs that were assigned to the post ID.
		//If no categories are passed with a post ID that has a post type of post, the default category will be used.
				
		if ( isset($cat_ids) && !$cat_ids ){			
			return false;
		}
		
		if ( isset($cat_ids) && count($ids) == 0 ){			
			foreach ($cat_ids as $term_id){
				$term = get_term($term_id);
				$updated = wp_remove_object_terms( $post_id, $term->slug, 'category' );//(bool|WP_Error) True on success, false or WP_Error on failure.
				if ( !$updated ){
					return false;
				}
			}			
		}	
		
		return true;
		
	}
	
	private function updatePostTags( $post_id, $tags ) {	
		
		if ( !empty($tags) ){
			$tag_ids = wp_set_post_terms($post_id, $tags, 'post_tag');
			if ( !is_array($tag_ids) ){				
				return false;
			}
		}
		else{
			$post_tags = get_the_tags($post_id);
			if ($post_tags){
				foreach ( $post_tags as $tag ){
					$updated = wp_remove_object_terms( $post_id, $tag->slug, 'post_tag' );//(bool|WP_Error) True on success, false or WP_Error on failure.
					if ( !$updated ){
						return false;
					}
				}
			}			
		}
		
		return true;
		
	}

	private function updatePostSeoTags( $post_id, $seo_tags ) {		
		
		if ( !empty($seo_tags) && taxonomy_exists( 'seo_tag' ) ){
			$tag_ids = wp_set_post_terms($post_id, $seo_tags, 'seo_tag'); //(array|boolean|WP_Error|string) 
			
			if ( is_wp_error($tag_ids) ){				
				return $tag_ids->get_error_message();
			}
		}
		else if ( empty($seo_tags) && taxonomy_exists( 'seo_tag' ) ) {
			
			$tags =  wp_get_post_terms( $post_id, 'seo_tag');//(array|WP_Error) Array of WP_Term objects on success or empty array if no terms were found. WP_Error object if $taxonomy doesn't exist.
			
			if ( is_wp_error($tags) ){				
				return $tags->get_error_message();
			}
			
			if ( !is_wp_error($tags) ){
				foreach ($tags as $tag){
					
					if ( is_a($tag, 'WP_Term') ){
						$updated = wp_remove_object_terms( $post_id, $tag->slug, 'seo_tag' );//(bool|WP_Error) True on success, false or WP_Error on failure.
						if ( !$updated ){
							return 'Error on clear seo tag: '.$tag;
						}
					}
				}
			}	
		}
		else if ( !empty($seo_tags) && !taxonomy_exists( 'seo_tag' ) ){
			return 'seo_tag taxonomy not exists to set post tag: '. $seo_tags;
		}
		
		return true;
		
	}
	
	private function updatePostHighlighted( $post_id, $value ) {		
		
		$highlighted_slugs = array();
		$highlight_option = array();
		
		if ( !empty($value) ){
			$value = str_replace('\\','',$value);
			$items = json_decode($value,TRUE);			
			
			if ( isset($items) && is_array($items) ){				
				
				// validate term and find out term infomation
				foreach ($items as $item) {				
					
					if (isset($item['slug'])){
						
						$term = get_term_by( 'slug', $item['slug'], 'highlight_option');
						
						if ( is_a($term, 'WP_Term') ){
							
							$highlighted_slugs[] = $term->slug;
							
							if ( isset($item['term_order']) ){
								$term_order = (int)$item['term_order'];
							}
							else {
								$term_order = -1;
							}
							
							$highlight_option[] = array(
								'term_order' => $term_order,
								'term_taxonomy_id' => $term->term_taxonomy_id
							);
						}
						else {
							// not found the term
							return 'Term is not found the term for slug '.$item['slug'];
						}						
					}
					else{
						// slug not found
						return 'slug not found';
					}					
				}// end foreach loop				
			}
			else{
				// json_decode error
				return 'json_decode error';
			}			
		}
		
		
		if ( count($highlighted_slugs) == 0 && taxonomy_exists( 'highlight_option' ) ){
			
			// to clear or remove all terms from an object, pass an empty string or NULL.
			$term_taxonomy_ids = wp_set_object_terms( $post_id, NULL, 'highlight_option' );
			if ( is_wp_error( $term_taxonomy_ids ) ) {
				return $term_taxonomy_ids->get_error_message();
			} 
			
		}
		else if ( count($highlighted_slugs) > 0 && taxonomy_exists( 'highlight_option' ) ){
			
			// update posts hightlighted options
			$term_taxonomy_ids = wp_set_object_terms( $post_id, $highlighted_slugs, 'highlight_option' );
			
			if ( is_wp_error( $term_taxonomy_ids ) ) {
				return $term_taxonomy_ids->get_error_message();;
			} 			
			
			// update term order
			foreach ($highlight_option as $highlighted){
				$updated = $this->setTaxonomyTermTermOrder( $highlighted['term_order'], $post_id, $highlighted['term_taxonomy_id'] );	
				if ($updated === false){
					return 'Update term order fail!';
				}				
			}
			// end update term order
			
		}
		else if ( count($highlighted_slugs) > 0 && !taxonomy_exists( 'highlight_option' ) ){
			// highlight_option taxonomy not exists
			return 'highlight_option taxonomy not exists to set post option';
		}	
		
		return true;
		
	}
	
	private function setTaxonomyTermTermOrder( $term_order, $object_id, $tt_id ) {
		global $wpdb;
		return $wpdb->query( $wpdb->prepare(
				"
				UPDATE $wpdb->term_relationships
				SET term_order = %d
				WHERE object_id = %d
				AND term_taxonomy_id = %d
				",
				$term_order,
				$object_id,
				$tt_id
		) );
	}

	private function setPostEditLock($post_id, $user_id){
		if ( ! $post = get_post( $post_id ) ) {
			return false;
		}
		
		$now  = time();
		$lock = "$now:$user_id";
		update_post_meta( $post_id, '_edit_lock', $lock );
		
		return array( $now, $user_id );
	}

	private function validatePassword($password){
		
		if ( !isset($password) || empty($password) ) {
			return 'Password not set!';	
		}
		
		$exp = '/^(?=.*\d)((?=.*[a-z])|(?=.*[A-Z])).{6,32}$/';
		
		if(strlen($password)<6 || !preg_match($exp, $password) ){
			
			return 'Password must be alphanumeric and contains minimum 6 characters!';				
		}
		
		return true;
	}

	/*
	** User Login 		
	**	 Url: /garfield/api/user/login
	**	 Post data:
	**	   username  -  string required
	**	   password -  string required
	**	 Response:
	**	   success:  true or false
	**	   message: String
	**	   source: String
	**	   user: null or user object as following
	**
	*/
	private function user_login(){
				
		return (new ANTIQUES_USER())->login($_POST);
	}

	
	/*
	** User verify (verify user membership)
	** 		Method: POST
	** 		Url: /garfield/api/user/verify
	** 		Post data:
	** 		  username -  string required
	** 		  email -  string required
	** 		  password -  string required
	** 		  token: string required (get it from user login response)
	** 		  token_expiration: string required (get it from user login response)
	** 		Response:
	** 		  success:  true or false
	** 		  message: String
	** 		  source: String
	** 
	*/
	private function user_verify(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),
		);
		
		// check required parameters
		if ( !isset($_POST['username']) ){			
			$result['message'] = 'User name not found!';
			return $result;
		}
		
		if ( !isset($_POST['email']) ){			
			$result['message'] = 'Email not found!';
			return $result;
		}
		
		if ( !isset($_POST['password']) ){			
			$result['message'] = 'Password not found!';
			return $result;
		}
		
		if ( !isset($_POST['token']) ){			
			$result['message'] = 'Token not found!';
			return $result;
		}
		
		if ( !isset($_POST['token_expiration']) ){			
			$result['message'] = 'Token expiration not found!';
			return $result;
		}
		// end check required parameters
		
		$username = trim($_POST['username']);
		$email = trim($_POST['email']);
		$password = trim($_POST['password']);
		$token = trim($_POST['token']);
		$token_expiration = trim($_POST['token_expiration']);
		
		
		if ( !email_exists($email)){
			// email not exist
			$result['message'] = 'Email not exists!';
			return $result;
		}
		
		$user = wp_authenticate( $username, $password ); 
		
		if ( is_wp_error( $user ) ) {
			// login fail
			$result['message'] = $user->get_error_message();
			return $result;
		}
		
		$centra_user = new AIT_CENTRA_USER;
		$centra_user->setUser($user);		
		
		
		if ($centra_user->user_email != $email){
			// not match email
			$result['message'] = 'User email not matched!';
			return $result;			
		}
		
		// verify user success		
		$result['success'] = true;		
		
		// update user token
		$this->updateToken($centra_user->id, $token, $token_expiration);
			
		return $result;
		
	}
	
	
	/*
	** User Profile 
	** 		Method: POST
	** 		Url: /garfield/api/user/profile
	** 		Post data:
	** 		  token: string required (get it from user login response)
	** 		Response:
	** 		  success:  true or false
	** 		  message: String
	** 		  source: String
	** 		  user: null or user object 
	** 
	*/
	private function user_profile(){
		
		$result = array(
			'success'=> false,
			'message'=>'',			
			'source'=> $this->getSource(),		
			'user' => null
		);
		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
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
				
		
		$centra_user = new AIT_CENTRA_USER;
		$centra_user->setUser($user);
		$centra_user->user_url = $user->data->user_url;		
		$centra_user->user_registered = $user->data->user_registered;	
		$centra_user->description = get_user_meta($user_id, 'description', true);
		
		//$result['wp_user'] = $user;
		$result['success'] = true;
		$result['user'] = $centra_user;
		return $result;
		
	}
	
	
	/*
	** User Profile Update (For user’s owned profile.)
	** 		Method: POST
	** 		Url: /garfield/api/user/profile/update
	** 		Post data:
	** 		  token: string required (get it from user login response)
	** 		  email: string required ( If empty or not set, will return false. )
	** 		  display_name: string required ( If empty or not set, will return false. )
	** 		  first_name: string optional ( If empty or not set, will set value to empty string. )
	** 		  last_name: string optional ( If empty or not set, will set value to empty string. )
	** 		  user_url: string optional ( If empty or not set, will set value to empty string. )
	** 		  description: string optional ( If empty or not set, will set value to empty string. )
	** 		  new_password: string optional (If empty or not set, will be ignored. Password must be alphanumeric and contains minimum 6 characters. This condition can discuss to change during development. )
	** 		Response:
	** 		  success:  true or false
	** 		  message: String
	** 		  source: String
	** 		  user: null or user object 
	** 
	*/
	private function user_profile_update(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),		
			'user' => null
		);
		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
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
		if ( !isset($_POST['email']) || !is_email($_POST['email']) ){			
			$result['message'] = 'Invalid user email!';
			return $result;
		}
		$user->user_email = trim($_POST['email']);
		
		if ( !isset($_POST['display_name']) || empty($_POST['display_name']) ){			
			$result['message'] = 'Display name is required!';
			return $result;			
		}
		$user->data->display_name = sanitize_text_field($_POST['display_name']);
		// end check required parameters
		
		
		// check optional parameters
		if ( isset($_POST['first_name']) ){			
			$user->first_name = sanitize_text_field($_POST['first_name']);
		}
		else{
			$user->first_name = '';
		}
		
		if ( isset($_POST['last_name']) ){			
			$user->last_name = sanitize_text_field($_POST['last_name']);			
		}
		else {
			$user->last_name = '';
		}
		
		if ( isset($_POST['user_url']) && !empty($_POST['user_url']) ){			
			$user->data->user_url = trim($_POST['user_url']);			
		}
		else {
			$user->data->user_url = '';	
		}
		
		// description
		if ( isset($_POST['description']) ){			
			$description = sanitize_textarea_field($_POST['description']);			
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
		if ( isset($_POST['avatar']) ){			
			$avatar = sanitize_textarea_field($_POST['avatar']);			
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
		if ( isset($_POST['new_password']) && !empty($_POST['new_password']) ){
			
			$new_password = trim($_POST['new_password']);
			
			$valid =  $this->validatePassword( $new_password );
			
			if ( $valid !== true ){
				$result['message'] = $valid;
				return $result;	
			}			
			
			reset_password( $user, $new_password );
			$result['reset_password'] = true;
		}
		// end password updated
		
		
		$user_data = get_userdata( $return_user_id );
		$centra_user = new AIT_CENTRA_USER;
		$centra_user->setUser($user_data);
		$centra_user->user_url = $user_data->data->user_url;		
		$centra_user->user_registered = $user_data->data->user_registered;	
		$centra_user->description = get_user_meta($user_id, 'description', true);
		
		//$result['wp_user'] = $user_data;
		$result['success'] = true;
		$result['user'] = $centra_user;
		return $result;
		
	}
	
	
	/*
	** User validate (Validate before create user. For admin only.)
	** 		Method: POST
	** 		Url:/garfield/api/user/validate
	** 		Post data:
	** 		  token: string required (get it from user login response)
	** 		  email -  string required
	** 		  password -  string required	
	** 		  username -  string (optional, if provided, will check the existing, if not provided, will use email before @ string to validate user name) 
	** 		Response:
	** 		  success:  true or false
	** 		  message: String
	** 		  source: String
	** 
	*/	
	private function user_validate(){
		global $ALLOWED_ROLES;			
		
		$result = array(
			'success'=> false,			
			'message'=>'',
			'source'=> $this->getSource(),				
		);
		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		$user=get_userdata($user_id);
		if ( !in_array('administrator', $user->roles ) ){
			$result['message'] = 'You can not create user.';
			return $result;	
		}
		// end validate user has the right to create new user
		
		// check required parameters		
		if ( !isset($_POST['email']) ){			
			$result['message'] = 'Email not found';
			return $result;
		}
		if ( !isset($_POST['password']) ){			
			$result['message'] = 'Password not found';
			return $result;
		}
		// end check required parameters
		
		
		// validate password
		$password = sanitize_text_field($_POST['password']);
		$valid = $this->validatePassword( $password );			
		if ( $valid !== true ){
			$result['message'] = $valid;
			return $result;	
		}
		
		
		// validate email
		$email = sanitize_text_field($_POST['email']);
		if ( !is_email($email) ){
			$result['message'] = 'Email address not correct!';
			return $result;
		}
		if (email_exists($email)){
			$result['message'] = 'Email already exists!';
			return $result;
		}
		
		
		// validate user name
		$user_name = explode('@', $email)[0];		
		if (isset($_POST['username'])){
			$user_name = sanitize_text_field($_POST['username']);
		}
		
		$user_id = username_exists( $user_name ); //(int|false) The user's ID on success, and false on failure.
		
		if ( $user_id !== false ){
			$result['message'] = 'User '.$user_name.' already exists!';
			return $result;
		}
		
		$result['success'] = true;
		return $result;		
	}
	
	
	/*
	** User Create(For admin only.) 
	**		Method: POST
	**		Url: /garfield/api/user/create
	**		Post data:
	**		  token: string required (get it from user login response)
	**		  email -  string required
	**		  password -  string required ( Password must be alphanumeric and contains minimum 6 characters. This condition can discuss to change during development. )
	**		  username -  string (optional, if provided, will check the existing, if not provided, will use email before @ string to validate user name)
	**		  roles: string optional ( Role name. If more than one, separated by comma. Default: no role. )
	**		Response:
	**		  success:  true or false
	**		  message: String
	**		  source: String
	**		  user: null or user object 
	**
	*/		
	private function user_create(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),	
			'user' => null
		);
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		$admin=get_userdata($user_id);
		if ( !in_array('administrator', $admin->roles ) ){
			$result['message'] = 'You can not create user.';
			return $result;	
		}
		// end validate user has the right to create new user
		
		
		// check required parameters		
		if ( !isset($_POST['email']) || empty($_POST['email']) ){			
			$result['message'] = 'Email is not found';
			return $result;
		}
		
		if ( !isset($_POST['password']) || empty($_POST['password']) ){			
			$result['message'] = 'Password is not found';
			return $result;
		}		
		// end check required parameters
		
		
		// check optional parameters
		$roles = array();
		if ( isset($_POST['roles']) && !empty($_POST['roles']) ){			
			$roles = explode(',', $_POST['roles']);
		}			
		// end check optional parameters
		
		
		
		$email = sanitize_text_field($_POST['email']);
		$password = sanitize_text_field($_POST['password']);
		
		// get user name
		if (isset($_POST['username']) && !empty($_POST['username']) ){
			$user_name = sanitize_text_field($_POST['username']);
		}
		else {
			$user_name = explode('@', $email)[0];
		}
		
		$new_user_id = wp_create_user( $user_name, $password, $email );//When successful returns the user ID,In case of failure (username or email already exists) the function returns an error object;
		
		if (is_wp_error($new_user_id)){
			$result['message'] = $new_user_id->get_error_message();
			return $result;
		}
		
		
		$new_user = get_user_by('id', $new_user_id);
		
		// set user roles
		$new_user->set_role(''); //This will remove the previous roles of the user and assign the user the new one. You can set the role to an empty string and it will remove all of the roles from the user.
		foreach ($roles as $role) {
			$new_user->add_role($role); 
		}
		
		$new_user_data  = get_userdata( $new_user_id );
		$centra_user = new AIT_CENTRA_USER;
		$centra_user->setUser($new_user_data);	
		$result['user'] = $centra_user;
		$result['success'] = true;		
		
		return $result;
		
	}
	
	
	/*
	** User Update (For admin only.) 
	**		Method: POST
	**		Url: /garfield/api/user/update
	**		Post data:
	**		  token: string required (get it from user login response)
	**		  user_id: int required ( If empty or not set or <=0, will return false. )	
	**		  roles: string optional ( Role name. If more than one, separated by comma. Default: no role. )
	**		Response:
	**		  success:  true or false
	**		  message: String
	**		  source: String
	**		  user: null or user object 
	**
	*/		
	private function user_update(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),	
			'user' => null
		);
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		$admin=get_userdata($user_id);
		if ( !in_array('administrator', $admin->roles ) ){
			$result['message'] = 'You can not update user.';
			return $result;	
		}
		// end validate user has the right to create new user
		
		
		// check required parameters		
		if ( !isset($_POST['user_id']) || !is_numeric($_POST['user_id']) || (int)$_POST['user_id'] <= 0 ){			
			$result['message'] = 'User ID invalid!';
			return $result;
		}				
		// end check required parameters
		
		
		// check optional parameters
		$roles = array();
		if ( isset($_POST['roles']) && !empty($_POST['roles']) ){			
			$roles = explode(',', $_POST['roles']);
		}			
		// end check optional parameters		
		
		$update_user_id = (int)$_POST['user_id'];
		$update_user = new WP_User( $update_user_id );

		if ( ! $update_user->exists() ) {
            $result['message'] = 'User with ID '.$update_user_id.' is not existed!';
			return $result;
        }

		
		// set user roles
		$update_user->set_role(''); //This will remove the previous roles of the user and assign the user the new one. You can set the role to an empty string and it will remove all of the roles from the user.
		foreach ($roles as $role) {
			$update_user->add_role($role); 
		}
		
		$user_data  = get_userdata( $update_user_id );
		$centra_user = new AIT_CENTRA_USER;
		$centra_user->setUser($user_data);	
		$result['user'] = $centra_user;
		$result['success'] = true;		
		
		return $result;
		
	}
	
	
	/*
	** User Delete (For admin only.) 
	**		Method: POST
	**		Url: /garfield/api/user/update
	**		Post data:
	**		  token: string required (get it from user login response)
	**		  user_id: int required ( If empty or not set or <=0, will return false. )		**		  
	**		Response:
	**		  success:  true or false
	**		  message: String
	**		  source: String	
	**
	*/	
	private function user_delete(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),				
		);
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		$admin=get_userdata($user_id);
		if ( !in_array('administrator', $admin->roles ) ){
			$result['message'] = 'You can not update user.';
			return $result;	
		}
		// end validate user has the right to create new user
		
		
		// check required parameters		
		if ( !isset($_POST['user_id']) || !is_numeric($_POST['user_id']) || (int)$_POST['user_id'] <= 0 ){			
			$result['message'] = 'User ID invalid!';
			return $result;
		}				
		// end check required parameters
		$delete_user_id = (int)$_POST['user_id'];
		
		
		// check optional parameters
		$reassign_id = null;
		if ( isset($_POST['reassign_id']) && !empty($_POST['reassign_id']) ){			
			$reassign_id = trim($_POST['reassign_id']);
		}			
		// end check optional parameters		
		
		
		
		// validate delete user account		
		$delete_user = new WP_User( $delete_user_id );

		if ( ! $delete_user->exists() ) {
            $result['message'] = 'User with ID '.$delete_user_id.' is not existed!';
			return $result;
        }
		
		// delete user
		require_once(ABSPATH.'wp-admin/includes/user.php' );
		if ( $reassign_id != null ){
			
			// validate reassign user account
			$reassign_user = new WP_User( $reassign_id ); 		
			if ( ! $reassign_user->exists() ){
				$result['message'] = 'Reassign user with ID '.$reassign_id .' is not existed!';
				return $result;
			}
			
			$result['success'] = wp_delete_user( $delete_user_id, $reassign_id );//True when finished.
		}
		else{
			
			$result['success'] = wp_delete_user( $delete_user_id );		
		}
		// end delete user
		
		
		// clear user tokens
		if ( $result['success'] === true ){
			
			$member_mapping = get_option( 'bp_centralized_member_mapping', array() ); //(mixed) Value set for the option.		
			$member_mapping_updated = array();
			
			foreach ($member_mapping as $token => $member){
				
				if ( $member['id'] != $delete_user_id ){
					$member_mapping_updated[$token] = $member;
				}
			}			
			
			// update mapping
			if (!update_option( 'bp_centralized_member_mapping', $member_mapping_updated )){		
				add_option( 'bp_centralized_member_mapping', $member_mapping_updated );
			}
			
		}
		//$result['member_mapping'] = get_option( 'bp_centralized_member_mapping', array());
		return $result;
		
	}
	
	
	/*
	** List users: For admin only.
	**
	** $_Post data:
	**   paged: int optional ( default 1. )
	** 	 num_per_page: int optional ( default 10. )
	**   role_in: string optional ( Role name. If more than one, separated by comma. ) Example: “author,editor,principleeditor”
	**   search: int or string optional ( Can be user id, username, display name, or email. If not set, will be ignored. )
	**   search_columns: string optional ( Work with search parameter. 
	**                   Value can be any of these: 'ID','user_login', 'user_email', 'display_name' or 'user_nicename'. 
	**                   If more than one, separated by comma. 'user_login' means username. )  
	**                   Example: 'user_login,user_email'  
	**   
	** @return result array.
	*/
	private function user_list(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'paged' => 1,
			'num_per_page' => 10,
			'total'=> 0,
			'source'=> $this->getSource(),		
			'users' => array(),
		);
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		$user=get_userdata($user_id);
		if ( !in_array('administrator', $user->roles ) ){
			$result['message'] = 'You can not see other users.';
			return $result;	
		}
		
				
		if ( isset($_POST['paged']) && is_numeric($_POST['paged']) ){
			$paged = (int) $_POST['paged'];
			if ($paged < 1){
				$paged = 1;
			}
			$result['paged'] = $paged;			
		}
		
		if ( isset($_POST['num_per_page']) && is_numeric($_POST['num_per_page']) ){
			$num_per_page = (int) $_POST['num_per_page'];
			if ($num_per_page < 0){
				$num_per_page = 10;
			}
			$result['num_per_page'] = $num_per_page;			
		}
				
		$args = array(			
			'number' => $result['num_per_page'],
            'paged'  => $result['paged'],
		);
		
		if ( isset($_POST['role_in']) && !empty($_POST['role_in']) ){
			$role_in = trim($_POST['role_in']);			
			$args['role__in'] = explode(',', $role_in);			
		}
		
		if ( isset($_POST['search']) && !empty($_POST['search']) ){	
			
			$args['search'] = trim($_POST['search']);	
			
			if ( isset($_POST['search_columns']) && !empty($_POST['search_columns']) ){				
				$args['search_columns'] = explode(',', $_POST['search_columns']);			
			}
		}

		
		$user_query = new WP_User_Query( $args ); // return (array) List of users.
		
		$users_data=$user_query->get_results();
		// User Loop
		if ( ! empty( $users_data ) ) {
			
			$result['total'] = $user_query->get_total();
			foreach ( $users_data as $user_data ) {
				
				$centra_user = new AIT_CENTRA_USER;
				$centra_user->setUser($user_data);	
				$result['users'][] = $centra_user;
			}
			
		} else {
			//echo 'No users found.';
		}
		
		$result['success'] = true;
		return $result;
	
	}


	/*
	** get user recent selected categories
	*/
	private function user_selected_categories()
	{
		$result = array(
			'success' => false,
			'message' => '',
			'source' => $this->getSource(),
			'categories' => array(),
		);

		// validate user has the right to create new user
		$user_id = $this->validateToken();
		if ($user_id == false) {
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;
		}

		$result['categories'] = $this->get_user_selected_categories($user_id);
		$result['success'] = true;
		return $result;
	}

	private function get_user_selected_categories($user_id)
	{
		$user_meta = get_user_meta($user_id, 'cms_selected_categories', true);
		return empty($user_meta) ? array() : json_decode($user_meta);
	}

	/*
	** insert user recent selected category
	*/
	private function user_select_category()
	{
		$result = array(
			'success' => false,
			'message' => '',
			'source' => $this->getSource(),
			'categories' => array(),
		);

		// validate user has the right to create new user
		$user_id = $this->validateToken();
		if ($user_id == false) {
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;
		}

		$categories = $this->get_user_selected_categories($user_id);
		if (!empty($_POST['id'])) {
			$cate_id = intval($_POST['id']);
			$new_categories = array_filter($categories, function ($item) use ($cate_id) {
				return $item != $cate_id;
			});
			array_unshift($new_categories, $cate_id);

			if ($categories !== $new_categories) {
				$categories = $new_categories;
				$updated = update_user_meta($user_id, 'cms_selected_categories', json_encode($categories));
				if ($updated === false) {
					$result['message'] = 'Error on updating user recent selected categories!';
					return $result;
				}
			}
		}

		$result['categories'] = $categories;
		$result['success'] = true;
		return $result;
	}

	/*
	** get user recent selected authors
	*/
	private function user_selected_authors()
	{
		$result = array(
			'success' => false,
			'message' => '',
			'source' => $this->getSource(),
			'authors' => array(),
		);

		// validate user has the right to create new user
		$user_id = $this->validateToken();
		if ($user_id == false) {
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;
		}

		$result['authors'] = $this->get_user_selected_authors($user_id);
		$result['success'] = true;
		return $result;
	}

	private function get_user_selected_authors($user_id)
	{
		$user_meta = get_user_meta($user_id, 'cms_selected_authors', true);
		return empty($user_meta) ? array() : json_decode($user_meta);
	}

	/*
	** insert user recent selected author
	*/
	private function user_select_author()
	{
		$result = array(
			'success' => false,
			'message' => '',
			'source' => $this->getSource(),
			'authors' => array(),
		);

		// validate user has the right to create new user
		$user_id = $this->validateToken();
		if ($user_id == false) {
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;
		}

		$authors = $this->get_user_selected_authors($user_id);
		if (!empty($_POST['id'])) {
			$author_id = intval($_POST['id']);
			$new_authors = array_filter($authors, function ($item) use ($author_id) {
				return $item != $author_id;
			});
			array_unshift($new_authors, $author_id);

			if ($authors !== $new_authors) {
				$authors = $new_authors;
				$updated = update_user_meta($user_id, 'cms_selected_authors', json_encode($authors));
				if ($updated === false) {
					$result['message'] = 'Error on updating user recent selected authors!';
					return $result;
				}
			}
		}

		$result['authors'] = $authors;
		$result['success'] = true;
		return $result;
	}


	/*
	** list posts sort by categories	
	*/
	private function post_list_category(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),		
			'items' => array(),
		);
		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		
		$is_author = false;
		$user = get_userdata( $user_id );
		if ( in_array( 'author', $user->roles ) ){
			$is_author = true;
		}
		
		$categories = array();
		
		$locations = get_nav_menu_locations();
		if (isset($locations['centrialzed'])){
			$menu_id = $locations['centrialzed'];
			$menu = wp_get_nav_menu_object( $menu_id );//(WP_Term|false) False if $menu param isn't supplied or term does not exist, menu object if successful.
			if ($menu){
				$menu_items = wp_get_nav_menu_items($menu->term_id);//(false|array) $items Array of menu items, otherwise false.
				if ( is_array($menu_items) ){
					
					foreach($menu_items as $item){
						$term = get_term($item->object_id);
						
						if ( is_a($term , 'WP_Term') ){							
							$categories[] = $term;
						}
					}
					
				}
			}
			
		}	
		
		// loop to get posts for each category, limited 3 posts
		foreach ($categories as $cat){			
			
			$args = array(				
				'numberposts' => 3,
				'post_status' => 'any',
				'tax_query' => array(
					array(
						'taxonomy' => $cat->taxonomy,
						'field'    => 'term_id',
						'terms'    => $cat->term_id,
					),
				),
			);
			if ( $is_author ){
				$args['post_author'] = $user_id;
			}
			
			// create centralized category object
			$centralized_cat = new AIT_CENTRA_CATEGORY($cat);
			
			$posts = get_posts( $args );
			foreach($posts as $p){
				$central_post = new AIT_CENTRA_POST($p);
				$central_post->find_more_options();				
				
				$centralized_cat->posts[] = $central_post;
			}
			
			$result['items'][] = $centralized_cat;
		}
		$result['success'] = true;
		return $result;	
	}


	/*
	** list posts sort by authors	
	*/
	private function post_list_author(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),		
			'items' => array(),
		);
		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		
		$is_author = false;
		$user = get_userdata( $user_id );
		if ( in_array( 'author', $user->roles ) ){
			$is_author = true;
		}
		
		
		$authors = array();
		
		
		// get author list
		if ( $is_author ){	
			$authors[$user->ID] = $user;
			
			
		}
		else {	
			global $wpdb;
			$metakey	= "Harriet's Adages";
			$metavalue	= "WordPress' database interface is like Sunday Morning: Easy.";

			$response = $wpdb->get_results( 
				"
				SELECT post_author
				FROM $wpdb->posts
				WHERE post_type = 'post'				
				ORDER BY post_date DESC	
				LIMIT 500
				"
			);
			
			$ids = array();
			foreach($response as $item){
				if ( !in_array($item->post_author, $ids) ){
					$ids[] = $item->post_author;
				}
			}
			//$result['ids'] = $ids;
			
			foreach ($ids as $author_id){
				
				$author = get_userdata( $author_id );
				
				if ( !isset($authors[$author->ID]) ){
					$authors[$author->ID] = $author;
				}				
			}
			
			/* foreach ($ids as $post_id){
				$author_id = get_post_field( 'post_author', $post_id );
				$author = get_userdata( $author_id );
				if ( !isset($authors[$author->ID]) ){
					$authors[$author->ID] = $author;
				}				
			} */		
		}
		
		
		// get posts list for each author
		foreach($authors as $id=>$object){
			$item = array(
				'id'=>$id,
				'name'=>$object->data->display_name,
				'posts' => array(),
			);
			
			$args = array(
				'author' => $id,
				'numberposts' => 3,
				'post_status' => 'any',
			);
			$posts = get_posts( $args );
			foreach($posts as $p){
				$central_post = new AIT_CENTRA_POST($p);
				$central_post->find_more_options();				
				
				$item['posts'][] = $central_post;
			}
			
			$result['items'][] = $item;
		}
		
		$result['success'] = true;		
		return $result;	
	}

	
	/*
	** list posts by condition	
	*/
	private function post_list(){		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),	
			'paged' => 1,
			'posts_per_page'=>20,
			'total' => 0,			
			'posts' => array(),
		);
		
		$args = array(
			'post_type'=>'post',
			'paged' => $result['paged'],
			'posts_per_page' => $result['posts_per_page'],
			'post_status' => 'any',
		);
		
		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
				
		// get optional input parameters
		if ( isset($_POST['paged']) && is_numeric($_POST['paged']) ){
			$paged = (int) $_POST['paged'];
			if ($paged < 1){
				$paged = 1;
			}
			$result['paged'] = $paged;
			$args['paged'] = $paged;
		}
		
		if ( isset($_POST['posts_per_page']) && is_numeric($_POST['posts_per_page']) ){
			$posts_per_page = (int) $_POST['posts_per_page'];
			if ($posts_per_page <= 0 ){
				$posts_per_page = $result['posts_per_page'];
			}
			$result['posts_per_page'] = $posts_per_page;
			$args['posts_per_page'] = $posts_per_page;
		}
			
		
		if ( isset($_POST['author']) && !empty($_POST['author']) ){			
			$args['author__in'] = explode(',', $_POST['author']);
		}
		
		if ( isset($_POST['category']) && !empty($_POST['category']) ){
			
			$args['tax_query'][]=array(
						'taxonomy' => 'category',
						'field'    => 'term_id',
						'terms'    => explode(',', $_POST['category']),
					);
			//$args['category__in'] = explode(',', $_POST['category']);					
		}
		
		if ( isset($_POST['tag']) && !empty($_POST['tag']) ){		
			$args['tax_query'][]=array(
						'taxonomy' => 'post_tag',
						'field'    => 'name',
						'terms'    => explode(',', $_POST['tag']),
					);
			//$args['tag__in'] = explode(',', $_POST['tag']);
		}
		
		if ( isset($_POST['s']) && !empty($_POST['s']) ){			
			$args['s'] = $_POST['s'];
		}
		
		if (isset($args['tax_query']) && count($args['tax_query'])>1){
			$args['tax_query']['relation']='AND';
		}
			
		// end get optional input parameters
		//$result['args'] = $args;
		
		//$posts = get_posts($args);
		$query = new WP_Query( $args );
		
		$result['total'] = $query->found_posts;		
		if ( $query->have_posts()){	

			
			
			
			foreach($query->posts as $p){
				
				$central_post = new AIT_CENTRA_POST( $p );
				$central_post->find_more_options();				
				
				$result['posts'][]  = $central_post;
			}					
		}		
		
		wp_reset_postdata();
		$result['success'] = true;
		return $result;
	}


	/*
	** single post detail	
	*/
	private function post_single(){		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),
			'post' => null,
		);		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		
		// get required post id
		if ( isset($_POST['post_id']) && is_numeric($_POST['post_id']) ){
			$post_id = (int) $_POST['post_id'];
			
			$post = get_post($post_id);
			
			if (is_a($post, 'WP_Post')){
				
				$can_edit = false;				
				$post_author = get_userdata( $post->post_author );
				$user = get_userdata( $user_id );
				
				// editor and author only can edit own posts
				if (  ( in_array('editor', $user->roles) && $post->post_author == $user_id ) ||  				
					  ( in_array('author', $user->roles) && $post->post_author == $user_id ) 
				   ){
					$can_edit = true;
				}				
				
				// senioreditor and administrator can edit all posts except for authors' posts
				if ( in_array( 'senioreditor', $user->roles ) && !in_array( 'author', $post_author->roles ) || 
					 in_array( 'administrator', $user->roles ) && !in_array( 'author', $post_author->roles )
				   ){
					$can_edit = true;
				}
				
				// principleeditor can edit all posts				
				if ( in_array('principleeditor', $user->roles) ){
					$can_edit = true;
				}	
				
				
				$central_post = new AIT_CENTRA_POST( $post, false );
				$central_post->post_content =  do_shortcode($post->post_content);				
				$central_post->find_more_meta();
				$central_post->find_more_options();				
								
				$central_post->find_seo_tags(false);
				
				if ( $can_edit ){
					$central_post->set_edit_link($_POST['token']);
				}				
				
				$result['post']	= $central_post;
				$result['success'] = true;
			}
			else {
				$result['message'] = 'Post not found for id '.$post_id.'!';
			}			
		}
		else {
			$result['message'] = 'Post id is required!';
		}
		
		return $result;
	}
	

	/*
	** update post
	*/
	private function post_update(){		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),
			'post'=>null
		);	
        
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
				
		// check user edit post capability
		if ( !user_can($user_id, 'edit_post') ){
			$result['message'] = 'You do not have capability to edit this post!';
			return $result;
		}	

		wp_set_current_user($user_id);
		
		// required parameters		
		if ( !isset($_POST['post_id']) || !is_numeric($_POST['post_id']) ){
			$result['message'] = 'Post id is required!';
			return $result;
		}
		
		if ( !isset($_POST['post_title']) || empty($_POST['post_title']) ){
			$result['message'] = 'Post title is required!';
			return $result;
		}		
		// end required parameters	
		
		
		
		// update post title
		$post_id = (int) $_POST['post_id'];	
		
		$my_post = array(
			  'ID'           => $post_id,
			  'post_title'   => trim($_POST['post_title']),			 
		);
		
		//must have post_date to update post status
		if (isset($_POST['post_date']) && !empty($_POST['post_date'])){
			
			$wp_timezone=get_option('timezone_string');
			$now=new DateTime(date('Y-m-d H:i:s',time()), new DateTimeZone('UTC'));
			$now->setTimezone(new DateTimeZone($wp_timezone));
			$now=$now->format('Y-m-d H:i:s');	
			
			$my_post['post_date'] = $_POST['post_date'];
			$my_post['post_status'] = $_POST['post_status'];
				
			if ( isset( $_POST['post_status'] ) && in_array( $_POST['post_status'], array( 'draft', 'pending' ) ) ) {		
				
				$my_post['post_date_gmt'] = ( '0000-00-00 00:00:00' === $_POST['post_date_gmt'] ) ? '' : $_POST['post_date_gmt'];
				
				
			} elseif ( isset( $_POST['post_status'] ) && in_array( $_POST['post_status'], array( 'publish', 'future' ) ) ) {
				
				
				if (!isset($_POST['post_date_gmt']) || empty($_POST['post_date_gmt']) || '0000-00-00 00:00:00' === $_POST['post_date_gmt']){
					$post_date_gmt=new DateTime($_POST['post_date'], new DateTimeZone($wp_timezone));
					$post_date_gmt->setTimezone(new DateTimeZone('UTC'));
					$my_post['post_date_gmt'] = $post_date_gmt->format('Y-m-d H:i:s');
				} else {
					$my_post['post_date_gmt'] = $_POST['post_date_gmt'];
				}
												
				$my_post['post_status'] = strtotime($my_post['post_date']) > strtotime($now) ? 'future' : 'publish';
						
			}	
			
		}

		//unset the content filters
		remove_filter('content_save_pre', 'wp_filter_post_kses');
		remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

		//save post without the content filters.
		$return = wp_update_post($my_post, true);

		//reset the content filters.
		add_filter('content_save_pre', 'wp_filter_post_kses');
		add_filter('content_filtered_save_pre', 'wp_filter_post_kses');		

		if( is_wp_error( $return ) ) {
			$result['message'] = $return->get_error_message();
			return $result;
		}
		// end update post title


		// update post meta
		if (isset($_POST['summary'])) {
			$summary = trim($_POST['summary']);
			$updated = update_post_meta($post_id, 'summary', $summary);
		}
		if (isset($_POST['feature_wall_highlight'])) {
			$feature_wall_highlight = trim($_POST['feature_wall_highlight']);
			$updated = update_post_meta($post_id, 'feature_wall_highlight', $feature_wall_highlight);
		}
		// end update post meta
		
		
		// get post
		$post = get_post($post_id);
		if ( !is_a($post, 'WP_Post')){
			$result['message'] = 'Post not found for id '.$post_id.'!';
			return $result;
		}
		
		
		// update post auto setting
		$auto_social_share = '';
		if ( isset($_POST['auto_social_share']) ){
			$auto_social_share = trim($_POST['auto_social_share']);
		}
		$auto_facebook_post = '';
		if ( isset($_POST['auto_facebook_post']) ){
			$auto_facebook_post = trim($_POST['auto_facebook_post']);
		}
		
		$this->savePostAutoSetting( $post, $auto_social_share, $auto_facebook_post);		
		// end update post auto setting
		
		
		
		// update post categories
		$categories = array();
		if ( isset($_POST['categories']) && !empty($_POST['categories']) ){
			$categories = explode( ',', $_POST['categories']);
		}
		$updated = $this->updatePostCategories($post_id, $categories);
		if ( !$updated ){
			$result['message'] = 'Categories updated fail!';
			return $result;
		}			
		// end update post categories
		
		
		
		// update post seo tags
		$seo_tags = '';
		if ( isset($_POST['seo_tags']) ){
			$seo_tags = trim($_POST['seo_tags']);
		
			$updated = $this->updatePostSeoTags($post_id, $seo_tags);

			if ( $updated !== true ){			
				$result['message'] = 'SEO tags updated fail! '.$updated;
				return $result;
			}		
		}
		// end update post seo tags
		
		
		// update post tags
		$tags = '';
		if ( isset($_POST['tags']) ){
			$tags = trim($_POST['tags']);
			$updated = $this->updatePostTags($post_id, $tags);
			if ( !$updated ){
				$result['message'] = 'Tags updated fail!';
				return $result;
			}		
		}
		
		// end update post tags
		
		
		// update post highlighted
		$highlighted = '';
		if ( isset($_POST['highlighted']) ){
			$highlighted = trim($_POST['highlighted']);
		
			$updated = $this->updatePostHighlighted($post_id, $highlighted);
			if ( $updated !== true ){
				$result['highlighted'] = $highlighted;
				$result['message'] = 'Post highlighted updated fail! '.$updated;
				return $result;
			}	
		}
		// end update post highlighted
		
		$now  = time();
		update_post_meta( $post_id, '_edit_last', $user_id );
		update_post_meta( $post_id, '_edit_lock', $now.':'.$user_id );
		
		// set output
		$post = get_post($post_id);
		if (is_a($post, 'WP_Post')){			
					
			$central_post = new AIT_CENTRA_POST( $post, false );
			$central_post->post_content = $post->post_content;
			$central_post->set_edit_link($_POST['token']);
			$central_post->find_more_meta();
			$central_post->find_more_options();			
			$central_post->find_seo_tags(false);
				
			$result['post']	= $central_post;
			$result['success'] = true;
		}
		else {
			
		}
		
		return $result;
	}
	


	/*
	** only update post priority	
	*/
	private function post_update_priority(){		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),
			'priority'=>''
		);		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		// validate priority value
		$priority = '';
		if ( isset($_POST['priority']) ){
			$priority = strtolower(trim($_POST['priority']));
		}
		
		if ( $priority != 'priority' && $priority != 'ignore' && strlen($priority) != 0 ){
			$result['message'] = 'Priority value is invalid! Value should be either "priority", "ignore" or empty string.';
			return $result;
		}
		// end validate priority value
			
		// get required post id
		if ( isset($_POST['post_id']) && is_numeric($_POST['post_id']) ){
			$post_id = (int) $_POST['post_id'];
			
			$post = get_post($post_id);
			
			if (is_a($post, 'WP_Post')){
				
				update_post_meta( $post_id, '_bp_post_priority', $priority); 
				//(int|bool) The new meta field ID if a field with the given key didn't exist and was therefore added, true on successful update, false on failure.
				$result['priority'] = get_post_meta( $post_id, '_bp_post_priority', true);
				if ($result['priority'] == $priority){
					
					$result['success'] = true;	
					update_post_meta( $post_id, '_edit_last', $user_id );
				}
			}
			else {
				$result['message'] = 'Post not found for id '.$post_id.'!';
			}			
		}
		else {
			$result['message'] = 'Post id is required!';
		}
		
		return $result;
	}
	

	/*
	** list all categories	
	*/
	private function category_list(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),		
			'categories' => array(),
		);
		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		$categories = get_categories( array(
			'parent' => 0,
			'hide_empty' => false,
		));
				
		foreach ($categories as $cat){			
			
			// create centralized category object
			$centralized_cat = new AIT_CENTRA_CATEGORY($cat);			
			
			// get sub categories
			$subCats = get_categories(array(
				'parent' => $cat->term_id,
				'hide_empty' => false,
			));			
			foreach ($subCats as $sub){				
				$child_1 =  new AIT_CENTRA_CATEGORY($sub);				
				
				$subSubCats = get_categories(array(
					'parent' => $sub->term_id,
					'hide_empty' => false,
				));
				
				foreach ($subSubCats as $subSub){
					$child_2 =  new AIT_CENTRA_CATEGORY($subSub);		
					$child_1->sub_categories[] = $child_2;
				}
				
				$centralized_cat->sub_categories[]= $child_1;
			}
			
			$result['categories'][] = $centralized_cat;
		}
		
		$result['success'] = true;
		return $result;	
	}


	/*
	** single category	
	*/
	private function category_single(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),		
			'category' => null,
		);
		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		
		// get required category id
		if ( isset($_POST['category_id']) && is_numeric($_POST['category_id']) ){
			$category_id = (int) $_POST['category_id'];
			
			$category = get_term($category_id);
			
			if (is_a($category, 'WP_Term')){				
				$result['category']	= array(
					'id'=>$category->term_id,
					'name'=>$category->name,
				);
				$result['success'] = true;
			}
			else {
				$result['message'] = 'Category not found for id '.$category_id.'!';
			}			
		}
		else {
			$result['message'] = 'Category id is required!';
		}
		
		return $result;
	}
	

	/*
	** list all users which in role 'author', 'administrator', 'editor', 'principleeditor', 'senioreditor' 	
	*/
	private function author_list(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),		
			'authors' => array(),
		);
		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		
		$authors = array();
		$user = get_userdata( $user_id );
		if (in_array('administrator', $user->roles) || in_array('principleeditor', $user->roles)) {
			$authors = get_users(['role__in' => ['author', 'administrator', 'editor', 'principleeditor', 'senioreditor']]);
		} elseif (in_array('editor', $user->roles) || in_array('senioreditor', $user->roles)) {
			$authors = get_users(['role__in' => ['editor', 'senioreditor']]);
		} else {
			$authors[] = $user;
		}		
		
		// set output 
		foreach ($authors as $author){
			if ( is_a($author, 'WP_User') ){
				$result['authors'][] = array(
					'id' => $author->ID,
					'name'=>$author->data->display_name,
					'roles' => $author->roles,
				);
			}			
		}
		
		$result['success'] = true;
		return $result;	
	}


	/*
	** single author	
	*/
	private function author_single(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),		
			'author' => null,
		);
		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		
		// get required author id
		if ( isset($_POST['author_id']) && is_numeric($_POST['author_id']) ){
			$author_id = (int) $_POST['author_id'];
			
			$author = get_userdata( $author_id );
			
			if (is_a($author, 'WP_User')){				
				$result['author']	= array(
					'id'=>$author->ID,
					'name'=>$author->data->display_name,
				);
				$result['success'] = true;
			}
			else {
				$result['message'] = 'Author not found for id '.$author_id.'!';
			}			
		}
		else {
			$result['message'] = 'Author id is required!';
		}
		
		return $result;
	}

	/*
	** list wp all roles and it's capabilities	
	*/
	private function role_list(){
		global $wp_roles;
		
		$result = array(			
			'source'=> $this->getSource(),		
			'roles' => array()
		);	
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		foreach ($wp_roles->roles as $role => $details ){
			
			$caps = array();
			foreach($details['capabilities'] as $key=>$value){
				if ($value === true){
					$caps[] = $key;
				}
			}
			
			$result['roles'][] = array(
				'key' => $role,
				'name' => $details['name'],
				'caps' => $caps
			);
		}
		return $result;
	}

	
	private function notification_list() {
		$result = array(
			'success' => false,
			'message' => '',
			'source' => $this->getSource(),
		);

		// validate user token
		$user_id = $this->validateToken();
		if ($user_id == false) {
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;
		}

		global $wpdb;
		$sql = "SELECT * FROM $wpdb->options wp_options WHERE wp_options.option_name like 'cms.notice.%' ORDER BY wp_options.option_id DESC";
		$options = $wpdb->get_results($sql);

		// maintain no more then 50 records
		if (count($options) > 50) {
			$options = array_slice($options, 0, 50);
			$sql = "DELETE FROM $wpdb->options WHERE option_name like 'cms.notice.%' AND option_id < " . end($options)->id;
			$wpdb->get_results($sql);
		}

		$notifications = array();
		if (isset($options) && !empty($options)) {
			$notifications = array_map(function ($option) {
				$data = json_decode($option->option_value);
				$data->id = $option->option_id;
				$data->date = $this->time_since(time() - $data->time);
				return $data;
			}, $options);
		}

		$user_meta = get_user_meta($user_id, 'cms_read_notices', true);
		$reads = empty($user_meta) ? array() : json_decode($user_meta);
		$read_ids = array_column($reads, 'id');
		foreach ($notifications as $item) {
			$item->read = in_array($item->id, $read_ids);
		}

		// filter read notices in user meta
		$option_ids = array_column($options, 'option_id');
		$read_ids = array_intersect($read_ids, $option_ids);
		$reads = array_filter($reads, function ($item) use ($read_ids) {
			return in_array($item->id, $read_ids);
		});
		$user_meta = json_encode(array_values($reads));
		update_user_meta($user_id, 'cms_read_notices', $user_meta);

		$result['notifications'] = $notifications;
		$result['success'] = true;
		return $result;
	}
	
	private function time_since($since) {
		$chunks = array(
			array(60 * 60 * 24 * 365, 'year'),
			array(60 * 60 * 24 * 30, 'month'),
			array(60 * 60 * 24 * 7, 'week'),
			array(60 * 60 * 24, 'day'),
			array(60 * 60, 'hour'),
			array(60, 'minute'),
			array(1, 'second')
		);

		for ($i = 0, $j = count($chunks); $i < $j; $i++) {
			$seconds = $chunks[$i][0];
			$name = $chunks[$i][1];
			if (($count = floor($since / $seconds)) != 0) {
				break;
			}
		}

		$print = ($count == 1) ? '1 ' . $name : "$count {$name}s";
		return $print . ' ago';
	}
	
	private function notification_get_option_name()
	{
		if (empty($_POST['type'])) {
            throw new Exception('Type is required');
		}
		$type = strtolower($_POST['type']);

		if (empty($_POST['site'])) {
            throw new Exception('Site is required');
		}
		$site = strtolower($_POST['site']);

		if (empty($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
            throw new Exception('Post id is required');
		}
		$post_id = intval($_POST['post_id']);

		return implode('.', array('cms.notice', $type, $site, $post_id));
	}

	private function notification_create() {
		$result = array(
			'success' => false,
			'message' => '',
			'source' => $this->getSource(),
		);

		// validate user token
		$user_id = $this->validateToken();
		if ($user_id == false) {
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;
		}

		try {
			$option_name = $this->notification_get_option_name();
		} catch (Exception $e) {
            $result['message']= $e->getMessage();
			return $result;
		}

		$data = array(
			'title' => $_POST['title'],
			'content' => $_POST['content'],
			'link' => $_POST['link'],
			'time' => time(),
		);
		if (empty($data['title'])) {
			$result['message'] = 'Title is required!';
			return $result;
		}

		delete_option($option_name);
		add_option($option_name, json_encode($data), '', 'no');

		$result['notification'] = $data;
		$result['success'] = true;
		return $result;
	}
	
	private function notification_delete() {
		$result = array(
			'success' => false,
			'message' => '',
			'source' => $this->getSource(),
		);

		// validate user token
		$user_id = $this->validateToken();
		if ($user_id == false) {
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;
		}

		try {
			$option_name = $this->notification_get_option_name();
		} catch (Exception $e) {
            $result['message']= $e->getMessage();
			return $result;
		}

		$result['deleted'] = delete_option($option_name);
		$result['success'] = true;
		return $result;
	}
	
	private function notification_read() {
		$result = array(
			'success' => false,
			'message' => '',
			'source' => $this->getSource(),
		);

		// validate user token
		$user_id = $this->validateToken();
		if ($user_id == false) {
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;
		}

		$notice_ids = empty($_POST['notice_ids']) ? [] : explode(',', $_POST['notice_ids']);
		if (!empty($notice_ids)) {
			$user_meta = get_user_meta($user_id, 'cms_read_notices', true);
			$reads = empty($user_meta) ? array() : json_decode($user_meta);
			$read_ids = array_column($reads, 'id');

			$notice_ids = array_diff($notice_ids, $read_ids);
			$notices = array_map(function ($id) {
				return (object) array(
					'id' => $id,
					'time' => time(),
				);
			}, $notice_ids);

			$reads = array_merge($notices, $reads);
			$user_meta = json_encode(array_values($reads));
			update_user_meta($user_id, 'cms_read_notices', $user_meta);
	
			$result['notifications'] = $notices;
		}

		$result['success'] = true;
		return $result;
	}

	/*
	** list social media	
	*/
	private function social_list(){		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),
			'social_media' => array(),
		);		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		
		global  $facebook_auth, $twitter_auth, $youtube_auth;
		
		if (isset($facebook_auth['post_pages']) && !empty($facebook_auth['post_pages']) ){
			
			$page_ids=explode(',', $facebook_auth['post_pages']);			
		}		
		
		if ( isset($page_ids) && $fb_user_name=get_option('fb_token_user_name_'.$user_id, false) ) {
			//has a user token
			
			foreach ($page_ids as $page_id) {				
				
				if ($fb_user_name=get_option('fb_page_'.$page_id.'_userid_'.$user_id, false)) {
					
					$fb_page = new AIT_CENTRA_SOCIAL_MEDIA;
					$fb_page->id = $page_id;
					$fb_page->name = get_option('fb_page_'.$page_id.'_pagename', '');
					$fb_page->type = 'fan_page';
					$fb_page->link = '';
					$fb_page->icon = site_url().'/wp-content/plugins/ait-social-media-publish/images/'.$page_id.'.png';
					
					$result['social_media'][] = $fb_page;
				}
			}
			
		}
		
		
		if (!empty($twitter_auth)){ 
			
			$twitter = new AIT_CENTRA_SOCIAL_MEDIA;	
			$twitter->name = 'Twitter Tweet';
			$twitter->type = 'twitter';
			$twitter->link = '';
			$twitter->icon = site_url().'/wp-content/plugins/ait-social-media-publish/images/twitter.png';
			
			$result['social_media'][] = $twitter;					
		}
		
		if (!empty($youtube_auth)) {

			$youtube = new AIT_CENTRA_SOCIAL_MEDIA;	
			$youtube->name = 'YouTube Video';
			$youtube->type = 'youtube';
			$youtube->link = '';
			$youtube->icon = site_url().'/wp-content/plugins/ait-social-media-publish/images/youtube.png';
			
			$result['social_media'][] = $youtube;				
		}
		
		
		$result['success'] = true;
		return $result;
	}
	
	
	/*
	** Social Media Update
	**		Method: POST
	**		Url: /garfield/api/social/update
	**		Post data:
	**		    token: string required (get it from user login response)
	**		    post_id: int required
	**		    type: string required ( Value should be one of these: fan_page, twitter, youtube or  fb_ia. )
	**		    post_link_id: string optional (For fan_page is fb post link id,  twitter is twitter id,  youtube is youtube link, fb_ia TBD. )
	**		    fb_page_id: string optional (For fan_page use only. If type is fan_page and it is not set, will return false. )
	**		    post_link_time: string optional (Use for fan_page and twitter,  youtube not need, fb_ia TBD.  If type is fan_page twitter and it is not set, will return false. Format('Y-m-d H:i'))
	**		    post_message: string optional (Use for fan_page and twitter,  youtube not need,  fb_ia TBD. )
	**		Response:
	**		    success:  true or false
	**		    message: String
	**		    source: String
	**		    social_media: social media object or null	
	*/
	private function social_update(){		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),
			'social_media' => null,
		);	
		global $SOCIAL_MEDIA_TYPES;
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		
		// required post_id parameter		
		if ( !isset($_POST['post_id']) || !is_numeric($_POST['post_id']) ){
			$result['message'] = 'Post id is required!';
			return $result;
		}
		$post_id = (int) $_POST['post_id'];
		
		
		// required type parameter
		if ( !isset($_POST['type']) || empty($_POST['type']) ){
			$result['message'] = 'Type is required!';
			return $result;
		}
		$type = trim( $_POST['type'] );
		if ( !in_array($type, $SOCIAL_MEDIA_TYPES) ){
			$result['message'] = 'Invalid type!';
			return $result;
		}
		

		// post_link_id optional parameter
		$post_link_id = '';
		if ( isset($_POST['post_link_id']) && !empty($_POST['post_link_id']) ){
			$post_link_id = trim($_POST['post_link_id']);
		} 
		
		
		// fb_page_id optional parameter
		$fb_page_id = '';
		if ( isset($_POST['fb_page_id']) && !empty($_POST['fb_page_id']) ){
			$fb_page_id = trim($_POST['fb_page_id']);
		}
		
		
		// post_link_time optional parameter
		$post_link_time = '';
		if ( isset($_POST['post_link_time']) && !empty($_POST['post_link_time']) ){
			$post_link_time = trim($_POST['post_link_time']);
		}
		
		
		// post_message optional parameter
		$post_message = '';
		if ( isset($_POST['post_message']) && !empty($_POST['post_message']) ){
			$post_message = trim($_POST['post_message']);
		}
		
		// update fb fan page
		if ( $type == 'fan_page' ) {
			
			if ( empty($fb_page_id) ){
				$result['message'] = 'FB page id is required for fb post update!';
				return $result;
			}
			
			if ( empty($post_link_id) ){
				$result['message'] = 'FB post link id is required for fb post update!';
				return $result;
			}			
			
			if ( empty($post_link_time) ){
				$result['message'] = 'FB post link time is required for fb post update!';
				return $result;
			}
			
			
			//2019-12-05 support multiple			
			$new_post_link=(object)array(
				'page_id'=>$fb_page_id,
				'fb_post_link_id'=>$post_link_id,
				'message'=>$post_message,
				'user_id'=>$user_id,
				'updated_time'=>$post_link_time,
			);				
				
			
			$idx=0;
			while ($idx<=10) {		
				if ($tmp=get_post_meta($post_id, '_fb_post_link_id_'.$fb_page_id.'_'.$idx, true)) {		
					
					//exist					
					if ($fb_post_link=json_decode($tmp)){
						if ($fb_post_link->fb_post_link_id==$post_link_id){
							if ($response_message=update_post_meta( $post_id,  '_fb_post_link_id_'.$fb_page_id.'_'.$idx, json_encode($new_post_link, JSON_UNESCAPED_UNICODE ))){
								$fb_page = new AIT_CENTRA_SOCIAL_MEDIA;	
								$fb_page->id = $fb_page_id;
								$fb_page->type = 'fan_page';
								$fb_page->icon = site_url().'/wp-content/plugins/ait-social-media-publish/images/'.$fb_page_id.'.png';
								$fb_page->link = 'https://www.facebook.com/permalink.php?story_fbid='.str_replace($fb_page_id.'_','',$new_post_link->fb_post_link_id).'&id='.$fb_page_id;			
								$fb_page->post_link_id = $new_post_link->fb_post_link_id;
								$fb_page->post_message = $new_post_link->message;
								$fb_page->post_link_time = $new_post_link->updated_time;

								$result['social_media'] = $fb_page;
								$result['success'] = true;
								break;
							}
						}						
					}
					
					$idx++;
										
				} else {
					if ($response_message=add_post_meta( $post_id,  '_fb_post_link_id_'.$fb_page_id.'_'.$idx, json_encode($new_post_link, JSON_UNESCAPED_UNICODE ), true)){
												
						$fb_page = new AIT_CENTRA_SOCIAL_MEDIA;	
						$fb_page->id = $fb_page_id;
						$fb_page->type = 'fan_page';
						$fb_page->icon = site_url().'/wp-content/plugins/ait-social-media-publish/images/'.$fb_page_id.'.png';
						$fb_page->link = 'https://www.facebook.com/permalink.php?story_fbid='.str_replace($fb_page_id.'_','',$new_post_link->fb_post_link_id).'&id='.$fb_page_id;			
						$fb_page->post_link_id = $new_post_link->fb_post_link_id;
						$fb_page->post_message = $new_post_link->message;
						$fb_page->post_link_time = $new_post_link->updated_time;

						$result['social_media'] = $fb_page;
						$result['success'] = true;
						break;
					}
				}
			} 
						
		}
		// end update fb fan page
		
		
		// update twitter 
		if ($type == 'twitter'){ 
			
			if ( empty($post_link_id) ){
				$result['message'] = 'Twitter id is required for twitter update!';
				return $result;
			}			
			
			if ( empty($post_link_time) ){
				$result['message'] = 'FB post link time is required for fb post update!';
				return $result;
			}
			
			
			update_post_meta( $post_id, '_twitter_id', $post_link_id );
			update_post_meta( $post_id, '_twitter_time', $post_link_time);
			update_post_meta( $post_id, '_twitter_message', $post_message);
		
			
			$updated_id = get_post_meta($post_id, '_twitter_id', true);
			$updated_publish_time = get_post_meta($post_id, '_twitter_time', true);
			$updated_message = get_post_meta($post_id, '_twitter_message', true);
			
			if ( $updated_id != $post_link_id ){
				$result['message'] = 'Twitter updated id fail!';
				return $result;
			}
			
			if ( $post_link_time != $updated_publish_time ){
				$result['message'] = 'Twitter updated time fail!';
				return $result;
			}
			
			if ( $updated_message != $post_message ){
				$result['message'] = 'Twitter updated message fail!';
				return $result;
			}
		
			global $twitter_page_url;
			$twitter = new AIT_CENTRA_SOCIAL_MEDIA;
			$twitter->id = $updated_id;
			$twitter->type = 'twitter';
			$twitter->icon = site_url().'/wp-content/plugins/ait-social-media-publish/images/twitter.png';
			$twitter->post_message = $updated_message;
			$twitter->post_link_time = $updated_publish_time;
			if ( isset ($twitter_page_url) ){
				$twitter->link = $twitter_page_url.'/status/'.$updated_id;
			}
			
			$result['social_media'] = $twitter;	
			$result['success'] = true;
		}
		// end update twitter 
		
		
		
		// update youtube
		if ($type == 'youtube') {
			
			if ( empty($post_link_id) ){
				$result['message'] = 'YouTube link is required for twitter update!';
				return $result;
			}
			
			update_post_meta( $post_id, 'youtube_id', $post_link_id);

			$updated_id = get_post_meta($post_id, 'youtube_id', true);
			if ( $updated_id != $post_link_id ){
				$result['message'] = 'YouTube updated link fail!';
				return $result;
			}
			
			$youtube = new AIT_CENTRA_SOCIAL_MEDIA;
			$youtube->type = 'youtube';
			$youtube->icon = site_url().'/wp-content/plugins/ait-social-media-publish/images/youtube.png';
			$youtube->link = $updated_id;			
			
			$result['social_media'] = $youtube;
			$result['success'] = true;
		}
		// end update youtube
		
		
		if ( $result['social_media'] == null ){
			$result['message'] = 'Can not update '.$type.' yet!';
		} 
	
		return $result;
	}
	

	/*
	** Social Media Update
	**		Method: POST
	**		Url: /garfield/api/social/delete
	**		Post data:
	**		    token: string required (get it from user login response)
	**		    post_id: int required
	**		    type: string required ( Value should be one of these: fan_page, twitter, youtube or  fb_ia. )
	**			fb_page_id: string optional (For fan_page use only. If type is fan_page and it is not set, will return false. )
	**			post_link_id: string optional (For fan_page use only. If type is fan_page and it is not set, will return false. )
	**		Response:
	**		    success:  true or false
	**		    message: String
	**		    source: String
	*/
	private function social_delete(){		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),			
		);	
		global $SOCIAL_MEDIA_TYPES;
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		
		// required post_id parameter		
		if ( !isset($_POST['post_id']) || !is_numeric($_POST['post_id']) ){
			$result['message'] = 'Post id is required!';
			return $result;
		}
		$post_id = (int) $_POST['post_id'];
		
		
		// required type parameter
		if ( !isset($_POST['type']) || empty($_POST['type']) ){
			$result['message'] = 'Type is required!';
			return $result;
		}
		$type = trim( $_POST['type'] );
		if ( !in_array($type, $SOCIAL_MEDIA_TYPES) ){
			$result['message'] = 'Invalid type!';
			return $result;
		}
		

		
		// delete fb fan page
		if ( $type == 'fan_page' ) {			
			
			if ( !isset($_POST['fb_page_id']) || empty($_POST['fb_page_id']) ){
				$result['message'] = 'FB page id is required for FB post delete!';
				return $result;
			}
			$fb_page_id = trim($_POST['fb_page_id']);
			
			if ( !isset($_POST['post_link_id']) || empty($_POST['post_link_id']) ){
				$result['message'] = 'FB post link id is required for FB post delete!';
				return $result;
			}
			$post_link_id = trim($_POST['post_link_id']);
			
			//2019-12-05 support multiple
			$idx=0;
			while ($idx<=10){				
				if ($tmp=get_post_meta($post_id, '_fb_post_link_id_'.$fb_page_id.'_'.$idx, true)){
					if($fb_post_link=json_decode($tmp)){						
						if ($fb_post_link->fb_post_link_id==$post_link_id){
							delete_post_meta( $post_id, '_fb_post_link_id_'.$fb_page_id.'_'.$idx);
							$result['success'] = true;
							return $result;
						}
					}
				} 			
				$idx++;
			} 
			
			if ( !metadata_exists('post', $post_id, '_fb_post_link_id_'.$fb_page_id) ){
				$result['success'] = true;
				return $result;
			}
			
			// verify to delete the same post link id, if not the same id, return
			$saved_link_id = get_post_meta($post_id, '_fb_post_link_id_'.$fb_page_id, true);			
			if ( $saved_link_id != $post_link_id ){
				$result['success'] = true;
				return $result;
			}
			
			$deleted_id = delete_post_meta( $post_id, '_fb_post_link_id_'.$fb_page_id );	//(bool) True on success, false on failure.
			$deleted_time = delete_post_meta( $post_id, '_fb_post_link_time_'.$fb_page_id );	
			$deleted_message = delete_post_meta( $post_id, '_fb_post_message_'.$fb_page_id );
			$deleted_user_id = delete_post_meta( $post_id, '_fb_post_user_id_'.$fb_page_id);			
			
			if ( $deleted_id && $deleted_time && $deleted_message && $deleted_user_id ){
				$result['success'] = true;
			}
			
		}
		
		
		
		// delete twitter 
		if ($type == 'twitter'){ 
			
			if ( !metadata_exists('post', $post_id, '_twitter_id') ){
				$result['message'] = 'Twitter is not existed to be deleted!';
				return $result;
			}
			
			$deleted_id = delete_post_meta( $post_id, '_twitter_id');
			$deleted_time = delete_post_meta( $post_id, '_twitter_time');
			$deleted_message = delete_post_meta( $post_id, '_twitter_message');
			if ( $deleted_id && $deleted_time && $deleted_message ){
				$result['success'] = true;
			}
		}
		
		
		// delete youtube 
		if ($type == 'youtube') {
			
			if ( !metadata_exists('post', $post_id, 'youtube_id') ){
				$result['message'] = 'YouTube is not existed to be deleted!';
				return $result;
			}
			
			$deleted_id = delete_post_meta( $post_id, 'youtube_id');
			if ( $deleted_id ){
				$result['success'] = true;
			}			
		}
		
		
		if ( $result['success'] == false ){
			$result['message'] = 'Delete '.$type.' fail!';
		} 
	
		return $result;
	}

	

	/*
	** search terms by search text	
	*/
	private function search_terms(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),		
			'items' => array(),
		);
		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		// required parameters		
		if ( !isset($_POST['taxonomy']) || empty($_POST['taxonomy']) ){
			$result['message'] = 'Taxonomy is required!';
			return $result;
		}	
		if ( !isset($_POST['text']) || empty($_POST['text']) ){
			$result['message'] = 'Search_text is required!';
			return $result;
		}		
		// end required parameters	
		
		$args = array(
			'taxonomy'=>$_POST['taxonomy'],
			'hide_empty' => false,
			'name__like'    => $_POST['text'],
		);
		
		$terms = get_terms( $args ); //(array|int|WP_Error) List of WP_Term instances and their children. Will return WP_Error, if any of $taxonomies do not exist.
		
		if ( is_wp_error($terms) ){
			$result['message'] = $terms->get_error_message();
		}
		else {
			foreach ($terms as $term) {
				$result['items'][] = array(
					'id'=> $term->term_id,
					'name'=> $term->name,
				);
			}
			
			$result['success'] = true;
		}
		
		
		return $result;
	}


	/*
	** Image Upload:  this is the process of retrieving a media item from another server instead of a traditional media upload.
	** 		Method: POST
	** 		Url: http://backend.bastillepost.com:8282/garfield/api/image/upload
	** 		Post data:
	** 		    token: string required (get it from user login response)
	** 		    image_url: string required. Example: https://merchant.aitshop.ca/images/asphalt-beauty.jpg
	** 		    image_name: string optional ( File name. If not set or invalid, will get the file name from the url. ) Example: download.jpg
	** 		Response:
	** 		    success:  true or false
	** 		    message: String
	** 		    source: String
	** 		    uploaded_url: url string or empty string
	*/
	private function image_upload(){
		
		$result = array(
			'success'=> false,
			'message'=>'',
			'source'=> $this->getSource(),		
			'uploaded_url' => '',
		);
		$allowed_type = array('jpg','jpeg','png','gif');
		$file = array();
		
		
		// validate user has the right to create new user
		$user_id= $this->validateToken();
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');
			$result['message'] = 'Invalid token!';
			return $result;			
		}
		
		
		// get required parameter image_url
		if ( !isset($_POST['image_url']) || empty($_POST['image_url']) ){			
			$result['message'] = 'File not found!';
			return $result;
		}
		$url = trim($_POST['image_url']);
		
		
		// get optional parameter image_name	
		if ( isset($_POST['image_name']) && !empty($_POST['image_name']) ){			
			
			$splits = explode('.',$_POST['image_name']);
			
			if ( isset($splits[1]) && in_array( strtolower($splits[1]), $allowed_type) ){
				$file['name'] = trim($_POST['image_name']);
				$file['type'] = 'image/'.strtolower($splits[1]);
			}			
		}
		
		
		// get file name and type from url
		if ( !isset($file['name']) ){
			$file['name'] = basename($url);	
			$splits = explode('.',$file['name']);		
			if ( isset($splits[1]) && in_array( strtolower($splits[1]), $allowed_type) ) {
				$file['type'] = 'image/'.strtolower($splits[1]);
			}
		}		
		
		
		// These files need to be included as dependencies.
		require_once( ABSPATH  . '/wp-admin/includes/image.php' );
		require_once( ABSPATH  . '/wp-admin/includes/file.php' );
		require_once( ABSPATH  . '/wp-admin/includes/media.php' );
		
		
		// Download file to temp dir
		$timeout_seconds = 5;
		$temp_file = download_url( $url, $timeout_seconds );//WP_Error on failure, string Filename on success.
		
		if ( is_wp_error( $temp_file ) ) {
			$result['message'] = $temp_file->get_error_message();
			return $result;
		}
		
		$file['tmp_name'] = $temp_file;
		$file['error'] = 0;
		$file['size'] = filesize($temp_file);
				
		
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
		);
	 
		// Move the temporary file into the uploads directory.
		$uploaded = wp_handle_sideload( $file, $overrides );
	 
		if ( ! empty( $uploaded['error'] ) ) {
			// Insert any error handling here.
			$result['message'] = $uploaded['error'];
			
		} else {
			$filename  = $uploaded['file']; // Full path to the file.
			$local_url = $uploaded['url'];  // URL to the file in the uploads dir.
			$type      = $uploaded['type']; // MIME type of the file.
	
			// Perform any actions here based in the above results.
			$result['uploaded_url'] = $local_url;
			$result['success'] = true;
		}
		
		//$result['uploaded'] = $uploaded;
		//$result['file'] = $file;
		return $result;
		
	}
	
	
	/*
	** FB Scrape API 
	** 		Method: POST
	** 		Url: /garfield/api/scrape/facebook
	** 		Post data:
	** 		  token: string required (get it from user login response)
	** 		  post_id:optional
	** 		  page_id:required 
	** 		  scrape_url:required
	** 		Response:
	** 		  success:  true or false
	** 		  message: String
	** 		  source: String
	** 
	*/
	private function scrape_facebook(){
		$result=(object)array('success'=>false, 'message'=>'', 'fb_response'=>null);
		$post_id=$page_id=0;	
		$scrape_url='';
		
		if ( !isset($_POST['scrape_url']) || empty($_POST['scrape_url']) ){	
			$result->message.= '<p>Scrape_url not found!</p>';			
			return $result;					
		}
		$scrape_url=$_POST['scrape_url'];
		
		if ( !isset($_POST['page_id']) || empty($_POST['page_id']) ){			
			$result->message.= '<p>Facebook Page ID not found!</p>';			
			return $result;					
		}
		$page_id=$_POST['page_id'];
		
		
		if ( isset($_POST['post_id']) && !empty($_POST['post_id']) ){		
			$post_id=intval($_POST['post_id']);
			$cur_post=get_post( $post_id );
			
			if (isset($cur_post->post_status) &&$cur_post->post_status == 'publish'){
				
				$post_url=site_url().'/article/'.$post_id.'/';
				
				$purge_url='https://backend.bastillepost.com/varnishpurge.php';
				$response = wp_remote_post( $purge_url, array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(),
					'body'        => array(
						'filename' => $post_id,
					),
					'cookies'     => array()
					)
				);
				
				
				if ( is_wp_error( $response ) ) {
    				$result->message.= print_r($response->get_error_message());
				} else {	
    				$result->message.= '<p>Successfully clear article cache.</p>';
					
					if ($html=file_get_contents($post_url)){
						$result->message.= '<p>Successfully create article cache ('.$post_url.')</p>';
					}
				}
			}				
		}
				
	
		include_once(plugin_dir_path( __DIR__ ) .'../ait-social-media-publish/includes/setting-facebook-tokens.php');
		
		if (isset($facebook_tokens[$page_id])){
			$token=$facebook_tokens[$page_id];			
		} else {
			$result->message.= '<p>Facebook Page Token not found (page_id='.$page_id.')</p>';
			return $result;
		}

		$url='https://graph.facebook.com/v4.0/?scrape=true&id='.$scrape_url;
		$response = wp_remote_post( $url, array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers' => array(
							'Authorization' => 'Bearer '.$token,
						 ),
			)
		);
		if ( is_wp_error( $response ) ) {
			$result->message.= print_r($response->get_error_message());
		} else {					
			$result->fb_response =json_decode($response['body']);
			
			if (isset($result->fb_response->error)){
				$result->fb_response->request=(object)array(
					'request_url'=>$url,
					'token'=>$token,
				);
			} else {
				$result->message.='<p>Successfully scraped url ('.$scrape_url.')</p>';
				$posted_to_pages='';
				if (isset($result->fb_response->pages)&& count($result->fb_response->pages)>0){
					$posted_to_pages.='<p>The link has been posted to pages:</p><ul>';
					foreach($result->fb_response->pages as $page){
						$posted_to_pages.='<li>'.$page->name.'(<a href="'.$page->url.'" target="_blank">'.$page->id.'</a>)</li>';
					}
					$posted_to_pages.='</ul>';
				}
				if (isset($result->fb_response->title)){
					if (isset($result->fb_response->image[0]->url)){
						$result->message.='<div style="width:96%; margin:10px 2%; overflow:auto"><div style="width:calc(30% - 10px); margin:0 10px 0 0; float:left"><img src="'.$result->fb_response->image[0]->url.'" style="width:200px; height:auto; max-width:100%; max-height:100%;" /></div><div style="width:70%; margin:0; float:left"><p>'.$result->fb_response->title.'</p>'.$posted_to_pages.'</div></div>';
					} else {
						$result->message.='<div style="width:96%; margin:10px 2%; overflow:auto"><div style="width:100%; margin:0; float:left"><p>'.$result->fb_response->title.'</p>'.$posted_to_pages.'</div></div>';
					}
				}
				
			}
		}
								
		$result->success=true;	
		return $result;
	}

}



?>