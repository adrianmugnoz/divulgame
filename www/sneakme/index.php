<?
// The source code packaged with this file is Free Software, Copyright (C) 2005 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

include('../config.php');
include('common.php');




if (!isset($_REQUEST['id']) && !empty($_SERVER['PATH_INFO'])) {
	$argv = preg_split('/\/+/', $_SERVER['PATH_INFO'], 4, PREG_SPLIT_NO_EMPTY);
	$argv[0] = clean_input_string($argv[0]);
} else {
	$argv = preg_split('/\/+/', $_REQUEST['id'], 4, PREG_SPLIT_NO_EMPTY);
	$argv[0] = clean_input_string($argv[0]);
}

if ($argv[0] == _priv) {
	// Load priv.php
	include('priv.php');
	die;
}

include(mnminclude.'html1.php');
include(mnminclude.'favorites.php');

$globals['search_options'] = array('w' => 'posts');
if ($current_user->user_id > 0) {
	array_push($globals['extra_js'], 'jquery-form.pack.js');
}

$user=new User();

$min_date = date("Y-m-d H:00:00", time() - 192800); //  about 48 hours
$page_size = 50;
$offset=(get_current_page()-1)*$page_size;
$page_title = _('nótame') . ' | '._('menéame');
$view = false;
$globals['ads'] = true;

switch ($argv[0]) {
	case '_best':
		$tab_option = 2;
		$page_title = _('mejores notas') . ' | ' . _('menéame');
		$min_date = date("Y-m-d H:00:00", time() - 86400); //  about 24 hours
		$where = "post_date > '$min_date'";
		$order_by = "ORDER BY post_karma desc";
		$limit = "LIMIT $offset,$page_size";
		$rows = $db->get_var("SELECT count(*) FROM posts where post_date > '$min_date'");
		break;

	case '_geo':
		$tab_option = 3;
		$page_title = _('nótame') . ' geo | '._('menéame');
		require_once(mnminclude.'geo.php');
		if ($current_user->user_id > 0 && ($latlng = geo_latlng('user', $current_user->user_id))) {
			geo_init('onLoad', $latlng, 5);
		} else {
			geo_init('onLoad', false, 2);
		}
		break;

	case '':
	case '_all':
		$tab_option = 1;
		//$sql = "SELECT SQL_CACHE post_id FROM posts ORDER BY post_id desc limit $offset,$page_size";
		$where = "post_id > 0";
		$order_by = "ORDER BY post_id desc";
		$limit = "LIMIT $offset,$page_size";
		//$rows = $db->get_var("SELECT count(*) FROM posts");
		$rows = Post::count();
		$min_date = date("Y-m-d 00:00:00", time() - 86400*10);
		//$rows = $db->get_var("SELECT SQL_CACHE count(*) FROM posts where post_date > '$min_date'");
		$rss_option="sneakme_rss2.php";
		break;

	default:
		$tab_option = 4;
		if ( (is_numeric($argv[0]) && ($post_id = intval($argv[0])) > 0)
				|| (is_numeric($argv[1]) && ($post_id = intval($argv[1])) > 0)  ) {
			// Individual post
			$user->id = $db->get_var("select post_user_id from posts where post_id=$post_id");
			if(!$user->read()) {
				do_error(_('usuario no encontrado'), 404);
			}
			$page_title = sprintf(_('nota de %s'), $user->username) . " ($post_id)";
			$globals['search_options']['u'] = $user->username;
			$where = "post_id = $post_id";
			$order_by = "";
			$limit = "";
			$rows = 1;
		} else {
			// User is specified
			$user->username = $db->escape($argv[0]);
			if(!$user->read()) {
				do_error(_('usuario no encontrado'), 404);
			}
			switch($argv[1]) {
				case '_friends':
					$view = 1;
					$page_title = sprintf(_('amigos de %s'), $user->username);
					$from = ", friends";
					$where = "friend_type='manual' and friend_from = $user->id and friend_to=post_user_id and friend_value > 0";
					$order_by = "ORDER BY post_id desc";
					$limit = "LIMIT $offset,$page_size";
					$rows = $db->get_var("SELECT count(*) FROM posts, friends WHERE friend_type='manual' and friend_from = $user->id and friend_to=post_user_id and friend_value > 0");
					$rss_option="sneakme_rss2.php?friends_of=$user->id";
					break;

				case '_favorites':
					$view = 2;
					$page_title = sprintf(_('favoritas de %s'), $user->username);
					$ids = $db->get_col("SELECT favorite_link_id FROM favorites WHERE favorite_user_id=$user->id AND favorite_type='post' ORDER BY favorite_link_id DESC LIMIT $offset,$page_size");
					$from = "";
					$where = "post_id in (".implode(',', $ids).")";
					$order_by = "ORDER BY post_id desc";
					$limit = "";
					$rows = $db->get_var("SELECT count(*) FROM favorites WHERE favorite_user_id=$user->id AND favorite_type='post'");
					$rss_option="sneakme_rss2.php?favorites_of=$user->id";
					break;

				case '_conversation':
					$view = 3;
					$page_title = sprintf(_('conversación de %s'), $user->username);
					$ids = $db->get_col("SELECT distinct conversation_from FROM conversations WHERE conversation_user_to=$user->id and conversation_type='post' ORDER BY conversation_time desc LIMIT $offset,$page_size");
					$where = "post_id in (".implode(',', $ids).")";
					$from = "";
					$order_by = "ORDER BY post_id desc ";
					$limit = "";
					$rows =  $db->get_var("SELECT count(distinct(conversation_from)) FROM conversations, posts WHERE conversation_user_to=$user->id and conversation_type='post' and post_id = conversation_from ");
					$rss_option="sneakme_rss2.php?conversation_of=$user->id";
					break;

				default:
					$view = 0;
					$page_title = sprintf(_('notas de %s'), $user->username);
					$globals['search_options']['u'] = $user->username;
					$where = "post_user_id=$user->id";
					$order_by = "ORDER BY post_id desc";
					$limit = "LIMIT $offset,$page_size";
					$rows = $db->get_var("SELECT count(*) FROM posts WHERE post_user_id=$user->id");
					$rss_option="sneakme_rss2.php?user_id=$user->id";
			}
		}
}


