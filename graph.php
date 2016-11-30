<?php

/**
 * This PHP script is used for graphing MyDB tables.
 *
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg
 * @since Beta version 2010
 */
require_once ("session.php");
require_once ("MyDBClass.php");
require_once ("PSIGraphClass.php");
require_once ("PSPSSchemaClass.php");
require_once ("PSIHTMLGoodiesClass.php");
require_once ("PSIAjaxGoodiesClass.php");
define('TITLE','PSI Graphing Page');

// Global Objects
global $PSISession;
global $PSIGraph;
global $MyDB;
global $PSIHTMLGoodies;
global $PSPSSchema;

// Global Variables
global $myDBTable;
global $myDBColumnList;
global $drawGraph; //Boolean on whether to draw graph or not.

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

// Assign other global objects
$PSIHTMLGoodies = new PSIHTMLGoodiesClass();
$MyDB = new MyDBClass( $PSISession );
$PSIGraph = new PSIGraphClass( $PSISession );
$PSIHelp = $PSISession->getHelpObject();

// Assign form variables
$PSIGraph->initGraphVariables();

// Get MyDB Table and Columns
$myDBTable = $PSIGraph->getSelectMyDBTable();
if ( isset( $myDBTable ) )
  $myDBColumnList = $MyDB->getTableColumnsList( $myDBTable );

//Find out if we are drawing a graph
if ( isset($_REQUEST['submitGraph']) )
  $drawGraph = 1;
else
  $drawGraph = 0;

// Handle the AJAX calls
if ( !empty( $_REQUEST['ajaxAction'] ) ) {
  $ajaxAction = $_REQUEST['ajaxAction'];
  switch ( $ajaxAction ) {
      case 'getMyDBTableColumnsXML':
        $PSIAjaxGoodies = new PSIAjaxGoodiesClass();
        $PSIAjaxGoodies->myDBColumns2XML( $myDBTable, $myDBColumnList );
        exit();
      default:
        // Error with unkown action
        print "Unkown Ajax action $ajaxAction";
        break;
  } //switch
} //if
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Query Page"/>
<meta name="keywords" content="Pan-STARRS Science Web Interface Astronomy Graphing Plotting Page MyDB"/>
<script type="text/javascript" src="javascript/psi_utils.js"></script>
<!--[if IE]>
<script type="text/javascript" src="javascript/flot/excanvas.min.js"></script>
<script type="text/javascript" src="javascript/excanvas/canvas.text.js"></script>
<script type="text/javascript" src="javascript/excanvas/faces/optimer-normal-normal.js"></script>
<![endif]-->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script type="text/javascript" src="javascript/flot/jquery.flot.js"></script>
<link href="css/default.css" rel="stylesheet" />
<link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
<title><?= TITLE ?></title>
<script type="text/javascript">

