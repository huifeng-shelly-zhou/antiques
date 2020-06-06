<?php
global $API_KEY, $API_SECRET, $ALLOWED_ROLES, $APP_Feeds, $lang;

$API_KEY = 'DZCxFZdWOjR7ZCxFZd';
$API_SECRET = '9cOqYF1aXQ2P6Tf9cOqYF1aXs8qq7XCtol5TblQT5ZCauAzqUmDAbfh8qbyXHOm5U2pPhmWNyWSrm2NP7';

$ALLOWED_ROLES = array(	'administrator', 'antique_player', 'antique_vip');

$lang = isset($_REQUEST['lang'])? $_REQUEST['lang']:'hk';//'hk';

$APP_Feeds = array(
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
	'filters' => array(),
	
);
?>