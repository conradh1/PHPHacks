<?php
/**
 * This is the home start page that is loading after a user successfully logs in
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg
 * @since Beta version 2010
 */
require_once ("session.php");
define('TITLE','PSI News');
$PSIHelp = $PSISession->getHelpObject();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Main Page" />
    <meta name="keywords" content="Pan-STARRS Science Interface Web SQL" />
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <title><?= TITLE ?></title>
    <link rel="stylesheet" href="css/default.css" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
    <?php require_once ("menubar_header.php"); ?>
</head>
<body>
<script type="text/javascript">
jQuery(document).ready(function() {
      $("#news_wiki").load('load_url.php?url=<?=urlencode($PSISession->getNewsWikiURL())?> .wikipage', function(response, status, xhr) {
	if (status == "error") {
	  var msg = "Wiki News page could not be loaded.  Might be possible cross link conflict or bad url. Check <?=$PSISession->getNewsWikiURL()?>";
	  $("#error_wiki").html(msg + xhr.status + " " + xhr.statusText);
	}
      });
});
</script>
<?php

require_once ("top.php");
require_once ("menubar.php");
$PSIHTMLGoodies = new PSIHTMLGoodiesClass();

?>
<div id="main">
    <div class="content">
	<h2 style="text-align: center">Welcome <?= $PSISession->getUserID() ?> to the Pan-STARRS Science Interface! </h2>
        <h3 style="text-align: center">Click the <?=$PSIHelp->getWikiURL('PSI-MainHelp')?>&nbsp;icon if you need help.</h3>
	<div id="news_wiki" style="text-align: left; padding-left: 20px;"></div>
    </div>
    <!-- End Content -->
</div>
<!-- End Main -->
<br/>
<?php require ("bottom.php"); ?>
</body>
</html>
