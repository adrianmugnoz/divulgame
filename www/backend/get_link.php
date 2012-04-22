<?
// The source code packaged with this file is Free Software, Copyright (C) 2005 by
// Ricardo Galli <gallir at uib dot es> and
// Beldar <beldar.cat at gmail dot com>
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".
// The code below was made by Beldar <beldar at gmail dot com>
if (! defined('mnmpath')) {
	include_once('../config.php');
	header('Content-Type: text/html; charset=utf-8');
	header('Cache-Control: public, s-maxage=300');
}


if (empty($_GET['id'])) die;
$id = intval($_GET['id']);
$link = Link::from_db($id);
if(!$link) die;
$user_login = $db->get_var("select user_login from users where user_id = $link->author");
echo '<p>';
if ($link->avatar) {
	echo '<img class="avatar" src="'.get_avatar_url($link->author, $link->avatar, 40).'" width="40" height="40" alt="avatar"  style="float:left; margin: 0 5px 0 0;"/>';
}
echo '<strong>' . $link->title . '</strong><br/>';
echo '<strong>' . $user_login . '</strong><br/>';
echo htmlentities(txt_shorter(preg_replace('/^https*:\/\//', '', $link->url), 70)) . '<br/>';
echo $link->meta_name.', '.$link->category_name.'&nbsp;|&nbsp;karma:&nbsp;'. intval($link->karma). '&nbsp;|&nbsp;'._('negativos').':&nbsp;'. $link->negatives. '</p>';
echo '<p>' . $link->to_html($link->content) . '</p>';
?>
