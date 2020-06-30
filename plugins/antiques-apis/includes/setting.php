<?php
global $API_KEY, $API_SECRET, $ALLOWED_ROLES, $Feeds, $lang, $languages;

$API_KEY = 'DZCxFZdWOjR7ZCxFZd';
$API_SECRET = '9cOqYF1aXQ2P6Tf9cOqYF1aXs8qq7XCtol5TblQT5ZCauAzqUmDAbfh8qbyXHOm5U2pPhmWNyWSrm2NP7';

$ALLOWED_ROLES = array(	'administrator', 'antique_player', 'antique_vip');

$languages = array(
	'hk'=>array('enable'=>true, 'text'=>'繁體'), 
	'cn'=>array('enable'=>true, 'text'=>'简体'), 
	'en'=>array('enable'=>false, 'text'=>'English')
);


$lang = isset($_REQUEST['lang'])? $_REQUEST['lang']:'hk';//'hk';

$Feeds = array(
	'app'=>array(
		'android'=>array(
			'version'=>100,
			'url'=>''
		),
		'ios'=>array(
			'version'=>100,
			'url'=>''
		),
		'endpoints'=>array(),
	),
	'languages'=> $languages,
);
?>