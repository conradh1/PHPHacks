<?php
/**
 * This script is the third step in creating a query dynamically
 *
 * The third step in building a dynamic query involves the following to be completed:
 *  -Choosing columns based on the table
 *  -Selecting filters
 *  -Selecting a spacial constraint
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley
 * @since Beta version 2010
 */
require_once ("session.php");
require_once ("PSPSSchemaClass.php");
define('TITLE','PSI PSPS Query Builder: Query Builder: Set Constraints');

global $PSPSSchema;
global $PSIHelp;
global $previousStep1;
global $previousStep2;
global $nextStep;
// Globals used in forms
global $radioSpacialConstraint;
global $selectSurvey;
global $surveyHash;
global $checkTables;
global $textBoxRa;
global $textBoxDec;
global $textBoxSize;
global $selectBoxUnits;
global $textConeRa;
global $textConeDec;
global $textConeRadius;
global $selectConeUnits;
global $selectRowLimit;
global $formTableColumnFilterHash;

$PSIHelp = $PSISession->getHelpObject();
$previousStep1 = 'query_builder_step1.php';
$previousStep2 = 'query_builder_step2.php';
$nextStep = 'query_builder_step4.php';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php

// Assign the PSPS Schema object
if ( isset( $_SESSION["PSPSSchemaClass"] ) ) {
  $PSPSSchema = unserialize($_SESSION["PSPSSchemaClass"]);
}
else {
  // If no PSPS Schema, forward to step 1
  header ('Location: query_builder_step1.php');
}
?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Query Builder Set Constraints"/>
    <meta name="keywords" content="Pan-STARRS Science Web Interface Astronomy Query Builder Set Constraints SQL"/>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <script type="text/javascript">
      jQuery(document).ready(function() {
        //If the user selects / deselect all of the checkboxes in that div tag in the query builder
	$("#checkAllSelectColumns").click(function()  {
	    $('#formStep3Filters').find(':checkbox').attr('checked', this.checked);
	});
      });
    </script>
    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
<?php

// Obtain form variables from PSPSSchema Object
$radioSpacialConstraint = $PSPSSchema->getRadioSpacialConstraint();
#$selectSurvey = $PSPSSchema->getSelectSurvey();
#$surveyHash = $PSPSSchema->getSurveyHash( $selectSurvey );
$checkTables = $PSPSSchema->getCheckTables();
$textBoxRa = $PSPSSchema->getTextBoxRa();
$textBoxDec = $PSPSSchema->getTextBoxDec();
$textBoxSize = $PSPSSchema->getTextBoxSize();
$selectBoxUnits = $PSPSSchema->getSelectBoxUnits();
$textConeRa = $PSPSSchema->getTextConeRa();
$textConeDec = $PSPSSchema->getTextConeDec();
$textConeRadius = $PSPSSchema->getTextConeRadius();
$selectConeUnits = $PSPSSchema->getSelectConeUnits();
$selectRowLimit = $PSPSSchema->getSelectRowLimit();
$formTableColumnFilterHash = $PSPSSchema->getFormTableColumnFilterHash();
?>
<?php require_once("menubar_header.php"); ?>
</head>
<body>
<?php require_once("top.php"); ?>
<?php require_once("menubar.php"); ?>
<div id="main">
<div align="center">
<h2><?=$PSIHelp->getWikiURL('PSI-QueryBuilderRestrictingAttributeRangesApplyingSpatialConstraintsOutputLimits')?>&nbsp;<?=TITLE?></h2>
<form name="step3" id="formStep3Filters" method="post" action="<?=$nextStep?>">
<table border="0" cellpadding="3" cellspacing="0">
  <tr>
    <td align="right"><strong>Survey (Database):</strong></td>
    <td align="left"><?=$QueryHandle->getUserSchema() ?></td>
  </tr>
  <tr>
    <td align="right"><strong>Filters:</strong></td>
<?php
    $checkFilterLetters = join(', ', $PSPSSchema->getCheckAstroFilterLetters());
    if ( empty( $checkFilterLetters ))
        $checkFilterLetters = 'None';
?>
    <td align="left"><?= $checkFilterLetters?></td>
  </tr>
  <tr>
    <td align="right">
      <strong><?= count ($checkTables) > 1 ? 'Tables' : 'Table' ?>:</strong>
    </td>
    <td align="left"><?= join (', ', $checkTables) ?></td>
  </tr>
