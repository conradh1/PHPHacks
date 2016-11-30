<?php
/**
 * This script shows MyDB tables and lets users manipulate them easily.
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley
 * @since Beta version 2010
 */
define('TITLE','PSI MyDB Page');

require_once ("session.php");
require_once ("MyDBClass.php");
require_once ("PSIHTMLGoodiesClass.php");
// Global Objects

//web globals
global $MyDBTableList; // Array containing all the MyDB tables that the user has.
global $PSIHTMLGoodies;
global $MyDB;
global $actionOutput;
global $action;

$PSIHTMLGoodies = new PSIHTMLGoodiesClass();
$MyDB = new MyDBClass( $PSISession );
$MyDBTableList= $MyDB->getTableList(); # Array of MyDB tables
$PSIHelp = $PSISession->getHelpObject();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Personal Database MyDB"/>
    <meta name="keywords" content="Pan-STARRS Science Web Interface Astronomy Personal Database MyDBs SQL"/>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <script type="text/javascript">
      jQuery(document).ready(function() {
<?
    // Decide whether to hide columns for table or not.
    if ( $MyDB->getAction() != MyDBClass::ACTION_TABLE_UPLOAD ) {
?>
        $('.toggle_menu_upload_item').hide();
        // Hide collapse buttons at first
        $('.toggle_menu_upload_button').each(function(i) {
          $(this).children().first().next().hide();
        });

<?
     } #php if
     # else we want to show the form but hide the plus image
     else {
?>
        $('.toggle_menu_upload_button').each(function(i) {
          $(this).children().first().hide();
        });
<?
     } #php else
?>

        //Switch the "Open" and "Close" state per click
        $('.toggle_menu_upload_button').click(function() {
          $(this).parent().next().slideToggle('fast');
          $(this).children().first().toggle().next().toggle();
          return false;
        });
        $('#idRadioTableTypeExisting').click(function() {
          $("#idTextNewUploadTable").val('')
        });
        $('#idRadioTableTypeNew').click(function() {
          $("#idSelectTable").val('')
        });

<?
    // Decide whether to hide columns for table or not.
    if ( $MyDB->getAction() != MyDBClass::ACTION_TABLE_EXTRACT ) {
?>
        // Hide the extract form
        $('.toggle_menu_extract_item').hide();
        // Hide collapse buttons at first
        $('.toggle_menu_extract_button').each(function(i) {
          $(this).children().first().next().hide();
        });
<?
     } #php if
     # else we want to show the form but hide the plus image
     else {
?>
        $('.toggle_menu_extract_button').each(function(i) {
          $(this).children().first().hide();
        });
<?
     } #php else
?>
        //Switch the "Open" and "Close" state per click
        $('.toggle_menu_extract_button').click(function() {
          $(this).parent().next().slideToggle('fast');
          $(this).children().first().toggle().next().toggle();
          return false;
        });

        //If the user selects / deselect all astro filters set the same for the rest of the checkboxes in that div tag
        $("#checkAllMyDBTables").click(function()  {
          $('#formDeleteTable').find(':checkbox').attr('checked', this.checked);
        });
      });
    </script>
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
    <div class="content">
        <div style="text-align: center">
        <h2><?=$PSIHelp->getWikiURL('PSI-MyDB')?>&nbsp;<?= TITLE ?></h2>
<?php
    // Print the action message based on a user event like deleting a table.
    // If there is not action, string returned is empty.
    print $MyDB->getActionMessage();
