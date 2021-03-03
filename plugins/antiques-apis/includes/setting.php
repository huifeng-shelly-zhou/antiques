<?php
global $API_KEY, $API_SECRET, $ALLOWED_ROLES, $Feeds, $lang, $languages, $PROMOTE_ROW_POSITIONS, $FCM_SERVER_KEY, $FCM_API;

$API_KEY = 'DZCxFZdWOjR7ZCxFZd';
$API_SECRET = '9cOqYF1aXQ2P6Tf9cOqYF1aXs8qq7XCtol5TblQT5ZCauAzqUmDAbfh8qbyXHOm5U2pPhmWNyWSrm2NP7';
$FCM_SERVER_KEY = 'key=AAAA3h7Tqhk:APA91bGCvZq4fPTC90MDvEW8vvbQnZLxE0KdqI6pbpqERE8Fjz4FmSD34S95yAtgqc1VZn45-7rGwnYc78-mI2NtL1igliEQwX6BSRNUpE1C2at-nRG8KrAnW2F8Ts8kXbPJeBANr6ge';
$FCM_API = 'https://fcm.googleapis.com/fcm/send';

$ALLOWED_ROLES = array(	'administrator', 'antique_player', 'antique_vip');
$PROMOTE_ROW_POSITIONS = array(1, 3, 10);

$languages = array(
	'hk'=>array('enable'=>true, 'text'=>'繁體'), 
	'cn'=>array('enable'=>true, 'text'=>'简体'), 
	'en'=>array('enable'=>true, 'text'=>'English')
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
	),
	'languages'=> $languages,	
);
?>