<?php
global $extra_contact_fields;

$extra_contact_fields =  array( 
	array( 'phone', __( 'Phone Number', 'antiques_cucm' ), true ),
	array( 'address', __( 'Address', 'antiques_cucm' ), true ),
	array( 'city', __( 'City', 'antiques_cucm' ), true ),
	array( 'province', __( 'Province', 'antiques_cucm' ), false ),
	array( 'country', __( 'Country', 'antiques_cucm' ), false ),
	array( 'postage', __( 'Post Code', 'antiques_cucm' ), false ),			
);

?>