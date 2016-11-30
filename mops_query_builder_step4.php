<?php
/**
 * This script is the fourth step in creating a query dynamically
 *
 * The fourth step actually builds the dynamic query by taking all the variables
 * and composes a query.
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley, drchang@ifa.hawaii.edu
 * @since Beta version 2010
 */
require_once ("session.php");

define('TITLE','MOPS Query Builder: Review Query');

require_once ("session.php");

global $MOPSSchema;
global $PSISession;

$previousStep1 = 'mops_query_builder_step1.php';
$previousStep2 = 'mops_query_builder_step2.php';
$previousStep3 = 'mops_query_builder_step3.php';
$nextStep = $_SERVER['PHP_SELF'];

// Assign the MOPS Schema object
if ( isset( $_SESSION["MOPSSchemaClass"] ) ) {
    $MOPSSchema = unserialize($_SESSION["MOPSSchemaClass"]);
}
else {
    // If no MOPS Schema, forward to step 1
    header ('Location: mops_query_builder_step1.php');
}

// Conduct Download of a quick query
if ( isset ($_REQUEST['Download'])) {
    $QueryHandle->setDownloadFilename( $_REQUEST['textFileName'] );
    $QueryHandle->setDownloadFileFormat( $_REQUEST['selectFileFormat'] );
    $QueryHandle->handleQueryDownload ( $errorString);
}

$PSIHTMLGoodies = new PSIHTMLGoodiesClass();
// Set old values already in Session
$checkTables = $MOPSSchema->getCheckTables();
$catalogSelection = $MOPSSchema->getCatalogSelection();
preg_match ('/\|(.*)/', $catalogSelection, $output);
$catalogSelectionShort = $output[1];

// Assign form values for SpacialConstraint
if ( isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Next' ) {
    $MOPSSchema->setSelectRowLimit( $_REQUEST['selectRowLimit'] );

    // Very important variable below to hold a nest hash for query filters
    $formTableColumnFilterHash = array();

    // Assign values for query filters
    foreach ($checkTables as $table) {

        $checkColumns = $MOPSSchema->getCheckTableColumns( $table );
        $columnsFilterHash = array();

        // Assign the filter values for the selected columns
        if ( !empty( $checkColumns ) ) {

            foreach ($checkColumns as $column) {
                $columnsFilterHash[$column] = array( 'checkColumn' => $_REQUEST['checkColumn_' . $table . '_' . $column],
                                                     'selectMinOper' => $_REQUEST['selectMinOper' . '_' . $table . '_' . $column],
                                                     'textMinValue' => $_REQUEST['textMinValue' . '_' . $table . '_' . $column],
                                                     'selectMaxOper' => $_REQUEST['selectMaxOper' . '_' . $table . '_' . $column],
                                                     'textMaxValue' => $_REQUEST['textMaxValue' . '_' . $table . '_' . $column] );
            } // foreach $column

        } //if !empty( $checkColumns

        $formTableColumnFilterHash[$table] = $columnsFilterHash;

    } // foreach $table

    // Finally add this giant nested hash to the MOPSSchema class instance.
    $MOPSSchema->setFormTableColumnFilterHash( $formTableColumnFilterHash );
} //if Next

$selectRowLimit = $MOPSSchema->getSelectRowLimit();

// Assign selected columns
if (!empty ($_REQUEST['query'])) {
    $QueryHandle->setUserQuery($_REQUEST['query']);
}
else {
    $QueryHandle->setUserQuery( $MOPSSchema->buildQuery());
}

$query = $QueryHandle->getUserQuery();
$queue = 'fast';
if (!empty ($_REQUEST['queue'])) {
    $queue = $_REQUEST['queue'];
    $QueryHandle->setUserQueue( $queue );
}

if (!empty ($_REQUEST['myDbTable'])) {
    $QueryHandle->setUserMyDbTable( $_REQUEST['myDbTable'] );
}

// Always set the default SchemaGroup and schema/context
$QueryHandle->setUserSchemaGroup( $MOPSSchema->getDefaultMopsSchemaGroup() );
preg_match ('/\|(.*)/', $catalogSelection, $output);
$database = $output[1];
$QueryHandle->setUserSchema( $database );

// Save session instance
$_SESSION['QueryHandleClass'] = serialize( $QueryHandle );
// Globals to handle queries
$resultSet;
$errorString;

//Determine if we can execute a query
if (!empty ( $query )       and
    isset ($_REQUEST['submitQuery']) and
    $_REQUEST['submitQuery'] == 'Submit Query') {
    $resultSet = $QueryHandle->executeUserQuery( $errorString );

    # Long Query, let's forward to the queued Jobs page
    if ( $queue == 'slow' and isset ($resultSet))
        header ("Location: queued.php");
} #if
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Query Builder Review"/>
    <meta name="keywords" content="Pan-STARRS Science Web Interface Astronomy Query Builder Review SQL"/>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
    <script type="text/javascript" src="javascript/psi_jquery.js"></script>
    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
