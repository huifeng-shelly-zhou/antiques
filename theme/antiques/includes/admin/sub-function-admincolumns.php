<?php
/**
 * Add/Remove admin columns in post list.
 *
 * This function is hooked into the actions below:
 *		'manage_[posttype]_posts_columns',
 *		'manage_[posttype]_posts_custom_column',
 *		'manage_edit-post_sortable_columns',
 *		'admin_init',
 *		'display_post_states',
 *		'request'.
 */
 
add_action( 'manage_post_posts_custom_column', 'bs_post_ait_sync_table_content', 10, 2 );
add_filter( 'manage_edit-post_sortable_columns', 'bs_post_ait_sync_table_sorting' );
add_filter( 'request', 'bs_ait_syn_source_key_column_orderby' );


add_action( 'admin_init' , 'ait_column_init' );


function bs_post_ait_sync_table_content( $column_name, $post_id ) {
    if ($column_name == 'ait_syn_source_key') {
		$syn_source = get_post_meta( $post_id, '_ait_syn_source_key', true );
		echo $syn_source;
    }
}


function bs_post_ait_sync_table_sorting( $columns ) {
  $columns['ait_syn_source_key'] = 'ait_syn_source_key';
  return $columns;
}


function bs_ait_syn_source_key_column_orderby( $vars ) {
    if ( isset( $vars['orderby'] ) && 'ait_syn_source_key' == $vars['orderby'] ) {
        $vars = array_merge( $vars, array(
            'meta_key' => '_ait_syn_source_key',
            'orderby' => 'meta_value'
        ) );
    }

    return $vars;
}


function unset_columns( $columns ) {
  unset($columns['comments']);
  return $columns;
}

function ait_column_init() {
  add_filter( 'manage_posts_columns' , 'unset_columns' );
}

/*
* Remove unused menu from admin screen
*/
add_action( 'admin_menu', 'remove_admin_metaboxes' );
add_action( 'admin_menu', 'remove_admin_menus' );

function remove_admin_metaboxes() {
	remove_meta_box( 'commentstatusdiv','post','normal' ); // Comments Status Metabox
	remove_meta_box( 'commentsdiv','post','normal' ); // Comments Metabox
	remove_meta_box( 'postcustom','post','normal' ); // Custom Fields Metabox
	remove_meta_box( 'postexcerpt','post','normal' ); // Excerpt Metabox
	remove_meta_box( 'trackbacksdiv','post','normal' ); // Trackback Metabox
	
}

function remove_admin_menus() {
    remove_menu_page( 'tools.php' );
    remove_menu_page( 'edit-comments.php' );
}



?>