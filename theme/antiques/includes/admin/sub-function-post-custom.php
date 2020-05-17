<?php

add_action( 'init', 'register_custom_antiques' );
function register_custom_antiques() {

	$labels = array(
		"name" => "古玩",
		"singular_name" => "antique",
		"menu_name" => "古玩",
		"all_items" => "全部古玩",
		"add_new" => "新增古玩",
		"add_new_item" => "新增古玩",
		"edit" => "编辑古玩",
		"edit_item" => "编辑古玩",
		"new_item" => "新增古玩",
		);

	$args = array(
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"show_ui" => true,
		"has_archive" => false,
		"show_in_menu" => true,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"rewrite" => array( "slug" => "%post_id%/antique", "with_front" => true ),
		"query_var" => true,
		"menu_position" => 5,		
		"supports" => array( "title", "editor", "custom-fields", "revisions", "thumbnail", "author", "post-formats" ),		
	);
	register_post_type( "antique", $args );
	
	
	$labels = array(
		"name" => "推荐",
		"label" => "推荐",
		"menu_name" => "推荐",
		"all_items" => "所有推荐",
		"edit_item" => "编辑推荐",
		"add_new_item" => "新推荐",
		"search_items" => "搜索推荐",
		"view_item" => "查看推荐",
		);

	$args = array(
		"labels" => $labels,
		"hierarchical" => true,
		"label" => "推荐",
		"show_ui" => true,
		"show_in_nav_menus" => false,
		"query_var" => true,
		"rewrite" => array( 'slug' => 'recommend', 'with_front' => true ),
		"show_admin_column" => true,		
	);
	register_taxonomy( "recommend", array( "antique" ), $args );
}
// End of register custom taxonomy


add_filter('post_type_link', 'custom_post_type_link', 1, 3);
function custom_post_type_link($post_link, $post = 0, $leavename = false) {
	
    if( $post->post_type == 'antique' ){
		
        return str_replace('%post_id%', $post->ID, $post_link);
    } else {
        return $post_link;
    }
}



add_action( 'admin_init', 'add_custom_antiques_meta_boxes' );
function add_custom_antiques_meta_boxes(){
		
	// add meta box to antique post type page
	add_meta_box('antiques_custom_fields_meta_box', '古玩图片', 'display_antique_custom__meta_box', 'antique', 'normal', 'high');
}

function display_antique_custom__meta_box(){
	
	$antique = get_post();	
	$gallery_images = get_antique_gallery_images($antique->ID);		
	
	echo '<div class="acf-fields">';		
		
		echo '<div class="acf-field">';
			
			foreach($gallery_images as $img_url){
				
				echo '<div class="image-wrap" style="max-width: 150px">';
				echo '<img   src="'.$img_url.'" alt="" width="150">';
				echo '</div>';				
			}			
			
		echo '</div>';
	
	echo '</div>';
}

?>