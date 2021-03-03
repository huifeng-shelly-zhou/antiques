<?php

class ANTIQUE_LIST_ITEM{
	
	public $id = '';
	public $title = '';
	public $status = '';
	public $thumbnail = '';
	public $headline_image = array('attachment_id'=>'', 'url'=> '');
	public $promotion_image = array('attachment_id'=>'', 'url'=> '');
	public $style = '';
	public $owner = '';
	public $price = '';
	public $endpoint = '';
	public $rank = 0.0;
	public $location_names = '';
	public $category_names = '';
	
	public function __construct(){ 
    }
	
	public function populateItem($post, $lang = 'hk', $endpoint_root=''){
		
		if (is_a($post, 'WP_Post')){
			
			$this->id = $post->ID;
			$this->status = $post->post_status;
			$this->endpoint = $endpoint_root.'/post/get';
			$this->price = get_post_meta($post->ID, 'price', true);
			$this->price = antLang($this->price, $lang);
			$this->thumbnail = get_antique_thumbnail($post->ID, 'medium');
			$this->headline_image = get_antique_headline_image($post->ID);
			$this->promotion_image = get_antique_promotion_image($post->ID);

			// post title
			if ($lang == 'en'){
				$this->title = get_post_meta($post->ID, 'name_en', true);
			}
			else{
				$this->title = antLang($post->post_title, $lang);
			}
			
			// post author
			if(isset($post->post_author)){
				$author = get_user_by( 'id', $post->post_author);
				
				if (is_a($author, 'WP_User')){
					$this->owner = antLang($author->display_name, $lang);
				}
			}
			
			// style
			//$recommends = wp_get_post_terms($post->ID, 'recommend', array( 'fields' => 'names' ));
			//if (is_array($recommends) && count($recommends) > 0){
			//	$this->style = $recommends[0];				
			//}
			
			// location
			if ($lang == 'en'){
				$locations = wp_get_post_terms($post->ID, 'location', array( 'fields' => 'slugs' ));
				if (is_array($locations) && count($locations) > 0){
					$this->location_names = implode(',',$locations);
					$this->location_names = ucwords(str_replace('-', ' ', $this->location_names));
				}
			}
			else{
				$locations = wp_get_post_terms($post->ID, 'location', array( 'fields' => 'names' ));
				if (is_array($locations) && count($locations) > 0){
					$this->location_names = implode(',',$locations);
					$this->location_names = antLang($this->location_names, $lang);
				}
			}
			
			
			// categoreis
			if ($lang == 'en'){
				$categoreis = wp_get_post_terms($post->ID, 'antique_cat', array( 'fields' => 'slugs' ));
				if (is_array($categoreis) && count($categoreis) > 0){
					$this->category_names = implode(',',$categoreis);
					$this->category_names = ucwords(str_replace('-', ' ', $this->category_names));
				}
			}
			else{
				$categoreis = wp_get_post_terms($post->ID, 'antique_cat', array( 'fields' => 'names' ));
				if (is_array($categoreis) && count($categoreis) > 0){
					$this->category_names = implode(',',$categoreis);
					$this->category_names = antLang($this->category_names, $lang);
				}
			}			
		}
	}	
}

class ANTIQUE_SINGLE{
	
	public $id = '';
	public $title = '';
	public $status = '';
	public $thumbnail = '';		
	public $owner = '';
	public $owner_id = '';
	public $owner_avatar = '';
	public $owner_antique_count = '';
	public $price = '';
	public $publish_url = '';
	public $description = '';
	public $rank = 0.0;
	public $location_names = '';
	public $category_names = '';
	public $images = array();
	public $images_small = array();
	public $images_large = array();	
	
