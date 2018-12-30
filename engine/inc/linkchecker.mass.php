<?php
/*
=============================================
 Name      : MWS Link Checker v1.0
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/   (c) 2015
 License   : MIT License
=============================================
*/

if ( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) ) {
	die( "Hacking attempt!" );
}

foreach( $selected_news as $news_id ) {
	$db->query( "DELETE FROM " . PREFIX . "_linkchecker WHERE news_id = '{$news_id}'" );
}

include ROOT_DIR . "/language/" . $config['langs'] . "/linkchecker.lng";

msg( "info", $lang['db_ok'], $lang['lc_35'], "?mod=linkchecker" );


?>