</table>
<table style="margin: 0 auto;" border="1" cellpadding="3" cellspacing="1">
  <tr style="font-weight: bold">
    <td rowspan="2" align="center">Columns</td>
    <td rowspan="2" align="center">
         <input type="checkbox" name="checkAllMyDBTables" id="checkAllSelectColumns" checked="checked"/> Select
    </td>
    <td colspan="2" align="center">Lower Limit</td>
    <td rowspan="2" align="center">Range<br/>Logical<br/>Operator</td>
    <td colspan="2" align="center">Upper limit</td>
    <!-- <td rowspan="2" align="center">Column<br/>Logical<br/>Operator</td> -->
  </tr>
  <tr style="font-weight: bold">
    <td align="center">Op</td>
    <td align="center">Value</td>
    <td align="center">Op</td>
    <td align="center">Value</td>
  </tr>

<?php


$isFirstColumn = true; // used for operator that isn't needed in the very first column selected.
// Print out a filter for each column selected from each table
foreach ($checkTables as $table) {
    $checkTableColumn = "check{$table}Column"; // form name for column
    $columns = $PSPSSchema->getCheckTableColumns( $table );


    if (count ($columns) > 0) {
        foreach ($columns as $column) {
            //print filter for each column
?>
       <tr>
          <td align="left"><?= $table .'.'. $column ?></td>
          <td align="center">
<?
//             $columnChecked = '';
// 	    if ( isset( $formTableColumnFilterHash[$table][$column]['checkColumn'] )) {
//               $columnChecked = ' checked="checked"';
//             }
?>
            <input type="checkbox" name="checkColumn_<?=$table?>_<?=$column?>" value="<?=$column?>" checked="checked"/>
          </td>
          <td>
             <strong>(</strong>
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
             $selectRangeLogicOper = '';
               if ( isset( $formTableColumnFilterHash[$table][$column]['selectRangeLogicOper'] ))
                    $selectRangeLogicOper = $formTableColumnFilterHash[$table][$column]['selectRangeLogicOper'];
?>
             <select name="selectRangeLogicOper_<?=$table?>_<?=$column?>">
               <option <?=$selectRangeLogicOper == 'AND' ? 'selected="selected"' : ''?> value="AND">AND</option>
               <option <?=$selectRangeLogicOper == 'OR' ? 'selected="selected"' : ''?> value="OR">OR</option>
             </select>
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
             <strong>)</strong>
          </td>
          <td>
<?php
        $textMaxValue = '';
        if ( isset( $formTableColumnFilterHash[$table][$column]['textMaxValue'] ))
            $textMaxValue = $formTableColumnFilterHash[$table][$column]['textMaxValue'];
?>
            <input type="text" size="11" name="textMaxValue_<?=$table?>_<?=$column?>" value="<?=$textMaxValue?>" />
            <input type="hidden" name="selectColumnLogicOper_<?=$table?>_<?=$column?>" value="AND"/>
          </td>
          <!--<td>
<?php
           # TODO Implement AND column logical operator.
           #exception for the very first column that doesn't need a logical operator before it. This is parse out later
           if ( $isFirstColumn ) {
               $isFirstColumn = false;
?>
             <input type="hidden" name="selectColumnLogicOper_<?=$table?>_<?=$column?>" value="AND"/>
<?php
           }
           else {
	     $selectColumnLogicOper = '';
               if ( isset( $formTableColumnFilterHash[$table][$column]['selectColumnLogicOper'] ))
                    $selectColumnLogicOper = $formTableColumnFilterHash[$table][$column]['selectColumnLogicOper'];
?>
             <select name="selectColumnLogicOper_<?=$table?>_<?=$column?>">
               <option <?=$selectColumnLogicOper == 'AND' ? 'selected="selected"' : ''?> value="AND">AND</option>
               <option <?=$selectColumnLogicOper == 'OR' ? 'selected="selected"' : ''?> value="OR">OR</option>
             </select>
<?php
           } #else
?>
          </td>-->
        </tr>
<?php
        } // foreach $column
    } // if (count ($columns) > 0)
} // foreach $table