	public function __construct($antique_id, $lang = 'hk'){
		
		$post = get_post($antique_id);
		
		if (is_a($post, 'WP_Post')){
			
			$this->id = $post->ID;			
			$this->status = $post->post_status;
			$this->price = get_post_meta($post->ID, 'price', true);
			$this->price = antLang($this->price, $lang);
			$this->thumbnail = get_antique_thumbnail($post->ID, 'medium');	
			$this->publish_url = get_post_permalink($post->ID).'?lang='.$lang;

			// post title and description
			if ($lang == 'en'){
				$this->title = get_post_meta($post->ID, 'name_en', true);
				$this->description = get_post_meta($post->ID, 'description_en', true);
			}
			else{
				$this->title = antLang($post->post_title, $lang);
				$this->description = ($post->post_content == '[New Content]')? '':$post->post_content;
			}
			
			
			// post author
			if(isset($post->post_author)){
				$author = get_user_by( 'id', $post->post_author);
				
				if (is_a($author, 'WP_User')){
					$this->owner_id = $post->post_author;
					$this->owner = antLang($author->display_name, $lang);
					$this->owner_avatar = esc_url( get_user_meta($post->post_author, 'author_profile_picture', true) );
					$this->owner_antique_count = count_user_posts($post->post_author, 'antique', true);
				}
			}			
			
			// location
			if ($lang == 'en'){
				$locations = wp_get_post_terms($post->ID, 'location', array( 'fields' => 'slugs' ));
				if (is_array($locations) && count($locations) > 0){
					$this->location_names = implode(',',$locations);
					$this->location_names = ucwords(str_replace('-', ' ', $this->location_names));
				}
			}
			else{
				$locations = wp_get_post_terms($post->ID, 'location', array( 'fields' => 'names' ));
				if (is_array($locations) && count($locations) > 0){
					$this->location_names = implode(',',$locations);
					$this->location_names = antLang($this->location_names, $lang);
				}
			}			
			
			// categoreis
			if ($lang == 'en'){
				$categoreis = wp_get_post_terms($post->ID, 'antique_cat', array( 'fields' => 'slugs' ));
				if (is_array($categoreis) && count($categoreis) > 0){
					$this->category_names = implode(',',$categoreis);
					$this->category_names = ucwords(str_replace('-', ' ', $this->category_names));
				}
			}
			else{
				$categoreis = wp_get_post_terms($post->ID, 'antique_cat', array( 'fields' => 'names' ));
				if (is_array($categoreis) && count($categoreis) > 0){
					$this->category_names = implode(',',$categoreis);
					$this->category_names = antLang($this->category_names, $lang);
				}
			}
			
			
			// images			
			$gallery_images = get_antique_gallery_images($post->ID, 'medium');
			if (count($gallery_images) > 0){
				$this->images = $gallery_images;
			}			
			
			
			// images small			
			$gallery_images_small = get_antique_gallery_images($post->ID, 'thumbnail');
			if (count($gallery_images_small) > 0){
				$this->images_small = $gallery_images_small;
			}
			
			// images large			
			$gallery_images_large = get_antique_gallery_images($post->ID, 'large');
			if (count($gallery_images_large) > 0){
				$this->images_large = $gallery_images_large;
			}			
		}
	}

	public function setSimpleProperties($antique_id, $lang = 'hk'){
		$post = get_post($antique_id);
		
		if (is_a($post, 'WP_Post')){
			$this->id = $post->ID;
			$this->thumbnail = get_antique_thumbnail($post->ID, 'medium');
			
			// post title and description
			if ($lang == 'en'){
				$this->title = get_post_meta($post->ID, 'name_en', true);				
			}
			else{
				$this->title = antLang($post->post_title, $lang);				
			}
			
			// post author
			if(isset($post->post_author)){
				$author = get_user_by( 'id', $post->post_author);
				
				if (is_a($author, 'WP_User')){
					$this->owner_id = $post->post_author;
					$this->owner = antLang($author->display_name, $lang);										
				}
			}	
		}
	}
}

class USUR_ANTIQUE_LIST_ITEM{
	
	public $id = '';
	public $title = '';
	public $status = '';
	public $thumbnail = '';
	public $price = '';
	public $location = '';
	
