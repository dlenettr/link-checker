<?php

@set_time_limit(0);
@error_reporting(E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE);
@ini_set('error_reporting', E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE);
@ini_set('display_errors', true);
@ini_set('html_errors', false);

define('DEBUG', 0);            // Debug modu açıkken adres satırından erişebilir ve tüm çıktıları görebilirsiniz.
define('PASSWORD', "123456");  // Modül veri dosyalarını silmek için gerekli şifredir
define('ACTIVE', 0);           // Modülün cron işlevini aktifleştir-pasifleştir işlemini buradan yapabilirsiniz.

$cl = false;
function p( $x ) { global $cl; if ( !$cl ) echo "<pre>"; print_r( $x ); if ( !$cl ) echo "</pre>"; echo PHP_EOL; }

if ( ACTIVE ) {
    define('DATALIFEENGINE', true);
    define('AUTOMODE', true);
    define('LOGGED_IN', true);

    define('ROOT_DIR', dirname(__FILE__));
    define('ENGINE_DIR', ROOT_DIR.'/engine');
    define('DATA_FILE', ENGINE_DIR.'/data/linkchecker.db');
    define('CRON_FILE', ENGINE_DIR.'/data/linkchecker.cron');

    require_once ENGINE_DIR.'/classes/mysql.php';
    require_once ENGINE_DIR.'/data/dbconfig.php';
    require_once ENGINE_DIR.'/data/config.php';
    require_once ENGINE_DIR.'/inc/include/functions.inc.php';
    require_once ENGINE_DIR.'/data/linkchecker.conf.php';

    date_default_timezone_set($config['date_adjust']);

    // Satır indisleri
    $i_date = 0;
    $i_newsid = 1;
    $i_xfname = 2;
    $i_error = 3;
    $i_url = 4;
    $i_rules = 5;

    $mode = false;

    if ($_REQUEST['mode'] && DEBUG) {
        $mode = $_REQUEST['mode'];
    } elseif (!empty($argc) && $argc > 1) {
        $mode = $argv[1];
        $cl = true;
    }

    if ( DEBUG ) p( "mode:" . $mode );

    if ( DEBUG ) p( $lset );

    // Makaledeki linklerin listesini oluştur
    if ($mode == "generate") {

        $links = [];
        $file = fopen( DATA_FILE, 'r' );
        $data = fread( $file, filesize( DATA_FILE ) );
        fclose( $file );
        $data = array_filter( explode( "\n", $data ) );
        if ( DEBUG ) p( $data );
        if ( count( $data ) > 0 ) {
            foreach( $data as $row ) {
                $_tmp = explode( "|", $row );
                $links[] = $_tmp[$i_url];
            }
        }
        if ( DEBUG ) p( $links );

        $file = fopen( DATA_FILE, 'a' );


        $vp_xf_name = "";
        if ($lset['video_part_active'] && file_exists( ENGINE_DIR . '/data/videopart.conf.php' )) {
            require_once ENGINE_DIR . '/data/videopart.conf.php';
            if ($vset['mod_on']) {
                $vp_xf_name = $vset['xf_name'];
            }
            if ( DEBUG ) p( $vset );
        }

        $stats = [ 'added' => 0, 'exists' => 0 ];

        if ($lset['data_list_active'] && file_exists( ENGINE_DIR . '/data/datalist.conf.php' )) {
            require_once ENGINE_DIR . '/data/datalist.conf.php';
            if ( DEBUG ) p( $data_list_conf );
            $dsql = $db->query("SELECT * FROM " . PREFIX . "_data_list");
            while ($row = $db->get_row($dsql)) {
                if ( DEBUG ) p( $row );
                $data = json_decode( $row['data'], true );
                if ( !empty( $lset['data_list_field'] ) && array_key_exists( $lset['data_list_field'], $data_list_conf ) ) {
                    foreach( $data as $data_row ) {
                        $url = urlencode( $data_row[ $lset['data_list_field'] ] );
                        if ( ! in_array( $url, $links ) && ! empty( $url ) ) {
                            fwrite( $file, date("Y-m-d H:i:s") . "|" . $row['news_id'] . "|" . "datalist" . "|0|" . $url . "|" . $lset['data_list_rules'] .  "\n" );
                            $stats['added']++;
                        } else $stats['exists']++;
                    }
                }
            }
        }

        $c_fields = explode(",", $lset['controls']);

        $nsql = $db->query("SELECT * FROM " . PREFIX . "_post");
        while ($row = $db->get_row($nsql)) {
            break;
            $xf = xfieldsdataload($row['xfields']);

            if (! empty($vp_xf_name) && array_key_exists($vp_xf_name, $xf)) {
                preg_match_all("#\[part=*(.*?)\](.*?)\[/part\]#is", $xf[$vp_xf_name], $matches);

                for ($x = 0; $x < count($matches[0]); $x++) {
                    $iframe = $matches[2][$x];

                    if (preg_match("#src=['\"](.+?)['\"]#is", stripslashes($iframe), $src)) {
                        if (substr($src[1], 0, 2) == "//") {
                            $url = "http:" . $src[1];
                        } else {
                            $url = $src[1];
                        }

                        if ( DEBUG ) p( $url );

                        $url = urlencode( $url );

                        if ( ! in_array( $url, $links ) && ! empty( $url ) ) {
                            fwrite( $file, date("Y-m-d H:i:s") . "|" . $row['id'] . "|" . $vp_xf_name . "|0|" . $url . "|" . $lset['video_part_rules'] .  "\n" );
                            $stats['added']++;
                        } else $stats['exists']++;

                    }
                }
            }

            if (count($xf) > 0 && count($c_fields) > 0) {
                foreach ($xf as $xf_n => $xf_v) {
                    if (in_array($xf_n, $c_fields)) {
                        if ($lset[ $xf_n . "_type" ] == "0") {
                            $url = str_replace("{code}", $xf_v, $lset[ $xf_n . "_template" ]);
                        } elseif ($lset[ $xf_n . "_type" ] == "1") {
                            if (substr($xf_v, 0, 5) == "http:") {
                                $url = $xf_v;
                            } else {
                                $url = "http:" . $xf_v;
                            }
                        }

                        if ( DEBUG ) p( $url );

                        $url = urlencode( $url );

                        if ( ! in_array( $url, $links ) && ! empty( $url ) ) {
                            fwrite( $file, date("Y-m-d H:i:s") . "|" . $row['id'] . "|" . $xf_n . "|0|" . $url . "|" . $lset[ $xf_n . "_text" ] . "\n" );
                            $stats['added']++;
                        } else $stats['exists']++;

                    }
                }
            }

        }
        fclose( $file );

        if ( DEBUG ) p( $stats );

        p( implode( "|", $stats ) );
        p("GENERATED:OK");
        die();

    } elseif ($mode == "check") {

        $file = fopen( CRON_FILE, 'r' );
        $data = fread( $file, filesize( CRON_FILE ) );
        fclose( $file );
        if ( ! empty( $data ) ) {
            list( $last_date, $last_row ) = explode( "|", $data );
            if ( DEBUG ) p( $last_date );
        } else {
            $last_row = 0;
        }

        $last_date = date("Y-m-d H:i:s");
        // Listedeki linkleri kontrol et

        $file = fopen( DATA_FILE, 'r' );
        $data = fread( $file, filesize( DATA_FILE ) );
        fclose( $file );
        $data_content = $data;
        $data = array_filter( explode( "\n", $data ) );

        if ( DEBUG ) p( $data );

        if ( count( $data ) <= $last_row ) $last_row = 0;

        if ( count( $data ) > 0 ) {
            foreach( $data as &$row ) {
                $row = explode( "|", $row );
            }

            $controlled = 0;
            $not_valids = [];
            for( $x = $last_row; $x < count( $data ); $x++ ) {

                // Zaten hatalıysa geç
                if ( $data[$x][$i_error] == 1 ) continue;

                if ( DEBUG ) p( $data[ $x ] );
                if ( DEBUG ) p( "=========================" );


                $rules = ( empty( $data[ $x ][$i_rules] ) ) ? [] : explode( "__EOL__", $data[ $x ][$i_rules] );
                if ( DEBUG ) p( $rules );

                $url = urldecode( $data[ $x ][$i_url] );
                $is_valid = check_link( $url, $rules );

                if ( DEBUG ) p( intval( $is_valid ) );

                if ( ! $is_valid ) {
                    $not_valids[] = $url;
                    $db->query("INSERT INTO " . PREFIX . "_linkchecker ( news_id, xfname, xfvalue, cdate, nfound ) VALUES ( '{$data[$x][$i_newsid]}', '{$data[$x][$i_xfname]}', '{$url}', '{$last_date}', '1' )");
                }

                if ( DEBUG ) p( "=========================" );
                $controlled++;

                if ( $controlled == $lset['cron_check_link'] ) {
                    $last_row = $x;
                    break;
                }
            }

            foreach( $not_valids as $not_valid ) {
                $data_content = str_replace( "0|" . urlencode( $not_valid ), "1|" . urlencode( $not_valid ), $data_content );
            }
            if ( DEBUG ) p( $not_valids );

            $file = fopen( DATA_FILE, 'w' );
            fwrite( $file, $data_content );
            fclose( $file );

            $log = date("Y-m-d H:i:s") . "|" . $last_row;

            p( $log );

            $file = fopen( CRON_FILE, 'w' );
            fwrite( $file, $log );
            fclose( $file );

        } else if ( DEBUG ) {
            p( $data );
        }

        p("CHECK:OK");
        die();

    } elseif ($mode == "test" && $_REQUEST['url']) {

        check_link( $_REQUEST['url'], [], true );


    } elseif ($mode == "refresh" && $_REQUEST['pass'] && $_REQUEST['pass'] == PASSWORD ) {

        unlink( DATA_FILE );
        unlink( CRON_FILE );

        p("DELETED");
        die();
    }

} else {
    p("NOT ALLOWED");
    die();
}


function check_link( $url, $rules, $debug = false ) {
    global $lset;
    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $lset['cron_check_timeout'] );
    curl_setopt( $ch, CURLOPT_TIMEOUT, $lset['cron_check_timeout'] );
    curl_setopt( $ch, CURLOPT_MAXREDIRS, 3 );
    $output  = curl_exec( $ch );
    $info  = curl_getinfo( $ch );

    if ( DEBUG || $debug ) p( $info );

    if ( $info['http_code'] == 200 ) {

        if ( $debug ) p( "SAYFA KAYNAĞI<br />----------------------<br />" . htmlspecialchars( $output ) );

        $no = true;
        foreach( $rules as $rule ) {
            if ( strpos( $output, htmlspecialchars_decode( $rule ) ) !== false ) {
                $no = false;
                break;
            }
        }
        return $no;

    } else if ( $info['http_code'] == 404 ) {
        return false;

    } else {
        return false;
    }

    curl_close( $ch );
}

