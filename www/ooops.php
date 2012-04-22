<?
// The source code packaged with this file is Free Software, Copyright (C) 2005-2009 by
// Benjamí Villoslada <benjami at bitassa dot cat>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
//		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

include('config.php');
include(mnminclude.'html1.php');

$errn = $_GET{"e"};

// Check we must reconstruct an image in cache directory
$cache_dir = preg_quote($globals['base_url'] . $globals['cache_dir'], '/');
if (preg_match("/$cache_dir/", $_SERVER['REQUEST_URI'])) {
	$filename = basename(clean_input_string($_SERVER['REQUEST_URI']));
	$base_filename = preg_replace('/\..+$/', '', $filename);
	$parts = explode('-', $base_filename);
	switch ($parts[0]) {
		case "media_thumb":
			// Comments' and posts' thumnails
			if ($parts[1] != 'post' && $parts[1] != 'comment') break;
			$media = new Upload($parts[1], $parts[2], 0);
			if (! $media->read()) break;
			if ($media->create_thumb($globals['media_thumb_size'])) {
				header("HTTP/1.0 200 OK");
				header('Content-Type: image/jpeg');
				$media->thumb->output();
				die;
			}
			$errn = 404;
			break;
		case "thumb":
			// Links' thumbnails
			if (count($parts) == 2 && $parts[1] > 0) {
				$link = Link::from_db(intval($parts[1]));
				if ($link && ($pathname = $link->thumb_download())) {
					header("HTTP/1.0 200 OK");
					header('Content-Type: image/jpeg');
					readfile($pathname);
					die;
				}
			}
			$errn = 404;
			break;
		case "tmp_thumb":
			// Temporal filenames
			$name = preg_replace('/^tmp_thumb\-/', '', $filename);
			$path = Upload::get_cache_dir().'/tmp/';
			$pathname = $path.$name;
			$thumbname = "$path/$filename";

			if (! file_exists($pathname)) {
				syslog(LOG_INFO, "Meneame, ooops, couldn't find $pathname");
				$errn = 404;
				break;
			}
			require_once(mnminclude."simpleimage.php");
			$thumb = new SimpleImage();
			$thumb->load($pathname);
			$thumb->resize($globals['media_thumb_size'], $globals['media_thumb_size'], true);
			if (! $thumb->save($thumbname, -1)) {
				$errn = 503;
				break;
			}
			header("HTTP/1.0 200 OK");
			header("Content-Type: " . $thumb->mime());
			readfile($thumbname);
			die;

		default:
			// it's an avatar
			if (count($parts) == 3 && $parts[0] > 0 && $parts[2] > 0) {
				$_GET['id'] = $parts[0];
				$_GET['time'] = $parts[1];
				$_GET['size'] = $parts[2];
				require_once('backend/get_avatar.php');
				die;
			}
			$errn = 404;
	}
}

switch($errn) {
  case 400:
	$errp = _('petición desconocida');
	break;
  case 401:
	$errp = _('no autorizado');
	break;
  case 403:
	$errp = _('acceso prohibido');
	break;
  case 404:
	$errp = _('la página no existe');
	break;
  case 500:
  case 501:
  case 503:
	$errp = _('error de servidor');
	break;
  default:
	$errn = false;
	$errp = false;
}

do_error($errp, $errn, true);