	public function __construct($post_id, $lang_version){
		
		$post = get_post($post_id);
		
		if (is_a($post, 'WP_Post')){
			$this->id = $post->ID;
			$this->status = $post->post_status;
			$this->title = antLang($post->post_title, $lang_version);
			$this->price = get_post_meta($post->ID, 'price', true);
			$this->price = antLang($this->price, $lang_version);
			$this->thumbnail = get_antique_thumbnail($post->ID, 'medium');
				
			if ($lang_version == 'en'){
				$this->title =  get_post_meta($post->ID, 'name_en', true);
			}
			
			// location
			if ($lang == 'en'){
				$locations = wp_get_post_terms($post->ID, 'location', array( 'fields' => 'slugs' ));
				if (is_array($locations) && count($locations) > 0){
					$this->location = implode(',',$locations);
					$this->location = ucwords(str_replace('-', ' ', $this->location));
				}
			}
			else{
				$locations = wp_get_post_terms($post->ID, 'location', array( 'fields' => 'names' ));
				if (is_array($locations) && count($locations) > 0){
					
					$this->location = implode(',',$locations);	
					$this->location = antLang($this->location, $lang_version);
				}
			}
			
		}		
	}
}

class USER_ANTIQUE_SINGLE{
	
	public $id = '';
	public $status = '';
	public $thumbnail = '';
	public $title_zh = '';
	public $title_en = '';
	public $description_zh = '';
	public $description_en = '';
	public $headline_image = array('attachment_id'=>'', 'url'=> '');
	public $promotion_image = array('attachment_id'=>'', 'url'=> '');
	public $images = array();	
	public $price = '';
	public $style = '';
	public $locations = array();
	public $categories = array();
	public $lang_versions = array();	
	
	public function __construct($post_id){
		
		$post = get_post($post_id);
		
		if (is_a($post, 'WP_Post')){
			
			$this->id = $post->ID;
			$this->status = $post->post_status;
			$this->title_zh = ($post->post_title == 'New Antique')? '':$post->post_title;
			$this->description_zh = ($post->post_content == '[New Content]')? '':$post->post_content;
			$this->thumbnail = get_antique_thumbnail($post->ID, 'medium');	
			
			// images
			$gallery_images = get_antique_gallery_images($post->ID, 'medium');
			if (count($gallery_images) > 0){
				$this->images = $gallery_images;
			}
			
			// style
			$recommends = wp_get_post_terms($post->ID, 'recommend', array( 'fields' => 'names' ));
			if (is_array($recommends) && count($recommends) > 0){
				$this->style = $recommends[0];				
			}
			
			$this->headline_image = get_antique_headline_image($post->ID);
			$this->promotion_image = get_antique_promotion_image($post->ID);
		
			// location
			$locations = wp_get_post_terms($post->ID, 'location', array( 'fields' => 'ids' ));
			if (is_array($locations) && count($locations) > 0){
				$this->locations = $locations;				
			}
			
			// categoreis
			$categoreis = wp_get_post_terms($post->ID, 'antique_cat', array( 'fields' => 'ids' ));
			if (is_array($categoreis) && count($categoreis) > 0){
				$this->categories = $categoreis;				
			}
			
			$post_meta = get_post_meta($post->ID);
			if (is_array($post_meta)){
				
				foreach($post_meta as $key=>$value){
					
					if ($key == 'price'){
						$this->price = $value[0];
					}
					
					if ($key == 'name_en'){
						$this->title_en = $value[0];
					}
					
					if ($key == 'description_en'){
						$this->description_en = $value[0];
					}
					
					if ($key == '_lang_version_zh' && $value[0] == '1'){
						$this->lang_versions[] = 'zh';
					}
					
					if ($key == '_lang_version_en' && $value[0] == '1'){
						$this->lang_versions[] = 'en';
					}
				}				
			}
		}
	}	
}


class ANTIQUES
{	
	public function __construct(){ 
    }
	
