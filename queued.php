<?php
/**
 * This script shows queued jobs and lets users manipulate them easily.
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley
 * @since Beta version 2010
 */
require_once ("session.php");
require_once ("QueuedJobClass.php");
define('TITLE','Queued Jobs');
# Set Queued Jobs filters

// Get QueuedJobClass session objection that we need
global $QueuedJob;

if ( isset( $_SESSION["QueuedJobClass"] ) ) {
    $QueuedJob = unserialize( $_SESSION["QueuedJobClass"] );
}
else {
    $QueuedJob = new QueuedJobClass( $PSISession );
}

// Assign form values from submission
$QueuedJob->initQueuedJobsFormVariables();
$PSIHelp = $PSISession->getHelpObject();
date_default_timezone_set ("Pacific/Honolulu");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
# Set Refresh so users can just check their jobs after a certain time period

?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Query Builder Review"/>
    <meta name="keywords" content="Pan-STARRS Science Web Interface Astronomy Query Builder Review SQL"/>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
    <script type="text/javascript" src="javascript/jquery-ui-1.8.2.custom.min.js"></script>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <script type="text/javascript">
      jQuery(document).ready(function() {
        //used for JQuery popup calendar
        $(function() {
            $("#timeStart").datepicker( {dateFormat: 'yy-mm-dd'});
        });
        //used for JQuery popup calendar
        $(function() {
            $("#timeEnd").datepicker( {dateFormat: 'yy-mm-dd'});
        });

        // validation upon search request
        $('#formQueuedJobSearch').bind('submit', function(event) {
          $msg = '';
          // Validate job id search format
          $textJobID = $('#textJobID').val();
          if ( $textJobID != '' ) {
            if ( !( $textJobID.match(/^\d+$/gi) || $textJobID.match(/^\d+-\d+$/gi)) ) {
              $msg += 'Error: the search field JobID only allows numbers or a range of numbers separated by a dash (i.e., 120-130).';
            }
          }
          $searchBy = $("#formQueuedJobSearch input[name=radioSearchBy]:checked" ).val()
          if ( $searchBy == null && ( $('#searchKeywords').val() != '' && $('#searchKeywords').val() != null ))  {
            $msg += 'Error: If you enter Keywords, you must select to search by name or query.';
          }
          if ( $('#searchKeywords').val().match(/[\#\&\$\@\-\^\*\'\"]+/gi) ) {
           $msg += "Error: Invalid Keywords. Special Characters @ # $ & ^ * - and single or double quotes are not allowed.";
          }
          if ($msg != '') {
            alert($msg);
            return false;
          }
          return true;
        });
      }); //JQuery
     </script>
    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link href="css/jquery-ui-1.8.2.custom.css" rel="stylesheet" type="text/css" media="screen" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
    <?php require_once("menubar_header.php"); ?>
</head>
<body>
<?php
  require_once("top.php");
  require_once("menubar.php");

  $cancelJobMessage = '';
  $queuedJobStatusTotalsList = array();
  # Make a request to get the queued queries
  $queuedQueryTable = $QueuedJob->getQueuedJobsTable( $queuedJobStatusTotalsList );
?>
<div id="main">
    <div class="content">
        <div style="text-align: center;">
        <h2><?=$PSIHelp->getWikiURL('PSI-QueuedJobs')?>&nbsp;<?=TITLE?></h2>
<?php
        # Cancel Job if need be.
        if ( isset( $_REQUEST['cancelJobID'] ) )
                print "<strong>".$QueuedJob->cancelQueuedJob( $_REQUEST['cancelJobID'] )."</strong>";
?>
        <form name="formQueuedJobSearch" id="formQueuedJobSearch" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
        <table class="results" border="0" cellpadding="1" cellspacing="0" style="margin: 0 auto">
          <tr>
            <td colspan="4" align="center">
              <strong>Queued Queries Search Form</strong>
            </td>
          </tr>
          <tr>
            <td align="right">Status:</td>
            <td align="left">
              <select name="selectJobStatus">
<?php
            $jobStatusHash = $QueuedJob->getJobStatusHash();
            foreach( $jobStatusHash as $jobStatus => $jobStatusString ) {
                $selected = '';
                if ( $jobStatus == $QueuedJob->getJobStatus() )
                    $selected = 'selected="selected"';
                else
                    $selected = '';
                    print <<<EOF
                  <option $selected value="$jobStatus">$jobStatusString ($queuedJobStatusTotalsList[$jobStatus])</option>

EOF;
            } #foreach
?>
              </select>
            </td>
            <td align="right">Job ID:</td>
            <td align="left">
              <input type="text" name="jobID" id="textJobID" value="<?=$QueuedJob->getJobID()?>" size="15" maxlength="15"/>
            </td>
          </tr>
          <tr>
            <td align="right">
                Also Search By:
            </td>
            <td align="left">
                <input type="radio" name="radioSearchBy" value="TaskName" <?= $QueuedJob->getRadioSearchBy() == 'TaskName' ? 'checked="checked"' : '' ?>/>Name&nbsp;
                <input type="radio" name="radioSearchBy" value="Query" <?= $QueuedJob->getRadioSearchBy() == 'Query' ? 'checked="checked"' : '' ?>/>Query
            </td>
            <td align="right">
                Keywords:
            </td>
            <td align="left">
                <input type="text" name="searchKeywords" id="searchKeywords" value="<?=$QueuedJob->getSearchKeywords()?>" size="6"/>
            </td>
          </tr> 
          <tr>
            <td align="right">
                Submitted After:
            </td>
            <td>
                <input id="timeStart"
                       class="datetime"
                       type="text"
                       name="timeStart"
                       value="<?=$QueuedJob->getJobTimeStart()?>" size="13"/>
            </td>
            <td align="right">
                Submitted Before:
             </td>
            <td>
                 <input id="timeEnd"
                        class="datetime"
                        type="text"
                        name="timeEnd"
                        value="<?=$QueuedJob->getJobTimeEnd()?>" size="13"/>
            </td>
          </tr>
          <tr>
            <td colspan="2" align="right">
              <input type="submit" value="Submit" id="submit-button" name="submitFilter"/>&nbsp;&nbsp;
            </td>
            <td colspan="2" align="left">
              &nbsp;&nbsp;<input type="button" value="Clear" onclick="clearForm(this.form)"/>
            </td>
          </tr>
          </table>
          </form>
          <hr/>
<?
            # Output our big ass table!
            if ( isset($queuedQueryTable) ) {
              print $queuedQueryTable;
            }
?>
        </div>
    </div>
    <!-- End Content -->
</div>
<br/>
<!-- End Main -->
<?php
 // ALWAYS ALWAYS ADD class to session if it is set.
$_SESSION["QueuedJobClass"] = serialize ($QueuedJob );

require ("bottom.php");

?>
</body>
</html>
