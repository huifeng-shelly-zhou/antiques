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
		"edit" => "編輯古玩",
		"edit_item" => "編輯古玩",
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
		"name" => "分類",
		"label" => "分類",
		"menu_name" => "分類",
		"all_items" => "所有分類",
		"edit_item" => "編輯分類",
		"add_new_item" => "新分類",
		"search_items" => "搜索分類",
		"view_item" => "查看分類",
		);

	$args = array(
		"labels" => $labels,
		"hierarchical" => true,
		"label" => "分類",
		"show_ui" => true,
		"show_in_nav_menus" => false,
		"query_var" => true,
		"rewrite" => array( 'slug' => 'antique_cat', 'with_front' => true ),
		"show_admin_column" => true,		
	);
	register_taxonomy( "antique_cat", array( "antique" ), $args );
	
	
	$labels = array(
		"name" => "地區",
		"label" => "地區",
		"menu_name" => "地區",
		"all_items" => "所有地區",
		"edit_item" => "編輯地區",
		"add_new_item" => "新地區",
		"search_items" => "搜索地區",
		"view_item" => "查看地區",
		);

	$args = array(
		"labels" => $labels,
		"hierarchical" => true,
		"label" => "地區",
		"show_ui" => true,
		"show_in_nav_menus" => false,
		"query_var" => true,
		"rewrite" => array( 'slug' => 'location', 'with_front' => true ),
		"show_admin_column" => true,		
	);
	register_taxonomy( "location", array( "antique" ), $args );
	
	
	$labels = array(
		"name" => "推薦",
		"label" => "推薦",
		"menu_name" => "推薦",
		"all_items" => "所有推薦",
		"edit_item" => "編輯推薦",
		"add_new_item" => "新推薦",
		"search_items" => "搜索推薦",
		"view_item" => "查看推薦",
		);

	$args = array(
		"labels" => $labels,
		"hierarchical" => true,
		"label" => "推薦",
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
	add_meta_box('antiques_custom_fields_meta_box', '古玩圖片', 'display_antique_custom__meta_box', 'antique', 'normal', 'high');
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