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
 * @author Conrad Holmberg, Haydn Huntley
 * @since Beta version 2010
 */
require_once ("session.php");
require_once ("QueryHandleClass.php");
require_once ("PSPSSchemaClass.php");
require_once ("PSIHTMLGoodiesClass.php");
define('TITLE','PSI PSPS Query Builder: Review and Execute Query');

global $PSPSSchema;
global $PSIHelp;
global $QueryHandle;
global $PSIHTMLGoodies;
global $PSISession;

// Globals to handle queries
global $resultSet;
global $errorString;
// Contains to different pages of the query builder.
global $previousStep1;
global $previousStep2;
global $previousStep3;
global $nextStep;

$PSIHelp = $PSISession->getHelpObject();
$previousStep1 = 'query_builder_step1.php';
$previousStep2 = 'query_builder_step2.php';
$previousStep3 = 'query_builder_step3.php';
$nextStep = $_SERVER['PHP_SELF'];

// Assign the PSPS Schema object
if ( isset( $_SESSION["PSPSSchemaClass"] ) ) {
  $PSPSSchema = unserialize($_SESSION["PSPSSchemaClass"]);
}
else {
  header ('Location: query_builder_step1.php');
  exit();
}

// Assign the Query Handle objects
if ( isset( $_SESSION["QueryHandleClass"] ) )
  $QueryHandle = unserialize( $_SESSION["QueryHandleClass"] );
else
  $QueryHandle = new QueryHandleClass( $PSISession ); // Make new PSI Session Class

// Assign variables to objects from form
initQueryBuilderFormVariables ();

// Conduct Download of a quick query
if ( isset ($_REQUEST['Download'])) {
    $QueryHandle->setDownloadFilename( $_REQUEST['textFileName'] );
    $QueryHandle->setDownloadFileFormat( $_REQUEST['selectFileFormat'] );
    $QueryHandle->handleQueryDownload (  $errorString);
}

$PSIHTMLGoodies = new PSIHTMLGoodiesClass();
 // Set old values already in Session
#$selectSurvey = $PSPSSchema->getSelectSurvey();
#$surveyHash = $PSPSSchema->getSurveyHash( $selectSurvey );


// Query builder variables
$radioSpacialConstraint = $PSPSSchema->getRadioSpacialConstraint();
$query = $QueryHandle->getUserQuery();

// Save session instance
$_SESSION['QueryHandleClass'] = serialize( $QueryHandle );

