<?php

function get_antique_gallery_images($antique_id, $size = 'thumbnail'){
	
	$gallery_images = array();	
	
	$gallery_image_ids = get_post_meta($antique_id, '_antique_gallery_image_ids', true);
	
	if (is_array($gallery_image_ids)){
		
		foreach ($gallery_image_ids as $attachment_id){
			
			if ( is_numeric($attachment_id) ){
				$attachment = wp_get_attachment_image_src( $attachment_id, $size );
				
				if ( is_array($attachment) ){
					$gallery_images[] = $attachment[0];
				}				
			}			
		}
	}
	
	return $gallery_images;	
}


function set_antique_gallery_images_ids($antique_id, $gallery_image_ids = array()){
	
	if (!update_post_meta($antique_id, '_antique_gallery_image_ids', $gallery_image_ids)){
		add_post_meta($antique_id, '_antique_gallery_image_ids', $gallery_image_ids, true);
	}
	
}


function add_antique_gallery_images_id($antique_id, $attachment_id){
	
	$gallery_image_ids = get_post_meta($antique_id, '_antique_gallery_image_ids', true);
	
	if ( !is_array($gallery_image_ids) ){
		$gallery_image_ids = array();
	}
	
	if (is_numeric($attachment_id)){
		$gallery_image_ids[] = $attachment_id;
	}
	
	set_antique_gallery_images_ids($gallery_image_ids);
}


function remove_antique_gallery_images_id($antique_id, $attachment_id){
	
	$gallery_image_ids = get_post_meta($antique_id, '_antique_gallery_image_ids', true);
	
	if ( !is_array($gallery_image_ids) ){
		$gallery_image_ids = array();
	}
	
	if (isset($gallery_images[$attachment_id])){
		unset($gallery_images[$attachment_id]);
	}
	
	set_antique_gallery_images_ids($gallery_image_ids);
}


function validate_password($pw){
	
	if ($pw == null || empty($wp) || strlen($pw) < 8){
		return '密码需多于八个字符!';
	}
	
	return true;
}
?>