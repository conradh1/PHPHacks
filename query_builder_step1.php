<?php
/**
 * This script is the first step in creating a query dynamically
 *
 * The first step in building a dynamic query involves the following to be completed:
 *  -Choosing a survey
 *  -Selecting filters
 *  -Selecting tables
 *  -Choosing the format of how to view the columns
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley
 * @since Beta version 2010
 */

define('TITLE','Query Builder Step 1: Choose Survey, Filters, and Tables');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
require_once ("session.php");
require_once ("PSPSSchemaClass.php");
?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Query Builder"/>
    <meta name="keywords" content="Pan-STARRS Science Web Interface Astronomy Query Builder SQL"/>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
<?php
global $PSISession;
global $PSPSSchema;
global $PSIHelp;
global $nextStep;
global $formErrorFlag;
global $PSIHTMLGoodies;


$PSIHTMLGoodies = new PSIHTMLGoodiesClass();
$PSIHelp = $PSISession->getHelpObject();
$nextStep = 'query_builder_step2.php';
$formErrorFlag = 0;  //Assume no error

// Assign the PSPS Schema object
if ( isset( $_SESSION["PSPSSchemaClass"] ) ) {
  $PSPSSchema = unserialize($_SESSION["PSPSSchemaClass"]);
}
else {
  // New class for PSPS Schema used for query builder
  $PSPSSchema = new PSPSSchemaClass( $PSISession );
}

// If user has completed step 1, assign the new variables into session and check for errors
if ( isset($_REQUEST['submitStep1']) == 'Next' ) {
    $PSPSSchema->setSelectSurvey( $_REQUEST['selectSurvey']);
    // Assign the values for arrays if indeed something was chosen
    // else assign empty array
    // HACK assign schema and schemaGroup since we have multiple surveys.
    if (!empty ($_REQUEST['selectSchema'])) {
	  list( $userSchemaGroup, $userSchema ) = preg_split("/[\|]/", $_REQUEST['selectSchema']);
          $QueryHandle->setUserSchemaGroup( $userSchemaGroup );
          $QueryHandle->setUserSchema( $userSchema );
    }
    if ( !empty( $_REQUEST['checkAstroFilterIDs'] ) ) {
        $PSPSSchema->setCheckAstroFilterIDs( $_REQUEST['checkAstroFilterIDs'] );
    }
    else {
        $PSPSSchema->setCheckAstroFilterIDs( array() );
    }
    if ( !empty( $_REQUEST['checkTables'] ) ) {
        $PSPSSchema->setCheckTables( $_REQUEST['checkTables'] );
    }
    else {
        // Assign error because no tables have been selected
        $formErrorFlag = 1;
        $PSPSSchema->setCheckTables( array() );
    }
    // User has completed the form properly, forward to step 2
    $PSPSSchema->setSelectColumnViewFormat( $_REQUEST['selectColumnViewFormat'] );

    if ( empty( $formErrorFlag ) ) {
        print "<meta http-equiv=\"refresh\" content=\"0;url=$nextStep\" />\n";
    }
} //if
?>
<?php require_once("menubar_header.php"); ?>
</head>
<body>
<?php require_once("top.php"); ?>
<?php require_once("menubar.php"); ?>

<div id="main">
<div style="text-align: center">
<h2><?=$PSIHelp->getWikiURL('PSI-QueryBuilder')?>&nbsp;<?=TITLE?></h2>
<form name="step1" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
<?php
    // If the User decides to start over
    if ( isset($_REQUEST['action']) == 'Start Over' ) {
        $PSPSSchema->clearFormValues();
    }
?>

    <table style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="3" >
        <tr>
          <td align="left"><strong>Survey (Database):</strong>
              <!-- HACK Survey can be selected once evertying is in one bloody database -->
              <input type="hidden" name="selectSurvey" value="0"/> 
              <select name="selectSchema">
<?php
            $schemaGroup = $PSISession->getDefaultPSPSSchemaGroup(); //Get the PSPS Schema group for the MOPS query builder
            # Check the default schema
            $pspsSchemas = $QueryHandle->getSchemas( $schemaGroup );
            # set default MOPS schema
            if ( !in_array($QueryHandle->getUserSchema(), $pspsSchemas ) ) {
              $QueryHandle->setUserSchema( $PSISession->getDefaultPSPSSchema() );
            }
            foreach ( $pspsSchemas as $schema ) {
                   $selected = '';
                   $pspsSchemaHash = $QueryHandle->getSchemaHash( $schemaGroup, $schema );
                   if ( $schemaGroup."|".$schema == $QueryHandle->getUserSchemaGroup().'|'.$QueryHandle->getUserSchema() )
                     $selected = ' selected="selected"';
                   #each value for option is a combination of section and title
?>
                    <option value="<?=$schemaGroup."|".$schema?>"<?=$selected?>><?=$schema.' - '.$pspsSchemaHash['Description']?></option>
<?php
             } #foreach schema
?>
              </select>
            </td>
        </tr>
        <tr>
            <td align="left"><strong>Filter:</strong>