	private function getTermInfo($term, $lang = 'hk', $name='', $taxonomy = ''){		
		
		if (is_a($term, 'WP_Term')){
			$info = array(
					'id'		=> $term->term_id,
					'name'		=> antLang($term->name, $lang),	
					'taxonomy'	=> $term->taxonomy,					
					'icon'		=> '',
				);
			
			if($lang == 'en'){
				$info['name'] = ucwords(str_replace('-', ' ', $term->slug));
			}
		}
		else{
			$info = array(
					'id'		=> 0,
					'name'		=> antLang($name, $lang),
					'taxonomy'	=> $taxonomy,
					'icon'		=> '',
				);
		}
		
		return $info;
	}
	
	private function getTermsMax3Levels($taxonomy, $lang, $hide_empty = false){
		
		$terms = array();
		
		$parents = get_terms($taxonomy, array(
			'orderby'    => 'count',
			'hide_empty'=>$hide_empty,	
			'parent'=>0,
		));
		
		if ( !empty($parents) && !is_wp_error( $parents ) ){
			
			foreach($parents as $parent){
				
				$formatted_parent = $this->getTermInfo($parent,$lang);
				
				
				// look for children
				$children = get_terms($taxonomy, array(
					'orderby'    => 'count',
					'hide_empty'=>$hide_empty,	
					'parent'=>$parent->term_id,
				));
				if ( !empty($children) && !is_wp_error( $children ) ){
					
					$formatted_parent['children'] = array();
					
					foreach($children as $child){
						
						$formatted_child = $this->getTermInfo($child,$lang);
						
						// look for grandchildren
						$grandchildren = get_terms($taxonomy, array(
							'orderby'    => 'count',
							'hide_empty'=>$hide_empty,	
							'parent'=>$child->term_id,
						));
						if ( !empty($grandchildren) && !is_wp_error( $grandchildren ) ){
							
							$formatted_child['children'] = array();
							foreach($grandchildren as $grandchild){
								
								$formatted_child['children'][] = $this->getTermInfo($grandchild,$lang);
							}
						}// end grandchildren
						
						$formatted_parent['children'][] = $formatted_child;
					}
					
				}// end children
				
				$terms[] = $formatted_parent;
			}
		
		}//end parent
		
		return $terms;
	}
	
	public function getPostsFilters($lang){	

		/* $refresh = (isset($_GET['refresh']) && $_GET['refresh'] == '1')? true:false;
	
		$file = plugin_dir_path( dirname( __FILE__ ) ) . 'antiques_filters.json';
		if ( file_exists($file) && !$refresh ){
			$result = file_get_contents($file);
			if ($result && !empty($result) > 0){
				return json_decode($result, true);
			}
			
		} */
	
		$result = array(
			'filters'=> array(),
		);
		
		$filters = array();
		
		$all_cats_text = ($lang == 'en')? 'Categories':'所有分類';
		$categories_top_levels1 = array(0=> $this->getTermInfo(null, $lang, $all_cats_text, 'antique_cat'));
		$categories_top_levels2 = $this->getTermsMax3Levels('antique_cat', $lang, true);		
		$filters['categories'] = array_merge($categories_top_levels1, $categories_top_levels2);	
		
		
		$all_locations_text = ($lang == 'en')? 'Locations':'所有地區';
		$locations_top_levels1 = array(0=> $this->getTermInfo(null, $lang, $all_locations_text, 'location'));			
		$locations_top_levels2 = $this->getTermsMax3Levels('location', $lang, true);
		$filters['locations'] = array_merge($locations_top_levels1, $locations_top_levels2);
		
		
		$result['filters'] = $filters;
		
		//file_put_contents($file, json_encode($result));
		
		return $result;
	}
	
	public function getTerms($lang){
		
		/* $file = plugin_dir_path( dirname( __FILE__ ) ) . 'antiques_terms.json';
		if ( file_exists($file) ){
			$result = file_get_contents($file);
			if ($result && !empty($result) > 0){
				return json_decode($result, true);
			}
			
		} */
	
		$result = array(
			'categories'=> $this->getTermsMax3Levels('antique_cat', $lang, false),
			'locations'=> $this->getTermsMax3Levels('location', $lang, false),
		);		
		
		
		//file_put_contents($file, json_encode($result));
		
		return $result;
	}
		