// JQuery handling
jQuery(document).ready(function() {
  // Change the table, change teh X/Y Axis
<?php
  // Decide whether to print hide the Filter select in the form.
  if (!preg_match ('/projection$/i', $PSIGraph->getSelectGraphType() ) )
    print '$("#idDivSelectAstroFilterIDColumn").hide()'."\n";
?>

  // For Submit action.
  if ( $('#idSelectGraphType').val().match(/bar/gi) ||
       $('#idSelectGraphType').val().match(/histogram/gi)) {
    $("#idYAxisDiv").hide(); $("#idTR_YAxisRange").hide();
  }
  else { $("#idYAxisDiv").show(); $("#idTR_YAxisRange").show(); }

  if ( $('#idSelectGraphType').val().match(/histogram/gi) ) { $("#idDivHistogramBins").show(); }
  else { $("#idDivHistogramBins").hide(); }

  if ( $('#idSelectGraphType').val().match(/projection/gi) ) { $('#idInverse').hide(); }
  else { $('#idInverse').show(); }

  //Call to get columns if user switches MyDB tables
  $('#idSelectMyDBTable').change(function() {
    $.get("<?=$_SERVER['PHP_SELF']?>?ajaxAction=getMyDBTableColumnsXML&selectMyDBTable="+$("#idSelectMyDBTable").val(), function(xml) {
      var myDBTableColumns = new Array()
      $(xml).find('column').each(function() {
        var column = $(this).text()
        myDBTableColumns.push(column)
      });
      setSelectColumns( myDBTableColumns, $('#idSelectGraphType').val() );
    }, 'xml')
    // Reset Range labels

    if ( $('#idSelectGraphType').val().match(/bar/gi) ||
         $('#idSelectGraphType').val().match(/histogram/gi) ) {
      $("#idYAxisDiv").hide(); $("#idTR_YAxisRange").hide();
    }
    else { $("#idYAxisDiv").show(); $("#idTR_YAxisRange").show(); }

    if ( $('#idSelectGraphType').val().match(/projection/gi) ) {
      $("#idDivXAxisRangeColumn").html( 'Ra' )
      $("#idDivYAxisRangeColumn").html( 'Dec' )
      $('#idInverse').hide();
    }
    else {
      $("#idDivXAxisRangeColumn").html( 'X Axis' )
      $("#idDivYAxisRangeColumn").html( 'Y Axis' )
      $('#idInverse').show();
    }
  })
  // Add labels for X/Y Axis based on Graph Type
  $('#idSelectGraphType').change(function() {

    if ( $('#idSelectGraphType').val().match(/bar/gi) ||
         $('#idSelectGraphType').val().match(/histogram/gi) ) {
      $("#idYAxisDiv").hide(); $("#idTR_YAxisRange").hide();
    }
    else { $("#idYAxisDiv").show(); $("#idTR_YAxisRange").show(); }

    if ( $('#idSelectGraphType').val().match(/histogram/gi) ) { $("#idDivHistogramBins").show(); }
    else { $("#idDivHistogramBins").hide(); }

    // Case for projection plot which needs the filter
    if ( $('#idSelectGraphType').val().match(/projection/gi) ) {
      $("#idSpanXAxisLabel").html( 'Ra: ' )
      $("#idSpanYAxisLabel").html( 'Dec: ' )
      if ( $("#idDivXAxisRangeColumn").html() ==  'X Axis' )
	$("#idDivXAxisRangeColumn").html( 'Ra' )
      if ( $("#idDivYAxisRangeColumn").html() ==  'Y Axis' )
        $("#idDivYAxisRangeColumn").html( 'Dec' )
      $("#idDivSelectAstroFilterIDColumn").show()
      $('#idInverse').hide()
      setDefaultRaDecSelectColumn();
    }
    // Case for graph plot, hide filter
    else {
      $("#idSpanXAxisLabel").html( 'X Axis: ' )
      $("#idSpanYAxisLabel").html( 'Y Axis: ' )
      if ( $("#idDivXAxisRangeColumn").html() ==  'Ra' )
	$("#idDivXAxisRangeColumn").html( 'X Axis' )
      if ( $("#idDivYAxisRangeColumn").html() ==  'Dec' )
	$("#idDivYAxisRangeColumn").html( 'Y Axis' )
      $("#idDivSelectAstroFilterIDColumn").hide()
      $("#idDivSelectAstroFilterIDColumn").find(':checked').removeAttr('checked');
      $('#idInverse').show();
    }
  });
  // Select a X Axis column, add its name to X Axis Range
  $('#idSelectXAxis').change(function() {
    if ( $("#idSelectXAxis").val() )
      $("#idDivXAxisRangeColumn").html( $("#idSelectXAxis").val() )
    else {
      if ( $('#idSelectGraphType').val().match(/projection/gi) )
	$("#idDivXAxisRangeColumn").html( 'Ra' )
      else
	$("#idDivXAxisRangeColumn").html( 'X Axis' )
    }
  });
  // Select a Y Axis column, add its name to Y Axis Range
  $('#idSelectYAxis').change(function() {
    if ( $("#idSelectYAxis").val() )
      $("#idDivYAxisRangeColumn").html( $("#idSelectYAxis").val() )
    else {
      if ( $('#idSelectGraphType').val().match(/projection/gi) ) {
	$("#idDivYAxisRangeColumn").html( 'Dec' )
      }
      else
	$("#idDivYAxisRangeColumn").html( 'Y Axis' )
    }
  });

  // validation upon submit.
  $('#formMyDBGraph').bind('submit', function(event) {
    $msg = '';
    //alert('validate');
    if ( $('#idSelectMyDBTable').val() == '' ) {
      $msg += 'MyDB Table cannot be empty.';
    }
    if ( $('#idSelectGraphType').val() == '') {
      if ($msg != '') $msg += '\n';
      $msg += 'Graph Type cannot be empty.';
    }

    if ( $('#idSelectXAxis').val() == '') {
      if ($msg != '') $msg += '\n';
      $msg += 'X axis value cannot be empty.';
    }

    // validate xAxis range values. 2011-09-01.
    $v = $('#textXAxisMin').val();
    if ( $v != '' && ! isNumber($v) ) {
      if ($msg != '') $msg += '\n';
      $msg += 'X Axis Range min value \'' + $v + '\' is not a number.';
    }
    $v = $('#textXAxisMax').val();
    if ( $v != '' && ! isNumber($v) ) {
      if ($msg != '') $msg += '\n';
      $msg += 'X Axis Range max value \'' + $v + '\' is not a number.';
    }

    if ( ! $('#idSelectGraphType').val().match(/bar/gi) &&
         ! $('#idSelectGraphType').val().match(/histogram/gi)) {
      if ($('#idSelectYAxis').val() == '') {
        if ($msg != '') $msg += '\n';
        $msg += 'Y axis value cannot be empty.';
      }
      if ($('#idSelectXAxis').val() == $('#idSelectYAxis').val()) {
        if ($msg != '') $msg += '\n';
        $msg += 'X and Y axis values cannot be the same.';
      }

      // validate yAxis range values. 2011-09-01
      $v = $('#textYAxisMin').val();
      if ( $v != '' && ! isNumber($v) ) {
        if ($msg != '') $msg += '\n';
        $msg += 'Y Axis Range min value \'' + $v + '\' is not a number.';
      }
      $v = $('#textYAxisMax').val();
      if ( $v != '' && ! isNumber($v) ) {
        if ($msg != '') $msg += '\n';
        $msg += 'Y Axis Range max value \'' + $v + '\' is not a number.';
      }
    }

    if ( $('#idSelectGraphType').val().match(/histogram/gi) ) {
      if ( ! isPosInt($('#histogramBins').val()) ) {
        if ($msg != '') $msg += '\n';
        $msg += 'Histogram Bins must be a positive integer.';
      }
    }

    if ($msg != '') {
      alert($msg);
      return false;
    }
    return true;
  });

  //If the user select / deselect all astro filters set the same for the rest of the checkboxes in that div tag
  $("#checkAllAstroFilterIDs").click(function()  {
    $('#idDivSelectAstroFilterIDColumn').find(':checkbox').attr('checked', this.checked);
    // If deselect all set filterID to none else fine filterID again
    if ( !$("#checkAllAstroFilterIDs").is(':checked')) {
      $('#idSelectFilterIDColumn').val('')
    }
    else {
         $('#idSelectFilterIDColumn').val('filterID')
         //HACK hard coded filter code below should find best match
//       $('#idSelectFilterIDColumn').each(function() {
//         if ( $(this).val().match(/filter/gi) ) {
//           $(this).attr('selected', true);
//         }
//       });
    }
  });

  // If the user sets the Filter ID column to none, then no filters are unchecked.
  $("#idSelectFilterIDColumn").click(function()  {
    if ( $('#idSelectFilterIDColumn').val() == '' ) {
      $('#idDivSelectAstroFilterIDColumn').find(':checkbox').attr('checked', false);
    }
    else if ( $('#idSelectFilterIDColumn').val().match(/filter/gi) ) {
      $('#idDivSelectAstroFilterIDColumn').find(':checkbox').attr('checked', true);
    }
  });

  $("#saveImage-button").click(function() {
    //if (document.getElementById("map") != NULL)
    window.open(map.toDataURL());
  });

}); //JQuery ready

