<?php
/**
 * This script is the first step in creating a query dynamically
 *
 * The first step in building a dynamic query involves the following to be completed:
 *  -Choosing a survey
 *  -Selecting tables
 *  -Choosing the format of how to view the columns
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley, drchang@ifa.hawaii.edu
 * @since Beta version 2010
 */

require_once ("session.php");
$debug_force_session=0; // force instantiation for debugging
define('TITLE','PSI MOPS Query Builder Step 1: Choose Tables');

global $QueryHandle;
global $MOPSSchema;
global $PSISession;
global $PSIHelp;

$DefaultMopsSchema = $PSISession->getDefaultMopsSchema();
$PSIHelp = $PSISession->getHelpObject();

$LabelCnt = 0;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
require_once ("session.php");
require_once ("PSIHTMLGoodiesClass.php");
?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface MOPS Query Builder"/>
    <meta name="keywords" content="Pan-STARRS Science Web Interface Astronomy MOPS Query Builder SQL"/>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
<?php

$PSIHTMLGoodies = new PSIHTMLGoodiesClass();
$thisStep = 'mops_query_builder_step1.php';
$nextStep = 'mops_query_builder_step2.php';
$formErrorFlag = 0;  // Assume no error

// Assign the MOPS Schema object
if ( isset( $_SESSION["MOPSSchemaClass"] ) ) {
    $MOPSSchema = unserialize($_SESSION["MOPSSchemaClass"]);
}
else {
    // New class for MOPS Schema used for query builder
    $MOPSSchema = new MOPSSchemaClass( $PSISession );
}

if(isset($_REQUEST['action'])){
    if($_REQUEST['action']=='Start20%Over'){
        $checkTables = array();
        $MOPSSchema->setCheckTables( $checkTables );
    }
}

// set MOPS catalog selection
$catalogSelection = $MOPSSchema->getCatalogSelection();
if(empty($catalogSelection)){
    $catalogSelection = $PSISession->getDefaultMopsSchema();
    $MOPSSchema->setCatalogSelection($catalogSelection);
}

// If user has completed step 1, assign the new variables into session and check for errors
if ( isset($_REQUEST['submitStep1']) == 'Next' ) {
    $MOPSSchema->setCatalogSelection (  $_REQUEST['selectSchema'] );
    if(isset($_REQUEST['checkTables'])) {
        $MOPSSchema->selectRequiredTables($_REQUEST['checkTables']);
    }

    if ( !empty( $_REQUEST['checkTables'] ) ) {
        $MOPSSchema->setCheckTables( $_REQUEST['checkTables'] );
    }
    else {
        // Assign error because no tables have been selected
        $formErrorFlag = 1;
        $MOPSSchema->setCheckTables( array() );
    } // if

    // User has completed the form properly, forward to step 2
    $MOPSSchema->setSelectColumnViewFormat( $_REQUEST['selectColumnViewFormat'] );

    if ( empty( $formErrorFlag ) ) {
        print "<meta http-equiv=\"refresh\" content=\"0;url=$nextStep\" />\n";
    }
    // Change default name of query name
    if ( $QueryHandle->getUserQueryName() == 'PSI Query' ) {
      $QueryHandle->setUserQueryName('MOPS Query');
    }
} // if

$checkTables = $MOPSSchema->getCheckTables(); // retrieve checked tables from schema object

?>
<?php require_once("menubar_header.php"); ?>
</head>
<body>
<?php require ("top.php"); ?>
<?php require_once("menubar.php"); ?>

<div id="main">
<div style="text-align: center">
<h2><?=$PSIHelp->getWikiURL('PSI-MopsQueryBuilder')?>&nbsp;<?=TITLE?></h2>
<div id="description">
Additional required tables will be automatically selected after submission, if necessary.
</div>
<form name="step1" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
<?php
    $mopsCatalogs = $QueryHandle->getSchemas($MOPSSchema->getDefaultMopsSchemaGroup());
?>
    <table border="0" cellpadding="3" cellspacing="3" style="margin: 0 auto">
        <tr>
            <td  align="right"><strong>Select Catalog:</strong></td>
            <td align="left">
    <select name="selectSchema">
