<?php
/**
 * This script is the second step in creating a query dynamically
 *
 * The second step in building a dynamic query involves the following to be completed:
 *  -Choosing columns based on the table
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley
 * @since Beta version 2010
 */
require_once ("session.php");
require_once ("PSPSSchemaClass.php");
define('TITLE','PSI PSPS Query Builder: Select Table Columns');

// Needed globals
global $PSPSSchema;
global $PSIHelp;
global $previousStep1;
global $nextStep;
global $formErrorFlag ;  //Flag to check for errors
global $MAX_COLS; // Used for html tables
//Used in forms
global $selectColumnViewFormat;
global $selectSurvey;
global $surveyHash;
global $checkTables;

$PSIHelp = $PSISession->getHelpObject();
$previousStep1 = 'query_builder_step1.php';
$nextStep = 'query_builder_step3.php';
$formErrorFlag = 0;  //Assume no errors
$MAX_COLS = 6;

// Assign the PSPS Schema object
if ( isset( $_SESSION["PSPSSchemaClass"] ) ) {
  $PSPSSchema = unserialize($_SESSION["PSPSSchemaClass"]);
}
else {
  // If no PSPS Schema, forward to step 1
  header ('Location: query_builder_step1.php');
}

// Assign variables from previous query builder step.
$selectColumnViewFormat =  $PSPSSchema->getSelectColumnViewFormat();
#$selectSurvey = $PSPSSchema->getSelectSurvey();
#$surveyHash = $PSPSSchema->getSurveyHash( $selectSurvey );
$checkTables = $PSPSSchema->getCheckTables();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Query Builder Select Table Columns"/>
    <meta name="keywords" content="Pan-STARRS Science Web Interface Astronomy Query Builder Select Table Columns SQL"/>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
    <script type="text/javascript">
<?php

     # Print JQuery scripts for each table so that there is a working select all columns checkbox
     foreach ($checkTables as $table) {

?>
      jQuery(document).ready(function() {
        //If the user selects / deselect all of the checkboxes in that div tag in the query builder
	$("#checkAllTableColumns<?=$table?>").click(function()  {
	    $('#tableID<?=$table?>').find(':checkbox').attr('checked', this.checked);
	});
      });
<?php
     } #foreach
?>
    </script>
    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
<?php


// Assign values if request
if ( isset($_REQUEST['submitStep2']) == 'Next' ) {
    $formErrorFlag = 1;  // guilty until proven innocent
    $tables = $PSPSSchema->getTables();
    foreach ($tables as $table) {
        $checkTableColumn = "check{$table}Column"; // form name for column
        $columns = array();

        if ( in_array( $table, $checkTables ) && !empty( $_REQUEST[$checkTableColumn] ) ) {
            $formErrorFlag = 0; // proven innocent
            $columns = $_REQUEST[$checkTableColumn];
        }
        $PSPSSchema->setCheckTableColumns( $table, $columns );
    } //foreach

    if ( empty( $formErrorFlag ) ) {
        print "<meta http-equiv=\"refresh\" content=\"0;url=$nextStep\" />\n";
    }
} //if
?>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script type="text/javascript" src="javascript/psi_jquery.js"></script>
<?php require_once("menubar_header.php"); ?>
</head>
<body>
<?php require_once("top.php"); ?>
<?php require_once("menubar.php"); ?>
<div id="main">
<div style="text-align: center;">
<h2><?=$PSIHelp->getWikiURL('PSI-QueryBuilderSelectingAttributesfromTables')?>&nbsp;<?=TITLE?></h2>
<form name="step2" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
<table style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="3">
    <tr>
        <td align="right"><strong>Survey (Database):</strong></td>
        <td align="left"><?=$QueryHandle->getUserSchema() ?></td>
    </tr>
    <tr>
<?php
    $checkFilterLetters = join(', ', $PSPSSchema->getCheckAstroFilterLetters());
    if ( empty( $checkFilterLetters ))
        $checkFilterLetters = 'None';
?>
        <td align="right"><strong>Filters:</strong></td>
        <td align="left"><?= $checkFilterLetters ?></td>
    </tr>
    <tr>
        <td align="right">
            <strong><?= count ($checkTables) > 1 ? 'Tables' : 'Table' ?>:</strong>
        </td>
        <td align="left"><?= join (', ', $checkTables) ?></td>
    </tr>
    <tr>
        <td colspan="2">
            <strong>Please click the columns you wish to view from each table.  To collapse or expand the column list; click the plus/minus icon accordingly.</strong>
        </td>
    </tr>
</table>
<br />
<?php
// Print the columns for each table select
foreach ($checkTables as $table) {
    if ($selectColumnViewFormat == 'short') {
        printShortColumnNames ( $table );
    }
    else if ($selectColumnViewFormat == 'full') {
        printFullColumnNames ( $table );
    }
    else {
        echo "Error... unkown column view format {$selectColumnViewFormat}.";
    }
} //foreach
?>
<?php
/**
 * Prints out the table columns in a short format
 *
 * @param  table The table name that contain the columns to be printed
 * @return nothing
 */
