<?php

class ANTIQUES_AUTHOR
{
	public $id = '';
	public $display_name = '';	
	public $public_url = '';	
	public $avatar = '';
	public $description = '';
	
	
	public function __construct($post_params, $lang){
		
		$author_id = 0;
		
		if(isset($post_params['c_author_id']) && is_numeric($post_params['c_author_id']) ){
			$author_id = (int)$post_params['c_author_id'];
		}
	
		if($author_id > 0 ){
			$author = get_userdata( $author_id );
		
			if ( $author && ! is_wp_error( $author ) ) {
				$this->populateAuthor($author);
			}
		}		
    }
	
	private function populateAuthor($user, $lang = 'hk'){
		
		if ( is_a($user, 'WP_User') ){
			
			$this->id = $user->ID;
			$this->display_name = empty($user->data->display_name)? $user->first_name:$user->data->display_name;
			$this->display_name = antLang($this->display_name, $lang);
			$this->public_url = get_author_posts_url($user->ID);
			
			$avatar_data = get_avatar_data($user->ID);
			if( isset($avatar_data['url']) ){
				$this->avatar = $avatar_data['url'];
			}
			
			$user_meta = get_user_meta($user->ID);
			foreach($user_meta as $key=>$value){
				
				if ($key == 'description'){
					$this->description = antLang($value[0], $lang);
				}
			}		
			
		}
	}
	
}

?>