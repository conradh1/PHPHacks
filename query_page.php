<?php
/**
 * This script executes PSPS queries for short/long queues.
 *
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley
 * @since Beta version 2010
 */
require_once ("session.php");
require_once ("PSIHTMLGoodiesClass.php");
require_once ("PSIAjaxGoodiesClass.php");
define('TITLE','PSI Query Page');

// Globals for handling queries and sessions
global $QueryHandle;
global $PSIHTMLGoodies;
global $resultSet;
global $queryErrorString;
global $uploadErrorString;
global $exampleQueryErrorString;
global $userQueryExampleHash;


$PSIHTMLGoodies = new PSIHTMLGoodiesClass();
$PSIHelp = $PSISession->getHelpObject();

// Determined by initQueryFormVariables
$executeQuery = 0;
$executeDownload = 0;

// Assign the Query Handle objects
if ( isset( $_SESSION["QueryHandleClass"] ) ) {
  $QueryHandle = unserialize( $_SESSION["QueryHandleClass"] );
}
else {
  $QueryHandle = new QueryHandleClass( $PSISession ); // Make new PSI Session Class
}

// Assign the PSPS Schema object
if ( isset( $_SESSION["PSPSSchemaClass"] ) ) {
  $PSPSSchema = unserialize($_SESSION["PSPSSchemaClass"]);
}
else {
  // New class for PSPS Schema used for query builder
  $PSPSSchema = new PSPSSchemaClass( $PSISession );
}

// Assign form values to QueryHandleClass instance attributes
$QueryHandle->initQueryFormVariables();

// Determine where to execute a query or not.
switch ( $QueryHandle->getAction() )  {
    case QueryHandleClass::ACTION_EXECUTE_QUERY:
      $resultSet = $QueryHandle->executeUserQuery( $queryErrorString );
      # Long Query, let's forward to the queued Jobs page
      if ( $QueryHandle->getUserQueue() == 'slow' and isset( $resultSet ) )
        header ("Location: queued.php");
      break;
    case QueryHandleClass::ACTION_DOWNLOAD_RESULTS:
      $QueryHandle->handleQueryDownload ( $queryErrorString);
      break;
    case QueryHandleClass::ACTION_UPLOAD_QUERY_FILE:
      $QueryHandle->handleUploadQueryFile( $uploadErrorString );
      break;
    case QueryHandleClass::ACTION_SHOW_QUERY_EXAMPLE:
      $QueryHandle->handleShowQueryExample( $exampleQueryErrorString );
      break;
    case QueryHandleClass::ACTION_LOAD_QUERY:
      $QueryHandle->handleLoadQuery( $_REQUEST['jobID'] );
      break;
    case QueryHandleClass::ACTION_DO_AJAX:
      // Handle the AJAX calls
      if ( !empty( $_REQUEST['ajaxAction'] ) ) {
        $PSPSSchema->setSelectFlagTable( $_REQUEST['selectFlagTable'] );
        switch (  $_REQUEST['ajaxAction'] ) {
          case 'getFlagTableDetailsXML':
            $PSIAjaxGoodies = new PSIAjaxGoodiesClass();
            $PSIAjaxGoodies->flagTableDetails2XML( $PSPSSchema );
            exit();
          default:
            // Error with unkown action
            print "Error: Unkown Ajax action $action";
          break;
        } //switch
      } //if
      break;
    case QueryHandleClass::ACTION_DO_NOTHING:
      break;
    default:
      break;
} #switch

//ALWAYS save the QueryHandleClass Object
$_SESSION['QueryHandleClass'] = serialize( $QueryHandle );
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
    <script type="text/javascript" src="javascript/psi_query_page.js"></script>
    <link href="css/default.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
    <script type="text/javascript">
      jQuery(document).ready(function() {
<?
        // Decide whether to hide columns for table or not.
        if ( $QueryHandle->getAction() != QueryHandleClass::ACTION_UPLOAD_QUERY_FILE ) {
?>
          $('.toggle_upload_menu_item').hide();
          // Hide collapse buttons at first
          $('.toggle_upload_menu_button').each(function(i) {
            $(this).children().first().next().hide();
          });

<?
        } #php if
        # else we want to show the form but hide the plus image
        else {
?>
          $('.toggle_upload_menu_button').each(function(i) {
            $(this).children().first().hide();
          });
<?
        } #php else
?>
        // validation upon submit.
        $('#formQuery').bind('submit', function(event) {
          $msg = '';

          // Check query test
          $textQuery = $('#textQuery').val();
          if ( $textQuery == '' ) {
            $msg += 'You must enter a SQL query to submit for executing.';
          }

          // If there is an error return alert with a message.
          if ($msg != '') {
            alert($msg);
            return false;
          }
          return true;
        }); //bind
      }); //JQuery
    </script>
    <title><?= TITLE ?></title>
    <?php require_once("menubar_header.php"); ?>
