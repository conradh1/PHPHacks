<?php
/**
 * This script is needed for the following
 *
 * Take the QueryHandle instance from PHP Session, dies otherwise
 * Every page in PSI needs to include this!!!
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley, drchang@ifa.hawaii.edu
 * @since Beta version 2010
 */
session_start();
error_reporting (E_ALL | E_STRICT);

// All Objects declared in a session must be set here
require_once ("PSISessionClass.php");
require_once ("QueryHandleClass.php");
require_once ("PSPSSchemaClass.php");
require_once ("MOPSSchemaClass.php");
require_once ("PSIHTMLGoodiesClass.php");

global $PSISession; // @note its better if this is explicitly imported
global $PSPSSchema;

$sessionBad = 0;

// Check needed session classes
if ( !(isset($_SESSION["QueryHandleClass"]) && isset($_SESSION["PSISessionClass"] ) ) ) {
    $sessionBad = 1;
}
else {
    $PSISession = unserialize($_SESSION["PSISessionClass"]);
    // Check for current session
    if ( !$PSISession->isSessionCurrent() )
        $sessionBad = 1;
    $QueryHandle = unserialize($_SESSION["QueryHandleClass"]);
}

//Make sure these objects are not empty
if ( empty( $QueryHandle ) || empty( $PSISession ) )
    $sessionBad = 1;

if ( $sessionBad ) {
    // Redirect to index.php if no session is found.
    session_destroy();
    header ('Location: ' . getReferencePageURL());
    exit;
}

/**
* Returns the URL of the page they were last trying to reference to including the http header information
*
* @param none
* @return URL to forward to the login page.
*/
function getReferencePageURL() {

    $refPage = 'index.php';
    # We don't want the ref page to be logout.php
    if ( !preg_match("/logout.php$/i", $_SERVER['PHP_SELF']) )
      $refPage = urlencode( $_SERVER['PHP_SELF'] );

    $refURL = 'login.php?referencePage='.$refPage;

    //
    foreach ($_GET as $variable => $value)
      $refURL .=  '&'.urlencode( $variable ).'='.urlencode( $value );

    return $refURL;
}

?>