	public function antiqueList($post_data, $lang = 'hk', $endpoint_root=''){		
		
		$result = array(
			'success'=> false,
			'message'=>'',				
			'paged' => 1,
			'posts_per_page'=>50,			
			'posts' => array(),			
		);
		
		$args = array(
			'post_type'=>'antique',
			'paged' => $result['paged'],
			'posts_per_page' => $result['posts_per_page'],
			'post_status' => 'publish',
			'tax_query' => array(),
		);		
		
		
		// get optional input parameters
		 if ( isset($post_data['c_paged']) && is_numeric($post_data['c_paged']) ){
			$paged = (int) $post_data['c_paged'];
			if ($paged < 1){
				$paged = 1;
			}
			$result['paged'] = $paged;
			$args['paged'] = $paged;
		}	
	 
		
		if ( isset($post_data['posts_per_page']) && is_numeric($post_data['posts_per_page']) ){
			$posts_per_page = (int) $post_data['posts_per_page'];
			if ($posts_per_page <= 0 ){
				$posts_per_page = $result['posts_per_page'];
			}
			$result['posts_per_page'] = $posts_per_page;
			$args['posts_per_page'] = $posts_per_page;
		}
		
		if ( isset($post_data['c_antique_cat']) && is_numeric($post_data['c_antique_cat']) && trim($post_data['c_antique_cat']) > 0 ){
			$is_home = false;
			$args['tax_query'][]=array(
						'taxonomy' => 'antique_cat',
						'field'    => 'term_id',
						'terms'    => explode(',', trim($post_data['c_antique_cat'])),
					);						
		}
		
		if ( isset($post_data['c_location']) && is_numeric($post_data['c_location']) && trim($post_data['c_location']) > 0 ){
			$is_home = false;
			$args['tax_query'][]=array(
						'taxonomy' => 'location',
						'field'    => 'term_id',
						'terms'    => explode(',', trim($post_data['c_location'])),
					);							
		}
				
		if ( isset($post_data['last_post_id']) && !empty($post_data['last_post_id']) ){			
			$last_post_id = $post_data['last_post_id'];
		}
		
		if ( isset($post_data['c_s']) && !empty($post_data['c_s']) ){
			$is_home = false;
			$args['s'] = $post_data['c_s'];
		}		
		
		if (isset($args['tax_query']) && count($args['tax_query'])>1){
			$args['tax_query']['relation']='AND';
		}
		
		//$posts = get_posts($args);
		$query = new WP_Query( $args );
		
		
		if ( $query->have_posts()){			
			
			foreach($query->posts as $p){
				
				if(isset($last_post_id) && $p->ID == $last_post_id){
					continue;
				}
				
				$item = new ANTIQUE_LIST_ITEM;
				$item->populateItem($p, $lang, $endpoint_root);

				$result['posts'][]  = $item;
				
			}//end foreach				
		}		
		
		wp_reset_postdata();	
				
		$result['success'] = true;
		return $result;
	}
	
