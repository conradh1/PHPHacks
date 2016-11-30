<?php
/**
 * This script provides a form for reuquesting postage stamps
 * Once the form is completed properly, it provides a preview.
 *
 *
 * GPL version 3 or any later version.
 * @copyleft 2013 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Thomas Chen, Conrad Holmberg
 * @since Beta version 2013
 */
require_once ("session.php");
require_once ("PostageStampClass.php");
require_once ("PSPSSchemaClass.php");
define('TITLE','PSI Postage Stamp Releases Information');

// Globals for handling queries and sessions
global $PSISession;
global $PostageStamp;

$PSIHelp = $PSISession->getHelpObject();
$PostageStamp = new PostageStampClass( $PSISession );

if ( isset($_REQUEST['btnDoSubmit']) ) {
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Query Page"/>
    <meta name="keywords" content="Pan-STARRS Science Web Interface Astronomy short long schema query SQL"/>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
    <script type="text/javascript" src="javascript/psi_jquery.js"></script>
    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
    <?php require_once("menubar_header.php"); ?>
</head>
<body>
<?php require_once("top.php"); ?>
<?php require_once("menubar.php"); ?>
<div id="main">
<div style="text-align: center;">
<h2><?=$PSIHelp->getWikiURL('PSI-PostageStampRequestForm')?>&nbsp;<?=TITLE?></h2>

<center>

<?php
echo $PostageStamp->showReleaseInfoList();
?>

</center>

<br/>

</div>
<!-- End Content -->
</div>

<!-- End Main -->
<?php require ("bottom.php"); ?>
</body>
</html>