<?php require_once("menubar_header.php"); ?>
</head>

<body>
<?php
    require_once("top.php");
    require_once("menubar.php");
?>
<div id="main">
<div align="center">
<h2><?= TITLE ?></h2>
<form name="mops_qb_step4_form" method="post" action="<?=$nextStep?>">
    <table style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="0" >
        <tr>
            <td align="center">
              <strong>Survey (Database):</strong>
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
                    <option value="<?=$schemaGroup."|".$schema?>"<?=$selected?>>
                      <?=$schema.' - '.$schemaHash['Description'].' ('.$schemaHash['Type'].')'?>
                    </option>
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
        <tr>
            <td align="center"><strong>Query:</strong></td>
        </tr>
        <tr>
          <td>
            <textarea name="query" rows="15" cols="80"><?=$query?></textarea>
          </td>
        </tr>
        <tr>
         <td align="left">Name:
          <input name="queryName" size="15" maxlength="15" value="<?=$QueryHandle->getUserQueryName()?>"/> (optional)
         </td>
        </tr>
        <tr>
            <td align="left">
                <input type="radio" name="queue" value="syntax"
                <?= $QueryHandle->getUserQueue() == 'syntax' ? 'checked="checked"' : '' ?>/>
                Check Syntax<br/>
                <input type="radio" name="queue" value="fast"
                <?= $QueryHandle->getUserQueue() == 'fast' ? 'checked="checked"' : '' ?>/>
                Fast Queue<br/>
                <input type="radio" name="queue" value="slow"
                <?= $QueryHandle->getUserQueue() == 'slow' ? 'checked="checked"' : '' ?>/>
                Slow Queue
                &nbsp;&nbsp;&nbsp;&nbsp;
                MyDB table for slow queue results:
                <input name="myDbTable" size="12" value="<?= $QueryHandle->getUserMyDbTable() ?>"/>
            </td>
        </tr>
    </table>
    <br />
    <table border="0"  cellpadding="5" cellspacing="5">
        <tr>
            <td align="center">
                <input type="button" name="buttonStartOver"
                 value="Start Over" onclick="confirmStartOverMopsQb();" />
            </td>
            <td align="center">
                <input type="button" value="Tables"
                onclick="window.location.href='<?=$previousStep1?>'" />
            </td>
            <td align="center">
                <input type="button" value="Columns"
                onclick="window.location.href='<?=$previousStep2?>'" />
            </td>
            <td align="center">
                <input type="button" value="Constraints"
                onclick="window.location.href='<?=$previousStep3?>'" />
            </td>
            <td  align="center">
                <input type="submit" value="Submit Query"
                id="submit-button" name="submitQuery"/>
            </td>
        </tr>
    </table>
<?php

// Display Results
if ( isset( $resultSet ) and $queue != 'syntax') {
?>
    <hr/><br />
    <div class="toggle_menu">
        <div class="toggle_menu_button">
            <img src="images/plus.gif" alt="+" />
            <img src="images/minus.gif" alt="-" />
            <strong>Click to expand/collapse Fast Query Download Form.</strong>
        </div>
    </div>
    <div class='toggle_menu_item' style="text-align: center;">
        <table align="center">
            <tr>
                <td align="right">Download File Type:</td>
                <td align="left">
<?php
      # Default value for file name
      $fileNameDefault = isset($_REQUEST['textFileName']) ? $_REQUEST['textFileName'] : '';
      # Default value for file format
      $fileFormatDefault = isset($_REQUEST['selectFileFormat']) ? $_REQUEST['selectFileFormat'] : NULL;
      print $PSIHTMLGoodies->showFormSelect( 'selectFileFormat', NULL, $QueryHandle->getDownloadFileFormats(), $fileFormatDefault, 'Select a File Format...' );
?>
                </td>
            </tr>
            <tr>
                <td align="right">File Name:</td>
                <td align="left"><input type"text" name="textFileName" value=""> (optional)</td>
            </tr>
            <tr>
                <td colspan="2" align="center"><input type="submit" value="Download Results" name="Download" id="submit-button" /></td>
            </tr>
        </table>
    </div>
    <br/><br/>
<?php
    print ( $PSIHTMLGoodies->showQueryResultSet( $resultSet ) );
} #if
else if (isset ($resultSet) and $queue == 'syntax') {
?>
    <h3>Syntax OK</h3>

<?php

} #else if
else if ( isset( $errorString ))
    print ( $PSIHTMLGoodies->showErrorResult ( $errorString ) );
?>
</form>
</div>
<!-- End Content -->
</div>
<!-- End Main -->
<?php
  if (isset ($MOPSSchema)) {
      // ALWAYS ALWAYS ADD class to session if it is set.
      $_SESSION["MOPSSchemaClass"] = serialize( $MOPSSchema );
  }
  require_once("bottom.php");
?>
</body>
</html>