// Searches for a default Ra (i.e., raBore columm)
function setDefaultRaDecSelectColumn() {
  // Find the best X Axis table column that matches ra
  for ( var i = 1; i < document.formMyDBGraph.selectXAxis.length; i++ ) {
    if ( document.formMyDBGraph.selectXAxis[i].value.match(/^ra/i) ||
         document.formMyDBGraph.selectXAxis[i].value.match(/ra$/i) ||
         document.formMyDBGraph.selectXAxis[i].value.match(/raBore/i))
      document.formMyDBGraph.selectXAxis.selectedIndex = i;
  }
  // Find the best Y Axis table column that matches dec
  for ( var i = 1; i < document.formMyDBGraph.selectYAxis.length; i++ ) {
    if ( document.formMyDBGraph.selectYAxis[i].value.match(/^dec/i) ||
         document.formMyDBGraph.selectYAxis[i].value.match(/dec$/i) ||
         document.formMyDBGraph.selectYAxis[i].value.match(/decBore/i) )
      document.formMyDBGraph.selectYAxis.selectedIndex = i;
  }
} //setDefaultRaSelectColumn()


//  Assigns values all the select items to myDBTable columns
function setSelectColumns( myDBTableColumns, graphType ) {
  // Reset the current values of the select items for X/Y Axis and order
  document.formMyDBGraph.selectXAxis.options.length = 0
  document.formMyDBGraph.selectXAxis.options[0] = new Option( 'Select a column', '', true, false)
  document.formMyDBGraph.selectYAxis.options.length = 0
  document.formMyDBGraph.selectYAxis.options[0] = new Option( 'Select a column', '', true, false)
  document.formMyDBGraph.selectColumnOrder.options.length = 0
  document.formMyDBGraph.selectColumnOrder[0] = new Option( 'Select a column', '', true, false)
  document.formMyDBGraph.selectFilterIDColumn.options.length = 0
  document.formMyDBGraph.selectFilterIDColumn[0] = new Option( 'None', '', true, false)
  var filterIDdefault = false
  var projectionGraph = false
  var xAxisDefault = false
  var yAxisDefault = false

  // Set all the astro filter ids to true
  $('#idDivSelectAstroFilterIDColumn').find(':checkbox').attr('checked', true);
  // Check for a projection type of graph
  if ( graphType.match(/projection/gi) ) {
    projectionGraph = true
  }

  for ( var i = 0; i < myDBTableColumns.length; i++ ) {

    // If we have a projection, set default ra and dec if we can find one
    if ( projectionGraph ) {
      if ( myDBTableColumns[i].match(/^ra/i) || myDBTableColumns[i].match(/ra$/i) )
        xAxisDefault = true
      else
        xAxisDefault = false
      if ( myDBTableColumns[i].match(/^dec/i) || myDBTableColumns[i].match(/dec$/i) )
        yAxisDefault = true
      else
        yAxisDefault = false
    }
    document.formMyDBGraph.selectXAxis.options[i+1] = new Option( myDBTableColumns[i], myDBTableColumns[i], false, xAxisDefault)
    document.formMyDBGraph.selectYAxis.options[i+1] = new Option( myDBTableColumns[i], myDBTableColumns[i], false, yAxisDefault)
    document.formMyDBGraph.selectColumnOrder.options[i+1] = new Option( myDBTableColumns[i], myDBTableColumns[i], false, false)
    if ( myDBTableColumns[i].match(/filter/gi) )
      filterIDdefault = true
    else
      filterIDdefault = false
    document.formMyDBGraph.selectFilterIDColumn.options[i+1] = new Option( myDBTableColumns[i], myDBTableColumns[i], false, filterIDdefault)
  } //for

  // Case we didn't find a filterID match unchek all filters
  if ( filterIDdefault == false && projectionGraph == true ) {
    for ( i=0; i < document.formMyDBGraph.elements.length; i++ ) {
      if ( document.formMyDBGraph.elements[i].type == "checkbox" )
        document.formMyDBGraph.elements[i].checked = false
    }
  }

} //setSelectColumns