</head>
<body>
<?php require_once("top.php"); ?>
<?php require_once("menubar.php"); ?>
<div id="main">
<div style="text-align: center;">
<h2><?=$PSIHelp->getWikiURL('PSI-QueryPage')?>&nbsp;<?=TITLE?></h2>
<table align="center" cellspacing="10" cellpadding="20">
  <tr>
    <td valign="top">
      <div class="toggle_upload_menu">
        <div class="toggle_upload_menu_button">
          <img src="images/plus.gif" alt="+" />
          <img src="images/minus.gif" alt="-" />
          <strong>Upload Query File Form</strong>
        </div>
      </div>
      <div class='toggle_upload_menu_item'>
        <?php !empty( $uploadErrorString ) ? print $uploadErrorString : print '';?>
        <form enctype="multipart/form-data" name="formUpload" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
          <span title="Upload Query File in *.txt or *.sql format">
              <?=$PSIHelp->getWikiURL('PSI-QueryPageEnteringYouQuery')?>&nbsp;
              Select Query File To Upload: <input type="file" name="fileUpload" size="25"/>
              <input type="hidden" name="hiddenSchema" value="<?=$QueryHandle->getUserSchemaGroup().'|'.$QueryHandle->getUserSchema()?>"/>
              <input type="hidden" name="hiddenQueue" value="<?=$QueryHandle->getUserQueue()?>"/>
              <input type="hidden" name="hiddenMyDbTable" value="<?=$QueryHandle->getUserMyDbTable()?>"/><br/><br/>
              <input type="submit" name="submitUpload" value="Upload Query File" onclick="assignQueryPageValues(this.form);"/>
          </span>
        </form>
      </div>
    </td>
    <td valign="top">
      <div class="toggle_flag_menu">
        <div class="toggle_flag_menu_button">
          <img src="images/plus.gif" alt="+" />
          <img src="images/minus.gif" alt="-" />
          <strong>Quality Flag Tool</strong>
        </div>
      </div>
      <div class='toggle_flag_menu_item'>
        <form enctype="multipart/form-data" name="formSelectFlagTable" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
          <span title="View a Quality Flag Table">
               <?=$PSIHelp->getWikiURL('PSI-QualityFlags')?>&nbsp;Select Flag Table:
<?php
      // show select form containg each quality flag in schema
      print $PSIHTMLGoodies->showFormSelect( 'selectFlagTable',
                                             'idSelectFlagTable',
                                             $PSPSSchema->getFlagTables(),
                                             $PSPSSchema->getSelectFlagTable(),
                                             'Select a Flag Table...' );

?>
          </span>
        </form>
      </div>
    </td>
    <td valign="top">
      <div class="toggle_load_example_menu">
        <div class="toggle_load_example_button">
          <img src="images/plus.gif" alt="+" />
          <img src="images/minus.gif" alt="-" />
          <strong>Query Examples Forms</strong>
        </div>
      </div>
      <div class='toggle_load_example_menu_item'>
        <form enctype="multipart/form-data" name="formLoadExample" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
          <span title="Load One of the Examples">
            <?=$PSIHelp->getWikiURL('PSPS-ExampleQueries')?>&nbsp;&nbsp;Select Example: <select name="selectExample">
<?php

          $queryExamplesHash = $PSIHelp->getQueryExamplesHash();
	  foreach ( $queryExamplesHash as $section => $queryHash ) {
?>
		  <optgroup label="<?=$section?>">
<?php
	    foreach ( $queryHash as $title => $query ) {
		   #each value for option is a combination of section and title
?>
		    <option value="<?=$section."_".$title?>"><?=$queryHash[$title]['shortDescription']?></option>
<?php
	     } #foreach
?>
		  </optgroup>
<?php
	  } #foreach sections
?>
	        </select><br/><br/>
		<input type="submit" name="submitQueryExample" value="Show Query Example"/>
          </span>
        </form>
      </div>
    </td>
  </tr>
  <tr>
   <td align="center" colspan="3">
      <div id="idDivFlagForm">
      </div>
   </td>
  </tr>
</table>
<!-- ############### QUERY FORM STARS HERE ############# -->
<hr/>
<form name="formQuery" id="formQuery" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
    <?php
    if ( $QueryHandle->getAction() == QueryHandleClass::ACTION_SHOW_QUERY_EXAMPLE ) {
      $userQueryExampleHash = $QueryHandle->getUserQueryExampleHash();
      #show query example details
?>
<table style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="3">
  <tr>
    <th colspan="2">Example Details</th>
  </tr>
  <tr>
    <th align="right">Title: </th>
    <td align="left"><?=$userQueryExampleHash['shortDescription']?></td>
   </tr>
  <tr>
    <th align="right">Author: </th>
    <td align="left"><?=$userQueryExampleHash['author']?></td>
  </tr>
  <tr>
     <th align="right">Description: </th>
     <td align="left"><?=$userQueryExampleHash['longDescription']?></td>
  </tr>
  <tr>
     <th align="right">Database: </th>
     <td align="left"><?=$userQueryExampleHash['database']?></td>
  </tr>
  <tr>
      <th align="right">Queue Type: </th>
      <td align="left"><?=$userQueryExampleHash['queue']?></td>
  </tr>
</table>
<?php
    } #if
