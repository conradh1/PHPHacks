<?php
/**
 * This page loads wiki news to be used on index.php
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg
 * @since Beta version 2010
 */

$url = $_REQUEST['url'];
$text = file_get_contents($url);

if ( empty( $text ) )
  print "<font color=\"red\">Error Loading New Wiki page. The URL: $url seems invalid.</font>";
else
  print $text;
?>

