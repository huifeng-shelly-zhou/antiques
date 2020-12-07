<?php
/**
 * Block user login when is not in allowed roles.
 * 
*/
add_action( 'admin_init', 'restrict_admin', 1 );
function restrict_admin(){
	//if not administrator, kill WordPress execution and provide a message
	$user = wp_get_current_user();
	$allowed_roles = array( 'administrator' );
	if ( ! array_intersect( $allowed_roles, $user->roles ) ) {
	   wp_die( __('You are not allowed to access this part of the site.') );
	}
}

// add custom user role
function antique_new_roles() {  

	add_role(
		'vip_player',
		'VIP Player',
		array(
			'read'        		=> true,
			'edit_posts'		=> true,
			'delete_posts' 		=> true,
			'publish_posts '	=> true
		)
	);
	
	add_role(
		'player',
		'Player',
		array(
			'read'        		=> true,
			'edit_posts'		=> true,
			'delete_posts' 		=> true,
			'publish_posts '	=> true
		)
	);    
 
}
add_action('admin_init', 'antique_new_roles');
// end add custom user role	



function new_modify_user_table( $column ) {
    $column['approved'] = 'Approved';
    return $column;
}
add_filter( 'manage_users_columns', 'new_modify_user_table' );


function new_modify_user_table_row( $val, $column_name, $user_id ) {
    switch ($column_name) {
        case 'approved' :
			$approved = get_user_meta($user_id, 'user-approved', true);
            return ( $approved === '1')? 'True':'False';
        default:
    }
    return $val;
}
add_filter( 'manage_users_custom_column', 'new_modify_user_table_row', 10, 3 );


add_filter('get_avatar_data', 'antProfilePicture', 12, 2);
function antProfilePicture($args, $id_or_email){
	
	if ( is_numeric( $id_or_email ) ) {
		$avatar = get_user_meta($id_or_email, 'author_profile_picture', true);
		if (!empty($avatar) && strlen($avatar) > 4 && substr($avatar, 0, 4) == 'http'){
			$args['url'] = $avatar;
		}
	}
	return $args;
}


add_filter('user_row_actions',  'deals_custom_user_row_actions', 16, 2);
function deals_custom_user_row_actions($actions, $user_object){
	
	if( $user_object instanceof WP_User ){
		$user_object->first_name = $user_object->data->display_name;
	}
	
	return $actions;
}

//===============================================================================================================
/**
 * Add/Remove user privilege.
 *
 * This function is hooked into the actions/filters below:
 *		'pre_get_posts',
 *		'views_edit-[posttype]',
 *		'get_terms_args'.
*/


//add_action('pre_get_posts','users_own_attachments');
//add_action('pre_get_posts','users_own_posts');

function users_own_attachments($wp_query_obj) {
    global $current_user, $pagenow;
    if( !is_a( $current_user, 'WP_User') )
        return;

    if( !in_array( $pagenow, array('upload.php', 'admin-ajax.php') ) )
        return;

    if( !current_user_can('delete_pages') )
        $wp_query_obj->set('author', $current_user->ID );

    return;
}

function users_own_posts($wp_query_obj) {	
	//Note that current_user_can('edit_others_posts') check for
    //capability_type like posts, custom capabilities may be defined for custom posts
    if( is_admin() && ! current_user_can('edit_others_posts') && $wp_query_obj->is_main_query() ) {

        //hide all posts that current user can not edit
        global $user_ID;
        $wp_query_obj->set('author', $user_ID );

        //For standard posts
        add_filter('views_edit-post', 'views_filter_for_own_posts' );

        //For gallery post type
        add_filter('views_edit-gallery', 'views_filter_for_own_posts' );

        //You can add more similar filters for more post types with no extra changes
    }

 	global $pagenow;
    //retriction apply to edit.php page, and users other then admin
	if( 'edit.php' != $pagenow || !$wp_query_obj->is_admin )
        return $wp_query_obj;	
	
}

