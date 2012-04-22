<?php
// The source code packaged with this file is Free Software, Copyright (C) 2007 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
//      http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

// This file is based on Wordpress' xmlrpc.php

include('config.php');
include(mnminclude.'IXR_Library.inc.php');
include(mnminclude.'ban.php');

// Some browser-embedded clients send cookies. We don't want them.
$_COOKIE = array();

header('Content-Type: text/xml; charset=UTF-8');

class Xmlrpc_server extends IXR_Server {
	function Xmlrpc_server() {
		$this->methods = array(
			// PingBack
			'pingback.ping' => 'this:pingback_ping',
			'demo.sayHello' => 'this:sayHello',
			'demo.addTwoNumbers' => 'this:addTwoNumbers'
		);
		$this->IXR_Server($this->methods);
	}

	function sayHello($args) {
		return 'Hello!';
	}

	function addTwoNumbers($args) {
		$number1 = $args[0];
		$number2 = $args[1];
		return $number1 + $number2;
	}

	/* PingBack functions
	 * specs on www.hixie.ch/specs/pingback/pingback
	 */

	/* pingback.ping gets a pingback and registers it */
	function pingback_ping($args) {
		global $db, $globals;

		$pagelinkedfrom = clean_input_string($args[0]);
		//$pagelinkedfrom = str_replace('&amp;', '&', $pagelinkedfrom);

		$pagelinkedto   = clean_input_string($args[1]);

		$title = '';

		$urlfrom = parse_url($pagelinkedfrom);
		$urltest = parse_url($pagelinkedto);

		if (!$urlfrom || !$urltest) {
			return new IXR_Error(0, 'Is there no link to us?');
		}

		if ($urltest['host'] != get_server_name()) {
			return new IXR_Error(0, 'Is there no link to us?');
		}
		$base_uri = preg_quote($globals['base_url'] . $globals['base_story_url'], '/');
		$uri = preg_replace("/^$base_uri/", '', $urltest[path]);


		if(check_ban($globals['user_ip'], 'ip')) {
			syslog(LOG_NOTICE, "Meneame: pingback, IP is banned (".$globals['user_ip']."): $pagelinkedfrom - $pagelinkedto");
	  		return new IXR_Error(33, 'IP is banned.');
		}

		// Antispam of sites like xxx.yyy-zzz.info/archives/xxx.php
		if (preg_match('/http:\/\/[a-z0-9]\.[a-z0-9]+-[^\/]+\.info\/archives\/.+\.php$/', $pagelinkedfrom)) {
	  		return new IXR_Error(33, 'Host not allowed.');
		}

		if(check_ban($urlfrom[host], 'hostname', false)) {
			syslog(LOG_NOTICE, "Meneame: pingback, site is banned (".$globals['user_ip']."): $pagelinkedfrom - $pagelinkedto");
	  		return new IXR_Error(33, 'Site is banned.');
		}

		$link = new Link;
		$link->uri= preg_replace('/#[\w\-\_]+$/', '', $uri);
		if( empty($uri) || !$link->read('uri') ) {
			syslog(LOG_NOTICE, "Meneame: pingback, story does not exist: $pagelinkedto");
	  		return new IXR_Error(33, 'Story doesn\'t exist.');
		}

		if ($link->get_permalink() == $pagelinkedfrom) {
			syslog(LOG_NOTICE, "Meneame: pingback, points to the same post: $pagelinkedfrom - $pagelinkedto");
	  		return new IXR_Error(48, 'The pingback points to the same post.');
		}

		if ($link->date < (time() - 86400*15) && $urlfrom['host'] != get_server_name()) {
			syslog(LOG_NOTICE, "Meneame (".$globals['user_ip']."): pingback, story is too old: $pagelinkedto ($pagelinkedfrom )");
	  		return new IXR_Error(33, 'Story is too old for pingbacks.');
		}

		$trackres = new Trackback;
		$trackres->link_id=$link->id;
		$trackres->type='in';
		$trackres->link = $pagelinkedfrom;
		$trackres->url = $pagelinkedfrom;
		if ($trackres->abuse()) {
	  		return new IXR_Error(33, 'Don\'t send so many pings.');
		}

		// very stupid, but gives time to the 'from' server to publish !
		sleep(1);

		$dupe = $trackres->read();
		if ( $dupe ) {
			syslog(LOG_NOTICE, "Meneame: pingback, we already have a ping from that URI for this post: $pagelinkedfrom - $pagelinkedto");
	  		return new IXR_Error(48, 'The pingback has already been registered.');
		}

		// Let's check the remote site
		if(version_compare(phpversion(), '5.1.0') >= 0) {
			$contents=@file_get_contents($pagelinkedfrom,FALSE,NULL,0,100000);
		} else {
			$contents=@file_get_contents($pagelinkedfrom);
		}

		if(!$contents) {
			syslog(LOG_NOTICE, "Meneame: pingback, the provided URL does not seem to work: $pagelinkedfrom - $pagelinkedto");
	  		return new IXR_Error(16, 'The source URL does not exist.');
		}

		if(preg_match('/charset=([a-zA-Z0-9-_]+)/i', $contents, $matches)) {
			$this->encoding=trim($matches[1]);
			if(strcasecmp($this->encoding, 'utf-8') != 0) {
				$contents=iconv($this->encoding, 'UTF-8//IGNORE', $contents);
			}
		}

		// Check is links back to us
		$permalink=$link->get_permalink();
		$permalink_q=preg_quote($permalink,'/');
		$pattern="/<\s*a[^>]+href=[\"']".$permalink_q."[#\/0-9a-z\-]*[\"'][^>]*>/i";
		if(!preg_match($pattern,$contents)) {
			syslog(LOG_NOTICE, "Meneame: pingback, the provided URL does not have a link back to us: $pagelinkedfrom - $pagelinkedto");
			return new IXR_Error(17, 'The source URL does not contain a link to the target URL, and so cannot be used as a source.');
		}

		// Search Title
		if(preg_match('/<title[^<>]*>([^<>]*)<\/title>/si', $contents, $matches)) {
			$url_title=clean_text($matches[1]);
			if (mb_strlen($url_title) > 3) {
				$title=$url_title;
			}
		}
		if (empty( $title )) {
			syslog(LOG_NOTICE, "Meneame: pingback, cannot find a title on that page: $pagelinkedfrom - $pagelinkedto");
			return new IXR_Error(32, 'We cannot find a title on that page.');
		}
		$title = (mb_strlen($title) > 120) ? mb_substr($title, 0, 120) . '...' : $title;
		$trackres->title=$title;
		$trackres->status='ok';
		$trackres->store();
		syslog(LOG_NOTICE, "Meneame: pingback ok: $pagelinkedfrom - $pagelinkedto");
		return "Pingback from registered. Keep the web talking! :-)";
	}
}

$xmlrpc_server = new Xmlrpc_server();

?>
