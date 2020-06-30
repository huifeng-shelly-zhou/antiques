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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'classes/class-posts.php';	
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
		global $Feeds, $lang;

		$endpoints = array();
		$endpoints['filters_url'] = $this->endpoint_root . '/filters';
		$endpoints['post_list_url'] = $this->endpoint_root . '/post/list';		
		
		
		$Feeds['endpoints'] = $endpoints;
		
		file_put_contents(plugin_dir_path( dirname( __FILE__ ) ) . 'antiques_feeds.json', json_encode($Feeds));
		
		return $Feeds;
	}


	/*
	** User Login 		
	**	 Url: /OX/api/user/login
	**	 Post data:
	**	   authorize  -  string required, base64 {email:password}
	**	   firebase_token - string optional
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
	** 		Response:
	** 		  success:  true or false
	** 		  message: String
	** 		  user: null or user object 
	** 
	*/
	private function user_profile(){
		
		if ( isset($this->user) ){
			return $this->user->profile($_POST);	
		}
		return $this->error_response;	
	}
	
	
	/*
	** User Profile Update (For user’s owned profile.)
	** 		Method: POST
	** 		Url: /OX/api/user/profile/update
	** 		Post data:
	** 		  token: string required (get it from user login response)
	** 		  email: string required ( If empty or not set, will return false. )
	** 		  display_name: string optional ( If empty or not set, will return false. )
	** 		  first_name: string optional ( If empty or not set, will set value to empty string. )
	** 		  last_name: string optional ( If empty or not set, will set value to empty string. )
	** 		  description: string optional ( If empty or not set, will set value to empty string. )
	** 		  new_password: string optional base64 encode (If empty or not set, will be ignored. Password must be minimum 8 characters.)
	** 		Response:
	** 		  success:  true or false
	** 		  message: String
	** 		  user: null or user object 
	** 
	*/
	private function user_profile_update(){
		
		if ( isset($this->user) ){
			return $this->user->profile_update($_POST);	
		}
		return $this->error_response;		
	}
	
	
	/*
	** User register 
	**		Method: POST
	**		Url: /OX/api/user/register
	**		Post data:	**		 
	**		  authorize -  string required (base64 encoded email:password)
	**		  first_name -  string optional
	**		  last_name - string optional
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
	** User Password Reset 
	**		Method: POST
	**		Url: /OX/api/user/password/reset
	**		Post data:	**		 
	**		  email -  string required base64 {email}
	**		Response:
	**		  success:  true or false
	**		  message: String
	**
	*/	
	private function user_password_reset(){
		
		if ( isset($this->user) ){
			return $this->user->password_reset($_POST);	
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
	**		Response:
	**		  success:  true or false
	**		  message: String
	**		  resend_link: String
	**		  user: null or object
	**
	*/	
	private function user_verify_email(){
		if ( isset($this->user) ){
			return $this->user->verify_email($_POST);	
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
	private function post_list_2(){		
		$result = array(
			'success'=> false,
			'message'=>'',
			'filters'=> array(),	
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