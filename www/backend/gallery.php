<?
// The source code packaged with this file is Free Software, Copyright (C) 2010 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

include('../config.php');

switch ($_GET['type']) {
	case 'comment':
		$type_in = '("comment")';
		break;
	case 'post':
		$type_in = '("post")';
		$type_in = '("post")';
		break;
	case 'all':
	default:
		$type_in = '("comment", "post", "link")';
		break;

}

$user_id = intval($_GET['user']);
if ($user_id > 0) $user = "and user = $user_id";
else $user = '';

header('Content-Type: text/html; charset=utf-8');
$media = $db->get_results("select type, id, version, user_login as user from media, users where type in $type_in $user and version = 0 and user_id = media.user order by date desc limit 200");
if ($media) Haanga::Load("backend/gallery.html", compact('media'));

?>
