<?
// The source code packaged with this file is Free Software, Copyright (C) 2005 by
// Ricardo Galli <gallir at uib dot es>.
// It's licensed under the AFFERO GENERAL PUBLIC LICENSE unless stated otherwise.
// You can get copies of the licenses here:
// 		http://www.affero.org/oagpl.html
// AFFERO GENERAL PUBLIC LICENSE is also included in the file called "COPYING".

include('config.php');
include(mnminclude.'html1-mobile.php');
include(mnminclude.'linkmobile.php');

$page_size = 20;
$offset=(get_current_page()-1)*$page_size;
$globals['ads'] = true;

do_header(_('noticias pendientes') . ' | ' . _('divúlgame mobile'));
echo "<script type=\"text/javascript\"><!--\n"; 
echo "  // XHTML should not attempt to parse these strings, declare them CDATA.\n"; 
echo "  /* <![CDATA[ */\n"; 
echo "  window.googleAfmcRequest = {\n"; 
echo "    client: 'ca-mb-pub-0158894246177946',\n"; 
echo "    format: '320x50_mb',\n"; 
echo "    output: 'html',\n"; 
echo "    slotname: '0302070500',\n"; 
echo "  };\n"; 
echo "  /* ]]> */\n"; 
echo "//--></script>\n"; 
echo "<script type=\"text/javascript\"    src=\"http://pagead2.googlesyndication.com/pagead/show_afmc_ads.js\"></script>\n";
do_tabs("main","shakeit");

echo '<div id="newswrap">'."\n";

$rows = Link::count('queued');
$links = $db->object_iterator("SELECT".Link::SQL."INNER JOIN (SELECT link_id FROM links WHERE link_status='queued' ORDER BY link_date DESC LIMIT $offset,$page_size) as id USING (link_id)", "LinkMobile");
if ($links) {
	foreach($links as $link) {
		$link->print_summary();
	}
}
do_pages($rows, $page_size);
echo '</div>'."\n";

do_footer();

?>
