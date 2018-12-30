<?php
/*
=============================================
 Name      : MWS Link Checker v1.1
 Author    : Mehmet Hanoğlu ( MaRZoCHi )
 Site      : http://dle.net.tr/
 License   : MIT License
 Date      : 26.12.2017
=============================================
*/

if ( !defined( 'DATALIFEENGINE' ) OR !defined( 'LOGGED_IN' ) ) {
	die( "Hacking attempt!" );
}

require_once ENGINE_DIR . '/data/linkchecker.conf.php';

require_once ROOT_DIR . "/language/" . $config['langs'] . "/linkchecker.lng";

function makeDropDown( $options, $name, $selected, $id = "" ) {
	$id = ( ! empty( $id ) ) ? " id=\"$id\"" : "";
	$output = "<select class=\"uniform\" name=\"$name\"{$id}>\r\n";
	foreach( $options as $value => $description ) {
		$output .= "<option value=\"$value\"";
		if ( $selected == $value ) {
			$output .= " selected ";
		}
		$output .= ">$description</option>\n";
	}
	$output .= "</select>";
	return $output;
}

$action = $_REQUEST['action'];

if ( ! $action ) $action = "list";

if ( $action == "list" ) {

	echoheader( "<i class=\"fa fa-link\"></i> " . $lang['lc_1'], $lang['lc_2'] );

	echo <<< HTML
<script>
function search_submit( prm ){
	document.optionsbar.start_from.value=prm;
	document.optionsbar.submit();
	return false;
}

function gopage_submit( prm ){
	document.optionsbar.start_from.value= (prm - 1) * {$news_per_page};
	document.optionsbar.submit();
	return false;
}
</script>
HTML;

	$start_from = intval( $_REQUEST['start_from'] );
	$news_per_page = intval( $_REQUEST['news_per_page'] );
	$gopage = intval( $_REQUEST['gopage'] );

	if ( ! $news_per_page or $news_per_page < 1 ) { $news_per_page = 50; }
	if ( $gopage ) $start_from = ($gopage - 1) * $news_per_page;
	if ( $start_from < 0 ) $start_from = 0;

	// Sadece kırık link içeren makale ID lerini topla
	$ids = array();
	$db->query( "SELECT news_id FROM " . PREFIX . "_linkchecker GROUP BY news_id");
	while( $row = $db->get_row( ) ) { $ids[] = $row['news_id']; }
	if ( count( $ids ) == 0 ) {

		echo <<<HTML
<div class="panel panel-default">
	<div class="panel-heading">
		<b>{$lang['lc_3']}</b>
		<div class="heading-elements">
			<ul class="icons-list">
				<li><a href="{$PHP_SELF}?mod=linkchecker&amp;action=settings"><i class="fa fa-wrench position-left"></i> {$lang['lc_15']}</a></li>
			</ul>
		</div>
	</div>
	<div class="box-content">
		<div class="row" style="display: table; min-height:100px;">
			<div class="col-md-12 text-center" style="display: table-cell; vertical-align:middle;">
				<p>&nbsp;&nbsp;{$lang['lc_34']}</p>
			</div>
		</div>
	</div>
</div>
HTML;
	echofooter();
	die();

	}
	$where = " WHERE id IN (" . implode( ",", $ids ) . ")";

	if ( ! $order_by ) $order_by = "fixed desc, approve asc, date desc";

	$sel = $db->query( "SELECT p.id, p.date, p.title, p.category, p.autor, p.alt_name, p.comm_num, p.approve, p.fixed, e.news_read, e.votes FROM " . PREFIX . "_post p LEFT JOIN " . PREFIX . "_post_extras e ON (p.id=e.news_id) " . $where . " ORDER BY " . $order_by . " LIMIT $start_from,$news_per_page" );

	if ( $start_from == "0" ) { $start_from = ""; }
	$i = $start_from;
	$entries_showed = 0;
	$entries = "";

	while ( $row = $db->get_row( $sel ) ) {
		$i ++;

		// linkchecker tablosundan makaleye ait verileri çek
		$lc = $db->super_query( "SELECT id, COUNT(*) as count FROM " . PREFIX . "_linkchecker WHERE news_id = '{$row['id']}'" );

		$itemdate = date( "d.m.Y", strtotime( $row['date'] ) );
		$title = $row['title'];
		$title = htmlspecialchars( stripslashes( $title ), ENT_QUOTES, $config['charset'] );
		$title = str_replace("&amp;","&", $title );

		$entries .= "<tr><td>{$itemdate} - ";

		if ( $row['fixed'] ) $entries .= "<span class=\"badge badge-red\">{$lang['edit_fix']}</span>&nbsp;&nbsp;";
		if ( $row['votes'] ) $entries .= "<i class=\"fa fa-bar-chart\"></i>&nbsp;&nbsp;";
		if ( $config['allow_alt_url'] ) {
			if ( $config['seo_type'] == 1 OR $config['seo_type'] == 2 ) {
				if ( intval( $row['category'] ) and $config['seo_type'] == 2 ) {
					$full_link = $config['http_home_url'] . get_url( intval( $row['category'] ) ) . "/" . $row['id'] . "-" . $row['alt_name'] . ".html";
				} else {
					$full_link = $config['http_home_url'] . $row['id'] . "-" . $row['alt_name'] . ".html";
				}
			} else {
				$full_link = $config['http_home_url'] . date( 'Y/m/d/', strtotime( $row['date'] ) ) . $row['alt_name'] . ".html";
			}
		} else {
			$full_link = $config['http_home_url'] . "index.php?newsid=" . $row['id'];
		}

		$comm_link = $row['comm_num'];

		$entries .= "<a title='{$lang['edit_act']}' href=\"{$PHP_SELF}?mod=editnews&action=editnews&id={$row['id']}\">{$title}</a>
		<td style=\"text-align: center\"><a data-original-title=\"{$lang['st_views']}\" class=\"status-info tip\" href=\"{$full_link}\" target=\"_blank\">{$row['news_read']}</a></td><td align=\"center\">" . $comm_link;
		$entries .= "</td><td style=\"text-align: center\">";
		if ( $row['approve'] ) $erlaub = "<span class=\"btn btn-sm btn-success\"><i class=\"fa fa-check\"></i></span>";
		else $erlaub = "<span class=\"tn btn-sm btn-error\"><b><i class=\"fa fa-exclamation\"></i></b></span>";
		$entries .= $erlaub;

		// Kırık link bilgileri
		$lc_url = $PHP_SELF . "?mod=linkchecker&action=view&id=" . $row['id'];
		$lc_link = <<<HTML
<a href="{$lc_url}" class="btn btn-sm btn-warning"><b>{$lc['count']}</b></a>
HTML;

		$entries .= "<td style=\"text-align: center\">{$lc_link}</td>";

		$entries .= "<td style=\"text-align: center\">";
		if ( ! $row['category'] ) $my_cat = "---";
		else {
			$my_cat = array ();
			$cat_list = explode( ',', $row['category'] );
			foreach ( $cat_list as $element ) {
				if ( $element ) $my_cat[] = $cat[$element];
			}
			$my_cat = implode( ',<br />', $my_cat );
		}

		$entries .= "{$my_cat}<td style=\"text-align: center;\"><a class=\"btn btn-sm btn-gray\" onclick=\"LCNotify( '" . $lc['id'] . "' ); return false;\">" . $row['autor'] . "</a>
			<td style=\"text-align: center\"><input name=\"selected_news[]\" value=\"{$row['id']}\" type=\"checkbox\">
		 </tr>";

		$entries_showed ++;

		if ( $i >= $news_per_page + $start_from ) {
			break;
		}
	}


	// End prelisting
	$result_count = $db->super_query( "SELECT COUNT(*) as count FROM " . PREFIX . "_post" . $where );
	$all_count_news = $result_count['count'];

	///////////////////////////////////////////

	if ( $entries_showed == 0 ) {

		echo <<<HTML
<div class="panel panel-default">
	<div class="panel-heading">
		<b>{$lang['lc_3']}</b>
		<div class="heading-elements">
			<ul class="icons-list">
				<li><a href="{$PHP_SELF}?mod=linkchecker&amp;action=settings"><i class="fa fa-wrench position-left"></i> {$lang['lc_15']}</a></li>
			</ul>
		</div>
	</div>
	<div class="box-content">
		<div class="row box-section" style="display: table;min-height:100px;">
			<div class="col-md-12 text-center" style="display: table-cell;vertical-align:middle;">{$lang['edit_nonews']}</div>
		</div>
	</div>
</div>
HTML;

	} else {

		echo <<<HTML
<script type="text/javascript">
function ckeck_uncheck_all() {
	var frm = document.editnews;
	for (var i=0;i<frm.elements.length;i++) {
		var elmnt = frm.elements[i];
		if (elmnt.type=='checkbox') {
			if (frm.master_box.checked == true){ elmnt.checked=false; }
			else{ elmnt.checked=true; }
		}
	}
	if (frm.master_box.checked == true){ frm.master_box.checked = false; }
	else{ frm.master_box.checked = true; }
}
function LCNotify( id ) {
	$.ajax( {
		type :'post',
		url  :'engine/ajax/linkchecker.ajax.php',
		data : { lc_action: 'notify', lc_id: id },
		beforeSend: function( ) {
			ShowLoading();
		}, complete: function( ) {
			HideLoading();
		}, success: function( result ) {
			DLEalert( result, '{$lang['lc_26']}' );
		}
	});
}
</script>

<form action="" method="post" name="editnews">
	<input type="hidden" name="mod" value="massactions">
	<input type="hidden" name="user_hash" value="{$dle_login_hash}" />
	<div class="panel panel-default">
		<div class="panel-heading">
			<b>{$lang['lc_3']}</b>
			<div class="heading-elements">
				<ul class="icons-list">
					<li><a href="{$PHP_SELF}?mod=linkchecker&amp;action=settings"><i class="fa fa-wrench position-left"></i> {$lang['lc_15']}</a></li>
				</ul>
			</div>
		</div>
		<div class="box-content">
			<table class="table table-normal table-striped">
				<thead>
					<tr>
						<td>{$lang['lc_4']}</td>
						<td style="width: 60px"><i class="fa fa-eye tip" data-original-title="{$lang['st_views']}"></i></td>
						<td style="width: 60px"><i class="fa fa-comment tip" data-original-title="{$lang['edit_com']}"></i></td>
						<td style="width: 60px">{$lang['edit_approve']}</td>
						<!-- Kırık link bilgisi + kontrol sayısı -->
						<td style="width: 60px"><i class="fa fa-link tip" data-original-title="{$lang['lc_5']}"></i></td>
						<!-- Kırık link bilgisi + kontrol sayısı -->
						<td>{$lang['edit_cl']}</td>
						<td style="width: 100px; text-align: center;">{$lang['edit_autor']}</td>
						<td style="width: 40px"><input type="checkbox" name="master_box" title="{$lang['edit_selall']}" onclick="javascript:ckeck_uncheck_all();"></td>
					</tr>
				</thead>
				<tbody>
					{$entries}
				</tbody>
			</table>
		</div>
		<div class="box-footer padded">
			<div class="pull-left">
HTML;

		// pagination
		$npp_nav = "";
		if ( $all_count_news > $news_per_page ) {
			if ( $start_from > 0 ) {
				$previous = $start_from - $news_per_page;
				$npp_nav .= "<li><a onclick=\"javascript:search_submit($previous); return(false);\" href=\"#\" title=\"{$lang['edit_prev']}\"><i class=\"fa fa-backward\"></i></a></li>";
			}
			$enpages_count = @ceil( $all_count_news / $news_per_page );
			$enpages_start_from = 0;
			$enpages = "";
			if ( $enpages_count <= 10 ) {
				for($j = 1; $j <= $enpages_count; $j ++) {
					if ( $enpages_start_from != $start_from ) {
						$enpages .= "<li><a onclick=\"javascript:search_submit($enpages_start_from); return(false);\" href=\"#\">$j</a></li>";
					} else {
						$enpages .= "<li class=\"active\"><span>$j</span></li>";
					}
					$enpages_start_from += $news_per_page;
				}
				$npp_nav .= $enpages;
			} else {
				$start = 1;
				$end = 10;
				if ( $start_from > 0 ) {
					if ( ($start_from / $news_per_page) > 4 ) {
						$start = @ceil( $start_from / $news_per_page ) - 3;
						$end = $start + 9;
						if ( $end > $enpages_count ) {
							$start = $enpages_count - 10;
							$end = $enpages_count - 1;
						}
						$enpages_start_from = ($start - 1) * $news_per_page;
					}
				}
				if ( $start > 2 ) {
					$enpages .= "<li><a onclick=\"javascript:search_submit(0); return(false);\" href=\"#\">1</a></li> <li><span>...</span></li>";
				}
				for($j = $start; $j <= $end; $j ++) {
					if ( $enpages_start_from != $start_from ) {
						$enpages .= "<li><a onclick=\"javascript:search_submit($enpages_start_from); return(false);\" href=\"#\">$j</a></li>";
					} else {
						$enpages .= "<li class=\"active\"><span>$j</span></li>";
					}
					$enpages_start_from += $news_per_page;
				}
				$enpages_start_from = ($enpages_count - 1) * $news_per_page;
				$enpages .= "<li><span>...</span></li><li><a onclick=\"javascript:search_submit($enpages_start_from); return(false);\" href=\"#\">$enpages_count</a></li>";
				$npp_nav .= $enpages;
			}
			if ( $all_count_news > $i ) {
				$how_next = $all_count_news - $i;
				if ( $how_next > $news_per_page ) {
					$how_next = $news_per_page;
				}
				$npp_nav .= "<li><a onclick=\"javascript:search_submit($i); return(false);\" href=\"#\" title=\"{$lang['edit_next']}\"><i class=\"fa fa-forward\"></i></a></li>";
			}
			echo "<ul class=\"pagination pagination-sm\">" . $npp_nav."</ul>";
		}
// pagination

			echo <<<HTML
			</div>
			<div class="panel-footer">
				<div class="pull-right">
					<select name="action" class="uniform">
						<option value="">{$lang['edit_selact']}</option>
						<option value="mass_link_discard">{$lang['lc_32']} {$lang['lc_0']}</option>
						<option value="mass_move_to_cat">{$lang['lc_33']} {$lang['edit_selcat']}</option>
						<option value="mass_approve">{$lang['lc_33']} {$lang['mass_edit_app']}</option>
						<option value="mass_not_approve">{$lang['lc_33']} {$lang['mass_edit_notapp']}</option>
						<option value="mass_fixed">{$lang['lc_33']} {$lang['mass_edit_fix']}</option>
						<option value="mass_not_fixed">{$lang['lc_33']} {$lang['mass_edit_notfix']}</option>
						<option value="mass_main">{$lang['lc_33']} {$lang['mass_edit_main']}</option>
						<option value="mass_not_main">{$lang['lc_33']} {$lang['mass_edit_notmain']}</option>
						<option value="mass_clear_count">{$lang['lc_33']} {$lang['mass_clear_count']}</option>
						<option value="mass_delete">{$lang['lc_33']} {$lang['edit_seldel']}</option>
					</select>&nbsp;<input class="btn btn-success" type="submit" value="{$lang['b_start']}">
				</div>
			</div>
		</div>
	</div>
</form>
HTML;

	}

	echofooter();


} else if ( $action == "view" ) {

	if ( ! isset( $_REQUEST['id'] ) ) { msg( "error", $lang['lc_7'], $lang['lc_8'] ); }

	$id = $_REQUEST['id'];

	echoheader( "<i class=\"fa fa-link\"></i> " . $lang['lc_1'], $lang['lc_2'] );

	echo <<<HTML
<script>
function LCDelete( id ) {
	$.ajax( {
		type :'post',
		url  :'engine/ajax/linkchecker.ajax.php',
		data : { lc_action: 'del', lc_id: id },
		beforeSend: function( ) {
			ShowLoading();
		}, complete: function( ) {
			HideLoading();
		}, success: function( result ) {
			$( "tr#lcrow_" + id ).fadeOut();
			//DLEalert( result, '{$lang['lc_26']}' );
		}
	});
}
</script>
<div class="panel panel-default">
	<div class="panel-heading">
		<b>{$lang['lc_3']}</b>
	</div>
	<div class="box-content">
		<table class="table table-normal table-hover">
			<thead>
				<tr>
					<td>{$lang['lc_9']}</td>
					<td>{$lang['lc_10']}</td>
					<td>{$lang['lc_11']}</td>
					<td>{$lang['lc_12']}</td>
					<td>{$lang['lc_13']}</td>
				</tr>
			</thead>
			<tbody>
HTML;

	$db->query( "SELECT * FROM " . PREFIX . "_linkchecker WHERE news_id = '{$id}'");
	while( $row = $db->get_row( ) ) {
		$date = date( "d.m.Y - H:i:s", $row['cdate'] );
		echo <<< HTML
				<tr id="lcrow_{$row['id']}">
					<td>{$row['xfname']}</td>
					<td>{$row['xfvalue']}</td>
					<td align="center">{$date}</td>
					<td align="center">{$row['nfound']}</td>
					<td align="center"><input class="btn btn-danger btn-sm" onclick="LCDelete('{$row['id']}'); return False;" type="button" value="{$lang['lc_14']}"></td>
				</tr>
HTML;
	}

	echo <<<HTML
			</tbody>
		</table>
	</div>
</div>
HTML;

	echofooter();


} else if ( $action == "settings" ) {

	echoheader( "<i class=\"fa fa-link\"></i> " . $lang['lc_1'], $lang['lc_2'] );

	$xfs = xfieldsload();

	$lset['pm_text'] = str_replace( "__EOL__", "\n", $lset['pm_text'] );
	$lset['video_part_rules'] = str_replace( "__EOL__", "\n", $lset['video_part_rules'] );
	$lset['data_list_rules'] = str_replace( "__EOL__", "\n", $lset['data_list_rules'] );

	$vp_active_html = makeDropDown( array( "0" => $lang['lc_40'], "1" => $lang['lc_39'] ), "save[video_part_active]", $lset['video_part_active'], "vp_active" );
	$dl_active_html = makeDropDown( array( "0" => $lang['lc_40'], "1" => $lang['lc_39'] ), "save[data_list_active]", $lset['data_list_active'], "dl_active" );

	$dl_field_options = "";
	if ( file_exists( ENGINE_DIR . '/data/datalist.conf.php' ) ) {
		require_once ENGINE_DIR . '/data/datalist.conf.php';
		$dl_fields = array_keys( $data_list_conf );
		foreach( $dl_fields as $dl_field ) {
			$dl_field_options .= "<option value=\"" . $dl_field . "\">";
		}
	}

	echo <<<HTML
<script>
function LCSaveSettings() {
	var formData1 = $("form#settings_form").serialize();
	$.ajax( {
		type :'post',
		url  :'engine/ajax/linkchecker.ajax.php',
		data :formData1,
		beforeSend: function( ) {
			ShowLoading();
		}, complete: function( ) {
			HideLoading();
		}, success: function( result ) {
			DLEalert( result, '{$lang['lc_26']}' );
		}
	});
}
$(document).ready( function() {
	$("#control1").change( function() {
		var sel = $(this).val();
		if ( sel == "1" ) {
			$(this).parents('td').find("div.c2_div").fadeIn();
			$(this).parents('td').find("div.c1_div, span.select").fadeIn();
		} else {
			$(this).parents('td').find("div.c1_div, span.select").fadeOut();
		}
	});
	$("#control2").change( function() {
		var sel = $(this).val();
		if ( sel == "0" ) {
			$(this).parents('td').find("div.c2_div").fadeIn();
		} else {
			$(this).parents('td').find("div.c2_div").fadeOut();
		}
	});
	/*
	$("#vp_active").change( function() {
		var sel = $(this).val();
		if ( sel == "0" ) {
			$("#vp_action").fadeOut();
		} else {
			$("#vp_action").fadeIn();
		}
	})
	*/
});
</script>

<form action="" id="settings_form" method="post" class="systemsettings">
<div class="panel panel-default">
	<div class="panel-heading">
		<b>{$lang['lc_16']}</b>
		<div class="heading-elements">
			<ul class="icons-list">
				<li><a href="{$PHP_SELF}?mod=linkchecker"><i class="fa fa-home position-left"></i> {$lang['lc_36']}</a></li>
			</ul>
		</div>
	</div>
	<div class="box-content">
		<div class="row box-section">
			<table class="table">
				<tr>
					<td class="col-xs-6 col-sm-6 col-md-7">
						<h6 class="media-heading text-semibold">{$lang['lc_30']}</h6>
						<span class="text-muted text-size-small hidden-xs">{$lang['lc_31']}</span>
					</td>
					<td class="col-xs-6 col-sm-6 col-md-5" id="{$xf['0']}_td">
						<input style="width: 90%;" class="form-control" name="save[pm_title]" value="{$lset['pm_title']}" type="text"><br /><br />
						<textarea name="save[pm_text]" class="form-control" style="width: 100%; height: 80px;">{$lset['pm_text']}</textarea>
					</td>
				</tr>


				<tr>
					<td class="col-xs-6 col-sm-6 col-md-7">
						<h6 class="media-heading text-semibold">{$lang['lc_44']}</h6>
						<span class="text-muted text-size-small hidden-xs">{$lang['lc_45']}</span>
					</td>
					<td class="col-xs-6 col-sm-6 col-md-5">
						<input style="width: 90%;" class="form-control" name="save[cron_check_link]" value="{$lset['cron_check_link']}" type="number">
					</td>
				</tr>
				<tr>
					<td class="col-xs-6 col-sm-6 col-md-7">
						<h6 class="media-heading text-semibold">{$lang['lc_46']}</h6>
						<span class="text-muted text-size-small hidden-xs">{$lang['lc_47']}</span>
					</td>
					<td class="col-xs-6 col-sm-6 col-md-5">
						<input style="width: 90%;" class="form-control" name="save[cron_check_timeout]" value="{$lset['cron_check_timeout']}" type="number">
					</td>
				</tr>

				<tr>
					<td class="col-xs-6 col-sm-6 col-md-7">
						<h6 class="media-heading text-semibold">{$lang['lc_37']}</h6>
						<span class="text-muted text-size-small hidden-xs">
							{$lang['lc_38']}
							<br />
							<br />
							{$lang['lc_19']}
						</span>
					</td>
					<td class="col-xs-6 col-sm-6 col-md-5" id="videopart_td">
						{$vp_active_html}
						<br />
						<br />
						<textarea name="save[video_part_rules]" class="form-control" style="width: 100%; height: 50px;">{$lset['video_part_rules']}</textarea>
					</td>
				</tr>
				<tr>
					<td class="col-xs-6 col-sm-6 col-md-7">
						<h6 class="media-heading text-semibold">{$lang['lc_48']}</h6>
						<span class="text-muted text-size-small hidden-xs">
							{$lang['lc_49']}
							<br />
							<br />
							{$lang['lc_19']}
						</span>
					</td>
					<td class="col-xs-6 col-sm-6 col-md-5" id="datalist_td">
						{$dl_active_html}
						<br />
						<br />
						{$lang['lc_50']}
						<br />
						<br />
						<input style="width: 90%;" class="form-control" list="data_list_fields" name="save[data_list_field]" placeholder="{$lang['lc_43']}" value="{$lset['data_list_field']}" type="text"><br />
						<datalist id="data_list_fields">
							{$dl_field_options}
						</datalist>
						<br />
						<textarea name="save[data_list_rules]" class="form-control" style="width: 100%; height: 50px;">{$lset['data_list_rules']}</textarea>
					</td>
				</tr>
HTML;

				$vp_xf_name = "";
				if ($lset['video_part_active'] && file_exists( ENGINE_DIR . '/data/videopart.conf.php' )) {
					require_once ENGINE_DIR . '/data/videopart.conf.php';
					if ($vset['mod_on']) {
						$vp_xf_name = $vset['xf_name'];
					}
				}

				foreach( $xfs as $xf ) {
					if ( $xf['0'] == $vp_xf_name ) continue;
					$n = $xf['0'] . "_";
					$s_text = str_replace( "__EOL__", "\n", $lset[ $n . "text" ] );
					$s_template = $lset[ $n . "template" ];
					$s_control = $lset[ $n . "control" ];
					$s_type = $lset[ $n . "type" ];
					$s_control_html = makeDropDown( array( "0" => $lang['lc_22'], "1" => $lang['lc_23'] ), "save[{$xf['0']}][control]", $s_control, "control1" );
					$s_type_html = makeDropDown( array( "-" => "-------", "0" => $lang['lc_24'], "1" => $lang['lc_25'] ), "save[{$xf['0']}][type]", $s_type, "control2" );
					$s_control_display = ( $lset[ $n . "control" ] == "1" ) ? "inline-block" : "none";
					$s_type_display = ( $lset[ $n . "type" ] == "0" ) ? "block" : "none";
					echo <<<HTML
					<tr>
						<td class="col-xs-6 col-sm-6 col-md-7">
							<h6 class="media-heading text-semibold">{$xf['0']} ( <i>{$xf['1']}</i> )</h6>
							<span class="text-muted text-size-small hidden-xs">
								<ul style="padding: 0; margin-left: 20px;">
									<li>{$lang['lc_21']}</li>
									<li>{$lang['lc_20']}</li>
								</ul>
								<br />{$lang['lc_19']}
							</span>
						</td>
						<td class="col-xs-6 col-sm-6 col-md-5" id="{$xf['0']}_td">
							{$s_control_html}<br /><br />
							<span class="select" style="display: {$s_control_display}">{$s_type_html}</span>
							<div style="clear: both"></div>
							<div class="c1_div" style="width: 100%; margin-top: -5px; display: {$s_control_display}">
								<div class="c2_div" style="display: {$s_type_display}; margin-bottom: -15px">
									<br />
									<input style="width: 90%;" class="form-control" name="save[{$xf['0']}][template]" value="{$s_template}" type="text">
									<span title="" data-original-title="" class="btn btn-sm btn-info" data-rel="popover" data-trigger="hover" data-placement="left" data-content="{$lang['lc_18']}">?</span>
								</div>
								<br /><br />
								<textarea name="save[{$xf['0']}][text]" class="form-control" style="width: 100%; height: 50px;">{$s_text}</textarea>
							</div>
						</td>
					</tr>
HTML;
	}

	echo <<< HTML
			</table>
		</div>
	</div>
	<div class="panel-footer">
		<div class="pull-right">
			<input type="hidden" name="save[user_hash]" value="{$dle_login_hash}" />
			<button type="button" onclick="LCSaveSettings();" class="btn btn-success"><i class="fa fa-save"></i> {$lang['lc_17']}</button>
		</div>
	</div>
</div>
</form>
HTML;

	echofooter();
}

?>