<?php
/**
 * This script is the third step in creating a query dynamically
 *
 * The third step in building a dynamic query involves the following to be completed:
 *  -Choosing columns based on the table
 *  -Selecting a spacial constraint
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley, drchang@ifa.hawaii.edu
 * @since Beta version 2010
 */
require_once ("session.php");

define('TITLE','PSI MOPS Query Builder: Set Constraints');

global $MOPSSchema;
$previousStep1 = 'mops_query_builder_step1.php';
$previousStep2 = 'mops_query_builder_step2.php';
$nextStep = 'mops_query_builder_step4.php';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
require_once ("session.php");

// Assign the MOPS Schema object
if ( isset( $_SESSION["MOPSSchemaClass"] ) ) {
  $MOPSSchema = unserialize($_SESSION["MOPSSchemaClass"]);
}
else {
  // If no MOPS Schema, forward to step 1
  header ('Location: mops_query_builder_step1.php');
}
?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Query Builder Set Constraints"/>
    <meta name="keywords" content="Pan-STARRS Science Web Interface Astronomy Query Builder Set Constraints SQL"/>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
<?php
// Obtain form variables from MOPSSchema Object
$checkTables = $MOPSSchema->getCheckTables();
$catalogSelection = $MOPSSchema->getCatalogSelection();
$selectRowLimit = $MOPSSchema->getSelectRowLimit();
$formTableColumnFilterHash = $MOPSSchema->getFormTableColumnFilterHash();
?>
<?php require_once("menubar_header.php"); ?>
</head>
<body>
<?php require_once("top.php"); ?>
<?php require_once("menubar.php"); ?>
<div id="main">
<div align="center">
<h2><?= TITLE ?></h2>
<form name="step3" method="post" action="<?=$nextStep?>">
    <table border="0" cellpadding="3" cellspacing="0">
        <tr>
            <td align="right">
                <strong><?= count ($checkTables) > 1 ? 'Tables' : 'Table' ?>:</strong>
            </td>
            <td align="left"><?= join (', ', $MOPSSchema->removeQuotes($checkTables)) ?></td>
        </tr>
    </table>

    <table style="margin: 0 auto;" border="1" cellpadding="3" cellspacing="0">
        <tr style="font-weight: bold">
            <td colspan="2">&nbsp;</td>
            <td colspan="2" align="center">Lower Limit</td>
            <td colspan="2" align="center">Upper limit</td>
        </tr>
        <tr style="font-weight: bold">
            <td align="center">Columns</td>
            <td align="center">Select</td>
            <td align="center">Op</td>
            <td align="center">Value</td>
            <td align="center">Op</td>
            <td align="center">Value</td>
        </tr>
<?php