//Determine if we can execute a query
if (!empty ( $query ) and
    isset ($_REQUEST['submitQuery']) and
    $_REQUEST['submitQuery'] == 'Submit Query') {

    $resultSet = $QueryHandle->executeUserQuery( $errorString );

     # Long Query, let's forward to the queued Jobs page
     if ( $QueryHandle->getUserQueue() == 'slow' and isset ($resultSet))
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
<h2><?=$PSIHelp->getWikiURL('PSI-QueryBuilderReviewtheQueryEditandSubmit')?>&nbsp;<?=TITLE?></h2>
<form name="form1" method="post" action="<?=$nextStep?>">
    <table style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="0" >
      <tr>
        <td  align="right"><strong>Survey (Database):</strong></td>
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
<!--      <tr>
        <td align="right" width="50%"><strong>Survey:</strong></td>
        <td align="left"><? /*print $surveyHash['Name'] ." - ".  $surveyHash['Description']*/; ?></td>
      </tr> -->
      <tr>
        <td align="right"><strong>Filters:</strong></td>
<?php
    $checkFilterLetters = join(', ', $PSPSSchema->getCheckAstroFilterLetters());
    if (empty ($checkFilterLetters))
        $checkFilterLetters = 'N/A';
?>
        <td align="left"><?= $checkFilterLetters?></td>
      </tr>
      <tr>
        <td align="right"><strong>Spatial Constraint Type:</strong></td>
        <td align="left"><?=$radioSpacialConstraint?></td>
      </tr>
      <tr>
        <td colspan="2" align="center"><strong>Query:</strong></td>
      </tr>
      <tr>
        <td colspan="2">
          <textarea name="query" rows="15" cols="80"><?=$query?></textarea>
        </td>
      </tr>
      <tr>
         <td colspan="2" align="left">Name:
                <input name="queryName" size="15" maxlength="15" value="<?=$QueryHandle->getUserQueryName()?>"/> (optional)
         </td>
      </tr>
      <tr>
        <td colspan="2" align="left">
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
           <input type="button" value="Constraints"
                  onclick="window.location.href='<?=$previousStep3?>'" />
         </td>
         <td align="center">
           <input type="submit" value="Submit Query" id="submit-button" name="submitQuery"/>
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
        <strong>Click to expand/collapse Fast Query Download Form.</strong>
      </div>
    </div>
    <div class='toggle_menu_item' style="text-align: center;">
      <table align="center">
	<tr>
	  <td align="right">Download File Type:</td>
	  <td align="left">
<?php
      # Defulat value for file name
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
      // Shows query results in pretty HTML table
      print ( $PSIHTMLGoodies->showQueryResultSet( $resultSet ) );
} #if
else if (isset ($resultSet) and $QueryHandle->getUserQueue() == 'syntax') {
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
  if (isset ($PSPSSchema)) {
      // ALWAYS ALWAYS ADD class to session if it is set.
      $_SESSION["PSPSSchemaClass"] = serialize( $PSPSSchema );
  }
/**
* Assigns variables needed  for the query page.
*
* @param none
* @return none
*/
function initQueryBuilderFormVariables () {
  global $QueryHandle;
  global $PSPSSchema;

  // Assign form values for SpacialConstraint
  if ( isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'Next' ) {
      $PSPSSchema->setRadioSpacialConstraint( $_REQUEST['radioSpacialConstraint'] );
      $PSPSSchema->setTextBoxRa( $_REQUEST['textBoxRa'] );
      $PSPSSchema->setTextBoxDec( $_REQUEST['textBoxDec'] );
      $PSPSSchema->setTextBoxSize( $_REQUEST['textBoxSize'] );
      $PSPSSchema->setSelectBoxUnits( $_REQUEST['selectBoxUnits'] );
      $PSPSSchema->setTextConeRa( $_REQUEST['textConeRa'] );
      $PSPSSchema->setTextConeDec( $_REQUEST['textConeDec'] );
      $PSPSSchema->setTextConeRadius( $_REQUEST['textConeRadius'] );
      $PSPSSchema->setSelectConeUnits( $_REQUEST['selectConeUnits'] );
      $PSPSSchema->setSelectRowLimit( $_REQUEST['selectRowLimit'] );
      // Very important variable below to hold a nest hash for query filters
      $formTableColumnFilterHash = array();
      $checkTables = $PSPSSchema->getCheckTables();
      // Assign values for query filters
      foreach ($checkTables as $table) {
          $checkColumns = $PSPSSchema->getCheckTableColumns( $table );
          $columnsFilterHash = array();
          // Assign the filter values for the selected columns
          if ( !empty( $checkColumns ) ) {
            foreach ($checkColumns as $column) {
              $columnsFilterHash[$column] = array( 'checkColumn' => $_REQUEST['checkColumn_'.$table.'_'.$column],
                          'selectColumnLogicOper' => $_REQUEST['selectColumnLogicOper_'.$table.'_'.$column],
                          'selectMinOper' => $_REQUEST['selectMinOper_'.$table.'_'.$column],
                          'textMinValue' => $_REQUEST['textMinValue_'.$table.'_'.$column],
                          'selectRangeLogicOper' => $_REQUEST['selectRangeLogicOper_'.$table.'_'.$column],
                          'selectMaxOper' => $_REQUEST['selectMaxOper_'.$table.'_'.$column],
                          'textMaxValue' => $_REQUEST['textMaxValue_'.$table.'_'.$column] );
            } // foreach $column
          } //if !empty( $checkColumns
        $formTableColumnFilterHash[$table] = $columnsFilterHash;
      } // foreach $table
      // Finally add this giant nested hash to the PSPSSchema class instance.
      $PSPSSchema->setFormTableColumnFilterHash( $formTableColumnFilterHash );
      // set values for query handle from previous step
      $QueryHandle->setUserQuery( $PSPSSchema->buildQuery ());
      // Check the fast/slow queue
      if ( isset($_REQUEST['queue']) && !empty ($_REQUEST['queue']))
          $QueryHandle->setUserQueue($_REQUEST['queue']);
      else
          $QueryHandle->setUserQueue('fast');

      if (isset($_REQUEST['myDbTable']) &&  !empty($_REQUEST['myDbTable']))
          $QueryHandle->setUserMyDbTable($_REQUEST['myDbTable']);
      else
          $QueryHandle->setUserMyDbTable('myDBQueryBuildTable');

  } //if Next
  if ( isset ($_REQUEST['submitQuery']) && $_REQUEST['submitQuery'] == 'Submit Query') {
    # change schema group and schema if selected
    if (!empty($_REQUEST['selectSchema'])) {
      $QueryHandle->initQueryFormVariables();
    }
    else {
      $QueryHandle->setUserSchemaGroup( $PSISession->getDefaultPSPSSchemaGroup() );
      $QueryHandle->setUserSchema( $PSISession->getDefaultPSPSSchema() );
    }
  }

}  //initQueryBuilderFormVariables
  require_once("bottom.php");
?>
</body>
</html>
