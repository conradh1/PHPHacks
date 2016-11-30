<?php

/**
 * Submitted NEOs report page
 */

$DEBUG = FALSE; // debugging flag

$recordCounter = 0; // counter used for counting records
$defaultPageSize = 25; // default number of submissions to display
$Start = 0; // current starting record position
$End = 0; // current ending record position
$MinStart = 1; // starting submission record count
$MaxEnd = 250; // maximum submission record count
$ReportTime = 0; // time that a report was generated

$BenchmarkStart = microtime(TRUE);

require_once('session.php');
require_once('MopsViews.php');
require_once('MopsPsiAstro.php');

$MopsViews = new MopsViews();
$MopsPsiAstro = new MopsPsiAstro ();
$PSIHelp = $PSISession->getHelpObject();
$mopsHelpWiki; // used for links to help wikis

$totals = array();
$submissions = array();
$detections = array();

if ( isset($_SESSION["MopsViewNeoSubmissionTotals"])) {
    // use session data
    $totals = $_SESSION["MopsViewNeoSubmissionTotals"];
    $ReportTime = $_SESSION['MopsViewNeoReportTime'];

    // Retrieve report time from session below
} else {
    $TimingDebugStart = microtime(TRUE);

    $_SESSION["MopsViewNeoSubmissionTotals"] = $MopsViews->getNeoSubmissionTotals( $PSISession );

    $TimingDebugEnd = microtime(TRUE);
    if($DEBUG == TRUE) {
        error_log(sprintf("getNeoSubmissionTotals: %.2f s.", $TimingDebugEnd - $TimingDebugStart));
    }

    $totals = $_SESSION["MopsViewNeoSubmissionTotals"];
    $ReportTime = time();
    $_SESSION["MopsViewNeoReportTime"] = $ReportTime;
}

if( $_REQUEST['type'] == 'SubmittedNeos') {
    if ( isset($_SESSION["MopsViewNeoSubmissions"])) {
        // use session data
        $submissions = $_SESSION["MopsViewNeoSubmissions"];
    } else {
        $TimingDebugStart = microtime(TRUE);

        $_SESSION["MopsViewNeoSubmissions"] = $MopsViews->getNeoSubmissions( $PSISession );

        $TimingDebugEnd = microtime(TRUE);
        if ( $DEBUG == TRUE ) {
            error_log(sprintf("getNeoSubmissions: %.2f s.", $TimingDebugEnd - $TimingDebugStart));
        }

        $submissions = $_SESSION["MopsViewNeoSubmissions"];
        $_SESSION['MopsViewSubmittedNeosReportTime'] = $ReportTime; // store the report time
    }
    $mopsHelpWiki = $PSIHelp->getWikiURL('PSI-Mops250MostRecentPs1NeoSubmissions');
    define('TITLE', '250 most recent PS1 NEO submissions');
}

if ( $_REQUEST['type'] == 'DiscoveredNeos' ) {
    if ( isset($_SESSION["MopsViewNeoSubmissionsDiscoveriesToDate"])) {
        // use session data
        $submissions = $_SESSION["MopsViewNeoSubmissionsDiscoveriesToDate"];
    } else {
        $TimingDebugStart = microtime(TRUE);

        $_SESSION["MopsViewNeoSubmissionsDiscoveriesToDate"] = $MopsViews->getNeoDiscoveriesToDate ( $PSISession );

        $TimingDebugEnd = microtime(TRUE);
        if ( $DEBUG == TRUE ) {
            error_log(sprintf("getNeoDiscoveriesToDate: %.2f s.", $TimingDebugEnd - $TimingDebugStart));
        }

        $submissions = $_SESSION["MopsViewNeoSubmissionsDiscoveriesToDate"];
        $_SESSION['MopsViewDiscoveredNeosReportTime'] = $ReportTime; // store the report time
    }
    $mopsHelpWiki = $PSIHelp->getWikiURL('PSI-MopsPs1NeoDiscoveries');
    define('TITLE', 'PS1 NEO Discoveries');
}

getDetections($PSISession, $MopsViews, $_REQUEST['type']);

if( isset ( $_REQUEST['start'])){
    $Start = $_REQUEST['start'];
} else {
    $Start = 1;
}
if( isset ( $_REQUEST['end'])){
    $End = $_REQUEST['end'];
} else {
    $End = $defaultPageSize;
}