function isPosInt(value){
  var n = parseInt(value);
  if((parseFloat(value) == n) && !isNaN(value)){
      return n > 0;
  } else {
      return false;
  }
}

function isNumber(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}

</script>
<?php require_once ("menubar_header.php"); ?>
</head>
<body>
<?php
require_once ("top.php");
require_once ("menubar.php");
?>
<div id="main">
<div class="content" style="text-align: center;">
<h2><?=$PSIHelp->getWikiURL('PSI-GenerateGraphs')?>&nbsp;<?=TITLE?></h2>

<table class="results" border="0" cellpadding="3" cellspacing="3" style="margin: 0 auto;">
<!--Option Panel. Start.-->
<tr>
<td valign='top' align='left'>
<form name="formMyDBGraph" id="formMyDBGraph" method="get" action="<?=$_SERVER['PHP_SELF']?>">
<table border="0" cellspacing="1" cellpadding="1">
<tr><th width="300">MyDB Table</th></tr>
<tr class="results_td"><td>
        <div id="idDivMyDBTableDiv">
        <?=$PSIHTMLGoodies->showFormSelect( 'selectMyDBTable', 'idSelectMyDBTable', $MyDB->getTableList(),
                                            $PSIGraph->getSelectMyDBTable(), 'Select a MyDB Table' );?>
        </div>
