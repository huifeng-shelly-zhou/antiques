<?php
error_reporting(E_ALL);


global $antiquea_apis;
$antiquea_apis = new ANTIQUES_APIS();


class ANTIQUES_APIS {
	
	private $name;
	private $version;
	private $controllers;
	private $endpoint_root;	
	
	private $user;	
	
	private $error_response = array('success'=>false);
	
	public function __construct( $config = array() )
	{
		
		$this->load_dependencies();
		$this->init_hooks();
		
		if (class_exists('ANTIQUES_USER')){
			$this->user = new ANTIQUES_USER();
		}
		
	} // __construct
	
	private function load_dependencies() {
	
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-antiques-wp-rewrite.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/setting.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-user.php';	
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-antiques.php';	
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-message.php';	
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-antiques-options.php';	
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-author.php';	
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
		$this->endpoint_root = site_url().'/'.$this->name.'/'.$this->controllers[0];		
				
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
		
		// validate action	
		$action = get_query_var( 'action' );	
		
		if ( $action == 'ZGE3MzRmanB5dWhhZzQ' ){			

			include(plugin_dir_path( dirname( __FILE__ ) ) .'api_doc.php');
			die(); 
		}
		
		
		header('Content-Type: application/json');
		
		if ( !in_array(strtolower( $current_controller ), $this->controllers) ) {
			$arr = array('success'=> false,'message'=>'unsupported controller : '.$current_controller);
			die( json_encode($arr) );
		}
		
		
		if ( empty( $action ) ) {
			$arr = array('success'=> false,'message'=>'Action is required.');
			die( json_encode($arr) );
		}
		
		
		
		// validate key and secret
		$headers = $this->parseRequestHeaders();
		if(!isset($headers["Key"]) || $headers["Key"] != $API_KEY || $headers["Secret"] != $API_SECRET)
		{
			$arr = array('success'=> false,'message'=>'Incorrect key and secret pair.');
			die( json_encode($arr) );
		}
		
		
		
		$result = array(
			'success'=> false,
			//'headers'=>$headers,
			//'controller'=>$current_controller,
			//'action'=> str_replace('/', ' ', $action),
			'message'=>'',
		
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
	
	
	/*
	** Get App Feed 		
	**	 Url: /OX/api/feed	
	**	 Response:
	**	   success:  true or false	
	*/
	private function feed(){
		
		$refresh = (isset($_GET['refresh']) && $_GET['refresh'] == '1')? true:false;
		
		$file = plugin_dir_path( dirname( __FILE__ ) ) . 'antiques_feeds.json';
		
		if ( file_exists($file) && !$refresh ){
			$result = file_get_contents($file);
			if ($result && !empty($result) > 0){
				return json_decode($result, true);
			}			
		}

		global $Feeds;		
		
		file_put_contents($file, json_encode($Feeds));
		
		return $Feeds;
	}


	/*
	** User Login 		
	**	 Url: /OX/api/user/login
	**	 Post data:
	**	   authorize  -  string required, base64 {email:password}
	**	   firebase_token - string optional
	**	   lang - string optional
	**	 Response:
	**	   success:  true or false
	**	   message: String
	**	   resend_link: String
	**	   user: null or user object as following
	**
	*/
	private function user_login(){
		global $lang;
		
		if ( isset($this->user) ){
			return $this->user->login($_POST, $lang);
		}
		return $this->error_response;
	}

	
	/*
	** User validate (validate user membership)
	** 		Method: POST
	** 		Url: /OX/api/user/validate
	** 		Post data: 		  
	** 		  token: string required (get it from user login response)
	** 		Response:
	** 		  success:  true or false
	** 		  message: String
	** 		  display_name: String
	** 
	*/
	private function user_validate(){
		
		if ( isset($this->user) ){
			return $this->user->validate($_POST);	
		}
		return $this->error_response;	
	}
	
	
	/*
	** User Profile 
	** 		Method: POST
	** 		Url: /OX/api/user/profile
	** 		Post data:
	** 		  token: string required (get it from user login response)
	**	   	  lang: string optional
	** 		Response:
	** 		  success:  true or false
	** 		  message: String
	** 		  user: null or user object 
	** 
	*/
	private function user_profile(){
		global $lang;
		
		if ( isset($this->user) ){
			return $this->user->profile($_POST, $lang);	
		}
		return $this->error_response;	
	}
	
	
	/*
	** User Profile Update (For user’s owned profile.)
	** 		Method: POST
	** 		Url: /OX/api/user/profile/update
	** 		Post data:
	** 		  token: string required (get it from user login response)	
	** 		  display_name: string optional ( If empty or not set, will return false. )	
	** 		  description: string optional ( If empty or not set, will set value to empty string. )
	** 		  updated_password: string optional base64 encode (If empty or not set, will be ignored. Password must be minimum 8 characters.)
	**	   	  lang: string optional
	** 		Response:
	** 		  success:  true or false
	** 		  message: String
	** 		  user: null or user object 
	** 
	*/
	private function user_profile_update(){
		global $lang;
		
		if ( isset($this->user) ){
			return $this->user->profile_update($_POST, $lang);	
		}
		return $this->error_response;		
	}
	
	
	/*
	** User register 
	**		Method: POST
	**		Url: /OX/api/user/register
	**		Post data:	**		 
	**		  authorize -  string required (base64 encoded email:password)
	**		  display_name -  string optional
	**		  firebase_token - string optional
	**		  lang - string optional
	**		Response:
	**		  success:  true or false
	**		  message: String
	**
	*/		
	private function user_register(){
		global $lang;
		
		if ( isset($this->user) ){
			return $this->user->register($_POST, $lang);	
		}
		return $this->error_response;		
	}
	
	
	/*
	** User login with other account 
	**		Method: POST
	**		Url: /OX/api/user/provider/account
	**		Post data:	**		 
	**		  authorize -  string required (base64 encoded email:id)
	**		  provider - string required
	** 		  display_name -  string optional
	** 		  avatar -  string optional 
	**		  firebase_token - string optional
	**		  lang - string optional
	**		Response:
	**		  success:  true or false
	**		  message: String
	**
	*/		
	private function user_provider_account(){
		global $lang;
		
		if ( isset($this->user) ){
			return $this->user->providerAccount($_POST, $lang);	
		}
		return $this->error_response;		
	}
	
	/*
	** User lang update 
	**		Method: POST
	**		Url: /OX/api/user/lang/update
	**		Post data:	**		 
	**		  token: string required (get it from user login response)
	**		  lang - string optional
	**		Response:
	**		  success:  true or false
	**		  message: String
	**
	*/		
	private function user_lang_update(){
		global $lang;
		
		if ( isset($this->user) ){
			return $this->user->langUpdate($_POST, $lang);	
		}
		return $this->error_response;		
	}
	
	/*
	** User Password Reset 
	**		Method: POST
	**		Url: /OX/api/user/password/reset
	**		Post data:	**		 
	**		  email -  string required base64 {email}
	**		  lang - string optional
	**		Response:
	**		  success:  true or false
	**		  message: String
	**
	*/	
	private function user_password_reset(){
		global $lang;
		
		if ( isset($this->user) ){
			return $this->user->password_reset($_POST, $lang);	
		}
		return $this->error_response;		
		
	}//merchant_reset_password
	
	
	/*
	** User Verify Email Address 
	**		Method: POST
	**		Url: /OX/api/user/verify/email
	**		Post data:	 
	**		  user_login -  string required base64 {user_login}
	**		  key -  string required 
	**		  firebase_token -  string
	**		  lang - string optional
	**		Response:
	**		  success:  true or false
	**		  message: String
	**		  resend_link: String
	**		  user: null or object
	**
	*/	
	private function user_verify_email(){
		global $lang;
		
		if ( isset($this->user) ){
			return $this->user->verify_email($_POST, $lang);	
		}
		return $this->error_response;	
	
	}
	
	
	/*
	** User Send Verification Code
	**		Method: GET
	**		Url: /OX/api/user/send/verification
	**		Get data:	 
	**		  verify -  string required base64 {user_login}	
	**		Response:
	**		  true or false
	*/	
	private function user_send_verification(){
		
		$email = isset($_GET['verify'])? base64_decode($_GET['verify']):'';
		
		if (function_exists('resendVerification')){
			return resendVerification($email);
		}
		return false;
	}
	

	/*
	** User antique single 
	** 		Method: POST
	** 		Url: /OX/api/user/antique/single
	** 		Post data:
	** 		  token: string required (get it from user login response)
	**		  antique_id: string requried
	**	   	  lang: string optional
	** 		Response:
	** 		  success:  true or false
	** 		  message: String
	** 		  antique: null or antique object 
	** 
	*/
	private function user_antique_single(){
		global $lang;
		
		if ( isset($this->user) ){
			
			$result = array(
				'success'=> false,
				'message'=>'',
				'terms' => null,
				'antique' => null
			);
			
			// validate user has the right to create new user
			$user_id = $this->user->validateToken($_POST, false);
			
			if ($user_id==false){
				header('HTTP/1.0 401 Unauthorized');				
				return $result;			
			}
			
			if (!isset($_POST['antique_id'])){		
			
				$result['message'] = 'Antique id not found!';		
				return $result;
			}
			
			$antique_id = empty($_POST['antique_id'])? 0:$_POST['antique_id'];
			
			if (!is_numeric($antique_id)){
				$result['message'] = 'Invalid Antique ID!';		
				return $result;
			}
			
			if (class_exists('ANTIQUES')){
				
				$ANTIQUES = new ANTIQUES;
				$result['antique'] = $ANTIQUES->getUserAntique($user_id, $antique_id);
				$result['terms'] = $ANTIQUES->getTerms($lang);
			}
			
			if ($result['antique'] != null && isset($result['antique']->id) && $result['antique']->id > 0){
				$result['success'] = true;
			}
			
			return $result;	
			
		}
		return $this->error_response;	
	}

	
	/*
	** User antique update 
	** 		Method: POST
	** 		Url: /OX/api/user/antique/update
	** 		Post data:
	** 		  token: string required (get it from user login response)
	**		  antique_id: string requried
	**		  lang_version_zh: string optional
	**		  lang_version_en: string optional
	**		  c_categories: string optional categories ids separated by comma
	**	   	  lang: string optional
	** 		Response:
	** 		  success:  true or false
	** 		  message: String	
	** 
	*/
	private function user_antique_update(){
		global $lang;
		
		if ( isset($this->user) ){
			
			$result = array(
				'success'=> false,				
				'message'=>'',
				'antique'=>null
			);
			
			// validate user has the right to create new user
			$user_id = $this->user->validateToken($_POST, false);
			
			if ($user_id==false){
				header('HTTP/1.0 401 Unauthorized');						
				return $result;			
			}
			
			if ( !isset($_POST['antique_id']) || empty($_POST['antique_id']) || !is_numeric($_POST['antique_id'])){
				$result['message'] = 'Invalid antique Id!';
				return $result;
			}
			
			$antique_id = intval($_POST['antique_id']);
			
			if (class_exists('ANTIQUES')){
			
				$ANTIQUES = new ANTIQUES;
				
				$result['success'] = $ANTIQUES->updateUserAntique($user_id, $antique_id, $_POST);
				if ($result['success']){
					$result['antique'] = new USER_ANTIQUE_SINGLE($antique_id);
				}
			}			
			
			return $result;	
		}
		return $this->error_response;	
	}
	
	
	/*
	** User antique delete 
	** 		Method: POST
	** 		Url: /OX/api/user/antique/delete
	** 		Post data:
	** 		  token: string required (get it from user login response)
	**		  antique_ids: string requried; ids separated by comma	
	**	   	  lang: string optional
	** 		Response:
	** 		  deleted_count:  int	
	** 
	*/
	private function user_antique_delete(){
		global $lang;
		
		if ( isset($this->user) ){
			
			$result = array(
				'deleted_count'=> 0,				
			);
			
			// validate user has the right to create new user
			$user_id = $this->user->validateToken($_POST);
			
			if ($user_id==false){
				header('HTTP/1.0 401 Unauthorized');							
				return $result;			
			}
			
			if ( !isset($_POST['antique_ids']) || empty($_POST['antique_ids']) ){
				$result['message'] = 'Invalid antique Ids!';
				return $result;
			}			
			
			
			if (class_exists('ANTIQUES')){
			
				$ANTIQUES = new ANTIQUES;
				
				$result['deleted_count'] = $ANTIQUES->deleteUserAntiques($user_id, $_POST['antique_ids']);
			}			
			
			return $result;	
		}
		return $this->error_response;	
	}
	
	
	/*
	** User antique list 
	** 		Method: POST
	** 		Url: /OX/api/user/antique/list
	** 		Post data:
	** 		  token: string required (get it from user login response)	
	**		  lang_version: string required
	**		  c_paged: int optional
	**	   	  lang: string optional
	** 		Response:
	** 		  success:  true or false
	** 		  message: String
	** 		  antique: null or antique object 
	** 
	*/
	private function user_antique_list(){
		global $lang, $languages;;
		
		if ( isset($this->user) ){
			
			$result = array(
				'success'=> false,
				'message'=>'',
				'paged'=>1,
				'antiques' => array()
			);
			
			// validate user has the right to create new user
			$user_id = $this->user->validateToken($_POST);
			
			if ($user_id==false){
				header('HTTP/1.0 401 Unauthorized');					
				return $result;			
			}
			
			if (!isset($_POST['lang_version'])){		
			
				$result['message'] = 'lang version not found!';		
				return $result;
			}
			
			$lang_version = $_POST['lang_version'];
			
			if (!isset($languages[$lang_version])){
				$result['message'] = 'Invalid lang version!';		
				return $result;
			}
			
			// get optional input parameters
			 if ( isset($_POST['c_paged']) && is_numeric($_POST['c_paged']) ){
				$paged = (int) $_POST['c_paged'];
				if ($paged < 1){
					$paged = 1;
				}
				$result['paged'] = $paged;
				
			}
			
			if (class_exists('ANTIQUES')){
				
				$ANTIQUES = new ANTIQUES;
				$result['antiques'] = $ANTIQUES->getUserAntiqueList($user_id, $lang_version, $result['paged']);
				
			}
			
			$result['success'] = true;
			return $result;
			
		}
		return $this->error_response;	
	}

	
	private function filters(){
		global $lang;
		
		$antiques = new ANTIQUES;	
		
		return $antiques->getPostsFilters($lang);
	}


	private function post_list(){
		global $lang;
		
		$antiques = new ANTIQUES;
		
		
		return $antiques->antiqueList($_POST, $lang, $this->endpoint_root);
	}
	
	
	private function home_list(){
		global $lang;
		
		$antiques = new ANTIQUES;
		
		
		return $antiques->antiqueHomeList($_POST, $lang, $this->endpoint_root);
	}
	
	
	private function post_single(){
		global $lang;
		
		$result = array(
			'success'=> false,
			'message'=>'',			
			'antique' => null
		);
		
		$antique_id = empty($_POST['antique_id'])? 0:$_POST['antique_id'];
		if (!is_numeric($antique_id)){
			$result['message'] = 'Invalid Antique ID!';		
			return $result;
		}
		
		if (class_exists('ANTIQUE_SINGLE')){				
			
			$result['antique'] = new ANTIQUE_SINGLE($antique_id, $lang);			
		}
		
		if ($result['antique'] != null && isset($result['antique']->id) && $result['antique']->id > 0){
			$result['success'] = true;
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
			'authors' => array(),
		);
		
		
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
	
	
	private function author_single(){
		
		global $lang;
		
		if ( class_exists('ANTIQUES_AUTHOR') ){			
			return new ANTIQUES_AUTHOR($_POST, $lang);
		}
		return $this->error_response;		
	}

	
	private function message_send() {
		global $FCM_SERVER_KEY, $FCM_API;		
		
		$result = array(
			'success' => false,
			'message' => '',			
		);

		// validate user has the right to create new user
		$user_id = $this->user->validateToken($_POST, false);
		
		if ($user_id==false){
			header('HTTP/1.0 401 Unauthorized');				
			return $result;			
		}

		if ( !isset($_POST['notification']) || empty($_POST['notification']) ){
			$result['message'] = 'No message body found!';
			return $result;
		}
		
		$notification = base64_decode($_POST['notification']);
		
		
		// save to message table in DB
		$jsonObj = json_decode($notification, true);
		if (isset($jsonObj['data']) && isset($jsonObj['data']['message'])){
			global $antiquea_message;
			
			$id = $antiquea_message->insert($jsonObj['data']['message']);
			
			$result['insert'] = $id;
			$jsonObj['data']['message']['id'] = $id;
			
			$notification = json_encode($jsonObj);
		}
		
		
		$headers = array
		(
			 'Authorization: ' . $FCM_SERVER_KEY, 
			 'Content-Type: application/json'
		);                                                                                 
	
		$ch = curl_init();  

		curl_setopt( $ch,CURLOPT_URL, $FCM_API );                                                                  
		curl_setopt( $ch,CURLOPT_POST, true );  
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, $notification);                                                                  

		$return = curl_exec($ch);
		$result['message'] = $return;
		
		if ($return){
			$jsonObj = json_decode($return, true);
			if (isset($jsonObj['message_id']) && is_numeric($jsonObj['message_id'])){
				$result['success'] = true;
			}
		}
		
		curl_close ($ch);		
		
		return $result;
	}


	private function message_remove(){
		
		if ( isset($this->user) ){
			
			$result = array(
				'success'=> false,				
				'message'=>'',				
			);
			
			// validate user has the right to create new user
			$user_id = $this->user->validateToken($_POST, false);
			
			if ($user_id==false){
				header('HTTP/1.0 401 Unauthorized');						
				return $result;			
			}
			
			if ( !isset($_POST['message_keys']) || empty($_POST['message_keys']) ){
				$result['message'] = 'Invalid message keys!';
				return $result;
			}
			
			global $antiquea_message;
			$message_keys = explode(',', $_POST['message_keys']);			
			
			$result['success'] = true;
			$result['message'] = $antiquea_message->updateAction($message_keys, $user_id, Message_Actions::removed);
			
			return $result;	
		}
		return $this->error_response;
	}
	
	
	private function message_check_unread(){
		
	}
	
	private function message_list(){
		
		if ( isset($this->user) ){
			
			$result = array(
				'success'=> false,				
				'message'=>'',
				'list'=>array()
			);			
			
			$user_id = $this->user->validateToken($_POST);
			
			if ($user_id==false){
				header('HTTP/1.0 401 Unauthorized');						
				return $result;			
			}			
			
			global $antiquea_message;
			$list = $antiquea_message->get_by_user($user_id);			
			$list = $antiquea_message->filteOutRemovedMessage($user_id, $list);
			$result['list'] = $antiquea_message->defineUnreadMessages($user_id, $list);
			$result['list'] = array_merge($result['list'], array());
			
			return $result;	
		}
		return $this->error_response;
	}
	
	
	private function message_single(){
		if ( isset($this->user) ){
			
			$result = array(
				'success'=> false,				
				'message'=>'',
				'list'=>array()
			);
			
			// validate user has the right to create new user
			$user_id = $this->user->validateToken($_POST, false);
			
			if ($user_id==false){
				header('HTTP/1.0 401 Unauthorized');						
				return $result;			
			}

			if ( !isset($_POST['message_key']) || empty($_POST['message_key']) ){
				$result['message'] = 'Invalid message key!';
				return $result;
			}
			
			$message_key = trim($_POST['message_key']);
			
			global $antiquea_message;
			$list = $antiquea_message->get_by_key($message_key);				
			$list = $antiquea_message->filteOutRemovedMessage($user_id, $list);
			$list = $antiquea_message->defineUnreadMessages($user_id, $list);	
			
			// get sender avatar
			$sender_avatars = array();
			foreach($list as $item){
				$item = (array)$item;
				if(isset($item['sender_id'])){
					
					if(!isset($sender_avatars[$item['sender_id']])){
						$sender_avatars[$item['sender_id']] = esc_url( get_user_meta($item['sender_id'], 'author_profile_picture', true) );
					}
					
					$item['sender_avatar'] = $sender_avatars[$item['sender_id']];
				}
			
				$result['list'][] = (object) $item;
			}
			
			
			// mark all read
			$antiquea_message->updateAction(array($message_key), $user_id, Message_Actions::read);
			
			$result['success'] = true;
			
			return $result;	
		}
		return $this->error_response;
	}

	
	/*
	** Image Upload:  this is the process of retrieving a media item from another server instead of a traditional media upload.
	** 		Method: POST
	** 		Url: /OX/api/image/upload
	** 		Post data:
	** 		    token: string required (get it from user login response)
	**			image: file	required
	** 		    attachment_type: string required. Values: avatar or antique 
	** 		    antique_id: int optional.
	**		  	lang - string optional
	** 		Response:
	** 		    success:  true or false
	** 		    message: String	
	** 		    uploaded_url: url string or empty string
	*/
	private function image_upload(){
		
		global $lang;
		
		if ( isset($this->user) ){
			return $this->user->uploadImage($_POST, $lang);	
		}
		return $this->error_response;		
	}
	
	
	/*
	** Image Delete: 
	** 		Method: POST
	** 		Url: /OX/api/image/delete
	** 		Post data:
	** 		    token: string required (get it from user login response)
	**			image_id: int required
	** 		    antique_id: int required.
	**		  	lang - string optional
	** 		Response:
	** 		    success:  true or false
	** 		    message: String	
	** 		    uploaded_url: url string or empty string
	*/
	private function image_delete(){
		
		global $lang;
		
		if ( isset($this->user) ){
			return $this->user->deleteImage($_POST, $lang);	
		}
		return $this->error_response;		
	}

	
	/*
	** Image Swap:  
	** 		Method: POST
	** 		Url: /OX/api/image/swap
	** 		Post data:
	** 		    token: string required (get it from user login response)
	**			image_id_1: int required
	**			image_id_2: int required
	** 		    antique_id: int required.
	**		  	lang - string optional
	** 		Response:
	** 		    success:  true or false
	** 		    message: String	
	** 		    uploaded_url: url string or empty string
	*/
	private function image_swap(){
		
		global $lang;
		
		if ( isset($this->user) ){
			return $this->user->swapImages($_POST, $lang);	
		}
		return $this->error_response;		
	}
	
	
}



?>