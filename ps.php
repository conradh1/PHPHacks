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
require_once ("MyDBClass.php");
require_once ("PSIHTMLGoodiesClass.php");
//define('TITLE','PSI Postage Stamp Job List');
define('TITLE', 'PS Job List');

// Globals for handling queries and sessions
global $PSISession;
global $PostageStamp;
global $MyDB;
global $PSIHTMLGoodies;
global $PSPSSchema;

global $myDBTable;
global $myDBColumnList;

$PSIHTMLGoodies = new PSIHTMLGoodiesClass();
$MyDB = new MyDBClass( $PSISession );
$PSIHelp = $PSISession->getHelpObject();

// Determined by initQueryFormVariables
$providePreview = 0;

// Assign new PSPS Schema object if needed
if ( isset( $_SESSION["PSPSSchemaClass"] ) ) {
  $PSPSSchema = unserialize($_SESSION["PSPSSchemaClass"]);
}
else {
  // New class for PSPS Schema used for query builder
  $PSPSSchema = new PSPSSchemaClass( $PSISession );
  // Add to session
  $_SESSION["PSPSSchemaClass"] = serialize($PSPSSchema);
}

/*
// Assign the Query Handle objects
if ( isset( $_SESSION["PostageStampClass"] ) ) {
  $PostageStamp = unserialize( $_SESSION["PostageStampClass"] );
}
else {
  $PostageStamp= new PostageStampClass( $PSISession ); // Make new PSI Session Class
//}

//ALWAYS save the PostageStampClass Object
$_SESSION['PostageStampClass'] = serialize( $PostageStamp );
}
*/

$PostageStamp = new PostageStampClass( $PSISession );

$action = isset($_REQUEST['action']) ? trim( $_REQUEST['action'] ) : "";
$req_id = isset($_REQUEST['req_id']) ? trim( $_REQUEST['req_id'] ) : "";
$job_id = isset($_REQUEST['job_id']) ? trim( $_REQUEST['job_id'] ) : "";
$url = isset($_REQUEST['url']) ? trim( $_REQUEST['url'] ) : "";
$size = isset($_REQUEST['size']) ? trim( $_REQUEST['size'] ) : "";

?>

<?php

if ($action == "") {
    print "<center>";
    if ( $req_id != "" ) {
        print "<b>Job List for Request: $req_id</b>";
        print $PostageStamp->getJobStatusForRequest( $req_id );
    }
    else if ($job_id != "") {
        print "<b>File List for Job: $job_id</b>";
        print $PostageStamp->getFileListForJob( $job_id );
    }
    else if ($url != "") {
        getFile($url, $size);
    }
    print "</center>";
} else {
    if ( $req_id != "" ) {
        print $PostageStamp->setRequestState( $req_id, $action );
    }
    else if ($job_id != "") {
        print $PostageStamp->setJobState( $job_id, $action );
    }
}

/**
  * Show image in a popup window, display image name below the image.
  *
  * @param url The full url of the image. 
  *            The url base is encrypted, and will be decrypted in psimg.php.
  *            The filename part is stripped out and to be displayed under the image.
  * @param size The height value when show the image.
  * @return none. Displays the image in a popup window.
  */
function getFile($url, $size) {
    //print "<b>$url</b><br/>";
    $i = strrpos($url, "/");           // i is the position of the last "/" in url.
    $filename = substr($url, $i + 1);  // get filename part from url, to display at the bottom of image.
    //$url_base = substr($url, 0, $i); // url base, encrypted, will be decrypted in psimg.php.
    $size -= 10; // Height value of image, minum 10 to give space for displaying filename.

    // Display image in a popup window.
    $img = "<a href='#' onclick='javascript: var w=window.open(\"psimg.php?url=$url\", \"_psimg\", \"height=800,width=800,left=100,top=100,location=0,toolbar=0,menubar=0,status=0,scrollbars=1,resizable=1,titlebar=0\"); w.focus();'><img src='psimg.php?url=$url' height='$size' title='Image: $filename \nClick to show full size in a new browser.'/></a>";

    echo $img;
    print "<br/><font size='-1'>$filename</font>";
}

?>

