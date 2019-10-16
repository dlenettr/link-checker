<?php
/*
=============================================
 Name      : MWS Link Checker v1.4
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : https://mehmethanoglu.com.tr
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