	public function antiqueHomeList($post_data, $lang = 'hk', $endpoint_root=''){		
		$paged = 1;
		
		$result = array(
			'success'=> false,
			'message'=>'',				
			'paged' => $paged,
			'posts_per_page'=>50,
			'headlines' => array(),			
			'posts' => array(),			
		);
		
		$args = array(
			'post_type'=>'antique',
			'paged' => $result['paged'],
			'posts_per_page' => $result['posts_per_page'],
			'post_status' => 'publish',
			'tax_query' => array(
				'relation' => 'AND',
				array(
					'taxonomy' => 'recommend',
					'field'    => 'slug',
					'terms'    => 'headline',
					'operator' => 'NOT EXISTS'
				),
				array(
					'taxonomy' => 'recommend',
					'field'    => 'slug',
					'terms'    => 'promote',
					'operator' => 'NOT EXISTS'
				),
			),
		);		
		
		
		// get optional input parameters
		 if ( isset($post_data['c_paged']) && is_numeric($post_data['c_paged']) ){
			$paged = (int) $post_data['c_paged'];
			if ($paged < 1){
				$paged = 1;
			}
			$result['paged'] = $paged;
			$args['paged'] = $paged;
		}	
	 
		
		if ( isset($post_data['posts_per_page']) && is_numeric($post_data['posts_per_page']) ){
			$posts_per_page = (int) $post_data['posts_per_page'];
			if ($posts_per_page <= 0 ){
				$posts_per_page = $result['posts_per_page'];
			}
			$result['posts_per_page'] = $posts_per_page;
			$args['posts_per_page'] = $posts_per_page;
		}
			
		if ( isset($post_data['last_post_id']) && !empty($post_data['last_post_id']) ){			
			$last_post_id = $post_data['last_post_id'];
		}		
	
		
		//$posts = get_posts($args);
		$query = new WP_Query( $args );
		
		
		if ( $query->have_posts()){			
			
			foreach($query->posts as $p){
				
				if(isset($last_post_id) && $p->ID == $last_post_id){
					continue;
				}
				
				$item = new ANTIQUE_LIST_ITEM;
				$item->populateItem($p, $lang, $endpoint_root);

				$result['posts'][]  = $item;
				
			}//end foreach				
		}		
		
		wp_reset_postdata();		
		
		// only add headline and promotes posts on page 1		
		if ($paged == 1){
			$post_ids = get_home_special_antiques();
			if ( isset($post_ids['headline']) && is_array($post_ids['headline']) ){
				
				foreach($post_ids['headline'] as $id){
				
					$item = new ANTIQUE_LIST_ITEM;
					$item->populateItem( get_post($id), $lang, $endpoint_root);

					$result['headlines'][]  = $item;
					
				}				
			}			
			//end headline	
			
			if ( isset($post_ids['promote']) && is_array($post_ids['promote']) ){
				
				foreach($post_ids['promote'] as $id){
				
					$item = new ANTIQUE_LIST_ITEM;
					$item->populateItem( get_post($id), $lang, $endpoint_root);

					$result['promotes'][]  = $item;
					
				}				
			}			
			//end promote			
		}
				
		$result['success'] = true;
		return $result;
	}	
	
	public function getUserAntique($user_id, $antique_id){
		
		$antique = null;
		
		if ($antique_id <= 0){
			
			// find user existing draft antique
			global $wpdb;
			$query = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'antique' AND post_status = 'draft' AND post_author = %d ORDER BY post_modified DESC", $user_id );
		 
			
			$ids = $wpdb->get_results( $query );
			
			if (count($ids) > 0){
				$antique_id = $ids[0]->ID;
			}
			else{
				
				// create new antique if no existing draft
				$antique_id = wp_insert_post(array('post_title'=>'New Antique', 'post_content'=>'[New Content]', 'post_type'=>'antique', 'post_author'=>$user_id)); 
			}			
		}
		
		$antique = new USER_ANTIQUE_SINGLE($antique_id);
		
		return $antique;
	}
	
	public function deleteUserAntiques($user_id, $antique_ids){
		
		$deleted_count = 0;
		
		if(!isset($user_id) || !isset($antique_ids)){
			return $deleted_count;
		}		
		
		$ids = explode(',', $antique_ids);		
		
		foreach($ids as $antique_id){
			
			if (!is_numeric($antique_id)){
				// not an id
				continue;
			}
			
			$author_id = get_post_field( 'post_author', $antique_id );
			
			if ($author_id != $user_id){
				// not the owner
				continue;
			}
			
			$trash = wp_trash_post($antique_id);
			
			if (is_a($trash, 'WP_Post')){
				$deleted_count += 1;
			}			
		}
		
		return $deleted_count;
		
	}
	