do_header($page_title);
do_posts_tabs($tab_option, $user->username);
$post = new Post;

$conversation_extra = '';
if ($tab_option == 4) {
	if ($current_user->user_id == $user->id) {
		$conversation_extra = ' ['.Post::get_unread_conversations($user->id).']';
	}
	$options = array(
		_('todas') => post_get_base_url($user->username),
		_('amigos') => post_get_base_url("$user->username/_friends"),
		_('favoritos') => post_get_base_url("$user->username/_favorites"),
		_('conversación').$conversation_extra => post_get_base_url("$user->username/_conversation"),
		sprintf(_('debates con %s').'&nbsp;&rarr;', $user->username) =>
				$globals['base_url'] . "between.php?type=posts&amp;u1=$current_user->user_login&amp;u2=$user->username",
		sprintf(_('perfil de %s').'&nbsp;&rarr;', $user->username) => get_user_uri($user->username),

	);
}  elseif ($tab_option == 1 && $current_user->user_id > 0) {
	$conversation_extra = ' ['.Post::get_unread_conversations($user->id).']';

	$options = array(
		_('amigos') => post_get_base_url("$current_user->user_login/_friends"),
		_('favoritos') => post_get_base_url("$current_user->user_login/_favorites"),
		_('conversación').$conversation_extra => post_get_base_url("$current_user->user_login/_conversation"),
		_('últimas imágenes') => "javascript:fancybox_gallery('post');",
		_('debates').'&nbsp;&rarr;' => $globals['base_url'] . "between.php?type=posts&amp;u1=$current_user->user_login",
	);
} else $options = false;
do_post_subheader($options, $view, $rss_option);


