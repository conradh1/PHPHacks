<?php
/**
 * This is the page that loads the IPP status page
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg
 * @since Beta version 2010
 */
require_once ("session.php");
define('TITLE','Postage Stamp Server Status Page');
$PSIHelp = $PSISession->getHelpObject();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Postage Stamp Status"/>
    <meta name="keywords" content="Pan-STARRS Science Interface Postage Stamp IPP Status Page" />
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <title><?= TITLE ?></title>
    <link rel="stylesheet" href="css/default.css" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
    <?php require_once ("menubar_header.php"); ?>
</head>
<body>
<?php

require_once ("top.php");
require_once ("menubar.php");
$PSIHTMLGoodies = new PSIHTMLGoodiesClass();

?>
<div id="main">
    <div class="content">
	<h2 style="text-align: center"><?=$PSIHelp->getWikiURL('PSI-PostageStampRequestList')?>&nbsp;<?= TITLE ?></h2>
	<div id="postage_stamp_status" style="text-align: left; padding-left: 20px;">
<?php

# Get content from IPP Postage Stamp Status Page
$statusPage = file_get_contents($PSISession->getPostageStampStatusURL());
$noticePage = file_get_contents($PSISession->getPostageStampNoticeURL());
print $noticePage;
print $statusPage;
?>

	</div>
    </div>
    <!-- End Content -->
</div>
<!-- End Main -->
<br/>
<?php require ("bottom.php"); ?>
</body>
</html>
