<?php

/**
** size: medium, thumbnail, large, full
**/
function get_antique_thumbnail($antique_id, $size = 'thumbnail'){
	
	// First priority: headling image
	$headlinen_image = get_antique_headline_image($antique_id, $size);
	if( isset($headlinen_image['url']) && !empty($headlinen_image['url']) ){
		return $headlinen_image['url'];
	}
	
	
	// Second priority: first image in gallery
	$thumbnail = '';
	$gallery_images = get_antique_gallery_images($antique_id, $size);
	
	foreach($gallery_images as $attachment_id=>$url){
		$thumbnail = $url;
		break;
	}
	
	// Third priority: placeholder
	if (empty($thumbnail)){
		$thumbnail = ant_get_placeholder();
	}
	
	return $thumbnail;
}

/**
** size: medium, thumbnail, large, full
**/
function get_antique_gallery_images($antique_id, $size = 'thumbnail'){
	
	$gallery_images = array();	
	
	/* $args = array(
        'post_parent'    => $antique_id,
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
		'fields'		 => 'ids',
    );
	
	$gallery_image_ids = get_children($args); */
	
	$gallery_image_ids = get_post_meta($antique_id, '_antique_gallery_image_ids', true);
	
	if (is_array($gallery_image_ids)){
		
		foreach ($gallery_image_ids as $attachment_id){
			
			if ( is_numeric($attachment_id) ){
				$attachment = wp_get_attachment_image_src( $attachment_id, $size );
				
				if ( is_array($attachment) ){
					$gallery_images[$attachment_id] = $attachment[0];
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
	
	set_antique_gallery_images_ids($antique_id, $gallery_image_ids);
}


function remove_antique_gallery_images_id($antique_id, $attachment_id){
	
	$gallery_image_ids = get_post_meta($antique_id, '_antique_gallery_image_ids', true);
	
	if ( !is_array($gallery_image_ids) ){
		$gallery_image_ids = array();
	}
	
	if (isset($gallery_images[$attachment_id])){
		unset($gallery_images[$attachment_id]);
	}
	
	set_antique_gallery_images_ids($antique_id, $gallery_image_ids);
	
	return wp_delete_attachment($attachment_id, true);
}


function swap_antique_gallery_images($antique_id, $first_image_id, $second_image_id){
	
	if (!is_numeric($antique_id) || !is_numeric($first_image_id) || !is_numeric($first_image_id)){
		return false;
	}
	
	$pre_gallery_image_ids = get_post_meta($antique_id, '_antique_gallery_image_ids', true);
	if ( !is_array($pre_gallery_image_ids) ){
		return false;
	}
	
	$new_gallery_image_ids = array();
	
	foreach ($pre_gallery_image_ids as $attachment_id){
		
		if ($attachment_id == $first_image_id){
			$found_one = true;
			$new_gallery_image_ids[] = $second_image_id;
		}
		else if ($attachment_id == $second_image_id){
			$found_two = true;
			$new_gallery_image_ids[] = $first_image_id;
		}
		else{
			$new_gallery_image_ids[] = $attachment_id;
		}		
	}
	
	set_antique_gallery_images_ids($antique_id, $new_gallery_image_ids);
	
	if (isset($found_one) && $found_one && isset($found_two) && $found_two) {
		return true;
	}
	
	return false;	
	
}


function ant_get_placeholder(){
	return site_url().'/wp-content/themes/antiques/images/image_not_avaliable_400.jpg';
}


function set_antique_headline_image($antique_id, $attachment_id){
	
	if (!update_post_meta($antique_id, '_antique_headline_image_id', $attachment_id)){
		add_post_meta($antique_id, '_antique_headline_image_id', $attachment_id, true);
	}
}


/**
** size: medium, thumbnail, large, full
**/
function get_antique_headline_image($antique_id, $size = 'medium'){
	
	$headlinen_image = array('attachment_id'=>'', 'url'=> '');
	
	$attachment_id = get_post_meta($antique_id, '_antique_headline_image_id', true);
	
	if ( is_numeric($attachment_id) ){
		$attachment = wp_get_attachment_image_src( $attachment_id, $size );
		
		if ( is_array($attachment) ){
			
			$headlinen_image['attachment_id'] = $attachment_id;
			$headlinen_image['url'] = $attachment[0];			
		}				
	}
	
	return $headlinen_image;
}


function set_antique_promotion_image($antique_id, $attachment_id){
	
	if (!update_post_meta($antique_id, '_antique_promotion_image_id', $attachment_id)){
		add_post_meta($antique_id, '_antique_promotion_image_id', $attachment_id, true);
	}
}


/**
** size: medium, thumbnail, large, full
**/
function get_antique_promotion_image($antique_id, $size = 'medium'){
	
	$promotion_image = array('attachment_id'=>'', 'url'=> '');
	
	$attachment_id = get_post_meta($antique_id, '_antique_promotion_image_id', true);
	
	if ( is_numeric($attachment_id) ){
		$attachment = wp_get_attachment_image_src( $attachment_id, $size );
		
		if ( is_array($attachment) ){
			
			$promotion_image['attachment_id'] = $attachment_id;
			$promotion_image['url'] = $attachment[0];			
		}				
	}
	
	return $promotion_image;
}


function validate_password($password, $lang){
	
	if ( $password == null || empty($password) || strlen($password) < 8 ){
				
		if($lang == 'en'){
			return 'The password must be more than eight characters!';
		}
		else{
			return antLang('密碼需多於八個字符!', $lang);
		}
	}
	
	return true;
}


function antLang($content, $lang = 'hk'){
	
	if (strpos($lang, 'cn') !== false){
		
		global $anq_zh2Hans;
		if ($anq_zh2Hans){
			$content = strtr($content, $anq_zh2Hans);	
		}		
	}
	else if (strpos($lang, 'hk') !== false){
		
		global $anq_hans2cns;
		if ($anq_hans2cns){
			$content = strtr($content, $anq_hans2cns);	
		}
	}
	
	return $content;
}


function get_home_special_antiques(){
	// need two fixed 02 position posts
	$post_ids = array('headline'=>array(), 'promote'=>array());
	$home_options = Antique_Options::get_last_recommend_posts();	
	
	
	/*
	* find headline ids
	*/
	$headline_post_ids = get_posts(
		array(
			'posts_per_page'=> -1,
			'post_type' 	=> 'antique',
			'fields'		=> 'ids',
			'tax_query' 	=> array(
				array(
					'taxonomy' => 'recommend',
					'field' => 'slug',
					'terms' => 'headline',
				)
			)
		)
	);
	
	$headline_count = count($headline_post_ids);
	
	$index = 0;
		
	// find last post index in post ids
	if( $home_options->last_headline_id > 0 ){
		$last_index = array_search($home_options->last_headline_id, $headline_post_ids);//Returns the key for needle if it is found in the array, FALSE otherwise.
		
		if( is_numeric($last_index) ){
			$index = (int)$last_index + 1;			
			
			if( $index >= $headline_count ){
				// rotate post
				$index = 0;					
			}
		}		
	}
	
	if ($index > 0 && $index < $headline_count){
		$frist_part = array_slice($headline_post_ids, 0, $index);
		$second_part = array_slice($headline_post_ids, $index);
		$headline_post_ids = array_merge($second_part, $frist_part);
	}
	$post_ids['headline'] = $headline_post_ids;
	
	
	// update headline option
	if( $headline_count > 0 ){		
		$home_options->last_headline_id = $headline_post_ids[0];		
	}
	else{
		$home_options->last_headline_id = 0;
	}



	/*
	* find promote ids
	*/
	$promote_post_ids = get_posts(
		array(
			'posts_per_page'=> -1,
			'post_type' 	=> 'antique',
			'fields'		=> 'ids',
			'tax_query' 	=> array(
				array(
					'taxonomy' => 'recommend',
					'field' => 'slug',
					'terms' => 'promote',
				)
			)
		)
	);
	
	$promote_count = count($promote_post_ids);
	
	$index = 0;
		
	// find last post index in post ids
	if( $home_options->last_promote_id > 0 ){
		$last_index = array_search($home_options->last_promote_id, $promote_post_ids);//Returns the key for needle if it is found in the array, FALSE otherwise.
		
		if( is_numeric($last_index) ){
			$index = (int)$last_index + 1;			
			
			if( $index >= $promote_count ){
				// rotate post
				$index = 0;					
			}
		}		
	}
	
	if ($index > 0 && $index < $promote_count){
		$frist_part = array_slice($promote_post_ids, 0, $index);
		$second_part = array_slice($promote_post_ids, $index);
		$promote_post_ids = array_merge($second_part, $frist_part);
	}
	$post_ids['promote'] = $promote_post_ids;
	
	
	// update promote option
	if( $promote_count > 0 ){		
		$home_options->last_promote_id = $promote_post_ids[0];		
	}
	else{
		$home_options->last_promote_id = 0;
	}
	
	
	// save home options
	Antique_Options::set_last_recommend_posts($home_options);
	
	return $post_ids;
}

?>