	public function updateUserAntique($user_id, $antique_id, $post_params){
		
		if(!isset($user_id) || !isset($antique_id) || !isset($post_params)){
			return false;
		}
		
		$allow_status=array('private','publish');
		$my_post = null;
		
		$lang_version_zh = isset($post_params['lang_version_zh'])? sanitize_text_field($post_params['lang_version_zh']):'';
		$lang_version_en = isset($post_params['lang_version_en'])? sanitize_text_field($post_params['lang_version_en']):'';
		$antique_status = isset($post_params['antique_status'])? sanitize_text_field($post_params['antique_status']):'private';
		$title_zh = isset($post_params['title_zh'])? sanitize_text_field($post_params['title_zh']):'';
		$title_en = isset($post_params['title_en'])? sanitize_text_field($post_params['title_en']):'';
		$antique_price = isset($post_params['antique_price'])? sanitize_text_field($post_params['antique_price']):'';
		$description_zh = isset($post_params['description_zh'])? sanitize_textarea_field($post_params['description_zh']):'';
		$description_en = isset($post_params['description_en'])? sanitize_textarea_field($post_params['description_en']):'';
		
		if ($lang_version_zh != '1' && $lang_version_en != '1'){
			return false;
		}
		
		if ($lang_version_zh == '1' && empty($title_zh)){
			return false;
		}
		
		if ($lang_version_zh == '1' &&  empty($description_zh)){
			return false;
		}
		
		if ($lang_version_en == '1' && empty($title_en)){
			return false;
		}
		
		if ($lang_version_en == '1' &&  empty($description_en)){
			return false;
		}
		
		if (!in_array($antique_status, $allow_status)){
			return false;
		}
		
		$my_post = get_post($antique_id);
		
		if (!is_a($my_post, 'WP_Post')){
			return false;
		}
		
		// categoreis
		$categories = isset($post_params['c_categories'])? sanitize_text_field($post_params['c_categories']):'';
		$cat_updated = wp_set_post_terms($antique_id, $categories, 'antique_cat', false);
		if (is_a($cat_updated, 'WP_Error')){
			return false;
		}
		
		
		// location		
		$locations = isset($post_params['c_locations'])? sanitize_text_field($post_params['c_locations']):'';
		$location_updated = wp_set_post_terms($antique_id, $locations, 'location', false);
		if (is_a($location_updated, 'WP_Error')){
			return false;
		}
		
		// update selected languages
		if ($lang_version_zh != '1'){
			update_post_meta($antique_id,'_lang_version_zh','1');
		}
		else{
			delete_post_meta($antique_id, '_lang_version_zh');
		}
		
		if ($lang_version_en != '1'){
			update_post_meta($antique_id,'_lang_version_en','1');
		}
		else{
			delete_post_meta($antique_id, '_lang_version_en');
		}
		
		update_post_meta($antique_id, 'price', $antique_price);
		update_post_meta($antique_id, 'name_en', $title_en);
		update_post_meta($antique_id, 'description_en', $description_en);
		
		
		
		$my_post->post_status = $antique_status;
		
		if (!empty($title_zh)){
			
			$my_post->post_title = $title_zh;
			$my_post->post_content = $description_zh;		
		}
		
		// Update the post into the database
		$updated = wp_update_post( $my_post );
		
		if (is_a($updated, 'WP_Error')){
			return false;
		}
		
		return true;
		
	}
	
	public function getUserAntiqueList($user_id, $lang_version, $paged = 1){
		
		$antiqueList = array();
		
		$args = array(
			'post_type'=>'antique',
			'paged' => $paged,
			'posts_per_page' => 50,
			'author' => $user_id,
			'post_status' => array( 'private', 'publish'),
			'fields' =>'ids',			
		);
		
		if ($lang_version == 'en'){
			$args['meta_key'] = '_lang_version_en';
			$args['meta_value'] = '1';
		}
		else{
			$args['meta_key'] = '_lang_version_zh';
			$args['meta_value'] = '1';
		}
		
		$list = get_posts($args);
		
		foreach($list as $antique_id){
			$antiqueList[] = new USUR_ANTIQUE_LIST_ITEM($antique_id, $lang_version);
		}
		
		return $antiqueList;		
	}
}

?>