<?php
global $AIT_KEY, $AIT_SECRET, $ALLOWED_ROLES, $SOURCE_LIST, $SOCIAL_MEDIA_TYPES;

$AIT_KEY = 'DZCxFZdWOjR7';
$AIT_SECRET = 'Q2P6Tf9cOqYF1aXs8qq7XCtol5TblQT5ZCauAzqUmDAbfh8qbyXHOm5U2pPhmWNyWSrm2NP7';

$ALLOWED_ROLES = array(	'administrator', 'author', 'editor', 'principleeditor', 'senioreditor');

$SOURCE_LIST = array(
	'backend.bastillepost.com:8282'=>'HONGKONG SANDBOX',
	'backend.bastillepost.com'=>'HONGKONG',
	'macau.backend.bastillepost.com'=>'MACAU',
	'global.backend.bastillepost.com'=>'GLOBAL',
	'australia.backend.bastillepost.com'=>'AUSTRALIA',
	'usa.backend.bastillepost.com'=>'USA',	
);

$SOCIAL_MEDIA_TYPES = array('fan_page','twitter','youtube','fb_ia');
?>