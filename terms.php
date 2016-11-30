<?php
/**
 * This is page that dispalys the Pan-STARRS disclaimer for data usage.
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley
 * @since Beta version 2010
 */
require_once ("PSISessionClass.php");
require_once ("QueryHandleClass.php");
define('TITLE','PSI Pan-STARRS Terms and Conditions');
define('HOME','index.php');
error_reporting (E_ALL | E_STRICT);

global $PSISession;
session_start();
// Check the session if the uer is already logged in
checkCurrentSession();
$errorString;

// Determine whether the form has been submitted for acceptence or declined
// In theory this should be unreachable code
if ( isset ($_REQUEST['submitDecline']) ) {
    $PSISession->acceptTerms('N', $errorString);
    session_destroy();
    header ("Location: http://www.lsst.org");
} #if
if( isset ($_REQUEST['submitAccept']) ) {
    $PSISession->acceptTerms('Y', $errorString);
    $_SESSION["PSISessionClass"] = serialize( $PSISession ); // Serialze Objects in session
    $QueryHandle = new QueryHandleClass( $PSISession ); // Make new Query Handle Class
    $_SESSION["QueryHandleClass"] = serialize( $QueryHandle );
    header ("Location: index.php");
} #if
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science PSPS <?= TITLE ?>"/>
    <meta name="keywords" content="Pan-STARRS Science Web <?= TITLE ?>"/>
    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
</head>
<body>
<?php require ("top.php"); ?>
<div id="main">
  <div style="text-align: left; padding-left: 100px; padding-right: 100px;" >
  <form name="formDisclaimer" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
    <div style="text-align: center;">
      <h3>Terms and Conditions Relating to the use of PS1 Survey Data</h3>
    </div>
    <p>
	    The access to and use of data obtained by the PS1 surveys through
	    the support of the PS1 Science Consortium are governed by the terms
	    and condition described in the Policies of the PS1 Science
	    Consortium. <strong>Before being granted access to these data
	    through this portal all users must acknowledge they agree to abide
	     by the <a href="http://www.ifa.hawaii.edu/users/chambers/ps1sc/documents/PS1_Science_Consortium_Policies.pdf">
             terms and conditions of the polices in these PS1SC documents</a>.
	  </strong>
    </p>
    <p>
	  PS1 publications, refereed or not, must include the following statement as acknowledgement:
    </p>
    <p>
	    "The PS1 Surveys have been made possible through contributions of
	      the Institute for Astronomy at the University of Hawaii in Manoa,
	      the Pan-STARRS Project Office, the Max-Planck Society and its
	      participating institutes, the Max Planck Institute for Astronomy,
	      Heidelberg and the Max Planck Institute for Extraterrestrial
	      Physics, Garching, The Johns Hopkins University, the University of
	      Durham, the University of Edinburgh, the Queen's University
	      Belfast, the Harvard-Smithsonian Center for Astrophysics, and the
	      Los Cumbres Observatory Global Telescope Network, Incorporated."
    </p>
    <p>
	    Once you have acknowledged acceptence of the terms and conditions
	    you will be able to access the PS1 survey data via this web portal
	    or through other tools developed for the PS1SC that provide such
	    access.
    </p>
    <br/>
    <div style="text-align: center;">
    <p>
	    <input type="submit" name="submitAccept" value="I accept these terms and conditions"/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	    <input type="submit" name="submitDecline" value="I decline these terms and conditions"/>
    </p>
    </div>
  </form>
  <br/><br/>
  </div> <!-- End Content -->
  </div> <!-- End Main -->
<?php require ("bottom.php"); ?>
<?php
  /**
  * forwards to the index page if the session is already current
  *
  * @param none
  * @return none
  */
  function checkCurrentSession() {
    global $PSISession;
    if ( isset($_SESSION["PSISessionClass"]) ) {
      // Start env class with configuration variables
      $PSISession = unserialize($_SESSION["PSISessionClass"]);
      if ( $PSISession->isSessionCurrent() ) {
	print "<h3 >You have already accepted the terms in the Pan-STARRS disclaimer.  Forwarding back to main page.</h3>";
	print "<meta http-equiv='refresh' content='3;url=".HOME."' />";
	exit;
      }
    }
    else {
      print "<h3 >You must log in first before accessing this page. Forwarding back to login page.</h3>";
      print "<meta http-equiv='refresh' content='3;url=".HOME."' />";
      exit;
    }

  } //checkCurrentSession


?>

</body>
</html>