function printShortColumnNames ( $table )
{
    GLOBAL $MAX_COLS;
    global $PSPSSchema;

    $tableColumns = $PSPSSchema->getTableColumns($table);  // Get all Table Columns
    $checkTableColumns = $PSPSSchema->getCheckTableColumns( $table );  // Get selected Table Columns
    $checkAll = '';
    // Find out if we have to set teh select/deselect all checkbox
    if ( count( $tableColumns ) == count( $checkTableColumns ) ) {
	$checkAll = 'checked="checked"';
    }

    print <<<EOF
        <div class="toggle_menu">
            <h2 class="toggle_menu_button">
                <img src="images/plus.gif" alt="+" />
                <img src="images/minus.gif" alt="-" />
                $table
            </h2>
        </div>
        <div class='toggle_menu_item'>
            <table id="tableID$table" style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="3" width="90%">
            <tr>
              <th align="center" colspan="$MAX_COLS">
                <input name="checkAllTableColumns$table" type="checkbox" id="checkAllTableColumns$table" $checkAll />Select/Delect All Columns
              </th>
           </tr>
EOF;
    $rows = sizeof($tableColumns);
    $yLimit = (int) (($rows + $MAX_COLS - 1) / $MAX_COLS);
    $tdWidth = (int) (100 / $MAX_COLS);

    for ($y = 0; $y < $yLimit; $y++) {
        print "<tr>\n";
        for ($x = 0; $x < $MAX_COLS; $x++) {
            $i = $x * $yLimit + $y;
            if ($i >= $rows) {
                print "<td>&nbsp;</td>\n";
                continue;
            }

            $columnName = $tableColumns[$i];
            $checked = ( isset( $checkTableColumns ) && in_array($columnName, $checkTableColumns )) ? 'checked="checked"' : '';

            print <<<EOF
                  <td align="left" width="$tdWidth%">
		      <label for="$table:$columnName"/>
                      <input $checked type="checkbox" name="check{$table}Column[]" value="$columnName" id="$table:$columnName"/>$columnName
                  </td>
EOF;
        } // for $x
        print "\t</tr>\n";
    } // for $y
    print <<<EOF
            </table>
       </div>
EOF;
} // function printShortColumnNames().

/**
 * Prints out the table columns in a long format includes datatypes and descriptions
 *
 * @param  table The table name that contain the columns to be printed
 * @return nothing
 */
function printFullColumnNames ( $table )
{
    global $PSPSSchema;
    $tableColumnsHash = $PSPSSchema->getTableColumnsHash( $table );
    $checkTableColumns = $PSPSSchema->getCheckTableColumns( $table );
    $checkAll = '';
    // Find out if we have to set teh select/deselect all checkbox
    if ( count( $tableColumnsHash ) == count( $checkTableColumns ) ) {
	$checkAll = 'checked="checked"';
    }

    print <<<EOF
        <div class="toggle_menu">
            <h2 class="toggle_menu_button">
                <img src="images/plus.gif" alt="+" />
                <img src="images/minus.gif" alt="-" />
                $table
            </h2>
        </div>
        <div class='toggle_menu_item'>
                <table id="tableID$table" style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="3" width="75%">
                    <tr>
                        <th><input name="checkAllTableColumns$table" type="checkbox" id="checkAllTableColumns$table" $checkAll />Column Name</th>
                        <th>Unit</th>
                        <th>Data Type</th>
                        <th>Size</th>
                        <th>Default Value</th>
                        <th>Description</th>
                    </tr>
EOF;
    // Print out each column and its details
    foreach( $tableColumnsHash as $columnName => $columnHash) {
        $checked = ( isset( $checkTableColumns ) && in_array($columnName, $checkTableColumns )) ? 'checked="checked"' : '';
        print <<<EOF
                    <tr>
                        <td align="left">
			  <label for="$table:$columnName"/>
			  <input type="checkbox" name="check{$table}Column[]" value="$columnName" id="$table:$columnName" $checked />$columnName</td>
                        <td align="left">{$columnHash['Unit']}</td>
                        <td align="left">{$columnHash['DataType']}</td>
                        <td align="left">{$columnHash['Size']}</td>
                        <td align="left">{$columnHash['Default']}</td>
                        <td align="left">{$columnHash['Description']}</td>
                    </tr>
EOF;
    } // for rows.
        print <<<EOF
                </table>
            </div>
EOF;
} // function printFullColumnNames().

?>
<div align="center">
    <table border="0" width="75%" cellpadding="0" cellspacing="0">
    <?php
        if ( !empty($formErrorFlag) ) {
?>
         <tr>
            <td colspan = "3" align="center">
                <h3 style="color: red">Error: You must select some columns from at least one table before proceeding.</h3>
             </td>
        </tr>
<?php
        } //if
?>
        <tr>
            <td align="center">
                <input type="button" name="buttonStartOver" value="Start Over" onclick="confirmStartOver();" />
            </td>
            <td align="center">
                <input type="button" value="Survey, Filters, Tables"
                    onclick="window.location.href='<?=$previousStep1?>'" />
            </td>
            <td width="33%" align="center">
                <input type="submit" name="submitStep2" value="Next" id="submit-button" />
            </td>
        </tr>
    </table>
</div>
</form>
</div>
<!-- End Content -->
</div>
<br />
<!-- End Main -->
<?php
  if (isset ($PSPSSchema)) {
      // ALWAYS ALWAYS ADD class to session if it is set.
      $_SESSION["PSPSSchemaClass"] = serialize($PSPSSchema);
  }

  require_once("bottom.php");
?>
</body>
</html>