// Print out a filter for each column selected from each table
foreach ($checkTables as $table) {
    $checkTableColumn = "check{$table}Column"; // form name for column
    $columns = $MOPSSchema->getCheckTableColumns( $table );

    if (count ($columns) > 0) {
        foreach ($columns as $column) {
?>
        <tr>
            <td align="left"><?= $MOPSSchema->removeQuotes($table) .'.'. $MOPSSchema->removeQuotes($column) ?></td>
            <td align="center">
                <input type="checkbox" name="checkColumn_<?=$MOPSSchema->removeQuotes($table)?>_<?=$MOPSSchema->removeQuotes($column)?>" value="<?=$MOPSSchema->removeQuotes($column)?>" checked="checked"/>
            </td>
            <td>
                <select name="selectMinOper_<?=$table?>_<?=$column?>">
<?php
            $selectMinOper = '';

            if ( isset( $formTableColumnFilterHash[$table][$column]['selectMinOper'] ))
                $selectMinOper = $formTableColumnFilterHash[$table][$column]['selectMinOper'];
?>
                    <option <?=$selectMinOper == 'gt' ? 'selected="selected"' : ''?> value="gt">&gt;</option>
                    <option <?=$selectMinOper == 'gteq' ? 'selected="selected"' : ''?> value="gteq">&gt;=</option>
                    <option <?=$selectMinOper == 'eq' ? 'selected="selected"' : ''?> value="eq">=</option>
                    <option <?=$selectMinOper == 'neq' ? 'selected="selected"' : ''?> value="neq">&lt;&gt;</option>
                </select>
            </td>
            <td>
<?php
            $textMinValue = '';
            if ( isset( $formTableColumnFilterHash[$table][$column]['textMinValue'] ))
                $textMinValue = $formTableColumnFilterHash[$table][$column]['textMinValue'];
?>
                <input type="text" size="11" name="textMinValue_<?=$table?>_<?=$column?>" value="<?=$textMinValue?>" />
            </td>
            <td>
<?php
            $selectMaxOper = '';
            if ( isset( $formTableColumnFilterHash[$table][$column]['selectMaxOper'] ))
                $selectMaxOper = $formTableColumnFilterHash[$table][$column]['selectMaxOper'];
?>
                <select name="selectMaxOper_<?=$table?>_<?=$column?>">
                    <option <?=$selectMaxOper == 'lt' ? 'selected="selected"' : ''?> value="lt">&lt;</option>
                    <option <?=$selectMaxOper == 'lteq' ? 'selected="selected"' : ''?> value="lteq">&lt;=</option>
                    <option <?=$selectMaxOper == 'eq' ? 'selected="selected"' : ''?> value="eq">=</option>
                    <option <?=$selectMinOper == 'neq' ? 'selected="selected"' : ''?> value="neq">&lt;&gt;</option>
                </select>
            </td>
            <td>
<?php
            $textMaxValue = '';
            if ( isset( $formTableColumnFilterHash[$table][$column]['textMaxValue'] ))
                $textMaxValue = $formTableColumnFilterHash[$table][$column]['textMaxValue'];
?>
                <input type="text" size="11" name="textMaxValue_<?=$table?>_<?=$column?>" value="<?=$textMaxValue?>" />
            </td>
        </tr>
<?php
        } // foreach $column
    } // if (count ($columns) > 0)
} // foreach $table

?>
        <tr>
            <td colspan = "6">&nbsp;&nbsp;
                <strong>Row Limit:</strong>
                <select name="selectRowLimit">
                    <option <?=( $selectRowLimit == '100' ? 'selected="selected"' : '')?> value="100">100</option>
                    <option <?=( $selectRowLimit == '1000' ? 'selected="selected"' : '')?> value="1000">1000</option>
                    <option <?=( $selectRowLimit == '10000' ? 'selected="selected"' : '')?> value="10000">10,000</option>
                    <option <?=( $selectRowLimit == '0' ? 'selected="selected"' : '')?> value="0">No Limit </option>
                </select>
            </td>
        </tr>
        <tr>
        <td colspan= "8">
           <strong>What queue do you want to use?</strong><br/>
           <input type="radio" name="queue" value="fast" <?= $QueryHandle->getUserQueue() == 'fast' ? 'checked="checked"' : '' ?>/>
           <strong>Fast Queue</strong>- Results are displayed in the browser.&nbsp;<br/>
           <input type="radio" name="queue" value="slow" <?= $QueryHandle->getUserQueue() == 'slow' ? 'checked="checked"' : '' ?>/>
           <strong>Slow Queue</strong>- Results are inserted into a table in your personal database (MyDB).&nbsp;
           <strong>Table Name:</strong> <input name="myDbTable" size="12" value="<?= $QueryHandle->getUserMyDbTable() ?>"/><br><br/>
           The fast queue has a timeout limit of one minute and used for returning a small number of rows.<br/>
           Use the the slow queue if you imagine your query taking a longer time and/or it will return too many rows to be displayed on your browser.<br/>
        </td>
      </tr>
    </table>
    <br/>
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
                <input type="submit" name="submit" value="Next" id="submit-button" />
            </td>
        </tr>
    </table>
</form>
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
