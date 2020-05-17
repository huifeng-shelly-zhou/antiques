<?php
/**
* AIT WordPress Utility 
*
* @since 1.0
*
*/

class Antiques_API_Rewrite {


	private $name;
	private $version;

	/**
	 * Initialize the class and set its properties.
	 */
	public function __construct( $name, $version ) {

		$this->name = $name;
		$this->version = $version;

	}

	public function add_rewrite_rules() {

        global $wp_rewrite;		

		add_rewrite_rule( $this->name.'/(.+?)/(.+?)/?$', 'index.php?'.$this->name.'=$matches[1]&action=$matches[2]', 'top' );
		
        $wp_rewrite->flush_rules();	
	}
	
	public function rewrite_query_vars( $qvars ) {
	
		$qvars[] = $this->name;
		$qvars[] = 'action';
		$qvars[] = 'id';
		$qvars[] = 'name';
		
		return $qvars;

	}
	
}