// Prevent paging past the last record.
if($End > $MaxEnd){
    $End = $MaxEnd;
    $Start = $End - $defaultPageSize + 1;
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface MOPS View"/>
    <meta name="keywords" content="Pan-STARRS Science Web Interface"/>
    <title><?= TITLE ?></title>
    <link href="css/mops-report.css" rel="stylesheet"/>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
<?php require_once("menubar_header.php"); ?>
</head>

<body>
<?php require ("top.php"); ?>
<?php require_once("menubar.php"); ?>
<h1><?=$mopsHelpWiki?>&nbsp;<?= TITLE ?></h1>
<table border="1" cellspacing="0" cellpadding="2" width="30%">
<tr>
<th class="darkheader"> Discovery Type </th>
<th class="darkheader"> Count </th>
</tr>
<tr>
<td><?= $totals['C'][0] ?></td>
<td align="right"><?= $totals['C'][1] ?></td>
</tr>
<tr>
<td><?= $totals['A'][0] ?></td>
<td align="right"><?= $totals['A'][1] ?></td>
</tr>
<tr>
<td><?= $totals['R'][0] ?></td>
<td align="right"><?= $totals['R'][1] ?></td>
</tr>
<tr>
<?php
$discoveryTotal = $totals['C'][1] + $totals['A'][1] + $totals['R'][1];
?>
<td><b>Total</b></td>
<td align="right"><b><?= $discoveryTotal ?></b></td>
</tr>
</table>
<br/>

<table border="1" cellspacing="0" cellpadding="2" width="30%">
<tr>
<th class="darkheader"> Incidental Astrometry </th>
<th class="darkheader"> Count (Tracklets) </th>
</tr>
<tr>
<td><?= $totals['T'][0] ?></td>
<td align="right"><?= $totals['T'][1] ?></td>
</tr>
<tr>
<td><?= $totals['N'][0] ?></td>
<td align="right"><?= $totals['N'][1] ?></td>
</tr>
<tr>
<td><?= $totals['O'][0] ?></td>
<td align="right"><?= $totals['O'][1] ?></td>
</tr>
<tr><td><?= $totals['K'][0] ?>
</td><td align="right"><?= $totals['K'][1] ?></td>
</tr>
<tr>
<td><?= $totals['J'][0] ?></td>
<td align="right"><?= $totals['J'][1] ?></td>
</tr>
<tr>
<td><?= $totals['D'][0] ?></td>
<td align="right"><?= $totals['D'][1] ?></td>
</tr>
</table>

<br/>

<?php
printNavigationBar();
?>

<table border="1" cellspacing="0" cellpadding="2" width="90%">
<tr>
    <th class="darkheader"> (#) Internal<br/>Designation </th>
    <th class="darkheader"> Database/Tracklet ID </th>
    <th class="darkheader"> Observation<br/>Date </th>
    <th class="darkheader"> Source Chunk </th>
    <th class="darkheader"> Digest2 </th>
    <th class="darkheader"> Disposition </th>
    <th class="darkheader"> MPC<br/>Designation </th>
</tr>

<?php

$TimingDebugStart = microtime(TRUE);

foreach($submissions as $s) {
    $recordCounter++;
    if($recordCounter >= $Start && $recordCounter <= $End) {
        printSubmissionRecord($s, $MopsPsiAstro, $MopsViews, $detections );
    }
    if( $recordCounter > $End) {
        break;
    }
} // foreach s

$TimingDebugEnd = microtime(TRUE);
if ( $DEBUG == TRUE ) {
    error_log(sprintf("print records: %.2f s.", $TimingDebugEnd - $TimingDebugStart));
}

?>

</table>

Report generated <?= date("Y-M-d H:i:s", $ReportTime) ?>

<?php
$BenchmarkEnd = microtime(TRUE);
echo sprintf("in  %.2f s.", $BenchmarkEnd - $BenchmarkStart);
?>



<?php

printNavigationBar();

?>

<?php require_once("bottom.php"); ?>
</body>
</html>

<?php


/**
 * Print the record data for a submission.
 *
 * @param s Submission
 * @param MopsPsiAstro Object reference
 * @param MopsViews
 * @param detections
 */
function printSubmissionRecord ( $s, $MopsPsiAstro, $MopsViews, $detections ) {
    global $recordCounter;
?>
<tr>
<td valign="middle" align="center">
<?php
    print "($recordCounter) ";
    print $s[7];
    $current_designation = $s[7];
?>
</td>
<td valign="middle" align="left">
    <?= $s[10] ?>/T<?= $s[11] ?>
</td>
<td valign="middle" align="left">
<?php
    list($calDay, $calMonth, $calYear, $calUt) = $MopsPsiAstro->mjd2cal($s[0]);
    $hours = (int)($calUt * 24);
    $minutes = (int)((-$hours + ($calUt * 24)) * 60);
    $fractionalMinutes = ((-$hours + ($calUt * 24)) * 60);
    $seconds = (int)((-$minutes + $fractionalMinutes) * 60);
    $hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
    $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
    $seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);
?>
    <?= $calYear ?>-<?= str_pad($calMonth, 2, '0', STR_PAD_LEFT) ?>-<?= str_pad($calDay, 2, '0', STR_PAD_LEFT) ?> <?= $hours ?>:<?= $minutes ?>:<?= $seconds?>.0Z

</td>
<td valign="middle" align="center">
    <?= $s[1] ?>
</td>
<td valign="middle" align="center">
    <?= $s[9] ?>
</td>
<?php
$dispositionStyle="";
if ($s[12] == 'C') { $dispositionStyle = 'style="background-color: #9ACD32"'; }
if ($s[12] == 'A') { $dispositionStyle = 'style="background-color: #FF4500"'; }
if ($s[12] == 'R') { $dispositionStyle = 'style="background-color: #B0C4DE"'; }
?>
<td <?= $dispositionStyle ?> valign="middle"  align="center">
<?php
    print $MopsViews->getMopsDisposition($s[12]);
?>
</td>
<td valign="middle" align="center">
<?php
    $mpcDesignation = $s[8];
    if ( $mpcDesignation != "NULL" ) {
      $refURL = "http://www.minorplanetcenter.net/db_search/show_object?object_id=".urlencode($mpcDesignation)."&amp;commit=Show";
?>
    <a target="_new" href="<?=$refURL?>"><?=$mpcDesignation?></a>
<?php
    } else {
?>
    N/A
<?php
    }
?>
</td>
</tr>
        <tr><td colspan="7">
<pre>
<?php
    foreach($detections as $d){
        if($s[7] == $d[0]) {
            list($calDay, $calMonth, $calYear, $calUt) = $MopsPsiAstro->mjd2cal($d[1]);

            $dayPlusDayFraction = $calDay + $calUt;
            $dayAndFraction = sprintf("%.5F", $dayPlusDayFraction);
            $calDay = str_pad($calDay, 2, '0', STR_PAD_LEFT);
            $dayFraction = preg_match("(\.\d{5})", $dayAndFraction, $dayFractionMatch);
            $dayAndFraction = $calDay . $dayFractionMatch[0];

            $ra = $MopsPsiAstro->deg2str($d[2], 'H', 2, ' ');
            if($d[3]>0){
                $dec = "+";
            } else {
                $dec = "";
            }
            $dec .= $MopsPsiAstro->deg2str($d[3], 'D', 2, ' ');
            $mag = sprintf("%.1F", $d[5]);

?>     <?= $d[0] ?>  <?= $calYear ?> <?= str_pad($calMonth, 2, '0' , STR_PAD_LEFT) ?> <?= $dayAndFraction ?> <?= $ra ?><?= $dec ?>         <?= $mag ?> <?= $d[4] ?>      <?= $d[6] ?>

<?php
        } // if s
    } // foreach detections
?>
</pre>
        </td></tr>
<?php
} // End printSubmissionRecord

/**
 * Print a navigation bar used for paging.
 */
function printNavigationBar () {
    global $MinStart;
    global $MaxEnd;
    global $defaultPageSize;
    global $Start;
    global $End;

    $newStartForPrevious = $Start - $defaultPageSize;
    $newEndForPrevious = $End - $defaultPageSize;
    if($newStartForPrevious < 1) { $newStartForPrevious = 1; $newEndForPrevious = $defaultPageSize; }
    $newStartForNext = $Start + $defaultPageSize;
    $newEndForNext = $End + $defaultPageSize;
    $previousURL = $_SERVER['PHP_SELF']."?type=".urlencode($_REQUEST['type'])."&amp;start=".urlencode($newStartForPrevious)."&amp;end=".urlencode($newEndForPrevious);
    $nextURL = $_SERVER['PHP_SELF']."?type=".urlencode($_REQUEST['type'])."&amp;start=".urlencode($newStartForNext)."&amp;end=".urlencode($newEndForNext);
    $allRecordsURL = $_SERVER['PHP_SELF']."?type=".urlencode($_REQUEST['type'])."&amp;start=".urlencode($MinStart)."&amp;end=".urlencode($MaxEnd);
?>
<a href="<?=$previousURL?>">Previous <?= $defaultPageSize ?> records</a> |
<a href="<?=$nextURL?>">Next <?= $defaultPageSize ?> records</a> |
<a href="<?=$allRecordsURL?>">All available records</a>
<?php
} // End printNavigationBar

function getDetections ( $PSISession, $MopsViews, $type ) {
    global $detections;
    global $DEBUG;
    $internalType = "";
    if($type == "SubmittedNeos"){
        $internalType = "submissions";
    }
    if($type == "DiscoveredNeos"){
        $internalType = "discovery";
    }

    $TimingDebugStart = microtime(TRUE);

    $_SESSION["MopsViewNeoSubmissionDetections"] = $MopsViews->getNeoSubmissionDetections( $PSISession, $internalType );

    $TimingDebugEnd = microtime(TRUE);
    if ( $DEBUG == TRUE ) {
        error_log(sprintf("getNeoSubmissionDetections: %.2f s.", $TimingDebugEnd - $TimingDebugStart));
    }

    $detections = $_SESSION["MopsViewNeoSubmissionDetections"];
}
?>
