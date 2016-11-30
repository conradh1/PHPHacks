<?php
/**
 * This is page that logins the PSI user else gets the login/password again
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley
 * @since Beta version 2010
 */
require_once ("QueryHandleClass.php");
require_once ("PSISessionClass.php");
define('TITLE','Login Page');
define('HOME','index.php');
error_reporting (E_ALL | E_STRICT);

global $PSISession;
global $QueryHandle;
global $errorString;
global $forwardURL;
global $PSIHelp;

session_start();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Login Page"/>
    <meta name="keywords" content="Pan-STARRS Science login Web SQL"/>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
</head>
<body onload="javascript: document.getElementById('userid').focus();">
<?php require ("top.php"); ?>
<div id="main">
<div style="text-align: center;">
<?php

// Check the session if the user is already logged in
checkCurrentSession();
$PSIHelp = $PSISession->getHelpObject();
$forwardURL = getForwardingPage();

if ( isset( $_POST['submitLogin'] ) && $_POST['submitLogin'] == 'Login' ) {

  // logged in, forward to main page.
  $userid   = isset($_POST['userid']) ? $_POST['userid'] : NULL;
  $password = isset($_POST['password']) ? $_POST['password'] : NULL;

  if ( $PSISession->login( $userid, $password, $errorString ) ) {
    $_SESSION["PSISessionClass"] = serialize( $PSISession ); // Serialze Objects in session

    $QueryHandle = new QueryHandleClass( $PSISession ); // Make new PSI Session Class
    $_SESSION["QueryHandleClass"] = serialize( $QueryHandle );

    print "<h3 > You have successfully logged in! </h3></br>Forwarding to $forwardURL";
    print "<meta http-equiv='refresh' content='0;url=".$forwardURL."' />";
    exit;
  }
  // Case they have logged in but have not read the disclaimer
  if ( preg_match('/Terms Error/i', $errorString) ) {
    // We need these again
    $PSISession->setUserID($userid);
    $PSISession->setTmpPass( $password );
    $_SESSION["PSISessionClass"] = serialize( $PSISession ); // Serialze Objects in session
    print "<meta http-equiv='refresh' content='0;url=terms.php' />";
    exit;
  }
} //if submitLogin
//
?>
  <form name="formLogin" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
    <table style="margin: 0 auto;">
	<tr>
	    <td align= "center" colspan="3">
		<img src="images/psi_logo.png" alt="PSI Logo" />
	    </td>
	</tr>
	<tr>
	    <td align= "center" colspan="3">
		<?=(!empty($errorString)) ? "<h3 class=\"outputerror\">$errorString</h3>" : '<h3>Welcome to the Pan-STARRS Science Interface.</h3>'?>
		<h3>Login Page</h3>
	    </td>
	</tr>
	<tr>
            <td rowspan="2" align="right"><?=$PSIHelp->getWikiURL('PSI-LogIn')?></td>
	    <td align="right">Name:&nbsp;</td>
	    <td><input type="text" id="userid" name="userid" size="20" style='width: 150px;'/></td>
	</tr>
	<tr>
	    <td align="right">Password:&nbsp;</td>
	    <td><input type="password" name="password" size="20" style='width: 150px;'/></td>
	</tr>
	<tr>
	    <td align="center" colspan="3">
	      <input type="hidden" name="forwardURL" value="<?=$forwardURL?>"/>
	      <input type="submit" name="submitLogin" value="Login"/>
	    </td>
	</tr>
    </table>
  </form>
</div> <!-- End Content -->
</div> <!-- End Main -->
<?php require ("disclaimer.php"); ?>
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
	print "<h3 >You are already logged in.  Forwarding back to main page.</h3>";
	print "<meta http-equiv='refresh' content='2;url=".HOME."' />";
	exit;
      }
    }
    //else
    $PSISession = new PSISessionClass();
  } //checkCurrentSession

  /**
  * Takes all the GET variables given by the last page they tried to reach within PSI
  *
  * @return the URL along with associated variables to the PSI page.
  */
  function getForwardingPage () {
    $forwardURL;
    // return the URL of the previous page the user
    // tried to load.
    if ( isset( $_REQUEST['referencePage'] ) ) {
      foreach ($_GET as $variable => $value) {
	if ( $variable == 'referencePage' )
	  $forwardURL = $value.'?';
        // Certain exceptions for intance they submit a query.
        else if ( $variable == 'submitQuery' && isset($value) ) {
	  continue;
        }
	else
	  $forwardURL .= '&'.urlencode( $variable ).'='.urlencode( $value );
      }
      // If there are no other variables besides the reference script, then get rid of the ? in the URL
      $forwardURL = preg_replace ("/\?$/", "", $forwardURL);
    }
    // This is the previous page but obtained from a hidden variable in the
    // login form.  If login is successful they are forwarded to this page.
    else if ( isset( $_POST['forwardURL'] ) )
      $forwardURL = $_POST['forwardURL'];
    else {
      $forwardURL = HOME; //Default is the index page.
    }
    return $forwardURL;
  } //getForwardingPage

?>

</body>
</html>