?>
         <table align="center" cellspacing="10" cellpadding="20">
          <tr>
            <td>
              <div class="toggle_menu_upload">
                <div class="toggle_menu_upload_button">
                    <img src="images/plus.gif" alt="+" />
                    <img src="images/minus.gif" alt="-" />
                    <strong>Upload File Form.</strong>
                </div>
              </div>
              <div class='toggle_menu_upload_item'>
              <form name="formFileUpload" method="post" action="<?=$_SERVER['PHP_SELF']?>" enctype="multipart/form-data">
                <table class="results" border="0" cellpadding="3" cellspacing="3" style="margin: 0 auto">
                  <tr>
                    <td rowspan="5"  align="right">
                      <?=$PSIHelp->getWikiURL('PSI-MyDBFileUploadsForm')?>
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2">
                        <input type="hidden" name="action" value="<?=MyDBClass::ACTION_TABLE_UPLOAD?>"/>
                        File: <input type="file" name="fileUpload" size="25"/>
                    </td>
                  </tr>
                  <tr>
                    <td>
                        <input type="radio" id="idRadioTableTypeNew" name="radioTableType" value="new" <?= (  $MyDB->getRadioTableType() == 'new' ) ? 'checked="checked"' : ''; ?>/>
                        <strong>Use new table.</strong>
                    </td>
                    <td>
                        Table Name: <input type="text" id="idTextNewUploadTable" name="textNewUploadTable" value="<?= $MyDB->getTextNewUploadTable() ?>" size="25"/>
                    </td>
                  </tr>
                  <tr>
                    <td>
                        <input type="radio" id="idRadioTableTypeExisting" name="radioTableType" value="existing" <?= ( $MyDB->getRadioTableType() == 'existing' ) ? 'checked="checked"' : ''; ?>/><strong>Use an existing table.</strong>
                    </td>
                    <td>
<?php
                        $selectedTable = NULL;
                        // Try to set last selected table
                        if ( $MyDB->getAction() == MyDBClass::ACTION_TABLE_UPLOAD && $MyDB->getTextNewUploadTable() == '' )
                          $selectedTable = $MyDB->getTable();
?>
                        MyDB Tables:<?=$PSIHTMLGoodies->showFormSelect( 'table', 'idSelectTable', $MyDBTableList, $selectedTable, 'Select a MyDB Table...' )?>
                    </td>
                  </tr>
                  <tr>
                    <td align="center">
                      <input type="submit" name="Upload" value="Upload Table"/>
                    </td>
                    <td align="center">
                      <input type="button" value="Clear" onclick="clearForm(this.form)"/>
                    </td>
                  </tr>
                </table>
              </form>
            </div>
           </td>
           <td>
<?php
                // Used for File Type MyDB table extraction.
                $selectTable = $PSIHTMLGoodies->showFormSelect( 'table', NULL, $MyDBTableList, $selectedTable, 'Select a MyDB Table...' );
                $selectFileFormat  = $PSIHTMLGoodies->showFormSelect( 'selectFileFormat', NULL, $MyDB->getExtractFileFormats(), NULL, 'Select a File Format...' );
?>
              <div class="toggle_menu_extract">
                <div class="toggle_menu_extract_button">
                    <img src="images/plus.gif" alt="+" />
                    <img src="images/minus.gif" alt="-" />
                    <strong>Table Extraction Form.</strong>
                </div>
              </div>
              <div class='toggle_menu_extract_item'>
                <form method="post" action="<?=$_SERVER['PHP_SELF']?>">
                  <input type="hidden" name="action" value="<?=MyDBClass::ACTION_TABLE_EXTRACT?>"/>
                  <table class="results" border="0" cellpadding="3" cellspacing="3" style="margin: 0 auto">
                    <tr>
                      <td rowspan="4" align="right">
                        <?=$PSIHelp->getWikiURL('PSI-MyDBTableExtractionForm')?>
                      </td>
                    </tr>
                    <tr>
                      <td>MyDB Tables: <?=$selectTable?></td>
                    </tr>
                    <tr>
                      <td>File Format: <?=$selectFileFormat?></td>
                    </tr>
                    <tr>
                      <td align="center">
                        <input type="submit" name="submitExtract" value="Extract"/>
                      </td>
                    </tr>
                  </table>
                </form>
              </div>
           </td>
          </tr>
        </table>
        <hr/>
         <form method="post" id="formDeleteTable" action="<?=$_SERVER['PHP_SELF']?>">
         <input type="hidden" name="action" value="<?=MyDBClass::ACTION_TABLE_DELETE_REQUEST?>"/>
         <table class="results" border="1" cellpadding="3" cellspacing="0" style="margin: 0 auto">