</td></tr>

<tr><th>Graph Type</th></tr>
<tr class="results_td"><td>
       <div id="idDivGraphTypeDiv">
        <?=$PSIHTMLGoodies->showFormSelect( 'selectGraphType', 'idSelectGraphType', $PSIGraph->getGraphTypesList(),
                                            $PSIGraph->getSelectGraphType(), 'Select a Graph Type' );?>
       </div>
       <div id="idDivHistogramBins">
       Histogram Bins: <input type="text" id="histogramBins" name="histogramBins"
                        maxlength="4" size="4" value="<?=$PSIGraph->histogramBins?>"/><br />
       <input type="checkbox" name="customizeHistogramTick" value="Y" style=""
                        <?if ($PSIGraph->customizeHistogramTick) {?>checked<?}?>/> Use customized tick
       </div>
</td></tr>

<tr><th>Axis</th></tr>
<tr class="results_td"><td>
        <div id="idDivXAxis">
          <span id="idSpanXAxisLabel">
             <?=(preg_match ('/projection$/i', $PSIGraph->getSelectGraphType() ) ) ? 'Ra: ' : 'X Axis: '?></span>
<?php
            if ($drawGraph)
              print $PSIHTMLGoodies->showFormSelect( 'selectXAxis', 'idSelectXAxis', $myDBColumnList,
                                                     $PSIGraph->getSelectXAxis(), 'Select a column' );
            else
              print $PSIHTMLGoodies->showFormSelect( 'selectXAxis', 'idSelectXAxis', NULL, NULL,
                                                     'Select Table and Graph Type.' );
?>
        </div>
        <div id="idYAxisDiv">
          <span id="idSpanYAxisLabel"><?=(preg_match ('/projection$/i', $PSIGraph->getSelectGraphType() ) ) ? 'Dec: ' : 'Y Axis: '?></span>
<?php
            if ($drawGraph)
              print $PSIHTMLGoodies->showFormSelect( 'selectYAxis', 'idSelectYAxis', $myDBColumnList, $PSIGraph->getSelectYAxis(), 'Select a column' );
            else
              print $PSIHTMLGoodies->showFormSelect( 'selectYAxis', 'idSelectYAxis', NULL, NULL, 'Select Table and Graph Type.' );
?>
        </div>

        <div id='idInverse'>
            <input type='checkbox' id='cbInverseX' name='cbInverseX' value='Y'
                   <?if ($PSIGraph->getInverseX()) { ?>checked<? } ?> />Inverse X
            <input type='checkbox' id='cbInverseY' name='cbInverseY' value='Y'
                   <?if ($PSIGraph->getInverseY()) { ?>checked<? } ?> />Inverse Y
        </div>

        <div id="idDivSelectAstroFilterIDColumn">
          Filter ID Column:
<?php
        if ($drawGraph) {
          $selectFilterIDColumn = $PSIGraph->getSelectFilterIDColumn();
          // Guessing the filterID
          if ( !isset( $selectFilterIDColumn ) )
            $selectFilterIDColumn = 'FilterID';
          print  $PSIHTMLGoodies->showFormSelect( 'selectFilterIDColumn', 'idSelectFilterIDColumn', $myDBColumnList, $selectFilterIDColumn, 'None' )."<br/>";
        }
        else
          print $PSIHTMLGoodies->showFormSelect( 'selectFilterIDColumn', 'idSelectFilterIDColumn', NULL, NULL, 'None' )."<br/>";

        // Display the filter check boxes
        $astroFilters = $PSPSSchema->getAstroFilterHash();
        $checkAstroFilterIDs = $PSIGraph->getCheckAstroFilterIDs();
