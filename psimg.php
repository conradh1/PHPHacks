<?php
/**
 * This script displays an image from the given url, 
 * by reading image binary data and write to browser directly.
 * Called in ps.php.
 * 
 * GPL version 3 or any later version.
 * @copyleft 2013 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Thomas Chen
 * @since version 2013
 */
require_once ("EncryptClass.php");

$image_url = $_REQUEST['url']; 

// Split file name and base URL.
// file name is in clear text, but base URL is encrypted and needs to be decrypted.
$i = strrpos($image_url, "/");
$filename = substr($image_url, $i + 1);
$url_base = substr($image_url, 0, $i);

// Decrype url base, to get actual value of url base.
$enc = new EncryptClass();
$url_base = $enc->decryptUrl($url_base);

// Combine decrypted url base and file name to get full url of the image.
$image_url = "$url_base/$filename";

// output image.
$imginfo = getimagesize($image_url);
header("Content-type: $imginfo[mime]");
readfile($image_url);

?>
