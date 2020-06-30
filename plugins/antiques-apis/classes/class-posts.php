<?php

class ANTIQUE_LIST_ITEM{
	
	public $id = '';
	public $title = '';
	public $thumbnail = '';
	public $style = '';
	public $owner = '';
	public $price = '';
	public $endpoint = '';
	public $rank = 0.0;
	public $location = '';
	public $categories = '';
	
	public function __construct(){ 
    }
	
	public function populateItem($post, $lang = 'hk', $endpoint_root=''){
		
		if (is_a($post, 'WP_Post')){
			
			$this->id = $post->ID;
			$this->title = antLang($post->post_title, $lang);
			$this->endpoint = $endpoint_root.'/post/get';
			$this->price = get_post_meta($post->ID, 'price', true);
			$this->price = antLang($this->price, $lang);
			
			// thumbnail
			$gallery_images = get_antique_gallery_images($post->ID);
			if (count($gallery_images) > 0){
				$this->thumbnail = $gallery_images[0];
			}
			else{
				$this->thumbnail = ant_get_placeholder();
			}
			
			// post author
			if(isset($post->post_author)){
				$author = get_user_by( 'id', $post->post_author);
				
				if (is_a($author, 'WP_User')){
					$this->owner = antLang($author->display_name, $lang);
				}
			}
			
			// style
			$recommends = wp_get_post_terms($post->ID, 'recommend', array( 'fields' => 'names' ));
			if (is_array($recommends) && count($recommends) > 0){
				$this->style = $recommends[0];				
			}
			
			// location
			$locations = wp_get_post_terms($post->ID, 'location', array( 'fields' => 'names' ));
			if (is_array($locations) && count($locations) > 0){
				$this->location = $locations[0];	
				$this->location = antLang($this->location, $lang);
			}
			
			// categoreis
			$categoreis = wp_get_post_terms($post->ID, 'antique_cat', array( 'fields' => 'names' ));
			if (is_array($categoreis) && count($categoreis) > 0){
				$this->categories = implode(',',$categoreis);
				$this->categories = antLang($this->categories, $lang);
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
	
	private function getTermsMax3Levels($terms, $lang){
	
		$terms_top_levels = array();
		
		if (!is_array($terms)){
			return array();
		}
		
		foreach ( $terms as $term ) {
			
			if (!is_a($term, 'WP_Term')){
				continue;
			}
			
			if ($term->parent > 0){
				
				// get parent
				$parent = get_term($term->parent);				
				
				
				if ($parent->parent > 0){
					// has grandparent
					$grandparent = get_term($parent->parent);
					
					if (!isset($terms_top_levels[$grandparent->term_id])){
						$terms_top_levels[$grandparent->term_id] = $this->getTermInfo($grandparent,$lang);
					}
					
					if (!isset($terms_top_levels[$grandparent->term_id]['children'])){
						$terms_top_levels[$grandparent->term_id]['children'] = array();
					}
					
					if (!isset($terms_top_levels[$grandparent->term_id]['children'][$parent->term_id])){
						$terms_top_levels[$grandparent->term_id]['children'][$parent->term_id] = $this->getTermInfo($parent,$lang);
					}
					
					if (!isset($terms_top_levels[$grandparent->term_id]['children'][$parent->term_id]['children'])){
						$terms_top_levels[$grandparent->term_id]['children'][$parent->term_id]['children'] = array();
					}
					
					$terms_top_levels[$grandparent->term_id]['children'][$parent->term_id]['children'][$term->term_id] = $this->getTermInfo($term,$lang);
				}
				else{
					// has one parent
					if (!isset($terms_top_levels[$parent->term_id])){
						$terms_top_levels[$parent->term_id] = $this->getTermInfo($parent,$lang);
					}
					
					if (!isset($terms_top_levels[$parent->term_id]['children'])){
						$terms_top_levels[$parent->term_id]['children'] = array();
					}
					$terms_top_levels[$parent->term_id]['children'][$term->term_id] = $this->getTermInfo($term,$lang);
				}
			}
			else{
				
				// no parent
				$terms_top_levels[$term->term_id] = $this->getTermInfo($term,$lang);
			}		
			
		}//end foreach
		
		return $terms_top_levels;
	}
	
	public function getPostsFilters($lang){		
	
		$result = array(
			'filters'=> array(),
		);
		
		$filters = array();
		
		$terms = get_terms('antique_cat', array(
			'orderby'    => 'count',
			'hide_empty'=>true,
		));
		
		if ( !empty($terms) && !is_wp_error( $terms ) ){		
			
			//$APP_Feeds['filters']['category_endpoint'] = $this->endpoint_root.'/category/';			
			
			$categories_top_levels1 = array(0=> $this->getTermInfo(null, $lang, '所有分類', 'antique_cat'));
			$categories_top_levels2 = $this->getTermsMax3Levels($terms, $lang);
			
			$filters['categories'] = array_merge($categories_top_levels1, $categories_top_levels2);			
				
		}//end if	
		
		
		$locations = get_terms('location', array(
			'orderby'    => 'count',
			'hide_empty'=>true,
			'hierarchical'=>false,
		));
		if ( !empty($locations) && !is_wp_error( $locations ) ){
			
			//$APP_Feeds['filters']['category_endpoint'] = $this->endpoint_root.'/category/';
			
			$locations_top_levels1 = array(0=> $this->getTermInfo(null, $lang, '所有地區', 'location'));			
			$locations_top_levels2 = $this->getTermsMax3Levels($locations, $lang);
			
			
			$filters['locations'] = array_merge($locations_top_levels1, $locations_top_levels2);			
		
		}//end if	
		
		$result['filters'] = $filters;
		
		return $result;
	}
		
	public function antiqueList($post_data, $lang = 'hk', $endpoint_root=''){
		
		$result = array(
			'success'=> false,
			'message'=>'',				
			'paged' => 1,
			'posts_per_page'=>20,			
			'posts' => array(),			
		);
		
		$args = array(
			'post_type'=>'antique',
			'paged' => $result['paged'],
			'posts_per_page' => $result['posts_per_page'],
			'post_status' => 'publish',
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
			
			$args['tax_query'][]=array(
						'taxonomy' => 'antique_cat',
						'field'    => 'term_id',
						'terms'    => explode(',', trim($post_data['c_antique_cat'])),
					);						
		}
		
		if ( isset($post_data['c_location']) && is_numeric($post_data['c_location']) && trim($post_data['c_location']) > 0 ){
			
			$args['tax_query'][]=array(
						'taxonomy' => 'location',
						'field'    => 'term_id',
						'terms'    => explode(',', trim($post_data['c_location'])),
					);							
		}
		
		if (isset($args['tax_query']) && count($args['tax_query'])>1){
			$args['tax_query']['relation']='AND';
		}
				
		if ( isset($post_data['last_post_id']) && !empty($post_data['last_post_id']) ){			
			$last_post_id = $post_data['last_post_id'];
		}
		
		if ( isset($post_data['s']) && !empty($post_data['s']) ){			
			$args['s'] = $post_data['s'];
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
		
		// only add headline posts on page 1
		if ($paged == 1 && count($result['posts']) > 0){
			$result['posts'][0]->style = 'headline';
		}
		
		$result['success'] = true;
		return $result;
	}
	
	
}

?>