<?php

class AIT_CENTRA_USER
{
	public $id = '';
	public $user_name = '';
	public $first_name = '';
	public $last_name = '';
	public $display_name = '';
	public $user_email = '';
	public $user_url = '';
	public $user_registered = '';
	public $avatar = '';
	public $description = '';
	public $token = '';
	public $token_expiration ='';	
	public $roles = array();
	public $caps = array();
	
	public function __construct(){ 
    }
	
	public function setUser($user){
		
		if ( is_a($user, 'WP_User') ){
			
			$this->id = $user->ID;
			$this->user_name = $user->user_login;
			$this->first_name =$user->first_name;
			$this->last_name =$user->last_name;
			$this->display_name = $user->data->display_name;
			$this->user_email = $user->user_email;			
			$this->roles = $user->roles;			
			
			$this->avatar = get_user_meta($user->ID, 'author_profile_picture', true);
			if ( empty($this->avatar) ){
				$this->avatar = esc_url( get_avatar_url( $user->ID ) );
			}
			
			foreach ($user->get_role_caps() as $key=>$value){
				
				if ($value === true){
					$this->caps[] = $key;
				}				
			}
			
			
			//$this->metas = get_user_meta($user->ID); 
			
		}
	}

	public function genToken() {
		
		if ( empty($this->id) ){
			return '';
		}
		$myDate = new DateTime("now", new DateTimeZone("Asia/Hong_Kong"));
		$myDate->modify('+24 hour');
		$this->token_expiration = $myDate->format( DateTime::ATOM ); 
		
		$this->token = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,

			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
		
		$this->token = $this->token.'-'.$this->id;
		
		return $this->token;
	}//genToken
	
}


class AIT_CENTRA_CATEGORY
{
	public $id = 0;
	public $name = '';		
	public $parent = 0;
	public $sub_categories = array();
	public $posts = array();
	
	public function __construct($category){ 
	
		if ( is_a($category, 'WP_Term') ){
			
			$this->id = $category->term_id;
			$this->name = $category->name;				
			$this->parent =$category->parent;	
		}
    }
	
	public function set_posts($posts){
		
		if (is_array($posts)){
			
			foreach ($posts as $p){				
				$this->posts[] = new AIT_CENTRA_POST($p);
			}
		}		
	}	
}


class AIT_CENTRA_POST
{
	public $ID = 0;
	public $post_title = '';
	public $post_content = '';	
	public $post_status = '';
	public $post_date = '';
	public $post_date_gmt = '';
	public $post_modified = '';
	public $post_modified_gmt = '';
	public $summary = '';
	public $edit_last = null;
	public $edit_lock = '';
	public $edit_link = '';
	public $authorize_link = '';
	public $has_image = false;
	public $feature_image = false;
	public $has_youtube = false;
	public $has_mp4 = false;
	public $priority = '';
	public $categories = array();
	public $tags = array();
	public $seo_tags = array();
	public $social_media = array();
	public $fan_pages = array();
	public $highlighted = array();
	public $feature_wall_highlight = '';
	