<?php
            $schemaGroup = $PSISession->getDefaultMopsSchemaGroup(); //Get the PSPS Schema group for the MOPS query builder
            # Check the default schema
            $mopsSchemas = $QueryHandle->getSchemas( $schemaGroup );
            # set default MOPS schema
            if ( !in_array($QueryHandle->getUserSchema(), $mopsSchemas ) ) {
              $QueryHandle->setUserSchema( $PSISession->getDefaultMopsSchema() );
            }
            foreach ( $mopsSchemas as $schema ) {
                   $mopsSchemaHash = $QueryHandle->getSchemaHash( $schemaGroup, $schema );
                   $selected = '';
                   if ( $schemaGroup."|".$schema == $QueryHandle->getUserSchemaGroup().'|'.$QueryHandle->getUserSchema() )
                     $selected = ' selected="selected"';
                   #each value for option is a combination of section and title EXCEPT EXPORT
                   if ( strcmp($PSISession->getDefaultMopsExportSchema(), $schema) != 0 ) {
?>
                    <option value="<?=$schemaGroup."|".$schema?>"<?=$selected?>><?=$schema.' - '.$mopsSchemaHash['Description']?></option>
<?php
                   }
             } #foreach schema
?>
              </select>
            </td>
        </tr>
    </table>
<?php
    // If the User decides to start over
    if ( isset($_REQUEST['action']) == 'Start Over' ) {
        $MOPSSchema->clearFormValues();
    }

    // Find the list of unique table types.
    $tables = $MOPSSchema->getUserInterestingTables(); // an array of tables

    $tableNum = count($tables);
    $tableCols = 3;
    $totalRows = ceil($tableNum / $tableCols);
    $columnLength = $totalRows;
    $index = 0; // row index
?>

    <table style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="3" >
        <tr>
            <td align="left" colspan="<?= $tableCols ?>">
                <strong>Click the checkbox beside each table that you wish to use for your search.</strong>
            </td>
        </tr>
<?php
    // create multiple columns
    for ($row = 0; $row < $totalRows; $row++) { // make rows
?>
        <tr>
<?php
        for ($col = 0; $col < $tableCols; $col++) {

            $splitIndex = $index + ($col * $columnLength); // index for splitting table across columns

            if ($splitIndex < $tableNum) {
                $tableName = $tables[$splitIndex]; // have a new starting point for each column
?>
            <td align="left" width="200">
                <label for="<?= $tableName. "_" .$LabelCnt ?>"/>
                  <input type="checkbox" name="checkTables[]" value="<?= $tableName ?>" id="<?= $tableName. "_" .$LabelCnt ?>"<?php

            $LabelCnt++;
            if (in_array($tableName, $checkTables)) {
                print " checked=\"checked\"";
            }
            print "/>";
?>

                    <?= $MOPSSchema->removeQuotes($tableName); // replace single quotes in table name ?>
            </td>
<?php
            } // if splitIndex
        } // end for col
?>

        </tr>
<?php
        $index++;
    } // end for row
?>
        <tr>
            <td align="left" colspan="<?= $tableCols ?>"><strong>Column View Format:</strong>
                <select name="selectColumnViewFormat">
<?php
    // Add column display format either short or long for column details
    // @note value in session exists only to fill this form value
    $selectColumnViewFormat = $MOPSSchema->getSelectColumnViewFormat();

    print "<option ".($selectColumnViewFormat == 'short' ? 'selected="selected"' : '')." value=\"short\">Column names only</option>\n";
    print "<option ".($selectColumnViewFormat == 'full' ? 'selected="selected"' : '')." value=\"full\">Full column information</option>\n";
?>
                </select>
            </td>
        </tr>
    </table>
    <br/>
    <div align="center">
        <table border="0" width="65%" cellpadding="0" cellspacing="0">
<?php
        if ( !empty($formErrorFlag) ) {
?>
            <tr>
                <td colspan = "2" align="center">
                    <h3 style="color: red">Error: You must select some tables before proceeding.</h3>
                </td>
            </tr>
<?php
        } //if !empty
?>
            <tr>
                <td align="center">
                    <input type="button" name="buttonStartOver"
                           value="Start Over" onclick="confirmStartOverMopsQb();" />
                </td>
                <td align="center" <?= ( empty($formErrorFlag ) ) ? '' : 'style="color: red"'  ?> >
                    <input type="submit" name="submitStep1" value="Next" id="submit-button" />
                </td>
            </tr>
        </table>
    </div>
</form>
<br/>
</div>
<!-- End Content -->
</div>
<!-- End Main -->
<?php
  if (isset ($MOPSSchema)) {
      // ALWAYS ALWAYS ADD class to session if it is set.
      $_SESSION["MOPSSchemaClass"] = serialize($MOPSSchema);
  }
  require_once("bottom.php");
?>
</body>
</html>