?>
          Filters:
<?php
        foreach($astroFilters as $astroFilterID => $astroFilterName) {
           // Set checked if value has already been selected
           $check = in_array( $astroFilterID, $checkAstroFilterIDs) ? 'checked="checked"' : '';
           print <<<EOF
                <input $check type="checkbox" name="checkAstroFilterIDs[]" value="$astroFilterID"/>$astroFilterName &nbsp;

EOF;
        }
?>
	  <br/><input name="checkAllAstroFilterIDs" type="checkbox" <?=isset($_REQUEST['checkAllAstroFilterIDs']) ? 'checked="checked"' : ''?> id="checkAllAstroFilterIDs"/>Select/Deselect All Filters<br/>
        </div>
</td></tr>

<tr><th>Range</th></tr>
<tr class="results_td"><td>
        <div id="idDivYAxisRange">
          <table>
            <tr>
              <td>
<?
                //Obtain form variables assign to PSIGraph to be used after form submission
                $selectedXgt = ( $PSIGraph->getSelectXAxisMinOper() == 'gt' ) ? 'selected="selected"' : '';
                $selectedXgteq = ( $PSIGraph->getSelectXAxisMinOper() == 'gteq' ) ? 'selected="selected"' : '';
                $selectedXlt = ( $PSIGraph->getSelectXAxisMaxOper() == 'lt' ) ? 'selected="selected"' : '';
                $selectedXlteq = ( $PSIGraph->getSelectXAxisMaxOper() == 'lteq' ) ? 'selected="selected"' : '';
                $selectedYgt = ( $PSIGraph->getSelectYAxisMinOper() == 'gt' ) ? 'selected="selected"' : '';
                $selectedYgteq = ( $PSIGraph->getSelectYAxisMinOper() == 'gteq' ) ? 'selected="selected"' : '';
                $selectedYlt = ( $PSIGraph->getSelectYAxisMaxOper() == 'lt' ) ? 'selected="selected"' : '';
                $selectedYlteq = ( $PSIGraph->getSelectYAxisMaxOper() == 'lteq' ) ? 'selected="selected"' : '';

?>
                <input type="text" size="2" maxlength="20" id="textXAxisMin" name="textXAxisMin" value="<?=$PSIGraph->getTextXAxisMin()?>"/>
              </td>
              <td>
                <select name="selectXAxisMinOper">
                  <option value="gt" <?=$selectedXgt?>>&lt;</option>
                  <option value="gteq" <?=$selectedXgteq?>>&lt;=</option>
                </select>
              </td>
              <td><div id="idDivXAxisRangeColumn"><?=($drawGraph) ? $PSIGraph->getSelectXAxis() : 'X Axis'?></div></td>
              <td>
                <select name="selectXAxisMaxOper">
                  <option value="lt" <?=$selectedXlt?>>&lt;</option>
                  <option value="lteq" <?=$selectedXlteq?>>&lt;=</option>
                </select>
              </td>
              <td>
                <input type="text" size="2" maxlength="20" id="textXAxisMax" name="textXAxisMax" value="<?=$PSIGraph->getTextXAxisMax()?>"/>
              </td>
             </tr>

            <tr id='idTR_YAxisRange'>
              <td>
                <input type="text" size="2" maxlength="20" id="textYAxisMin" name="textYAxisMin" value="<?=$PSIGraph->getTextYAxisMin()?>"/>
              </td>
              <td>
                <select name="selectYAxisMinOper">
                  <option value="gt" <?=$selectedYgt?>>&lt;</option>
                  <option value="gteq" <?=$selectedYgteq?>>&lt;=</option>
                </select>
              </td>
              <td><div id="idDivYAxisRangeColumn"><?=($drawGraph) ? $PSIGraph->getSelectYAxis() : 'Y Axis'?></div></td>
              <td>
                <select name="selectYAxisMaxOper">
                  <option value="lt" <?=$selectedYlt?>>&lt;</option>
                  <option value="lteq" <?=$selectedYlteq?>>&lt;=</option>
                </select>
              </td>
              <td>
                <input type="text" size="2" maxlength="20" id="textYAxisMax" name="textYAxisMax" value="<?=$PSIGraph->getTextYAxisMax()?>"/>
              </td>
             </tr>

          </table>
        </div>

