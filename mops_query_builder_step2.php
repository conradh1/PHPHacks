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
 * @author Conrad Holmberg, Haydn Huntley, drchang@ifa.hawaii.edu
 * @since Beta version 2010
 */

require_once ("session.php");
require_once ("session.php");
require_once ("MOPSSchemaClass.php");
define('TITLE','PSI MOPS Query Builder: Select Table Columns');

// Needed globals
global $MOPSSchema;
$previousStep1 = 'mops_query_builder_step1.php';
$nextStep = 'mops_query_builder_step3.php';
$formErrorFlag = 0;  //Flag to check for errors
$MAX_COLS = 6;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php

// Assign the MOPS Schema object
if ( isset( $_SESSION["MOPSSchemaClass"] ) ) {
    $MOPSSchema = unserialize($_SESSION["MOPSSchemaClass"]);
}
else {
    // If no MOPS Schema, forward to step 1
    header ('Location: mops_query_builder_step1.php');
}

// Assign variables from previous query builder step.
$selectColumnViewFormat =  $MOPSSchema->getSelectColumnViewFormat();
$checkTables = $MOPSSchema->getCheckTables();
$catalogSelection = $MOPSSchema->getCatalogSelection();
$tableNames = array();

// Change table names because MOPS uses single quotes
foreach($checkTables as $t) {
    array_push($tableNames, $MOPSSchema->removeQuotes($t));
}

?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface MOPS Query Builder Select Table Columns"/>
    <meta name="keywords" content="Pan-STARRS Science Web Interface Astronomy MOPS Query Builder Select Table Columns SQL"/>
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
// Prepare for advancing to step 3
// @note since we have a user session is this the best way to handle form submission, by a refresh?
if ( isset($_REQUEST['submitStep2']) == 'Next' ) {
    $formErrorFlag = 1;  // guilty until proven innocent
    $tables = $MOPSSchema->getTables();

    foreach ($tables as $table) {
        $checkTableColumn = "check{$table}Column"; // form name for column

        $columns = array();

        if ( in_array( $table, $checkTables ) && !empty( $_REQUEST[$checkTableColumn] ) ) {
            $formErrorFlag = 0; // proven innocent
            $columns = $_REQUEST[$checkTableColumn];
        }

        $MOPSSchema->setCheckTableColumns( $table, $columns );
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
<?php
require ("top.php");
require_once("menubar.php");
?>
<div id="main">
<div style="text-align: center;">
<h2><?= TITLE ?></h2>

<form name="step2" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
    <table style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="3">
        <tr>
            <td align="center">
                <strong><?= count ($checkTables) > 1 ? 'Tables' : 'Table' ?>:</strong>
                <?= join (', ', $tableNames) ?>
            </td>
        </tr>
        <tr>
            <td>
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
        if($selectColumnViewFormat == '') {
            $selectColumnViewFormat = "(no selection)";
        }
        echo "Error: Unkown column view format {$selectColumnViewFormat}.";
    }

    // Allow select all for det_rawattr_v2
    if($table == "'det_rawattr_v2'") {
?>
        <div>
            Select <a href="javascript:checkAll(1, 'check\'det_rawattr_v2\'Column[]', 'step2');">All</a> | <a href="javascript:checkAll(0, 'check\'det_rawattr_v2\'Column[]', 'step2');">None</a>
        </div>
<?php
    } // if table
} //foreach
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
                    <input type="button" name="buttonStartOver"
                           value="Start Over" onclick="confirmStartOverMopsQb();" />
                </td>
                <td align="center">
                    <input type="button" value="Tables"
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
<br/>
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
    global $MOPSSchema;

    //$table = $MOPSSchema->removeQuotes( $table );

    $tableColumns = $MOPSSchema->getTableColumns($table);  // Get all Table Columns
    $checkTableColumns = $MOPSSchema->getCheckTableColumns( $table );  // Get selected Table Columns
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
                <input name="checkAllTableColumns$table" type="checkbox" id="checkAllTableColumns$table" $checkAll/>Select/Delect All Columns
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
                      <input $checked type="checkbox" name="check{$table}Column[]" value="$columnName" id="$table:$columnName"/>
EOF;
            print $MOPSSchema->removeQuotes($columnName);
print <<<EOF
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

// name="check{$table}Column[]"

/**
 * Prints out the table columns in a long format includes datatypes and descriptions
 *
 * @param  table The table name that contain the columns to be printed
 * @return nothing
 */
function printFullColumnNames ( $table )
{
    global $MOPSSchema;
    $tableColumnsHash = $MOPSSchema->getTableColumnsHash( $table );
    $checkTableColumns = $MOPSSchema->getCheckTableColumns( $table );
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
EOF;
    print $MOPSSchema->removeQuotes($table);
    print <<<EOF
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
    foreach( $tableColumnsHash as $columnName => $columnHash ) {

        $checked = ( isset( $checkTableColumns ) && in_array($columnName, $checkTableColumns )) ? 'checked="checked"' : '';

        $desc =  $MOPSSchema->removeQuotes($columnHash['Description']);
        $datatype = $MOPSSchema->removeQuotes($columnHash['DataType']);
        $tbl = $MOPSSchema->removeQuotes($table);
        $cn = $MOPSSchema->removeQuotes($columnName); // column name
        $dflt = $MOPSSchema->removeQuotes($columnHash['Default']); // default value
        $sz = $MOPSSchema->removeQuotes($columnHash['Size']); // size

        print <<<EOF
                <tr>
                    <td align="left"><label for="$table:$columnName"/><input type="checkbox" name="check{$table}Column[]" value="$columnName" id="$table:$columnName" $checked />$cn</td>
                    <td align="left">{$columnHash['Unit']}</td>
                    <td align="left">{$datatype}</td>
                    <td align="left">{$sz}</td>
                    <td align="left">{$dflt}</td>
                    <td align="left">{$desc}</td>
                </tr>
EOF;
    } // foreach tableColumnsHash
        print <<<EOF
            </table>
        </div>
EOF;
} // function printFullColumnNames().