function views_filter_for_own_posts( $views ) {
//Rewrites the query to only display the can  edit posts from the edit page
    $post_type = get_query_var('post_type');
    $author = get_current_user_id();
    unset($views['mine']);
    $new_views = array(
            'all'       => __('All'),
            'publish'   => __('Published'),
            'private'   => __('Private'),
            'pending'   => __('Pending Review'),
            'future'    => __('Scheduled'),
            'draft'     => __('Draft'),
            'trash'     => __('Trash')
            );

    foreach( $new_views as $view => $name ) {
        $query = array(
            'author'      => $author,
            'post_type'   => $post_type
        );

        if($view == 'all') {
            $query['all_posts'] = 1;
            $class = ( get_query_var('all_posts') == 1 || get_query_var('post_status') == '' ) ? ' class="current"' : '';
            $url_query_var = 'all_posts=1';
        } else {
            $query['post_status'] = $view;
            $class = ( get_query_var('post_status') == $view ) ? ' class="current"' : '';
            $url_query_var = 'post_status='.$view;
        }

        $result = new WP_Query($query);

        if($result->found_posts > 0) {
            $views[$view] = sprintf(
                '<a href="%s"'. $class .'>'.__($name).' <span class="count">(%d)</span></a>',
                admin_url('edit.php?'.$url_query_var.'&post_type='.$post_type),
                $result->found_posts
            );
        } else {
            unset($views[$view]);
        }
    }
    return $views;
}




/*
* Adds filters for category restriction
* Usage:
*		1, modify $retriction_roles to assign retricted category or custom taxonomy
*/

// Instantiate new class
//$restrict_categories_load = new RestrictCategories();

// Restrict Categories class
class RestrictCategories{
	
	private $cat_list = NULL; 
	private $retriction_roles = array(
										array(
											'roles'=>array('author','contributor','subscriber'), //user role array
											'user_logins'=>array(), //login username array
											'retrictions' => array (
													array(
														'taxonomy' => 'featured',
														'slug'    => 'featured_homepage',
													),
													array(
														'taxonomy' => 'featured',
														'slug'    => 'featured_categorypage',
													),
												),
										),
									);
	
	public function __construct(){						
	
		if ( empty($this->retriction_roles) ) return;	

		// Make sure we are in the admin before proceeding.
		if ( is_admin() ) {
			$post_type = ( isset( $_GET['post_type'] ) ) ? $_GET['post_type'] : false;
			if ( $post_type == false || $post_type == 'post' )
				add_action( 'admin_init', array( &$this, 'posts' ) );
		}

		// Make sure XML-RPC requests are filtered to match settings
		if ( defined ( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
			add_action( 'xmlrpc_call', array( &$this, 'posts' ) );
	}
	
	
	public function get_restrict_cats($user_cap, $user_login){	
		if ( empty($this->retriction_roles) || !(count($this->retriction_roles) >0) ) return;			
		if ( empty($user_cap) || $user_cap=='' ) return;		
		if ( empty($user_login) || $user_login=='' ) return;	
		
		$currentuser_cat_list = NULL;
		foreach ($this->retriction_roles as $retriction_cat_list) {
			$user_cap_retrict=false;
			foreach ($user_cap as $cap) {
				if (in_array($cap, $retriction_cat_list['roles'])) $user_cap_retrict=true;
			}
			
			if ($user_cap_retrict || in_array($user_login, $retriction_cat_list['user_logins'])):
				foreach ($retriction_cat_list['retrictions'] as $retriction ) {
					$term = get_term_by( 'slug', $retriction['slug'], $retriction['taxonomy'] );
					if ($term) $currentuser_cat_list .= $term->term_id . ',';
				}
			endif;
		}
		return $currentuser_cat_list;
	}
	
	public function posts() {
		global $wp_query, $current_user;

		// Get the current user in the admin
		$user = new WP_User( $current_user->ID );

		// Get the user role and user_login name
		$user_cap = $user->roles;
		$user_login = '';
		if ( function_exists( 'get_users' ) ) $user_login = $user->user_login;

		// Get selected retriction categories for current user
		$this->cat_list=$this->get_restrict_cats( $user_cap, $user_login );

		$this->cat_filters( $this->cat_list );
	}
	
	public function cat_filters( $categories ){
		// Clean up the category list
		$this->cat_list = rtrim( $categories, ',' );

		// If there are no categories, don't do anything
		if ( empty( $this->cat_list ) ) return;

		global $pagenow;
		// Allowed pages for term exclusions
		$pages = array( 'edit.php', 'post-new.php', 'post.php' );

		// Make sure to exclude terms from $pages array as well as the Category screen
		if ( in_array( $pagenow, $pages ) || ( $pagenow == 'edit-tags.php' && $_GET['taxonomy'] == 'category' ) || ( defined ( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) )
			add_filter( 'list_terms_exclusions', array( &$this, 'exclusions' ) );
	}
	
	public function exclusions(){
		$excluded = " AND ( t.term_id NOT IN ( $this->cat_list )  )";
		return $excluded;
	}

}//class RestrictCategories


?>