<?php
       // Display the filter check boxes
        $astroFilters = $PSPSSchema->getAstroFilterHash();
        $checkAstroFilterIDs = $PSPSSchema->getCheckAstroFilterIDs();

        foreach($astroFilters as $astroFilterID => $astroFilterName) {
           // Set checked if value has already been selected
           $check = in_array( $astroFilterID, $checkAstroFilterIDs) ? 'checked="checked"' : '';
           print <<<EOF
                <input $check type="checkbox" name="checkAstroFilterIDs[]" value="$astroFilterID"/>$astroFilterName &nbsp;

EOF;
        }
?>
            </td>
        </tr>
        <tr>
             <td align="left">
                <strong>Click the checkbox beside each table that you wish to use for your search.</strong>
             </td>
        </tr>
        <tr>
            <td>
                <table style="margin: 0 auto;" border="1" cellpadding="3" cellspacing="0">
                  <tr>
<?php
                $hasDisabledTable = false;
                // Find the list of unique table types.
                $tables = $PSPSSchema->getTables();
                $checkTables = $PSPSSchema->getCheckTables();
                for ($i = 0; $i < count ($tables); $i++) {
                    $tableHash = $PSPSSchema->getTableHash ($tables[$i]);
                    $tableType = $tableHash['Type'];
                    if (isset ($alreadySeenTableTypes[$tableType])) continue;
                    $alreadySeenTableTypes[$tableType] = 1;
                    $uniqueTableTypes[] = $tableType;
                }
                $width = (int) (100 / count ($uniqueTableTypes));
                // Display each of the unique table types.
                for ($i = 0; $i < count ($uniqueTableTypes); $i++) {
                    $tableType = $uniqueTableTypes[$i];
                    print "<th valign=\"bottom\">$tableType</th>\n";
                }
                // Foreach unique table type, display the list of tables it
                // has with checkboxes.
                print "</tr>\n<tr>\n";
                for ($i = 0; $i < count ($uniqueTableTypes); $i++) {
                    $targetTableType = $uniqueTableTypes[$i];
                    print "<td valign=\"top\">\n";
                    print "<table border=\"0\" cellpadding=\"3\" cellspacing=\"0\">\n";
                    for ($j = 0; $j < count ($tables); $j++) {
                        $table = $tables[$j];
                        $tableHash = $PSPSSchema->getTableHash ($table);
                        $tableType = $tableHash['Type'];
                        if ($tableType != $targetTableType) continue;
                        $checked = (isset ($checkTables) and in_array ($table, $checkTables)) ? 'checked="checked"' : '';

                        $disableTable = (stripos($tableHash['Description'], "Not populated at this time") != false);
                        $disabled = $disableTable ? 'disabled="disabled"' : "";
                        $disableNote = $disableTable ? "<font color='red'>*</font>" : "";
                        $tableText = $disableTable ? "<font color='#666666'>$table</font>" : $table;
                        if ($disableTable) $hasDisabledTable = true;

                        print <<<EOF
                        <tr>
                          <td align="left">
                            <label for="$table"/><input $checked $disabled type="checkbox" name="checkTables[]" value="$table" id="$table"/>$tableText $disableNote
                          </td>
                        </tr>
EOF;
                    }
                    print "</table>\n";
                    print "</td>\n";
                }
?>
                  </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="left"><strong>Column View Format:</strong>
              <select name="selectColumnViewFormat">
<?php
                // Add column display format either short or long for column details
                $selectColumnViewFormat = $PSPSSchema->getSelectColumnViewFormat();
                print "<option ".($selectColumnViewFormat == 'short' ? 'selected="selected"' : '')." value=\"short\">Column names only</option>\n";
                print "<option ".($selectColumnViewFormat == 'full' ? 'selected="selected"' : '')." value=\"full\">Full column information</option>\n";
?>
              </select>
<?php
  if ($hasDisabledTable) { print "<font color='red' size='-1'> * Checkbox is disabled because the table is not populated at this time.</font>"; }
?>
            </td>
        </tr>
        </table>
    <br/>
    <div align="center">
        <table border="0" width="65%" cellpadding="0" cellspacing="0">
<?php
        // Error handling in the event no tables are selected.
        if ( !empty($formErrorFlag) ) {
?>
            <tr>
                <td colspan = "2" align="center">
                    <h3 style="color: red">Error: You must select some tables before proceeding.</h3>
                </td>
            </tr>
<?php
        } //if
?>
            <tr>
                <td align="center">
                    <input type="button" name="buttonStartOver" value="Start Over" onclick="confirmStartOver();" />
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
  if (isset ($PSPSSchema)) {
      // ALWAYS ALWAYS ADD class to session if it is set.
      $_SESSION["PSPSSchemaClass"] = serialize($PSPSSchema);
  }
  if ( isset( $QueryHandle ) )
      //ALWAYS save the QueryHandleClass Object
     $_SESSION['QueryHandleClass'] = serialize( $QueryHandle );

  require_once("bottom.php");
?>
</body>
</html>