	public function __construct($post, $name_only = true){ 
	
		if ( is_a($post, 'WP_Post') ){
			
			$this->ID = $post->ID;
			$this->post_title = $post->post_title;
			$this->post_status =$post->post_status;
			$this->post_date =$post->post_date;
			$this->post_date_gmt =$post->post_date_gmt;
			$this->post_modified =$post->post_modified;
			$this->post_modified_gmt =$post->post_modified_gmt;
			$this->priority = get_post_meta( $post->ID, '_bp_post_priority', true);
			$this->find_categories($name_only);
			$this->find_tags($name_only);
			$this->find_social_media();
			$this->find_facebook_fan_page();
			$this->find_edit_lock();
			$this->find_edit_last($post->post_author, $name_only);

			$this->feature_image = $this->get_feature_image($post->ID);

			if (function_exists('getYoutubeLink')) {
				$this->has_youtube = get_post_meta($post->ID, 'youtube_id', true);
				$YoutubeLink = getYoutubeLink($this->has_youtube);
				if (!empty($YoutubeLink)) {
					$this->has_youtube = $YoutubeLink;
				}
			}

			if (function_exists('getPostVideo')) {
				$videoArr = getPostVideo($post);
				if (isset($videoArr['link']) && !empty($videoArr['link'])) {
					$this->has_mp4 = str_replace('http://', 'https://', $videoArr['link']);
				}
			}
			
			// check post has image
			$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);			  
			if (count($matches[1]) > 0){
				$this->has_image = true;
			}
		}
	}

	private function get_feature_image($post_id) {
		if (function_exists('catch_that_image')) {
			return catch_that_image($post_id);
		}

		if (function_exists('getPostAttachments')) {
			$images = getPostAttachments($post_id);
			return empty($images) ? '' : $images[0]['url'];
		}

		return '';
	}
	
	private function find_edit_lock(){
		
		if ($this->ID > 0){
			$edit_lock = get_post_meta($this->ID, '_edit_lock', true);//(mixed) Will be an array if $single is false. Will be value of the meta field if $single is true.
			if ( !empty($edit_lock) ){
				list($num, $user_id) = explode(':', $edit_lock);
				if (isset($user_id)){
					$editor = get_user_by('id', $user_id);
					
					if ( is_a($editor, 'WP_User') ){
						$this->edit_lock = $editor->data->display_name;
					}
				}				
			}			
		}
		
	}

	private function find_edit_last($post_author, $name_only = true){
		
		if ($this->ID > 0){
			
			$last_id = get_post_meta( $this->ID, '_edit_last', true );
			if (empty($last_id)){
				$last_id = $post_author;
			}
			
			$last_user = get_userdata( $last_id );
			
			if (is_a($last_user, 'WP_User')){
				
				if ($name_only){
					$this->edit_last = $last_user->data->display_name;
				}
				else{
					$this->edit_last = array(
						'id'=>$last_id,
						'name'=>$last_user->data->display_name,
					);
				}
			}
		}
		
	}
	
	private function find_categories($name_only = true){
		if ($this->ID > 0){
			$categories = get_the_category($this->ID);
 
			if ( !empty($categories) ) {
				foreach( $categories as $cat ) {
					
					if ($name_only){
						$this->categories[] =  $cat->name;
					}
					else {
						$this->categories[] =  array(
								'id'=>$cat->term_id,
								'name'=>$cat->name
							);
					}					
				}
			}
		}
	}
	
	private function find_tags($name_only = true){
		if ($this->ID > 0){
			$post_tags = get_the_tags($this->ID);
 
			if ( $post_tags ) {
				foreach( $post_tags as $tag ) {
					
					if ($name_only){
						$this->tags[] = $tag->name;
					}
					else {
						$this->tags[] =  array(
								'id'=>$tag->term_id,
								'name'=>$tag->name
							);
					}					
				}
			}
		}
	}	
		
	private function find_social_media(){
		
		if ( $this->ID > 0 ){
			
			/*
			// facebook ia
			$arg=array(
				'post_type' 		=> 'fb_ia',
				'posts_per_page' 	=> -1,
				'post_parent'		=> $this->ID,
				'post_status'		=> 'publish',
				);
			$isCreated=get_posts( $arg );
			
			if (isset($isCreated[0]->ID)) {
				$fb_ia = new AIT_CENTRA_SOCIAL_MEDIA;
				$fb_ia->type = 'fb_ia';
				$fb_ia->link = '';
				$fb_ia->icon = site_url().'/wp-content/plugins/ait-facebook-ia/images/facebook_32x32.png';	
				$this->social_media[] = $fb_ia;
			} 
			*/
			
			// other social medias
			$twitter_id= get_post_meta($this->ID, '_twitter_id', true);
			$youtube_link= get_post_meta($this->ID, 'youtube_id', true);
			
			global $twitter_page_url;
			
			if ( isset ($twitter_page_url) && !empty($twitter_id)) {
				$twitter = new AIT_CENTRA_SOCIAL_MEDIA;
				$twitter->post_link_id = $twitter_id;
				$twitter->type = 'twitter';
				$twitter->icon = site_url().'/wp-content/plugins/ait-social-media-publish/images/twitter.png';
				$twitter->link = $twitter_page_url.'/status/'.$twitter_id;
				
				$twitter->post_message = get_post_meta($this->ID, '_twitter_message', true);
				$twitter->post_link_time = get_post_meta($this->ID, '_twitter_time', true);
				
				$this->social_media[] = $twitter;
								
			}
			if (!empty($youtube_link)) {
				$youtube = new AIT_CENTRA_SOCIAL_MEDIA;
				$youtube->type = 'youtube';
				$youtube->icon = site_url().'/wp-content/plugins/ait-social-media-publish/images/youtube.png';
				$youtube->link = $youtube_link;
				$this->social_media[] = $youtube;								
			}
		}
		
		
	}

	private function find_facebook_fan_page(){
		
		if ( $this->ID > 0 ){
			
			/* global $fb_page_ids;			
			if ( isset($fb_page_ids) && count($fb_page_ids)>0 ){
				foreach ($fb_page_ids as $fb_page_id){
					if ($tmp=get_post_meta($this->ID, '_fb_post_link_id_'.$fb_page_id, true) ) {
						$fb_page = new AIT_CENTRA_SOCIAL_MEDIA;
						$fb_page->id = $fb_page_id;
						$fb_page->type = 'fan_page';
						$fb_page->icon = site_url().'/wp-content/plugins/ait-social-media-publish/images/'.$fb_page_id.'.png';
						$fb_page->link = 'https://www.facebook.com/permalink.php?story_fbid='.str_replace($fb_page_id.'_','',$tmp).'&id='.$fb_page_id;
						
						$fb_page->post_link_id = get_post_meta($this->ID, '_fb_post_link_id_'.$fb_page_id, true);
						$fb_page->post_message = get_post_meta($this->ID, '_fb_post_message_'.$fb_page_id, true);
						$fb_page->post_link_time = get_post_meta($this->ID, '_fb_post_link_time_'.$fb_page_id, true);
					
						$this->fan_pages[] = $fb_page;						
					}
				}
			} */
			
			
			$metas = get_post_meta($this->ID);
			
			global $facebook_auth;
			if (isset($facebook_auth['post_pages']) && !empty($facebook_auth['post_pages']) ){			
				$page_ids=explode(',', $facebook_auth['post_pages']);	
				$page_ids[]='129651714333265';
			}	
			
		
			
			if ( is_array($metas) && isset($page_ids) ){
				
				foreach ($metas as $key=>$value) {
					
					if ( strpos($key, '_fb_post_link_id_') !== false ){		
						
						$tmp=explode('_',str_replace('_fb_post_link_id_', '', $key));
						$fb_page_id=$tmp[0];
						
						if (!in_array($fb_page_id, $page_ids)) continue;
						
						if (count($tmp)==1){
							if ($tmp=get_post_meta($this->ID, $key, true)){
								$fb_page = new AIT_CENTRA_SOCIAL_MEDIA;
								$fb_page->post_link_id = get_post_meta($this->ID, '_fb_post_link_id_'.$fb_page_id, true);
								$fb_page->post_message = get_post_meta($this->ID, '_fb_post_message_'.$fb_page_id, true);
								$fb_page->post_link_time = get_post_meta($this->ID, '_fb_post_link_time_'.$fb_page_id, true);						

								$fb_page->id = $fb_page_id;						
								$fb_page->type = 'fan_page';
								$fb_page->icon = site_url().'/wp-content/plugins/ait-social-media-publish/images/'.$fb_page_id.'.png';
								$fb_page->link = 'https://www.facebook.com/permalink.php?story_fbid='.str_replace($fb_page_id.'_','',$fb_page->post_link_id).'&id='.$fb_page_id;	


								$this->fan_pages[] = $fb_page;	
							}
							
						} else {					
							if ($tmp=get_post_meta($this->ID, $key, true)){
								if($fb_post_link=json_decode($tmp)){	
									$fb_page_id = $fb_post_link->page_id;
									$fb_page = new AIT_CENTRA_SOCIAL_MEDIA;
									$fb_page->post_link_id = $fb_post_link->fb_post_link_id;
									$fb_page->post_message = $fb_post_link->message;
									$fb_page->post_link_time = $fb_post_link->updated_time;
									$fb_page->id = $fb_page_id;						
									$fb_page->type = 'fan_page';
									$fb_page->icon = site_url().'/wp-content/plugins/ait-social-media-publish/images/'.$fb_page_id.'.png';
									$fb_page->link = 'https://www.facebook.com/permalink.php?story_fbid='.str_replace($fb_page_id.'_','',$fb_page->post_link_id).'&id='.$fb_page_id;	

									$this->fan_pages[] = $fb_page;											
								}
							} 
						}										
					}
				}
			}
		}		
	}	

	private function get_taxonomy_term_term_order( $object_id, $tt_id ) {
		global $wpdb;
		$result=$wpdb->get_results( $wpdb->prepare(
				"
				SELECT term_order
				FROM $wpdb->term_relationships			
				WHERE object_id = %d
				AND term_taxonomy_id = %d
				",
				$object_id,
				$tt_id
		) );
		
		if (count($result)>0){
			return (int)$result[0]->term_order;
		} else {
			return -1;
		}
	}

	public function set_edit_link($token){
		if ($this->ID > 0){
		
			$this->authorize_link = plugins_url().'/ait-centralized/includes/authorize.php';
			$this->edit_link = site_url().'/wp-admin/post.php?post='.$this->ID.'&action=edit';			
		}		
	}

	public function find_more_meta()
	{
		if ($this->ID > 0) {
			$this->summary = get_post_meta($this->ID, 'summary', true);
			$this->feature_wall_highlight = get_post_meta($this->ID, 'feature_wall_highlight', true);
		}
	}
	
	public function find_more_options() {
		if ($this->ID > 0) {
			$this->facebook_ia = (object) array(
				'auto_publish' => get_post_meta($this->ID, '_auto_social_share', true),
				'fb_ia_id' => '',
				'fb_ia_pageid' => '',
				'fb_import_status_id' => '',
				'fb_ia_wp_id' => '',
				'fb_ia_page_name' => '',
				'fb_ia_page_icon' => '',
			);
			
			
			if (empty($this->facebook_ia->auto_publish) || $this->facebook_ia->auto_publish !== 'pending_social_share') {
				$arg = array(
					'post_type' 		=> 'fb_ia',
					'fields'			=> 'ids',
					'posts_per_page' 	=> -1,
					'post_parent'		=> $this->ID,
					'post_status'		=> 'publish',
				);
				$facebook_ia_postids = get_posts($arg);

				if (is_array($facebook_ia_postids) && !empty($facebook_ia_postids)) {
					$this->facebook_ia->fb_ia_id = get_post_meta($facebook_ia_postids[0], '_fb_ia_id', true);
					$this->facebook_ia->fb_ia_pageid = get_post_meta($facebook_ia_postids[0], '_fb_ia_pageid', true);
					$this->facebook_ia->fb_import_status_id = get_post_meta($facebook_ia_postids[0], '_fb_import_status_id', true);
					$this->facebook_ia->fb_ia_wp_id = $facebook_ia_postids[0];

					if (!empty($this->facebook_ia->fb_ia_pageid)) {
						$this->facebook_ia->fb_ia_page_name = get_option('fb_page_' . $this->facebook_ia->fb_ia_pageid . '_pagename', '');
						$this->facebook_ia->fb_ia_page_icon = site_url() . '/wp-content/plugins/ait-social-media-publish/images/' . $this->facebook_ia->fb_ia_pageid . '.png';
					}
				}
			}

			$this->facebook_post = (object) array(
				'auto_publish' => get_post_meta($this->ID, '_auto_facebook_post', true),
			);

			$shortern_url = get_post_meta($this->ID, 'google_shortern_url', true);
			if (empty($shortern_url) && function_exists('generate_google_short_link')) {
				$shortern_url = generate_google_short_link($this->ID);
			}
			$this->google_shortern_url = $shortern_url;
			
			
			//for article sync from SingTao Hongkong STHeadline
			$source_link=get_post_meta($this->ID, '_ait_rss_sync_source_link', true);
			if (!empty($source_link)){				
				$this->sync_source=(object)array(
					'source_link'=>$source_link,
					'update_found_on'=>get_post_meta($this->ID, '_ait_rss_sync_source_last_update', true),
				);
			}
				
		}
	}

	public function find_seo_tags($name_only = true){
		
		if ($this->ID > 0 && taxonomy_exists( 'seo_tag' ) ){
			
			$seo_tags = wp_get_post_terms( $this->ID, 'seo_tag');//(array|WP_Error) Array of WP_Term objects on success or empty array if no terms were found. WP_Error object if $taxonomy doesn't exist.
			if ( !is_wp_error($seo_tags) ){
				foreach ($seo_tags as $seo){
					if ( is_a($seo, 'WP_Term') ){
						
						if ($name_only){
							$this->seo_tags[] = $seo->name;
						}
						else {
							$this->seo_tags[] =  array(
								'id' => $seo->term_id,
								'name' => $seo->name,								
							);
						}						
					}
				}
			}			
		}
	}
	
	public function find_highlighted($options, $name_only = true){
		
		if ($this->ID > 0 && is_array($options) && taxonomy_exists( 'highlight_option' ) ){
			
			$highlightedSlugs=wp_get_post_terms( $this->ID, 'highlight_option', array("fields" => "slugs"));
			
			$highlighted = array();
			foreach($options as $option){
				if ( in_array($option->slug, $highlightedSlugs) ){
					
					$term_order= $this->get_taxonomy_term_term_order( $this->ID, $option->term_taxonomy_id );
					
					if ($name_only){
						$highlighted[] = $option->name;						
					}
					else{
						$highlighted[] = array(
							'slug'=>$option->slug,
							'name'=>$option->name,
							'term_order'=>$term_order
						);
					}
				}
			}
			$this->highlighted = $highlighted;
		}
	}

}


class AIT_CENTRA_SOCIAL_MEDIA
{	
	public $id = '0';
	public $name = '';
	public $type = '';
	public $icon = '';
	public $link = '';
	
	public function __construct(){ 
    }
}
