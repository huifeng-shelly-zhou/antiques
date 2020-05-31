<?php
global $API_KEY, $API_SECRET, $ALLOWED_ROLES, $APP_Feeds;

$API_KEY = 'DZCxFZdWOjR7ZCxFZd';
$API_SECRET = '9cOqYF1aXQ2P6Tf9cOqYF1aXs8qq7XCtol5TblQT5ZCauAzqUmDAbfh8qbyXHOm5U2pPhmWNyWSrm2NP7';

$ALLOWED_ROLES = array(	'administrator', 'antique_player', 'antique_vip');

$APP_Feeds = (Object) array(
	'app'=>array(
		'android'=>array(
			'version'=>100,
			'url'=>''
		),
		'ios'=>array(
			'version'=>100,
			'url'=>''
		),
	),
	'feeds'=>array(
		'categories' => array(),		
		'filters' => array(),
	),
	
);
?>