?>
      <tr>
        <td colspan="8" align="center">
          <br />
          <table border="0" cellpadding="3" cellspacing="0">
            <tr>
              <td colspan="5" align="center" style="font-weight: bold">
                Spatial Constraints:
              </td>
            </tr>
            <tr>
              <td colspan="5">
                <script type="text/javascript">
                    // Clears the Spacial Constraint Fields if user click 'None'
                    function clearSpacialConstraintFields( type ) {
                        if ( type == 'box' || type == 'all' ) {
                            document.step3.textBoxRa.value = '';
                            document.step3.textBoxDec.value = '';
                            document.step3.textBoxSize.value = '';
                        }
                        if ( type == 'cone' || type == 'all' ) {
                            document.step3.textConeRa.value = '';
                            document.step3.textConeDec.value = '';
                            document.step3.textConeRadius.value = '';
                        }
                    } //clearSpacialConstraintFields
                </script>
                <input type="radio" name="radioSpacialConstraint" value="None" <?=( $radioSpacialConstraint == 'None' ? 'checked="checked"' : '')?> onclick="javascript: clearSpacialConstraintFields('all');" />None
              </td>
            </tr>
            <tr>
              <td valign="top">
                <input type="radio" name="radioSpacialConstraint" value="Box" <?=( $radioSpacialConstraint == 'Box' ? 'checked="checked"' : '')?> onclick="javascript: clearSpacialConstraintFields('cone');" />Box
              </td>
              <td>
                Ra: <input type="text" size="5" name="textBoxRa" value="<?=$textBoxRa?>" />
              </td>
              <td>
                Dec: <input type="text" size="5" name="textBoxDec" value="<?=$textBoxDec?>" />
              </td>
              <td align="right">
                Size: <input type="text" size="5" name="textBoxSize" value="<?=$textBoxSize?>" />
              </td>
              <td>
                <select name="selectBoxUnits">
                  <option <?=( $selectBoxUnits == 'box_arcsec' ? 'selected="selected"' : '')?> value="box_arcsec">arc-seconds</option>
                  <option <?=( $selectBoxUnits == 'box_arcmin' ? 'selected="selected"' : '')?> value="box_arcmin">arc-minutes</option>
                  <option <?=( $selectBoxUnits == 'box_degrees' ? 'selected="selected"' : '')?> value="box_degrees">degrees</option>
                </select>
              </td>
            </tr>
            <tr>
              <td valign="top">
                <input type="radio" name="radioSpacialConstraint" value="Cone" <?=( $radioSpacialConstraint == 'Cone' ? 'checked="checked"' : '')?> onclick="javascript: clearSpacialConstraintFields('box');" />Cone
              </td>
              <td>
                Ra: <input type="text" size="5" name="textConeRa" value="<?=$textConeRa?>" />
              </td>
              <td>
                Dec: <input type="text" size="5" name="textConeDec" value="<?=$textConeDec?>" />
              </td>
              <td>
                Radius: <input type="text" size="5" name="textConeRadius" value="<?=$textConeRadius?>" />
              </td>
              <td>
                <select name="selectConeUnits">
                  <option <?=( $selectConeUnits == 'cone_arcsec' ? 'selected="selected"' : '')?> value="cone_arcsec">arc-seconds</option>
                  <option <?=( $selectConeUnits == 'cone_arcmin' ? 'selected="selected"' : '')?> value="cone_arcmin">arc-minutes</option>
                  <option <?=( $selectConeUnits == 'cone_degrees' ? 'selected="selected"' : '')?> value="cone_degrees">degrees</option>
                </select>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td colspan= "8">&nbsp;&nbsp;
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
           <strong>Table Name:</strong> <input name="myDbTable" size="12" value="<?= $QueryHandle->getUserMyDbTable() ?>"/><br/><br/>
           The fast queue has a timeout limit of one minute and used for returning a small number of rows.<br/>
           Use the the slow queue if you imagine your query taking a longer time and/or it will return too many rows to be displayed on your browser.<br/>
        </td>
      </tr>
    </table>
    <br />
    <table border="0"  cellpadding="5" cellspacing="5">
      <tr>
        <td align="center">
            <input type="button" name="buttonStartOver"
                   value="Start Over" onclick="confirmStartOver();" />
        </td>
        <td align="center">
          <input type="button" value="Survey, Filters, Tables"
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
  if (isset ($PSPSSchema)) {
      // ALWAYS ALWAYS ADD class to session if it is set.
      $_SESSION["PSPSSchemaClass"] = serialize($PSPSSchema);
  }
  require_once("bottom.php");
?>
</body>
</html>
