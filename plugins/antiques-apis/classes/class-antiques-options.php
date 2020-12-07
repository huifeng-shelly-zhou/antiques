<?php

class Antique_Options{
	
	const HEADLINE_MAX = 5;
	const PROMOTE_MAX = 5;	
	
	public $last_headline_id;
	public $last_promote_id;
	
	private function __construct(){
		$this->last_headline_id = 0;
		$this->last_promote_id = 0;
    }    
	
	public static function get_last_recommend_posts(){
		return get_option('home_last_recommend_posts', new Antique_Options());
	}
	
	public static function set_last_recommend_posts($options) {
		
		if( is_a($options, 'Antique_Options') ){
			update_option('home_last_recommend_posts', $options);
		}
	}	
}
?>