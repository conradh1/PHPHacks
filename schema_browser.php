<?php
/**
 * The Schema Browser is basically an Interface to show users the PSPS Schema
 *
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Daniel Chang
 * @since Beta version 2010
 */
require_once ("session.php");
require_once ("PSPSSchemaClass.php");
define('TITLE','Schema Browser');

global $PSPSSchema;

// Assign the PSPS Schema object
if ( isset( $_SESSION["PSPSSchemaClass"] ) ) {
  $PSPSSchema = unserialize($_SESSION["PSPSSchemaClass"]);
}
else {
  // New class for PSPS Schema used for query builder
  $PSPSSchema = new PSPSSchemaClass( $PSISession );
}


$rowcnt = 0; // row counter for table display
$rowbg = ""; // table row background color

?>
<?php
// @todo move header without logo out of here
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?= TITLE ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS PSI Help Schema Page"/>
    <meta name="keywords" content="Pan-STARRS Science PSI Schema SQL"/>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <script type="text/javascript" src="javascript/psi_jquery.js"></script>
    <link rel="stylesheet" href="css/jquery.treeview.css" />
    <link rel="stylesheet" href="css/default.css"/>
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
</head>
<body>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
<script type="text/javascript" src="javascript/jquery.treeview.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
        $("#navigation").treeview({
            animated: "fast",
            collapsed: true,
            control: "#treecontrol",
            persist: "location",
        });
    });
</script>

<div class="schema_browser_content">
    <div class="sidebar">
        <div id="treecontrol">
            <a title="Collapse" href="#"> Collapse All</a><br/>
            <a title="Expand" href="#"> Expand All</a>
        </div>

        <ul id="navigation">
<?php
    $tables = $PSPSSchema->getTables(); // db tables
    $lastTableType = ''; // last table type processed

    for ( $i = 0; $i < count( $tables ); $i++ ) { // iterate over the db tables
        $tableHash = $PSPSSchema->getTableHash( $tables[$i] );
        $tableType = $tableHash['Type']; // db table type
        if ($tableType == "") { $tableType = "(Unknown Table Type)"; } // XC. Added on 2-11-2011.

        if ( $lastTableType != $tableType ) {
            $lastTableType = $tableType; // set the last table type

            if ( $i != 0 ) { // if not the first table, close the previous tag
                print <<<EOF
                </ul> <!-- close describeTable -->
            </li> <!-- close tableType -->
EOF;
            } // if $i != 0
?>
            <li> <!-- tableType -->
                <span><?= $tableType ?></span>
                <ul> <!-- start describeTable, individual table -->
<?php
        } // if ( $lastTableType != $tableType)
?>
                    <li style="text-align: left;"><span><a href="<?= $_SERVER['PHP_SELF']."?describeTable=".$tables[$i]?>"><?= $tables[$i] ?></a></span></li>
<?php
    } // end iterate over the db tables, foreach $i = 0; $i < count( $tables ); $i++

    // Close the last list!
?>
                </ul> <!-- close describeTable -->
            </li> <!-- close last tableType -->
        </ul> <!-- End navigation -->
    </div> <!-- End sidebar -->

    <div class="title" style="text-align: center;">
        <h3><?= TITLE ?></h3>
    </div>

<?php
    if ( isset( $_REQUEST['describeTableType'] ) ) {

    }
    else if ( isset( $_REQUEST['describeTable'] ) ) {
        $rowcnt = 0;
        $table = $_REQUEST['describeTable'];
        $tableColumnsHash = $PSPSSchema->getTableColumnsHash( $table );
        $tableHash = $PSPSSchema->getTableHash( $table );

        // @todo move style specification to external CSS
        print <<<EOF

    <table style="margin: 0 auto; border-width: 1px; border-style: solid;" cellpadding="3" cellspacing="0" width="75%">
        <tr>
            <th colspan = "6">$table</th>
        </tr>
        <tr>
            <th colspan = "6" align="left">Description: {$tableHash['Description']}</th>
        </tr>
        <tr>
            <th align="left">Name</th>
            <th align="left">Unit</th>
            <th align="left">Data Type</th>
            <th align="left">Size</th>
            <th align="left">Default Value</th>
            <th align="left">Description</th>
        </tr>
EOF;
        if ( isset( $tableColumnsHash ) ) {
            foreach( $tableColumnsHash as $columnName => $columnHash ) {
                $checkTableColumns = $PSPSSchema->getCheckTableColumns( $table );
                $checked = ( isset( $checkTableColumns ) && in_array( $columnName, $checkTableColumns )) ? 'checked="checked"' : '';

                $rowcnt++;

                // display alternating colors for rows
                // @todo move this into CSS
                if($rowcnt % 2 == 0) {
                    $rowbg = "#eeeeee";
                }
                else {
                    $rowbg = "#ffffff";
                }

                // substitute & with &amp; otherwise not valid HTML
                $columnHash['Description']=preg_replace( '/\&/','&amp;',$columnHash['Description'] );

// @todo move style definitions to external CSS
                print <<<EOF

        <tr valign="top" bgcolor="{$rowbg}">
            <td align="left" style="border-width: 1px; border-right-style: solid; border-color:#999999;"><strong>$columnName</strong></td>
            <td align="left" style="border-width: 1px; border-right-style: solid; border-color:#999999;">{$columnHash['Unit']}</td>
            <td align="left" style="border-width: 1px; border-right-style: solid; border-color:#999999;">{$columnHash['DataType']}</td>
            <td align="right" style="border-width: 1px; border-right-style: solid; border-color:#999999;">{$columnHash['Size']}</td>
            <td align="right" style="border-width: 1px; border-right-style: solid; border-color:#999999;">{$columnHash['Default']}</td>
            <td align="left">{$columnHash['Description']}</td>
        </tr>
EOF;
            } // foreach $tableColumnHash
        } // if isset( $tableColumnsHash )
        else {
            print "Unknown Table bra!";
        } // else

        print <<<EOF

    </table>
EOF;
    } // else if
    else {
        print <<<EOF

    <p>
        Welcome to the schema browser use the drop down menu list
        on the left and click on the tables you wish to view details on.
    </p>
EOF;
    } // else

?>

</div> <!-- End main -->
<?php
  if (isset ($PSPSSchema)) {
      // ALWAYS ALWAYS ADD class to session if it is set.
      $_SESSION["PSPSSchemaClass"] = serialize($PSPSSchema);
  }
?>
</body>
</html>
