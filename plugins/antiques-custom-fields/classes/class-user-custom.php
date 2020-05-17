<?php
error_reporting(E_ALL);


if (!class_exists("ANTIQUES_USER_CUSTOM")){
	

class ANTIQUES_USER_CUSTOM {
	
	
	public function __construct( $config = array() )
	{
		
		$this->load_dependencies();
		$this->init_hooks();
		
		
	} // __construct
	
	private function load_dependencies() {	
		
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/setting.php';		
	}
	
	private function init_hooks(){
		
		add_action( 'show_user_profile', array( &$this, 'user_custom_fields'), 9 );
		add_action( 'edit_user_profile', array( &$this, 'user_custom_fields'), 9 );
		
		add_filter( 'user_contactmethods', array( &$this, 'add_user_contactmethods'), 9 );
		
		add_action('admin_menu', array(&$this, 'remove_built_in_roles'));
		add_action('admin_init', array(&$this, 'add_antiques_roles'));
		
	} // init_hooks()
	
	public function user_custom_fields($user){
		
		$_antiques_certificates = get_user_meta($user->ID, '_antiques_certificates', true);
		
		
		if ( !empty($_antiques_certificates) ) {
		?>   
		<h2><?php __( 'VIP', 'antiques_cucm' ); ?></h2>
		<table class="form-table">
		 <tr>
			 <th><label for="antiques_certificates"><?php __( 'Certificates', 'antiques_cucm' ); ?></label></th>
			 <td>
				<?php echo $_antiques_certificates;?>
			 </td>
		 </tr>	 
		</table>		
		<?php
		}
	}
	
	public function add_user_contactmethods($user_contactmethods){
		
		global $extra_contact_fields;		
		
		// Display each fields
		foreach( $extra_contact_fields as $field ) {
			if ( !isset( $contactmethods[ $field[0] ] ) )
				$user_contactmethods[ $field[0] ] = $field[1];
		}	
		
		return $user_contactmethods;
	}
		
	public function remove_built_in_roles(){
		
		global $wp_roles;
 
		$roles_to_remove = array('subscriber', 'contributor', 'author', 'editor');
	 
		foreach ($roles_to_remove as $role) {
			if (isset($wp_roles->roles[$role])) {
				$wp_roles->remove_role($role);
			}
		}
		
	}
	
	public function add_antiques_roles(){		
		
		add_role(
			'antique_player',
			__( 'Antique Player', 'antiques_cucm' ),
			array(
				'read'        		=> true,
				'edit_posts'		=> true,
				'delete_posts' 		=> true,
				'publish_posts '	=> true
			)
		);
		
		add_role(
			'antique_vip',
			__( 'Antique VIP', 'antiques_cucm' ),
			array(
				'read'        		=> true,
				'edit_posts'		=> true,
				'delete_posts' 		=> true,
				'publish_posts '	=> true
			)
		);
	}

}


}
?>