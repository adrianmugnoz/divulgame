<?
// The Meneame source code is Free Software, Copyright (C) 2005-2009 by
// Ricardo Galli <gallir at gmail dot com> and Menéame Comunicacions S.L.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
//	  http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

include('config.php');
include(mnminclude.'html1.php');

meta_get_current();

$offset=(get_current_page()-1)*$page_size;
$globals['ads'] = true;


$rows = -1; // Don't show page numbers by default
$cat = $_REQUEST['category'];

$from = '';
switch ($globals['meta']) {
	case '_personal':
		$globals['tag_status'] = 'queued';
		$from_time = '"'.date("Y-m-d H:00:00", $globals['now'] - $globals['time_enabled_votes']).'"';
		$where = "link_date > $from_time and link_status='queued' and link_category in (".$globals['meta_categories'].") ";
		$order_by = "ORDER BY link_date DESC";
		$tab = 7;
		break;
	case '_friends':
		$globals['noindex'] = true;
		$from_time = '"'.date("Y-m-d H:00:00", $globals['now'] - $globals['time_enabled_votes']).'"';
		$from = ", friends";
		$where = "link_date >  $from_time and link_status='queued' and friend_type='manual' and friend_from = $current_user->user_id and friend_to=link_author and friend_value > 0";
		$order_by = "ORDER BY link_date DESC";
		$tab = 2;
		$globals['tag_status'] = 'queued';
		break;
	case '_popular':
		// Show  the hihgher karma first
		$globals['noindex'] = true;
		$from_time = '"'.date("Y-m-d H:00:00", $globals['now'] - 86400*4).'"';
		$where = "link_date > $from_time and link_status='queued' and link_karma > 10";
		$order_by = "ORDER BY link_karma DESC";
		$tab = 3;
		$globals['tag_status'] = 'queued';
		break;
	case '_discarded':
		// Show only discarded in four days
		$globals['noindex'] = true;
		$globals['ads'] = false;
		$from_time = '"'.date("Y-m-d H:00:00", $globals['now'] - 86400*4).'"';
		$where = "link_status in ('discard', 'abuse', 'autodiscard')";
		$order_by = " ORDER BY link_date DESC ";
		$tab = 5;
		$globals['tag_status'] = 'discard';
		$rows = Link::count('discard') + Link::count('autodiscard') + Link::count('abuse');
		break;
	case '_all':
	default:
		$globals['tag_status'] = 'queued';
		$order_by = "ORDER BY link_date DESC";
		if ($globals['meta_current'] > 0) {
			if ($cat) $rows = Link::count('queued', $cat);
			else $rows = Link::count('queued', $globals['meta_categories']);
			$where = "link_status='queued' and link_category in (".$globals['meta_categories'].") ";
			$tab = false;
		} else {
			$rows = Link::count('queued');
			$where = "link_status='queued'";
			$tab = 1;
		}
		break;
}

if($cat) {
	$where .= " AND link_category=$cat ";
}


do_header(_('noticias pendientes') . ' | ' . _('divúlgame'));
do_tabs("main","shakeit");
print_shakeit_tabs($tab);

do_mnu_categories_horizontal($_REQUEST['category']);

