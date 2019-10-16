<?php
/*
=============================================
 Name      : MWS Link Checker v1.4
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : https://mehmethanoglu.com.tr
 License   : MIT License
=============================================
*/

if ( !defined('DATALIFEENGINE') ) {
	header( "HTTP/1.1 403 Forbidden" );
	header ( 'Location: ../../' );
	die( "Hacking attempt!" );
}

if ( $_REQUEST['user_hash'] == "" OR $_REQUEST['user_hash'] != $dle_login_hash ) {
	die( "error" );
}

require_once ENGINE_DIR . '/data/linkchecker.conf.php';

require_once ROOT_DIR . "/language/" . $config['langs'] . "/linkchecker.lng";

if ( isset( $_POST['save'] ) ) {
	$settings = $lset;

	if ( $_POST['save']['user_hash'] == "" or $_POST['save']['user_hash'] != $dle_login_hash ) {
		die( "Hacking attempt!" );
	}
	unset( $_POST['save']['user_hash'] );

	//$_POST['save']['video_part_action'] = intval( $_POST['save']['video_part_action'] );
	$_POST['save']['video_part_active'] = intval( $_POST['save']['video_part_active'] );
	$_POST['save']['cron_check_link'] = intval( $_POST['save']['cron_check_link'] );
	$_POST['save']['cron_check_timeout'] = intval( $_POST['save']['cron_check_timeout'] );
	$_POST['save']['data_list_active'] = intval( $_POST['save']['data_list_active'] );

	$controls = array();
	foreach ( $_POST['save'] as $key => $values ) {
		if ( is_array( $values ) && $values['control'] == "1" ) {
			foreach ( $values as $val_key => $value ) {
				$set_name = $key . "_" . $val_key;
				$settings[ $set_name ] = $value;
			}
			$controls[] = $key;
		} else {
			$settings[ $key ] = $values;
		}
	}
	$settings['controls'] = implode( ",", $controls );

	$find = array( "'\r'", "'\n'" );
	$replace = array( "", "" );

	$handler = fopen( ENGINE_DIR . '/data/linkchecker.conf.php', "w" );
	fwrite( $handler, "<?PHP \n\n//MWS Link Checker Configurations\n\n\$lset = [\n\n" );
	foreach ( $settings as $name => $value ) {
		$value = str_replace( "\n", "__EOL__", $value );
		$value = trim(strip_tags(stripslashes( $value )));
		$value = htmlspecialchars( $value, ENT_QUOTES, $config['charset']);
		$value = preg_replace( $find, $replace, $value );
		$name = trim(strip_tags(stripslashes( $name )));
		$name = htmlspecialchars( $name, ENT_QUOTES, $config['charset'] );
		$name = preg_replace( $find, $replace, $name );
		$value = str_replace( "$", "&#036;", $value );
		//$value = str_replace( "{", "&#123;", $value );
		//$value = str_replace( "}", "&#125;", $value );
		//$value = str_replace( '/', "", $value );
		//$value = str_replace( ".", "", $value );
		$value = str_replace( chr(92), "", $value );
		$value = str_replace( chr(0), "", $value );
		$value = str_replace( '(', "", $value );
		$value = str_replace( ')', "", $value );
		$value = str_ireplace( "base64_decode", "base64_dec&#111;de", $value );
		$name = str_replace( "$", "&#036;", $name );
		$name = str_replace( "{", "&#123;", $name );
		$name = str_replace( "}", "&#125;", $name );
		$name = str_replace( ".", "", $name );
		$name = str_replace( '/', "", $name );
		$name = str_replace( chr(92), "", $name );
		$name = str_replace( chr(0), "", $name );
		$name = str_replace( '(', "", $name );
		$name = str_replace( ')', "", $name );
		$name = str_ireplace( "base64_decode", "base64_dec&#111;de", $name );
		fwrite( $handler, "'{$name}' => '{$value}',\n\n" );
	}
	fwrite( $handler, "];\n\n?>" );
	fclose( $handler );

	echo $lang['lc_27'];

} else if ( isset( $_POST['lc_action'] ) && isset( $_POST['lc_id'] ) ) {

	$act = $db->safesql( $_POST['lc_action'] );

	if ( $act == "del" ) {

		$lc_id = intval( $_POST['lc_id'] );
		$db->query( "DELETE FROM " . PREFIX . "_linkchecker WHERE id = '{$lc_id}'" );
		echo $lang['lc_28'];

	} else if ( $act == "notify" ) {

		$lc_id = intval( $_POST['lc_id'] );
		$lc = $db->super_query( "SELECT * FROM " . PREFIX . "_linkchecker WHERE id = '{$lc_id}'" );
		$nw = $db->super_query( "SELECT title, autor, id FROM " . PREFIX . "_post WHERE id = '{$lc['news_id']}'" );
		$us = $db->super_query( "SELECT user_id FROM " . PREFIX . "_users WHERE name = '{$nw['autor']}'" );

		$temp = str_replace( "__EOL__", "<br />", $lset['pm_text'] );
		$temp = str_replace( "{news}", "<a href=\"" . $config['http_home_url'] . "index.php?newsid=" . $lc['news_id'] . "\">" . stripslashes( $nw['title'] ) . "</a>", $temp );
		$temp = str_replace( "{date}", date( "d.m.Y H:i:s", $lc['cdate'] ) , $temp );
		$temp = str_replace( "{link}", $lc['xfvalue'], $temp );
		$temp = str_replace( "{count}", $lc['nfound'], $temp );

		$db->query( "INSERT INTO " . PREFIX . "_pm (subj, text, user, user_from, date, pm_read, folder) VALUES ('{$lset['pm_title']}', '{$temp}', '{$us['user_id']}', '', '{$_TIME}', '0', 'inbox')" );
		$db->query( "UPDATE " . PREFIX . "_users SET pm_unread = pm_unread + 1, pm_all = pm_all+1 WHERE user_id = '{$us['user_id']}'" );

		echo $lang['lc_29'];
	}

}


?>