?>
<table style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="3">
  <tr>
    <td  align="right"><strong>Select Database:</strong></td>
    <td align="left">
      <select name="selectSchema">
      <?php

          $schemaGroups = $QueryHandle->getSchemaGroups();
          foreach ( $schemaGroups as $schemaGroup ) {
?>
        <optgroup label="<?=$schemaGroup?>">
<?php
            $schemas = $QueryHandle->getSchemas( $schemaGroup );
            foreach ( $schemas as $schema ) {
                   $schemaHash = $QueryHandle->getSchemaHash( $schemaGroup, $schema );
                   #each value for option is a combination of group and title
                    $selected = '';
                   if ( $schemaGroup."|".$schema == $QueryHandle->getUserSchemaGroup().'|'.$QueryHandle->getUserSchema() )
                    $selected = ' selected="selected"';
?>
          <option value="<?=$schemaGroup."|".$schema?>"<?=$selected?>><?=$schema.' - '.$schemaHash['Description']?></option>
<?php
             } #foreach
?>
        </optgroup>
<?php
          } #foreach sections
?>
      </select>
    </td>
  </tr>
</table>
<table style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="3">
    <tr>
        <td>
          <strong>Query:</strong>
        </td>
    </tr>
    <tr>
        <td>
            <textarea name="query" id="textQuery" rows="25" cols="100"><?=$QueryHandle->getUserQuery()?></textarea>
        </td>
    </tr>
    <tr>
        <td align="left">
          <table style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="3">
            <tr>
              <td>&nbsp;</td>
              <td>&nbsp;</td>
              <td align="left">
                Name:
                <input name="queryName" size="15" maxlength="15" value="<?=$QueryHandle->getUserQueryName()?>"/> (optional)
             </td>
            </tr>
            <tr>
              <td rowspan="3" align="right"><?=$PSIHelp->getWikiURL('PSI-QueryPageTheTypesofQuery')?></td>
              <td align="left">
                <input type="radio" name="queue" value="syntax" <?= $QueryHandle->getUserQueue() == 'syntax' ? 'checked="checked"' : '' ?>/>
              </td>
              <td align="left">
                 Check Syntax
              </td>
            </tr>
            <tr>
             <td align="left">
              <input type="radio" name="queue" value="fast" <?= $QueryHandle->getUserQueue() == 'fast' ? 'checked="checked"' : '' ?>/>
             </td>
             <td align="left">
                Fast Queue
             </td>
            </tr>
            <tr>
             <td align="left">
               <input type="radio" name="queue" value="slow" <?= $QueryHandle->getUserQueue() == 'slow' ? 'checked="checked"' : '' ?>/>
             </td>
             <td align="left">
                Slow Queue
                &nbsp;&nbsp;&nbsp;&nbsp;
                MyDB table for slow queue results:
                <input name="myDbTable" size="12" value="<?= $QueryHandle->getUserMyDbTable() ?>"/>
             </td>
            </tr>
          </table>
        </td>
    </tr>
    <tr>
        <td  align="center">
            <input type="submit" value="Submit Query" id="submit-button" name="submitQuery"/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="button" value="Clear Query" name="resetUser" onclick="javascript: document.formQuery.query.value = '';"/>
        </td>
    </tr>
</table>
<?php

// Display Results
if (isset ($resultSet) and $QueryHandle->getUserQueue() != 'syntax') {
?>
    <hr width="90%" /><br />
    <div class="toggle_menu">
      <div class="toggle_menu_button">
	<img src="images/plus.gif" alt="+" />
        <img src="images/minus.gif" alt="-" />
        <strong>Fast Query Download Form</strong>
      </div>
    </div>
    <div class='toggle_menu_item' style="text-align: center;">
      <table align="center">
        <tr>
          <td align="right" rowspan="4">
             <?=$PSIHelp->getWikiURL('PSI-QueryPageExtractingResultsfromaFastQueue')?>
          </td>
        </tr>
        <tr>
          <td align="right">Download File Type:</td>
          <td align="left">
<?php

      print $PSIHTMLGoodies->showFormSelect( 'selectDownloadFileFormat',
                                             NULL,
                                             $QueryHandle->getDownloadFileFormats(),
                                             $QueryHandle->getDownloadFileFormat(),
                                             'Select a File Format...' );
?>
          </td>
        </tr>
        <tr>
        <td align="right">File Name:</td>
          <td align="left"><input name="textDownloadFileName" value=""/> (optional)</td>
        </tr>
        <tr>
          <td colspan="2" align="center">
            <input type="submit" value="Download Results" name="submitDownload" />
          </td>
        </tr>
      </table>
    </div>
    <br/><br/>
<?php
      print ( $PSIHTMLGoodies->showQueryResultSet( $resultSet ) );
} #if
else if (isset ($resultSet) and $QueryHandle->getUserQueue() == 'syntax') {
?>
        <h3>Syntax OK</h3>

<?php

} #else if
else if ( isset( $queryErrorString ))
    print ( $PSIHTMLGoodies->showErrorResult ( $queryErrorString ) );

?>
</form>
</div>
<!-- End Content -->
</div>
<!-- End Main -->
<?php require ("bottom.php"); ?>
</body>
</html>