<?php

        $numRows = count( $MyDBTableList );

        if ($numRows == 0) {
            print <<<EOF
              <tr>
                 <th align="center">You don't have any MyDB tables yet.</th>
              </tr>
EOF;
        }
        else if ( strtoupper($MyDBTableList[0]) == 'ERROR' ) {
            # Case the query to get the table list caused an error
            print <<<EOF
              <tr>
                 <th align="center">
                  There seems to be an eror with getting your MyDB table list.
                  Please trying refreshing the page or content PSPS help (see email below).
                 </th>
              </tr>
EOF;
            $numRows = 0; # We don't want to print anything after
            $MyDBTableList= NULL;
         }
        else {
?>
              <tr>
                 <th colspan="8" align="center">You have a total of <?=$numRows?> tables in your personal database (MyDB).</th>
              </tr>
<?php
        } //else
        // Used for add totals
        $memoryTotals = array();
        //  Loop through each MyDB Table and print out the details and needed forms for editing.
        for ($row = 0; $row < $numRows; $row++) {
            $table = $MyDBTableList[$row];
            $tableDetailsHash = $MyDB->getTableDetailsHash( $table );
            // print headers once
            if ( $row == 0 ) {
?>
              <tr>
                <th width="95px" align="center">
                  <input name="checkAllMyDBTables" type="checkbox" <?=isset($_REQUEST['checkAllMyDBTables']) ? 'checked="checked"' : ''?> id="checkAllMyDBTables"/>
                  <input type="submit" name="submitDeleteRequest" value="Delete"/>
                </th>
                <th align="center">Table Name</th>

<?php
              # print Table details headers
              foreach ( $tableDetailsHash as $key => $value) {
                $memoryTotals[$key] = 0; // inital totals
                print <<<EOF
                <th align="center">$key</th>

EOF;
              } # foreach
              print <<<EOF
              </tr>
EOF;
            }


            // Display the data, with alternate rows having white and
            // light green backgrounds.
            $class = ($row % 2) ? 'green-row' : 'white-row';
            $tableDescriptionURL = "<a href=\"".$_SERVER['PHP_SELF']."?table=".
                                   urlencode($table).
                                  "&amp;action=".MyDBClass::ACTION_TABLE_STRUCTURE_VIEW.
                                   "\">$table</a>";
            $checked = in_array( $table, $MyDB->getCheckTables()) ? 'checked="checked"' : '';
?>
              <tr class="<?=$class?>">
                <td><input type="checkbox" <?=$checked?> name="checkTables[]" value="<?=$table?>"/></td>
                <td><?=$tableDescriptionURL?></td>
<?
              # print Table details headers
              foreach ( $tableDetailsHash as $key => $value) {
                #debug
                $memoryTotals[$key] += $value;
                // Exception for number of rows print url to show
                // top 10 rows
                if ( $key == 'Number of Rows' ) {
                  $row_url = "<a href=\"".$_SERVER['PHP_SELF']."?table=".
                           urlencode($table).
                           "&amp;action=".MyDBClass::ACTION_TABLE_TOP_10_ROWS.
                           "\">".number_format($value)."</a>";
?>
                  <td align="right"><?=$row_url?></td>
<?php
                 }
                           # place commas for every 000 with number format function.
                else {
?>
                 <td align="right"><?=number_format($value)?> KB</td>
<?php
                }


              } #foreach tableDetailHash
?>
              </tr>
<?php
        } //for $row
        if ( count($MyDBTableList) > 0 ) {
?>
              <tr>
                <th align="right" colspan="2">Totals</th>
<?php
                foreach ( $memoryTotals as $key => $total) {
                    if ( $key != 'Number of Rows' )
                       $total = number_format($total).' KB';
                    else
                      $total = number_format($total);
                      print <<<EOF
                 <th align="right">$total</th>

EOF;
                }
        }
?>
              </tr>
            </table>
            </form>
        </div>
    </div>
    <!-- End Content -->
</div>
<!-- End Main -->
<br/>
<?php
require ("bottom.php");
?>
</body>
</html>