/*** SIDEBAR ****/
echo '<div id="sidebar">';
echo "<div class=\"sidemedia\">\n"; 
echo "<div class=\"sidetw\"><iframe src=\"http://platform.twitter.com/widgets/follow_button.html#_=1317896519462&amp;align=&amp;button=blue&amp;id=twitter_tweet_button_0&amp;lang=es&amp;link_color=&amp;screen_name=divulgame_net&amp;show_count=&amp;show_screen_name=&amp;text_color=\" allowtransparency=\"true\" frameborder=\"0\" scrolling=\"no\" class=\"twitter-follow-button\" style=\"width: 300px; height: 20px; \" title=\"\"></iframe>\n"; 
echo "<script src=\"http://platform.twitter.com/widgets.js\" type=\"text/javascript\"></script></div>\n"; 
echo "<div class=\"sideplus\"><div id=\"___plusone_0\" style=\"height: 20px; width: 90px; display: inline-block; text-indent: 0px; margin-top: 0px; margin-right: 0px; margin-bottom: 0px; margin-left: 0px; padding-top: 0px; padding-right: 0px; padding-bottom: 0px; padding-left: 0px; background-image: initial; background-attachment: initial; background-origin: initial; background-clip: initial; background-color: transparent; border-top-style: none; border-right-style: none; border-bottom-style: none; border-left-style: none; float: none; line-height: normal; font-size: 1px; vertical-align: baseline; background-position: initial initial; background-repeat: initial initial; \"><g:plusone size=\"medium\" href=\"http://www.divulgame.net\"></g:plusone></div> Recomienda Divúlgame</div>\n"; 
echo "<div class=\"sidefb\"><iframe src=\"//www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwww.facebook.com%2FDivulgame&amp;send=false&amp;layout=standard&amp;width=290&amp;show_faces=true&amp;action=like&amp;colorscheme=light&amp;font&amp;height=80\" scrolling=\"no\" frameborder=\"0\" style=\"border:none; overflow:hidden; width:290px; height:80px;\" allowtransparency=\"true\"></iframe></div>\n"; 
echo "</div>\n";
do_banner_right();
if ($globals['show_popular_queued']) do_best_queued();
do_last_blogs();
//do_best_comments();
do_categories_cloud('queued', 24);
do_vertical_tags('queued');
echo '</div>' . "\n";
/*** END SIDEBAR ***/


echo '<div id="newswrap">'."\n";

$links = $db->object_iterator("SELECT".Link::SQL."INNER JOIN (SELECT link_id FROM links $from WHERE $where $order_by LIMIT $offset,$page_size) as id USING (link_id)", "Link");
if ($links) {
	foreach($links as $link) {
		if ($link->votes == 0 && $link->author != $current_user->user_id) continue;
		if ($offset < 1000) {
			$link->print_summary('full', 16);
		} else {
			$link->print_summary('full');
		}
	}
}


do_pages($rows, $page_size);
echo '</div>'."\n";

do_footer_menu();
do_footer();

function print_shakeit_tabs($option=-1) {
	global $globals, $current_user, $db;

	$items = array();
	if ($current_user->has_personal) {
		$items[] = array('id' => 7, 'url' => 'shakeit.php', 'title' => _('personal'));
	}
	$items[] = array('id' => 1, 'url' => 'shakeit.php'.$globals['meta_skip'], 'title' => _('todas'));

	$metas = $db->get_results("SELECT SQL_CACHE category_id, category_name, category_uri FROM categories WHERE category_parent = 0 ORDER BY category_id ASC");
	if ($metas) {
		foreach ($metas as $meta) {
			$items[] = array(
				'id'  => 9999, /* fake number */
				'url' =>'shakeit.php?meta='.$meta->category_uri,
				'selected' => $meta->category_id == $globals['meta_current'],
				'title' => $meta->category_name
			);
		}
	}

	$items[] = array('id' => 3, 'url' => 'shakeit.php?meta=_popular', 'title' => _('candidatas'));

	if ($current_user->user_id > 0) {
		$items[] = array('id' => 2, 'url' => 'shakeit.php?meta=_friends', 'title' => _('amigos'));
	}

	
	// Print RSS teasers
	switch ($option) {
	case 1: // All, queued
		$feed = array("url" => "?status=queued", "title" => "");
		break;
	case 7: // Personalised, queued
		$feed = array("url" => "?status=queued&amp;personal=".$current_user->user_id, "title" => "");
		break;
	default:
		$feed = array("url" => "?status=queued&amp;meta=".$globals['meta_current'], "title" => "");
		break;
	}
	$vars = compact('items', 'option', 'feed');
	$vars['container_id']   = 'topcatlist';
	$vars['toggle_enabled'] = isset($_REQUEST['category']);
	return Haanga::Load('print_tabs.html', $vars);
}

?>