</td></tr>

<tr><th>Plot Limit</th></tr>
<tr class="results_td"><td>
<?php
        # Can't use PSIHTMLGoodies for this because the hash behaves strange because it things that 100, 1000, etc is an index value.
        $selectPlotLimit = $PSIGraph->getSelectPlotLimit();
?>
        <select name="selectPlotLimit">
          <option value="100" <?=(isset($selectPlotLimit) && $selectPlotLimit == "100") ? "selected=\"selected\"" : ''?>>100</option>
          <option value="1000" <?=(isset($selectPlotLimit) && $selectPlotLimit == "1000") ? "selected=\"selected\"" : ''?>>1000</option>
          <option value="5000" <?=(isset($selectPlotLimit) && $selectPlotLimit == "5000") ? "selected=\"selected\"" : ''?>>5000</option>
          <option value="10000" <?=(isset($selectPlotLimit) && $selectPlotLimit == "10000") ? "selected=\"selected\"" : ''?>>10,000</option>
          <option value="" <?=!isset($selectPlotLimit) || empty($selectPlotLimit) ? "selected=\"selected\"" : ''?>>No Limit </option>
      </select>
</td></tr>

<tr><th>Column Order</th></tr>
<tr class="results_td"><td>
        <div id="idDivColumnOrder">
<?php
              if ($drawGraph)
                print $PSIHTMLGoodies->showFormSelect( 'selectColumnOrder', 'idSelectColumnOrder', $myDBColumnList, $PSIGraph->getSelectColumnOrder(), 'Select a column')."<br/>";
              else
                print $PSIHTMLGoodies->showFormSelect( 'selectColumnOrder', 'idSelectColumnOrder', NULL, NULL, 'Select Table and Graph Type.' )."<br/>";
              // Assign default value
              $radioColumnAscDec = $PSIGraph->getRadioColumnAscDec();
              $checkedAsc = 'checked="checked"';
              $checkedDesc = '';
              if ( isset($radioColumnAscDec) && $radioColumnAscDec == 'desc') {
                $checkedAsc = '';
                $checkedDesc = 'checked="checked"';
              }
?>
            <input type="radio" name="radioColumnAscDec" <?=$checkedAsc?> value="asc"/> Assending<br />
            <input type="radio" name="radioColumnAscDec" <?=$checkedDesc?> value="desc"/> Descending
        </div>
</td></tr>

<tr>
  <td>
     <input type="submit" value="Submit Graph" id="submit-button" name="submitGraph"/>&nbsp;
     <input type="button" value="Clear Form" onclick="window.location.href='<?=$_SERVER['PHP_SELF']?>'"/>

<?php
  $UA = $_SERVER['HTTP_USER_AGENT']; //echo $UA;
  if ( stripos($UA, 'chrome') || stripos($UA, 'safari') ) { ?>
     <input type="button" value="Save Image" id="saveImage-button" <?php if ($drawGraph == 0) { ?>disabled<?php } ?>>
<?php } ?>

  </td>
</tr>

</table>
</form>

</td>
<!--Option Panel. End.-->

<!--Drawing Panel. Start.-->
<td align='center' width='905' height='600' style="border:solid; border-color: #eee;">
<?php
  // Draw final graph or projection
  if ( $drawGraph ) {
    if ( preg_match("/projection/i", $PSIGraph->getSelectGraphType() ) ) {
      print $PSIGraph->drawProjection( $PSPSSchema->getAstroFilterHash() );
    }
    else {
      print "<div id=\"placeholder\" style=\"margin: auto;width:800px;height:600px\"></div>".$PSIGraph->drawGraph('placeholder');
    }
  }
?>
&nbsp;
</td>
</tr>
<!--Drawing Panel. End.-->

</table>

</div><!-- End Content -->
</div> <!-- End Main -->
<?php require ("bottom.php"); ?>
</body>
</html>