/*** SIDEBAR ****/
echo '<div id="sidebar">';
do_banner_right();
//do_best_stories();
if ($rows > 20) {
	do_best_posts();
	do_best_comments();
	do_last_blogs();
}
do_banner_promotions();
echo '</div>' . "\n";
/*** END SIDEBAR ***/

echo '<div id="newswrap">'."\n";
do_pages($rows, $page_size);

echo '<div class="notes">';


if ($current_user->user_id > 0) {
	echo '<div id="addpost"></div>';
	echo '<ol class="comments-list"><li id="newpost"></li></ol>'."\n";
}

if ($argv[0] == '_geo') {
	echo '<div class="topheading"><h2>'._('notas de las últimas 24 horas').'</h2></div>';
	echo '<div id="map" style="width: 95%; height: 500px;margin:0 0 0 20px;"></div></div>';
?>
<script type="text/javascript">
var baseicon;
var geo_marker_mgr = null;

function onLoad(lat, lng, zoom, icon) {
	baseicon = new GIcon();
	baseicon.iconSize = new GSize(20, 25);
	baseicon.iconAnchor = new GPoint(10, 25);
	baseicon.infoWindowAnchor = new GPoint(10, 10);
	if (geo_basic_load(lat||18, lng||15, zoom||2)) {
		geo_map.addControl(new GLargeMapControl());
		geo_marker_mgr = new GMarkerManager(geo_map);
		geo_load_xml('post', '', 0, base_url+"img/geo/common/geo-newnotame01.png");
		GEvent.addListener(geo_map, 'click', function (overlay, point) {
			if (overlay && overlay.myId > 0) {
				GDownloadUrl(base_url+"geo/"+overlay.myType+".php?id="+overlay.myId, function(data, responseCode) {
				overlay.openInfoWindowHtml(data);
				});
			} //else if (point) geo_map.panTo(point);
		});
	}
}
</script>
<?
} else {
	$posts = $db->object_iterator("SELECT".Post::SQL."INNER JOIN (SELECT post_id FROM posts $from WHERE $where $order_by $limit) as id USING (post_id)", 'Post');
	//$posts = $db->object_iterator("SELECT".Post::SQL."$from WHERE $where $order_by $limit", 'Post');
	if ($posts) {
		$ids = array();
		echo '<ol class="comments-list">';
		$time_read = 0;
		foreach ($posts as $post) {
			if ( $post_id > 0 && $user->id > 0 && $user->id != $post->author) {
				echo '<li>'. _('Error: nota no existente') . '</li>';
			} else {
				echo '<li>';
				$post->print_summary();
				if ($post->date > $time_read) $time_read = $post->date;
				echo '</li>';
				if (! $post_id) $ids[] = $post->id;
			}
		}
		echo "</ol>\n";

		// Print "conversation" for a given note
		if ($post_id > 0) {
			$answers = $db->object_iterator("SELECT".Post::SQL.", conversations WHERE conversation_type='post' and conversation_to = $post_id and post_id = conversation_from ORDER BY conversation_from asc LIMIT 100", 'Post');

			if ($answers) {
				echo '<div style="padding-left: 40px; padding-top: 10px">';
				echo '<ol class="comments-list">';
				foreach ($answers as $answer) {
					echo '<li>';
					$answer->print_summary();
					echo '</li>';
					$ids[] = $answer->id;
				}
				echo "</ol>";
				echo '</div>'."\n";
			}
		}

		if ($current_user->user_id > 0) {
			Haanga::Load('get_total_answers_by_ids.html', array('type' => 'post', 'ids' => implode(',', $ids)));
		}

		// Update conversation time
		if ($view == 3 && $time_read > 0 && $user->id == $current_user->user_id) {
			Post::update_read_conversation($time_read);
		}
	}
	echo '</div>';
	do_pages($rows, $page_size);
}

echo '</div>';
if ($rows > 15) do_footer_menu();
do_footer();
exit(0);

?>
