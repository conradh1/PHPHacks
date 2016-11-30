<?php
/**
 * This script provides a form for reuquesting postage stamps
 * Once the form is completed properly, it provides a preview.
 *
 *
 * GPL version 3 or any later version.
 * @copyleft 2013 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Thomas Chen, Conrad Holmberg
 * @since Beta version 2013
 */
require_once ("session.php");
require_once ("PostageStampClass.php");
require_once ("PSPSSchemaClass.php");
require_once ("MyDBClass.php");
require_once ("PSIHTMLGoodiesClass.php");
define('TITLE','PSI Postage Stamp Request Form');

// Globals for handling queries and sessions
global $PSISession;
global $PostageStamp;
global $MyDB;
global $PSIHTMLGoodies;
global $PSPSSchema;

global $myDBTable;
global $myDBColumnList;

$PSIHTMLGoodies = new PSIHTMLGoodiesClass();
$MyDB = new MyDBClass( $PSISession );
$PSIHelp = $PSISession->getHelpObject();

// Determined by initQueryFormVariables
$providePreview = 0;

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

$PostageStamp = new PostageStampClass( $PSISession );
if ( isset($_REQUEST['IsPostBack']) || ! empty( $_REQUEST['ajaxAction']) )
{
    $PostageStamp->initFormVariables();
}

// Get MyDB Table and Columns
$myDBTable = $PostageStamp->getSelectMyDBTable();
if ( isset( $myDBTable ) ) {
    $myDBColumnList = $MyDB->getTableColumnsList( $myDBTable ); //, 1 );
}

$previewGraph = 0;
$upload = 0;
$loadDB = 0;

$msg = "";

//if ( isset($_REQUEST['submitPreview']) )
if ( isset($_REQUEST['btnDoSubmit']) ) {
    if ( $_REQUEST['btnDoSubmit'] == 'preview' || $_REQUEST['btnDoSubmit'] == 'run' ) {
        $previewGraph = 1;
        $msg = $PostageStamp->previewGraph();
    }
    else if ( $_REQUEST['btnDoSubmit'] == 'upload' ) $upload = 1;
    else if ( $_REQUEST['btnDoLoadDB'] == 'loadDB' ) {
        $loadDB = 1;
        $sql = $PostageStamp->doLoadDB_buildQuery();
        $PostageStamp->parseLoadDBRet( $MyDB->executeMyDBQuery($sql, $dbErrStr) );
    }
}

//
// Handle the AJAX calls
//
if ( !empty( $_REQUEST['ajaxAction'] ) ) {
  $ajaxAction = $_REQUEST['ajaxAction'];
  switch ( $ajaxAction ) {
      case 'getMyDBTableColumnsXML':
        ajaxMyDBColumns2XML( $myDBTable, $myDBColumnList );
        exit();
      default:
        // Error with unkown action
        print "Unkown Ajax action $ajaxAction";
        break;
  } //switch
} //if

// Copied from graph.php.
/**
* Transforms columns from a table into XML for output from a AJAX call.
*
*
* @param table myDB table
*/
function ajaxMyDBColumns2XML ( $table, $columnList ) {
  global $MyDB;

  $XMLWriter = new XMLWriter();
  // Output directly to the user

  header("Content-Type: text/xml");
  $XMLWriter->openURI('php://output');
  $XMLWriter->startDocument();

  $XMLWriter->setIndent(4);

  // declare it as a PSI Response document
  $XMLWriter->startElement('PSIResponse');

  $XMLWriter->startElement( 'table' );
  $XMLWriter->writeAttribute( 'name', $table );
  if ( isset( $columnList ) && is_array( $columnList ) ) {
    foreach ( $columnList as $column ) {
      $XMLWriter->writeElement( 'column', $column);
    } #foreach
  }
  $XMLWriter->endElement(); // Table
  // End PSIResponse
  $XMLWriter->endElement();

  $XMLWriter->endDocument();

  $XMLWriter->flush();
} //ajaxMyDBColumns2XML

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Query Page"/>
    <meta name="keywords" content="Pan-STARRS Science Web Interface Astronomy short long schema query SQL"/>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>
    <script type="text/javascript" src="javascript/psi_jquery.js"></script>
    <script type="text/javascript">
      jQuery(document).ready(function() {
        //Hide form fields that are not needed at first
        $("#idCoordRangeSkyDiv").hide();  
        $("#idCoordRangePixelDiv").hide();  
        $("#idRadioCoordRangeEntireFilterTD").hide();
        if ($("#idRadioCoordRangeEntire").attr('checked')) { $("#idRadioCoordRangeEntireFilterTD").show(); }
        if ($("#idRadioCoordRangeSky").attr('checked')) { $("#idCoordRangeSkyDiv").show(); }
        if ($("#idRadioCoordRangePixel").attr('checked')) $("#idCoordRangePixelDiv").show();

        $("#idExposureIDDiv").hide();
        $("#idImageSourceDiv").hide();
        $("#idSkyCellDiv").hide();
        $("#idDiffDetDiv").hide();
        $("#idSkyCellOptionalDiv").hide();
        $("#idOtaDiv").hide();

        $('#idSelectImageSource').change(function() {
          $("#idOtaDiv").hide();
          $("#spanSurveyID").show();
          $("#spanReleaseName").show();
          if ( $('#idSelectImageSource').val().match(/^coord$/gi) ) {
              $("#idExposureIDDiv").hide();
              $("#idImageSourceDiv").hide();
              $("#idSkyCellDiv").hide();
              showCoordForm();
          }
          else if ( $('#idSelectImageSource').val().match(/^exposure$/gi) ) {
              $("#idExposureIDDiv").show();
              $("#idImageSourceDiv").hide();
              $("#idSkyCellDiv").hide();
              ///$("#spanSurveyID").hide();
              hideCoordForm();

              if ( $('#idSelectImageType').val().match(/^chip$/gi) ) {
               $("#idOtaDiv").show();
              }
          }
          else if ( $('#idSelectImageSource').val().match(/^id$/gi) ) {
              $("#idImageSourceDiv").show();
              // Add label according to image type
              if ( $('#idSelectImageType').val().match(/^warp$/gi) ) {
               $("#idImageSourceLabel").html( 'Warp ID: ' );
              }
              else if ( $('#idSelectImageType').val().match(/^chip$/gi) ) {
               $("#idImageSourceLabel").html( 'Chip or Image ID: ' );
               $("#idOtaDiv").show();
              }
              else if ( $('#idSelectImageType').val().match(/^raw$/gi) ) {
               $("#idImageSourceLabel").html( 'Raw or Frame ID: ' );
              }
              else if ( $('#idSelectImageType').val().match(/^stack$/gi) ) {
                $("#idImageSourceLabel").html( 'Stack ID: ' );
              }
              else if ( $('#idSelectImageType').val().match(/^diff$/gi) ) {
               $("#idImageSourceLabel").html( 'Diff ID: ' );
              }

              $("#idExposureIDDiv").hide();
              $("#idSkyCellDiv").hide();
              $("#spanSurveyID").hide();
              $("#spanReleaseName").hide();
              hideCoordForm();
          }
          else if ( $('#idSelectImageSource').val().match(/^skycell$/gi) ) {
              $("#idExposureIDDiv").hide();
              $("#idImageSourceDiv").hide();
              $("#idSkyCellDiv").show();

              if ($("#idRadioCoordRangeEntire").attr('checked')) {
                  hideCoordForm();
              } else {
                  showCoordForm();
              }
          }

          useOptionalSkyCellID();

        }); //idSelectImageSource change

        $('#idSelectImageType').change(function() {
           $("#idOtaDiv").hide();
           $("#option_RESTORE_BACKGROUND").attr("disabled", true);

           // Add label according to image type
           if ( $('#idSelectImageType').val().match(/^warp$/gi) ) {
             $("#idImageSourceLabel").html( 'Warp ID: ' );
           }
           else if ( $('#idSelectImageType').val().match(/^chip$/gi) ) {
             $("#idImageSourceLabel").html( 'Chip or Image ID: ' );
             $("#option_RESTORE_BACKGROUND").removeAttr("disabled");
           }
           else if ( $('#idSelectImageType').val().match(/^raw$/gi) ) {
             $("#idImageSourceLabel").html( 'Raw or Frame ID: ' );
           }
           else if ( $('#idSelectImageType').val().match(/^stack$/gi) 
                  || $('#idSelectImageType').val().match(/^stack_short$/gi) ) {
             $("#idImageSourceLabel").html( 'Stack ID: ' );
             $("#option_RESTORE_BACKGROUND").removeAttr("disabled");
           }      
           else if ( $('#idSelectImageType').val().match(/^diff$/gi) ) {
             $("#idImageSourceLabel").html( 'Diff ID: ' );
           }

           updateSelectImageSource( $('#idSelectImageType').val() );
           useOptionalSkyCellID();
           updateOptionsDisplay();
        });

        $('#idRadioCoordRangeEntire').click(function() {
          if ( $('#idRadioCoordRangeEntire').val().match(/^entire$/gi) ) {
            $("#idCoordRangeSkyDiv").hide(); 
            $("#idCoordRangePixelDiv").hide(); 
            $("#idRadioCoordRangeEntireFilterTD").show();

            if ( $('#idSelectImageSource').val().match(/^coord$/gi) ) {
                showCoordForm();
            } else {
                hideCoordForm();
            }
          }
        });
        $('#idRadioCoordRangeSky').click(function() {          
          if ( $('#idRadioCoordRangeSky').val().match(/^sky$/gi) ) {
            $("#idCoordRangeSkyDiv").show();  
            $("#idCoordRangePixelDiv").hide();  
            $("#idRadioCoordRangeEntireFilterTD").hide();
            showCoordForm();
          }          
        });
        $('#idRadioCoordRangePixel').click(function() {          
          if ( $('#idRadioCoordRangePixel').val().match(/^pixel$/gi) ) {
            $("#idCoordRangeSkyDiv").hide();  
            $("#idCoordRangePixelDiv").show();  
            $("#idRadioCoordRangeEntireFilterTD").hide();
            showCoordForm();
          }          
        });

        $('#idSurveyID').change(function() {
            updateReleaseName();
        });

        $('#idReleaseName').change(function() {
            updateStackCount();
        });

        $('#idSelectMyDBRa').change(function() {
            validateMyDB2();
        });

        $('#idSelectMyDBDec').change(function() {
            validateMyDB2();
        });

        $('#idSelectMyDBFilter').change(function() {
            validateMyDB3();
        });

        // Update Column list if user chooses a different MyDB table.
        $('#idSelectMyDBTable').change(function() {
            validateMyDB1();
            initSelectColumns();

            $.get("<?=$_SERVER['PHP_SELF']?>?ajaxAction=getMyDBTableColumnsXML&selectMyDBTable="+$("#idSelectMyDBTable").val(), function(xml) {
                var myDBTableColumns = new Array()
                $(xml).find('column').each(function() {
                    var column = $(this).text()
                    myDBTableColumns.push(column)
                });
                setSelectColumns( myDBTableColumns );

                validateMyDB2();
                validateMyDB3();
            }, 'xml')
        });

        // form fields for selected image type/source.
        getSelection(document.PostageStampForm.selectSurveyID, '<?=$PostageStamp->getselectSurveyID() ?>');
        updateReleaseName();
        getSelection(document.PostageStampForm.selectReleaseName, '<?=$PostageStamp->getselectReleaseName() ?>');
        //updateStackCount(document.PostageStampForm.selectReleaseName.value);

        getSelection(document.PostageStampForm.selectImageType, '<?=$PostageStamp->getselectImageType() ?>');
        // Thomas. 1/29/2015. Bug fix: Hide "Exposure" for stack. Reported by David L. Clark on 1/23/2015.
        updateSelectImageSource( $('#idSelectImageType').val() ); 
        getSelection(document.PostageStampForm.selectImageSource, '<?=$PostageStamp->getselectImageSource() ?>');
        getSelection(document.PostageStampForm.selectMyDBRows, '<?=$PostageStamp->getselectMyDBRows()?>');
        getSelection(document.PostageStampForm.option_DIFF_TYPE, '<?=$PostageStamp->getOption_DIFF_TYPE()?>');
        $('#idSelectImageSource').trigger("change");
        validateMyDB1();
        validateMyDB2();
        validateMyDB3();
        updateCoordRowsLabel( <?=$PostageStamp->getcoordCount()?> );
        updateBtnMjdDateText( <?=$PostageStamp->getuseMjdDate()?> );
        updateMoreOpt( <?=$PostageStamp->getShowMoreOpt()?> );
        changeCoordSrc( '<?=$PostageStamp->getcoordSrc()?>', null );
        updateOptionsDisplay();

        // Note: ignoreRaDec() is undefined at this stage.
        /*
        var ignoreRaDec = ( $('#idSelectImageSource').val().match(/^exposure$/gi) ||
               $('#idSelectImageSource').val().match(/^id$/gi)
             ) || $('#idRadioCoordRangeEntire').is(':checked');

        if ( $('#idSelectImageSource').val().match(/^coord$/gi) ) {
            ignoreRaDec = false;
        }
        */

        if ( ignoreRaDec() ) {
            hideCoordForm();
        }

      }); //JQuery ready

function ignoreRaDec() {
    //alert( $('#idSelectImageSource').val() + ',' + $('#idRadioCoordRangeEntire').is(':checked') );
    var src = $('#idSelectImageSource').val(); 
    var ignoreRaDec;
    
    if (! $('#idRadioCoordRangeEntire').is(':checked')) {
        ignoreRaDec = false;
    } else if ( src.match(/^coord$/gi) ) {
        ignoreRaDec = false;
    } else {
        ignoreRaDec = true;
    }

    return ignoreRaDec;
}

/*
function ignoreRaDec() {
    //alert( $('#idSelectImageSource').val() + ',' + $('#idRadioCoordRangeEntire').is(':checked') );

    var s = $('#idSelectImageSource').val();
    var r = $('#idRadioCoordRangeEntire').is(':checked');

    if (r == false) return false;
    else {
        if (s.match(/^coord$/gi)) {
            return false;
        }
    }

    // note here r is always true, so this can be simplified to "return r;".
    if ( (s.match(/^exposure$/gi) || s.match(/^id$/gi)) || r == true) return true;
    return false;
}
*/

// show only when 1) method of image selection is NOT 'exposure' or 'id'
//            AND 2) size of postage stamp is NOT 'entire'.
function showCoordForm() {
    if ( ignoreRaDec() ) { return; }

    $("#idCoordCenterDiv").show();
    $("#idInputCoordFrom").show();
    changeCoordSrc( getCoordSrc(), null );
}

function hideCoordForm() {
    if (! ignoreRaDec() ) return;

    $("#idCoordCenterDiv").hide();
    $("#idInputCoordFrom").hide();
    $("#idCoordRowsDiv").hide();
    $("#idCoordRowsDiv2").hide();
    $("#idCoordUploadDiv").hide();
    $("#idCoordDbDiv").hide();
}

function updateOptionsDisplay() {
    $("#option_RESTORE_BACKGROUND").attr("disabled", true);

    var o = document.getElementById('idSelectImageType');
    if (o.value == 'stack' || o.value == 'stack_short') {
        document.getElementById('spanOptStack').style.display = 'block';
        document.getElementById('spanOptDiff').style.display = 'none';
        document.getElementById('spanOptChipWarpDiff').style.display = 'none';
        document.getElementById('spanOptNotDiff').style.display = 'block';
    
        $("#option_RESTORE_BACKGROUND").removeAttr("disabled");    
    } else if (o.value == 'diff') {
        document.getElementById('spanOptStack').style.display = 'none';
        document.getElementById('spanOptDiff').style.display = 'block';
        document.getElementById('spanOptChipWarpDiff').style.display = 'block';
        document.getElementById('spanOptNotDiff').style.display = 'none';
    } else if (o.value == 'chip' || o.value == 'warp') {
        document.getElementById('spanOptStack').style.display = 'none';
        document.getElementById('spanOptDiff').style.display = 'none';
        document.getElementById('spanOptChipWarpDiff').style.display = 'block';
        document.getElementById('spanOptNotDiff').style.display = 'block';

        if (o.value == 'chip') $("#option_RESTORE_BACKGROUND").removeAttr("disabled");
    }
}

// 
// Restrictions:
//
//  ImageType  ImageSource
//  ----------------------
//  - chip:    no skycell
//  - stack:   no exposure
//
function updateSelectImageSource( v ) {
    var o = document.getElementById('idSelectImageSource');
    //var id = o.selectedIndex; //alert(id);

    o.options.length = 0;
    if (v == 'chip') { // no skycell.
        o.options[0] = new Option('-- Select --', '');
        o.options[1] = new Option('Coordinate - Images are selected based on supplied RA and DEC.', 'coord');
        o.options[2] = new Option('Exposure - Single frame images selected by name (IPP rawExp.exp_name PSPS FrameMeta.name).', 'exposure');
        o.options[3] = new Option('ID - Specific images are selected by IPP database ID (frame_id, chip_id, warp_id, stack_id, diff_id).', 'id');
    } else if (v == 'stack' || v == 'stack_short') { // no exposure.
        o.options[0] = new Option('-- Select --', '');
        o.options[1] = new Option('Coordinate - Images are selected based on supplied RA and DEC.', 'coord');
        o.options[2] = new Option('ID - Specific images are selected by IPP database ID (frame_id, chip_id, warp_id, stack_id, diff_id).', 'id');
        o.options[3] = new Option('Skycell -  Warp, Stack, and Difference images selected based on tessellation id (tess_id) and skycell_id.', 'skycell');
    } else {
        o.options[0] = new Option('-- Select --', '');
        o.options[1] = new Option('Coordinate - Images are selected based on supplied RA and DEC.', 'coord');
        o.options[2] = new Option('Exposure - Single frame images selected by name (IPP rawExp.exp_name PSPS FrameMeta.name).', 'exposure');
        o.options[3] = new Option('ID - Specific images are selected by IPP database ID (frame_id, chip_id, warp_id, stack_id, diff_id).', 'id');
        o.options[4] = new Option('Skycell -  Warp, Stack, and Difference images selected based on tessellation id (tess_id) and skycell_id.', 'skycell');
    }
}


function updateReleaseName() {
    var v = document.getElementById("idSurveyID").value;
    var o = document.getElementById("idReleaseName");

    o.options.length = 0;
    var oi = 0;
    o.options[oi] = new Option('-- Select --', '');

    var e = h[v];
    if (e == null) return;

    var lene = e.length;

    if (lene > 0) {
        ++ oi;
        o.options[oi] = new Option(' ', ' ');
        o.selectedIndex = 1;
    }

    for (var i = 0; i < lene; ++ i) {
        ++ oi;
        o.options[oi] = new Option(e[i], e[i]);
    }

    if (v == '3PI' && lene >= 1) {
        o.selectedIndex = 2;
    }

    // if v == '3PI', disable this option, because short stack count is 0.
    //document.getElementById('idSelectImageType').options[3].disabled = (v == '3PI');
    //updateStackCount(o.value);
}

function updateStackCount() {
    var o_total_stack = document.getElementById('idSelectImageType').options[3];
    var o_short_stack = document.getElementById('idSelectImageType').options[4];
    var divImageType  = document.getElementById('idSelectImageType');

    o_total_stack.disabled = false;
    o_short_stack.disabled = false;

    var v = $('#idReleaseName').val();
    if (v.trim() != '') {
        //alert(v + ', ts=' + ts[v] + ', ss=' + ss[v]);
        if (ts[v] == 0) { 
            o_total_stack.disabled = true; 
            if (divImageType.selectedIndex == 3) divImageType.selectedIndex = 0;
        }
        if (ss[v] == 0) { 
            o_short_stack.disabled = true; 
            if (divImageType.selectedIndex == 4) divImageType.selectedIndex = 0;
        }
    }
}


function validateMyDB3() {
    if (document.getElementById('idSelectMyDBRa').value != '' &&
        document.getElementById('idSelectMyDBDec').value != '' &&
        document.getElementById('idSelectMyDBFilter').value != '') {
        document.getElementById('idSelectMyDBStartDate').disabled = false;
        document.getElementById('idSelectMyDBEndDate').disabled = false;
    }
    else {
        document.getElementById('idSelectMyDBStartDate').disabled = true;
        document.getElementById('idSelectMyDBEndDate').disabled = true;
    }
}

function validateMyDB2() {
    if (document.getElementById('idSelectMyDBRa').value != '' &&
        document.getElementById('idSelectMyDBDec').value != '') {
        document.getElementById('idSelectMyDBFilter').disabled = false;
        //document.getElementById('idSelectMyDBStartDate').disabled = false;
        //document.getElementById('idSelectMyDBEndDate').disabled = false;
    }
    else {
        document.getElementById('idSelectMyDBFilter').disabled = true;
        //document.getElementById('idSelectMyDBStartDate').disabled = true;
        //document.getElementById('idSelectMyDBEndDate').disabled = true;
    }
}

function validateMyDB1() {
    if (document.getElementById('idSelectMyDBTable').value != '') {
        document.getElementById('idSelectMyDBRa').disabled = false;
        document.getElementById('idSelectMyDBDec').disabled = false;
    } else {
        document.getElementById('idSelectMyDBRa').disabled = true;
        document.getElementById('idSelectMyDBDec').disabled = true;
    }
}

//
// Initialize the contents of these selection lists to "Loading", for better clue to user what's going on.
//
function initSelectColumns () {
   initSelectColumn( document.getElementById('idSelectMyDBRa') );
   initSelectColumn( document.getElementById('idSelectMyDBDec') );
   initSelectColumn( document.getElementById('idSelectMyDBFilter') );
   initSelectColumn( document.getElementById('idSelectMyDBStartDate') );
   initSelectColumn( document.getElementById('idSelectMyDBEndDate') ); 
}

function initSelectColumn(c) {
    c.options.length = 0;
    c.options[0] = new Option('Loading ...', '');
}

function setSelectColumns( myDBTableColumns ) {
    //alert( myDBTableColumns.length );
    setListColumns( myDBTableColumns, document.getElementById('idSelectMyDBRa'), '', 'ra' );
    setListColumns( myDBTableColumns, document.getElementById('idSelectMyDBDec'), '', 'dec' );
    setListColumns( myDBTableColumns, document.getElementById('idSelectMyDBFilter'), '', 'filter' );
    setListColumns( myDBTableColumns, document.getElementById('idSelectMyDBStartDate'), 'datetime', '' );
    setListColumns( myDBTableColumns, document.getElementById('idSelectMyDBEndDate'), 'datetime', '' );
}

function setListColumns( myDBTableColumns, list, data_type, default_v ) {
    list.options.length = 0;
    // new Option([text], [value], [defaultSelected], [selected]);
    list.options[0] = new Option( '-- Select --', ''); //, true, false);

    //var ct = 1;
    var v;
    list.selectedIndex = 0;
    for ( var i = 0; i < myDBTableColumns.length; i++ ) {
        //var a = myDBTableColumns[i].split(",");
        //if (data_type == '') { 
        //    list.options[ct ++] = new Option(a[0], a[0]);
        //} else if (data_type == 'datetime' && (a[1] == 'datetime' || a[1] == 'datetimeoffset') ) {
        //    list.options[ct ++] = new Option(a[0], a[0]);
        //}

        v = myDBTableColumns[i];
        list.options[i + 1] = new Option(v, v);

        if (default_v != '' && list.selectedIndex == 0) { // use the first match.
            var re1 = new RegExp(default_v + '$', 'i');
            var re2 = new RegExp('^' + default_v, 'i');
            if (v.match(re1) || v.match(re2)) {
                list.selectedIndex = (i + 1);
            }
        }
    }
}

function useOptionalSkyCellID() {
    var type = document.getElementById('idSelectImageType').value;
    var src  = document.getElementById('idSelectImageSource').value;
    //if ( ( type == 'warp' || type == 'stack' || type == 'diff' ) &&
    if ( ( type == 'warp' || type == 'diff' ) &&
               ( src == 'coord' || src == 'exposure' || src == 'id' ) ) {
        $("#idSkyCellOptionalDiv").show();
    } else {
        $("#idSkyCellOptionalDiv").hide();
    }
}

function getSelection(obj, v) {
    for (var i = 0; i < obj.length; ++ i) {
        if (v == obj[i].value) {
           obj.selectedIndex = i;
           break;
        }
    }
    return -1;
}

function validateUI() {
    var msg = '';
    var o = null, t, v;

    v = document.getElementById('option_FWHM_MIN');
    if (! validateFloat(v.value)) {
        o = v;
        msg = 'fwhm_min should be a float number\r\n' + msg;
    }
    v = document.getElementById('option_FWHM_MAX');
    if (! validateFloat(v.value)) {
        o = v;
        msg = 'fwhm_max should be a float number\r\n' + msg;
    }

    // Validate coord center: ra/dec etc.
    var coordSrc = getCoordSrc();

    var ignore_ra_dec = (
           $('#idSelectImageSource').val().match(/^exposure$/gi) ||
           $('#idSelectImageSource').val().match(/^id$/gi)
         )
           ||
         $('#idRadioCoordRangeEntire').is(':checked');
    //ignore_ra_dec = ignoreRaDec();

    if ( $('#idSelectImageSource').val().match(/^coord$/gi) ) ignore_ra_dec = false;

  if (! ignore_ra_dec) {
    if (coordSrc == "form" && document.getElementById('idSelectImageSource').value == 'coord') {
        var coordCt = parseInt( document.getElementById('idCoordCount').value );
        //alert('ct: ' + coordCt);
        for (i = coordCt; i >= 1; -- i) {
            var v = document.getElementById('textCoordCenterRa_' + i);
            if (v) {
                //alert('row ' + i + ' exists');
                if ( ! validateDate( document.getElementById('textMjdMax_' + i) )) {
                    o = document.getElementById('textMjdMax_' + i);
                    msg = 'End Date: should be either empty, or a valid date in YYYY-MM-DD or MJD format\r\n' + msg;
                }

                if ( ! validateDate( document.getElementById('textMjdMin_' + i) )) {
                    o = document.getElementById('textMjdMin_' + i); 
                    msg = 'Start Date: should be either empty, or a valid date in YYYY-MM-DD or MJD format\r\n' + msg;
                }

                if ( document.getElementById('textCoordCenterDec_' + i).value == '') {
                    o = document.getElementById('textCoordCenterDec_' + i);
                    msg = 'Center Coordinate Dec: cannot be empty\r\n' + msg;
                }

                if ( document.getElementById('textCoordCenterRa_' + i).value == '') {
                    o = document.getElementById('textCoordCenterRa_' + i);
                    msg = 'Center Coordinate Ra: cannot be empty\r\n' + msg;
                }
            }
        }
    } else if (coordSrc == "upload"  && document.getElementById('idSelectImageSource').value == 'coord' ) {
        var v = document.getElementById('textAreaUpload');
        if (v.value == '') {
            o = v; //document.getElementById('fileUpload');
            msg = 'Center Coordinates List should not be empty, please upload\r\n' + msg;
        } else if (<?=($PostageStamp->getuploadErrStr() == '') ? 0 : 1?>) {
            msg = 'Uploaded data contains error\r\n' + msg;
        }
    } else if (coordSrc == "db"  && document.getElementById('idSelectImageSource').value == 'coord' ) {
        var v = document.getElementById('textAreaDb');
        //alert(v.innerHTML);
        if (v.innerHTML == '') {
            o = v; 
            msg = 'Center Coordinates List should not be empty, please load\r\n' + msg;
        } else if (<?=($PostageStamp->getuploadErrStr() == '') ? 0 : 1?>) {
            msg = 'Loaded data contains error\r\n' + msg;
        }
    }
  } // end if (! ignoreRaDec)

    t = document.getElementById('idSelectImageSource').value;
    if (t == 'coord') {
        // do nothing
    } else if (t == 'exposure') {
        if (document.getElementById('textExposureID').value == '') {
            o = document.getElementById('textExposureID');
            msg = 'Frame Name: cannot be empty\r\n' + msg;
        }
    } else if (t == 'id') {
        // do nothing.
    } else if (t == 'skycell') {
        // disabled on 4/26/2013, per Bill's request.
        if (document.getElementById('textTessellation').value == '') {
           o = document.getElementById('textTessellation');
           msg = 'Tessellation ID: cannot be empty\r\n' + msg;
        }
        if (document.getElementById('textSkyCell').value == '') {
           o = document.getElementById('textSkyCell');
           msg = 'Skycell ID: cannot be empty\r\n' + msg;
        }
    }

    if (document.getElementById('idSelectImageSource').value == 'id') {
        o = document.getElementById('textImageSource');
        if (o.value == '') {
            msg = document.getElementById('idImageSourceLabel').innerHTML + ': cannot be empty\r\n' + msg;
        }
    }

    if ( document.getElementById('idRadioCoordRangeSky').checked ) {
        if (document.getElementById('textCoordRangeDec').value == '') {
            o = document.getElementById('textCoordRangeDec');
            msg = 'Sky Height: cannot be empty\r\n' + msg;
        }
        if (document.getElementById('textCoordRangeRa').value == '') {
            o = document.getElementById('textCoordRangeRa');
            msg = 'Sky Width: cannot be empty\r\n' + msg;
        }
    } else if ( document.getElementById('idRadioCoordRangePixel').checked ) {
        if (! validateGE0( document.getElementById('textCoordPixelHeight') )) {
            o = document.getElementById('textCoordPixelHeight');
            //msg = 'Pixel height: should be either empty, or double value >= 0.0\r\n' + msg;
            msg = 'Pixel height: should be non-empty double value >= 0.0\r\n' + msg;
        }
        if (! validateGE0( document.getElementById('textCoordPixelWidth') )) {
            o = document.getElementById('textCoordPixelWidth');
            //msg = 'Pixel width: should be either empty, or double value >= 0.0\r\n' + msg;
            msg = 'Pixel width: should be non-empty double value >= 0.0\r\n' + msg;
        }
    } 

    if (document.getElementById('idSelectImageSource').value == '') {
        o = document.getElementById('idSelectImageSource');
        msg = 'Method of Image Selection: cannot be empty\r\n' + msg;
    }

    if (document.getElementById('idSelectImageType').value == '') {
        o = document.getElementById('idSelectImageType');
        msg = 'Image Type: cannot be empty\r\n' + msg;
    }

    //if (document.getElementById('idReleaseName').value == '') {
    //    o = document.getElementById('idReleaseName');
    //    msg = 'Release Name: cannot be empty\r\n' + msg;
    //}

    if (document.getElementById('idSurveyID').value == '') {
        if ( $('#idSelectImageSource').val() != 'id' && 
             $('#idSelectImageSource').val() != 'exposure' ) {
            o = document.getElementById('idSurveyID');
            msg = 'Survey ID: cannot be empty\r\n' + msg;
        }
    }

    if (msg != '') {
        alert(msg);
        if (o != null) o.focus();
        return false;
    }

    return true;
}

function validateGE0(obj) {
    var v0 = jQuery.trim( obj.value );
    if (v0 == '') {
        //return true;
        return false;
    } else {
        var v = parseFloat( v0 );
        return v >= 0;
    }
}

function validateDate(obj) {
    var v = jQuery.trim( obj.value );
    if (v == '') {
        return true;
    } else {
        if ( /^\d\d\d\d-\d\d-\d\d$/.test(v) && ! isNaN( Date.parse(v) ) ) return true; // YYYY-MM-DD. 
        else if ( /^\d{1,5}(\.\d{1,5})?$/.test(v) ) return true; // MJD format.
        return false;
    }
}

function validateFloat(v) {
    var v = jQuery.trim( v );
    if (v == '') {
        return true;
    } else {
        if ( /^\d+(\.\d+)?$/.test(v) ) return true;
        else return false;
    }
}

function doSubmit(v) {
    if (! validateUI()) return false;

    document.getElementById('actionNote').innerHTML = '<blink>Submitting request to ' + v + '...</blink>';
    document.getElementById('btnDoSubmit').value = v;
    document.PostageStampForm.submit();
    return false;
}

function doReset() {
    window.location.href = "postage_stamp.php";
    return false;
}

function toggleMjdDate() {
    var u = document.getElementById('idUseMjdDate');
    if (u.value == '0') {
        u.value = '1';
    } else {
        u.value = '0';
    }

    updateBtnMjdDateText(u.value);
}

function updateBtnMjdDateText(v) {
    document.getElementById('idToggleMjdDate').value = (v == 1) ? "Hide Optional Date Range" : "Show Optional Date Range";
    document.getElementById('idDateFormat').style.display = (v == 1) ? "inline-block" : "none";
    var i, o, ct = parseInt( document.getElementById('idCoordCount').value );
    var dis = getMjdDateDisplay();
    for (i = 1; i <= ct; ++ i) {
        o = document.getElementById('spanMjdDate_' + i);
        if (o) {
            o.style.display = dis;
        }
    }
}

function getMjdDateDisplay() {
    return (document.getElementById('idUseMjdDate').value == 1) ? "inline-block" : "none";
}

function copyCoordRow() {
    addCoordRows();
 
    // copy value from last row.
    var n_new = parseInt( document.getElementById('idCoordCount').value );
    var n_old = n_new - 1;
    while ( document.getElementById('textCoordCenterRa_' + n_old) == null ) {
        -- n_old; // handle the case the previous row was deleted.
    }

    document.getElementById('textCoordCenterRa_' + n_new).value = 
        document.getElementById('textCoordCenterRa_' + n_old).value;
    document.getElementById('textCoordCenterDec_' + n_new).value = 
        document.getElementById('textCoordCenterDec_' + n_old).value;
    document.getElementById('selectFilter_' + n_new).selectedIndex = 
        document.getElementById('selectFilter_' + n_old).selectedIndex;
    document.getElementById('idCoordUnit_' + n_new).selectedIndex =
        document.getElementById('idCoordUnit_' + n_old).selectedIndex;

    if (document.getElementById('idUseMjdDate').value == 1) {
        document.getElementById('textMjdMin_' + n_new).value = 
            document.getElementById('textMjdMin_' + n_old).value;
        document.getElementById('textMjdMax_' + n_new).value = 
            document.getElementById('textMjdMax_' + n_old).value;
    }
}

function addCoordRows() {
    var ct = document.getElementById('idCoordCount');
    ct.value = 1 + parseInt(ct.value);
    $('#idCoordRows').append("<div id='newCoordRow_" + ct.value + "'>" + addCoordRow(ct.value) + "</div>");
    updateCoordRowsLabel(parseInt( $('#idCoordCountLabel').html() ) + 1);
}

function updateCoordRowsLabel(ct) {
    $('#idCoordCountLabel').html(ct);
}

//
//  The unit dropdown list removed:
//  <select id='idCoordUnit_" + i + "' name='selectCoordUnit_" + i + "'> \
//      <option value='deg'>deg</option> \
//      <option value='arc-sec'>arc-sec</option> \
//      <option value='arc-min'>arc-min</option> \
//  </select>&nbsp; \
//
function addCoordRow(i) {
    var dis = getMjdDateDisplay();
    var s = "\
Ra: <input type='text' id='textCoordCenterRa_" + i + "' name='textCoordCenterRa_" + i + "' maxlength='20' size='10'/> \
Dec: <input type='text' id='textCoordCenterDec_" + i + "' name='textCoordCenterDec_" + i + "' maxlength='20' size='10'/> \
<span style='font-size: 12px; color: red;'>deg (J2000)</span> &nbsp; \
<input type='hidden' id='idCoordUnit_" + i + "' name='selectCoordUnit_" + i + "' value='arc-min'> \
Filters: \
<select id='selectFilter_" + i + "' name='selectFilter_" + i + "'> \
  <option value='a'>all</option> \
  <option value='g'>g</option> \
  <option value='r'>r</option> \
  <option value='i'>i</option> \
  <option value='z'>z</option> \
  <option value='y'>y</option> \
  <option value='w'>w</option> \
</select> \
&nbsp; \
<span id='spanMjdDate_" + i + "' style='display: " + dis + "'> \
Start Date<?=$PostageStamp->getstrOptional()?>: <input type='text' id='textMjdMin_" + i + "' name='textMjdMin_" + i + "' size='10' maxlength='20' value='' /> \
End Date<?=$PostageStamp->getstrOptional()?>: <input type='text' id='textMjdMax_" + i + "' name='textMjdMax_" + i + "' size='10' maxlength='20' value='' /> \
</span> \
<input type='button' name='btnDelete_" + i + "' value='Remove' onclick='javascript: deleteCoordRow(" + i + ");' /> \
";
    return s;
}

function deleteCoordRow(i) {
    $('#newCoordRow_' + i).remove();
    updateCoordRowsLabel(parseInt( $('#idCoordCountLabel').html() ) - 1);
}

function onLoadDB() {
    if (document.getElementById('idSelectMyDBTable').value == '' ||
        document.getElementById('idSelectMyDBRa').value == '' ||
        document.getElementById('idSelectMyDBDec').value == '')
    {
        alert('Please choose a MyDB table and RA, DEC fields in this table');
        return false;
    }

    // Make sure no two fields have the same name.
    var columns = [
        document.getElementById('idSelectMyDBRa').value, 
        document.getElementById('idSelectMyDBDec').value,
        document.getElementById('idSelectMyDBFilter').value, 
        document.getElementById('idSelectMyDBStartDate').value,
        document.getElementById('idSelectMyDBEndDate').value
    ];
    columns.sort();
    for (var i = 0; i < columns.length - 1; ++ i) {
        if (columns[i] != null && columns[i] != '') {
            if (columns[i] == columns[i+1]) {
                alert("All selected MyDB table fields should be different");
                return false;
            }
        }
    }

    document.getElementById('btnLoadDB').disabled = true;
    document.getElementById('actionNote').innerHTML = '<blink>Loading...</blink>';
    document.getElementById('btnDoLoadDB').value = 'loadDB';
    document.PostageStampForm.submit();
    return false;
}

function onFileSelect(o) {
    //alert(o + '. val = ' + o.value);
    document.getElementById('actionNote').innerHTML = '<blink>Uploading...</blink>';
    document.getElementById('btnDoSubmit').value = 'upload';
    document.PostageStampForm.submit();
    return false;
}

function changeCoordSrc(v, o) {
    //alert(o.value);
    if (v == 'form') {
        $("#idCoordRowsDiv").show();
        $("#idCoordRowsDiv2").show();
        $("#idCoordUploadDiv").hide();
        $("#idCoordDbDiv").hide();
        $("#idDivMyDBTableDiv").hide();
        if (o == null) document.getElementById("idRadioCoordSrcForm").checked = true;
    } else if (v == 'upload') {
        $("#idCoordRowsDiv").hide();
        $("#idCoordRowsDiv2").hide();
        $("#idCoordUploadDiv").show();
        $("#idCoordDbDiv").hide();
        $("#idDivMyDBTableDiv").hide();
        if (o == null) document.getElementById("idRadioCoordSrcUpload").checked = true;
    } else if (v == 'db') {
        $("#idCoordRowsDiv").hide();
        $("#idCoordRowsDiv2").hide();
        $("#idCoordUploadDiv").hide();
        $("#idCoordDbDiv").show();
        $("#idDivMyDBTableDiv").show();
        if (o == null) document.getElementById("idRadioCoordSrcDb").checked = true;
    } else {
        // shouldn't happen.
    }
}

function getCoordSrc() {
    if (document.getElementById("idRadioCoordSrcForm").checked) return "form";
    else if (document.getElementById("idRadioCoordSrcUpload").checked) return "upload";
    else if (document.getElementById("idRadioCoordSrcDb").checked) return "db";
    else return "";
}

function toggleMoreOpt() {
    var u = document.getElementById('btnShowMoreOptionsVal');
    if (u.value == '0') {
        u.value = '1';
    } else {
        u.value = '0';
    }

    updateMoreOpt(u.value);
}

function updateMoreOpt(v) {
    document.getElementById('idMoreOpt').style.display = (v == 1) ? 'block' : 'none';
    document.getElementById('btnShowMoreOptions').value = (v == 1) ? 'Hide More Options' : 'Show More Options';
}

     </script>
    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
    <?php require_once("menubar_header.php"); ?>
</head>
<body>
<?php require_once("top.php"); ?>
<?php require_once("menubar.php"); ?>
<div id="main">
<div style="text-align: center;">
<h2><?=$PSIHelp->getWikiURL('PSI-PostageStampRequestForm')?>&nbsp;<?=TITLE?></h2>
<form enctype="multipart/form-data" name="PostageStampForm" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">

<input type='hidden' name='IsPostBack' value='Y'/>

<table style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="3" width="1000">
    <tr>
         <td align="left">
<?php echo $PostageStamp->buildSurveyReleaseList(); ?>
         </td>
    </tr>
    <tr>
        <td align="left">
            Image Type: &nbsp;
            <select id="idSelectImageType" name="selectImageType">
              <option value="">-- Select One --</option>
              <optgroup label="Single Epoch Images">
                  <option value="chip">Detrended Chip Images, 1 pixel ~ 0.256 arcsec, orientation as observed</option>
                  <option value="warp">Warped Image, 1 pixel = 0.250 arcsec, pixels resampled, registered in RA, Dec</option>
              </optgroup>
              <optgroup label="Stacked Images">
                  <option value="stack">Total Stacked Image, 1 pixel = 0.250 arcsec, pixels resampled, registered in RA, Dec</option>
                  <option value="stack_short">Short Stacked Image, 1 pixel = 0.250 arcsec, pixels resampled, registered in RA, Dec</option>
              </optgroup>
              <optgroup label="Difference Images">
                  <option value="diff">Diff Image, 1 pixel = 0.250 arcsec, pixels resampled, registered in RA, Dec</option>
              </optgroup>
           </select>
        </td>
    </tr>
    <tr>
        <td align="left">
            Method of Image Selection by: &nbsp;
            <select id="idSelectImageSource" name="selectImageSource" >
              <option value="">-- Select One --</option>
              <option value="coord">Coordinate - Images are selected based on supplied RA and DEC.</option>
              <option value="exposure">Exposure - Single frame images selected by name (IPP rawExp.exp_name PSPS FrameMeta.name).</option>
              <option value="id">ID - Specific images are selected by IPP database ID (frame_id, chip_id, warp_id, stack_id, diff_id).</option>
              <option value="skycell">Skycell -  Warp, Stack, and Difference images selected based on tessellation id (tess_id) and skycell_id.</option>
           </select>
        </td>
    </tr>
    <tr id="idCoordRangeTypeDiv">
      <td>

        <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td align="left">
           <strong>Size of Postage Stamp: </strong> 
           <input type="radio" id="idRadioCoordRangeEntire" name="radioCoordRangeType" value="entire"
             <?=($PostageStamp->getradioCoordRangeType() == '' || $PostageStamp->getradioCoordRangeType() == 'entire') ? 'checked' : '' ?>/>Entire Image
           <input type="radio" id="idRadioCoordRangeSky" name="radioCoordRangeType" value="sky" 
             <?=($PostageStamp->getradioCoordRangeType() == '' || $PostageStamp->getradioCoordRangeType() == 'sky') ? 'checked' : '' ?>/>Arc-seconds
           &nbsp; <input type="radio" id="idRadioCoordRangePixel" name="radioCoordRangeType" value="pixel"
             <?=($PostageStamp->getradioCoordRangeType() == 'pixel') ? 'checked' : '' ?> /> Pixels
             &nbsp; &nbsp;
          </td>
          <td align="left" id="idCoordRangeSkyDiv">           Width: <input type="text" id="textCoordRangeRa" name="textCoordRangeRa" maxlength="20" size="10" value="<?=$PostageStamp->gettextCoordRangeRa()?>"/> <span style='font-size: 12px;'>arc-seconds</span>
           &nbsp;Height: <input type="text" id="textCoordRangeDec" name="textCoordRangeDec" maxlength="20" size="10" value="<?=$PostageStamp->getTextCoordRangeDec()?>"/>  <span style='font-size: 12px;'>arc-seconds</span>
          </td>
          <td align="left" id="idCoordRangePixelDiv">           Width: <input type="text" id="textCoordPixelWidth" name="textCoordPixelWidth" maxlength="20" size="10" value="<?=$PostageStamp->gettextCoordPixelWidth()?>"/> <span style='font-size: 12px;'>pixels</span>
           Height: <input type="text" id="textCoordPixelHeight" name="textCoordPixelHeight" maxlength="20" size="10" value="<?=$PostageStamp->gettextCoordPixelHeight()?>"/>  <span style='font-size: 12px;'>pixels</span>
          </td>

          <td align="left" id="idRadioCoordRangeEntireFilterTD">
          Filters: <SELECT id="idRadioCoordRangeEntireFilter" name="idRadioCoordRangeEntireFilter">
                   <option value='a'>all</option>
                   <option value='g'>g</option>
                   <option value='r'>r</option>
                   <option value='i'>i</option>
                   <option value='z'>z</option>
                   <option value='y'>y</option>
                   <option value='w'>w</option>
                   </SELECT> 
          </td>

        </tr>
        </table>

      </td>
    </tr>
    <tr id="idExposureIDDiv">
        <td align="left">
            Frame Name: 
            <input type="text" id="textExposureID" name="textExposureID" maxlength="25" size="16" value="<?=$PostageStamp->getTextExposureID()?>"/>
        </td>
    </tr>
    <tr id="idImageSourceDiv"> 
        <td align="left">
            <span id="idImageSourceLabel">Image ID:</span>
            <input type="text" id="textImageSource" name="textImageSource" maxlength="25" size="16" value="<?=$PostageStamp->getTextImageSource()?>"/>
        </td>
    </tr>
    <tr id="idSkyCellDiv">
        <td align="left">
            Skycell ID: &nbsp;
            <input type="text" id="textSkyCell" name="textSkyCell" maxlength="25" size="16" value="<?=$PostageStamp->gettextSkyCell()?>"/>
            Tessellation ID:
            <input type="text" id="textTessellation" name="textTessellationID" maxlength="25" size="16" value="<?=$PostageStamp->gettextTessellationID()?>"/> 
        </td>
    </tr>
    <tr id="idSkyCellOptionalDiv">
        <td align="left">
            Skycell ID<?=$PostageStamp->getstrOptional()?>: &nbsp;
            <input type="text" id="textSkyCellOptional" name="textSkyCellOptional" maxlength="25" size="16" value="<?=$PostageStamp->gettextSkyCellOptional()?>"/>
        </td>
    </tr>
    <tr id="idOtaDiv">
        <td align="left">
            OTA ID<?=$PostageStamp->getstrOptional()?>: &nbsp;
            <input type="text" id="textOta" name="textOta" maxlength="25" size="16" value="<?=$PostageStamp->getTextOta()?>"/>
        </td>
    </tr>
    <tr id="idDiffDetDiv">
        <td align="left">
            Difference Detection: 
            <input type="text" id="textDiffDet" name="textDiffDet" maxlength="25" size="16" value="<?=$PostageStamp->getTextDiffDet()?>"/>
        </td>        
    </tr>

    <tr id="idCoordCenterDiv">
        <td align="left">
            <hr style='color: #eeeeff;'/>
            <strong>Center Coordinates of Image</strong> - Input Coordinates from: <span style='font-size: 12px; color: red;'>(Ra and Dec in decimal degrees, e.g., 180.2 and 60.5)</span>
            &nbsp;&nbsp;
            <span id='actionNote_old' style='font-size: 12px; color: green;'></span>
        </td>
    </tr>
    <tr>
        <td align="left" id="idInputCoordFrom">
            <input type="radio" id="idRadioCoordSrcForm" name="radioCoordSrc" value="form" checked onclick='javascript: changeCoordSrc(this.value, this);'> Keyboard Entry Form
            &nbsp;<input type="radio" id="idRadioCoordSrcUpload" name="radioCoordSrc" value="upload" onclick='javascript: changeCoordSrc(this.value, this);' /> Upload File 
            &nbsp;<input type="radio" id="idRadioCoordSrcDb" name="radioCoordSrc" value="db" onclick='javascript: changeCoordSrc(this.value, this);' /> MyDB

            <span id="idDivMyDBTableDiv" style='inline'>
            Table: <?=$PSIHTMLGoodies->showFormSelect( 'selectMyDBTable', 'idSelectMyDBTable', $MyDB->getTableList(),
                                              $PostageStamp->getSelectMyDBTable(), '-- Select Table --' );?>

            <select name='selectMyDBRows' id='selectMyDBRows'>
            <option value="1">Top 1 row</option>
            <option value="5">Top 5 rows</option>
            <option value="10">Top 10 rows</option>
            <option value="100">Top 100 rows</option>
            <option value="1000">Top 1000 rows</option>
            <option value="5000">Top 5000 rows</option>
            <option value="10000">Top 10000 rows</option>
            </select>

            </span>
        </td>
    </tr>

    <tr id="idCoordRowsDiv">
      <td align="left">
            <input type="hidden" value="<?=$PostageStamp->getCoordCount()?>" id="idCoordCount" name="CoordCount" />
            <input type="button" name="btnAddCoordRow" id="idAddCoordRow" value="Add New Row" onclick="javascript: addCoordRows();" />
            <input type="button" name="btnCopyCoordRow" id="idCopyCoordRow" value="Copy Last Row" onclick="javascript: copyCoordRow(); " />
            &nbsp; <span style='font-size: 12px; color: green;'>Total rows: <span id='idCoordCountLabel'>1</span></span>

            &nbsp; &nbsp;
            <input type="hidden" name="useMjdDate" id="idUseMjdDate" value="<?=$PostageStamp->getuseMjdDate()?>" />
            <input type="button" name="btnToggleMjdDate" id="idToggleMjdDate" value="Show Optional Date Range" onclick="javascript: toggleMjdDate();" />
            &nbsp;
            <span id="idDateFormat" style="font-size: 12px; display: inline-block; background: #eeeeee;">(Allowed date formats: YYYY-MM-DD or MJD)</span>
     </td>
   </tr>
   <tr id="idCoordRowsDiv2">
     <td align="left">
        <div id="idCoordRows">
<?php
    $PostageStamp->addCoordRows();
?>
        </div>

      </td>
    </tr>

    <tr id="idCoordUploadDiv" style='display: none;'>
        <td align="left">

          <span title="Upload Ra/Dec File in *.txt format">
              <span style=""> Select Ra/Dec File To Upload (delimited by space, comma or tab):</span>
              <input type="file" id="fileUpload" name="fileUpload" size="25" onchange="javascript: return onFileSelect(this);"/>
              <br />
              <span style="font-size: 12px; display: inline-block; width:100%; background: #eeeeee; "><strong>Format: Ra, Dec, [Filter, [Start Date, End Date]]</strong> 
                  &nbsp; &nbsp; (field delimiter can be space, tab, comma or vertical bar '|')
                  <br/>where Ra, Dec is in degrees, Filter is (lower case) one of g, r, i, z, y, w, a, where a will return
                  all available filters, Date is in MJD format.</span>
          </span>

          <br />
          <textarea style='width: 100%; height: 150px;' id='textAreaUpload' name='textAreaUpload' READONLY><?=$PostageStamp->getuploadContent();?></textarea>

        </td>
    </tr>

    <tr id="idCoordDbDiv" style='display: none;'>
        <td align="left">

          <span id="idDivMyDBTableColsDiv">
          Ra: <?=$PSIHTMLGoodies->showFormSelect( 'selectMyDBRa', 'idSelectMyDBRa', $myDBColumnList,
                                                     $PostageStamp->getSelectMyDBRa(), ' -- Select --' );?>
          Dec: <?=$PSIHTMLGoodies->showFormSelect( 'selectMyDBDec', 'idSelectMyDBDec', $myDBColumnList,
                                                     $PostageStamp->getSelectMyDBDec(), '-- Select --' ); ?>
          Filter: <?=$PSIHTMLGoodies->showFormSelect( 'selectMyDBFilter', 'idSelectMyDBFilter', $myDBColumnList,
                                                     $PostageStamp->getSelectMyDBFilter(), '-- Select --' ); ?>
          Start Date: <?=$PSIHTMLGoodies->showFormSelect( 'selectMyDBStartDate', 'idSelectMyDBStartDate', $myDBColumnList,
                                                     $PostageStamp->getSelectMyDBStartDate(), '-- Select --' ); ?>
          End Date: <?=$PSIHTMLGoodies->showFormSelect( 'selectMyDBEndDate', 'idSelectMyDBEndDate', $myDBColumnList,
                                                     $PostageStamp->getSelectMyDBEndDate(), '-- Select --' ); ?>
          &nbsp;

          <input type='button' id='btnLoadDB' name='btnLoadDB' value="Load Data" onclick='javascript: onLoadDB();'/-->
          <input type='hidden' id='btnDoLoadDB' name='btnDoLoadDB' value=""/>

          <br />
          <span style="font-size: 12px; display: inline-block; width:100%; background: #eeeeee; ">
          Filter must be one of g, r, i, z, y, w or a (for all). If they are integers, automatic conversion rule is: 
          1: g, 2: r, 3: i, 4: z, 5: y, 6: w, 0: a.
          </span>
          </span>

          <br />
          <textarea style='width: 100%; height: 150px;' id='textAreaDb' name='textAreaDb' READONLY><?=$PostageStamp->getdbContent();?></textarea>

        </td>
    </tr>

    <tr id="idOptionMask">
        <td align="left">
            <hr style='color: #eeeeff;'/>
            
            <strong>Data Products and Options</strong><?=$PostageStamp->getstrOptional()?>:
            <input type="hidden" id="textOptionMask" name="textOptionMask" maxlength="25" size="10" value="<?=$PostageStamp->gettextOptionMask()?>" />
            
            <input type='button' id='btnShowMoreOptions' name='btnShowMoreOptions' value='Show More Options' onclick='javascript: toggleMoreOpt(this);' />
            <input type='hidden' id='btnShowMoreOptionsVal' name='btnShowMoreOptionsVal' value='<?=$PostageStamp->getShowMoreOpt()?>' />

            <table border='0' width='850'>
            <tr>
            <td valign='top' width='175'>
            <!--Basic options: <br/>-->
            <input type="checkbox" id="option_SELECT_IMAGE" name="option_SELECT_IMAGE" value="Y"
                <?=$PostageStamp->doCheck( $PostageStamp->getOption_SELECT_IMAGE() )?>/> Image <br/>
            <input type="checkbox" id="option_SELECT_MASK" name="option_SELECT_MASK" value="Y"
                <?=$PostageStamp->doCheck( $PostageStamp->getOption_SELECT_MASK() )?>/> Mask <br/>
            <input type="checkbox" id="option_SELECT_VARIANCE" name="option_SELECT_VARIANCE" value="Y"
                <?=$PostageStamp->doCheck( $PostageStamp->getOption_SELECT_VARIANCE() )?>/> Variance <br/>
            <input type="checkbox" id="option_SELECT_JPEG" name="option_SELECT_JPEG" value="Y"
                <?=$PostageStamp->doCheck( $PostageStamp->getOption_SELECT_JPEG() )?>/> JPEG
            </td>
    
            <td valign='top' width='325'>
            <!--Image type-specific options: <br/>-->
            
            <span id='spanOptDiff'>
            <!-- for 'diff' -->
            Difference image type:  
            <select id="option_DIFF_TYPE" name="option_DIFF_TYPE">
                <option value="">-- Select --</option>
                <option value="warp_stack">Warp - Stack diff</option>
                <option value="warp_warp">Warp - Warp diff</option>
                <option value="stack_stack">Stack - Stack diff</option>
            </select> <br/>
            <input type="checkbox" id="option_SELECT_INVERSE" name="option_SELECT_INVERSE" value="Y"
                <?=$PostageStamp->doCheck( $PostageStamp->getOption_SELECT_INVERSE() )?>/> Inverse diff image <br/>
            </span>
            
            <span id='spanOptChipWarpDiff'>
            <!-- for 'chip, warp and diff' -->
            <input type="checkbox" id="option_ENABLE_REGENERATION" name="option_ENABLE_REGENERATION" value="Y"
                <?=$PostageStamp->doCheck( $PostageStamp->getOption_ENABLE_REGENERATION() )?>/> Regenerate images if necessary <br/>
            </span>
            
            &nbsp;
            </td>
            <td valign='top' width='350'>
            
            <span id='idMoreOpt' style='display: none;'>
            <input type="checkbox" id="option_SELECT_CMF" name="option_SELECT_CMF" value="Y" 
                <?=$PostageStamp->doCheck( $PostageStamp->getOption_SELECT_CMF() )?>/> Sources file (cmf) <br/>
            <input type="checkbox" id="option_SELECT_PSF" name="option_SELECT_PSF" value="Y" 
                <?=$PostageStamp->doCheck( $PostageStamp->getOption_SELECT_PSF() )?>/> PSF file <br/>
            <input type="checkbox" id="option_SELECT_BACKMDL" name="option_SELECT_BACKMDL" value="Y"
                <?=$PostageStamp->doCheck( $PostageStamp->getOption_SELECT_BACKMDL() )?>/> Background model file <br/>
            <input type="checkbox" id="option_SELECT_UNCOMPRESSED" name="option_SELECT_UNCOMPRESSED" value="Y"
                <?=$PostageStamp->doCheck( $PostageStamp->getOption_SELECT_UNCOMPRESSED() )?>/> Make uncompressed images <br/>

            <span id='spanOptStack'>
            <!-- for 'stack' -->
            <input type="checkbox" id="option_SELECT_CONVOLVED" name="option_SELECT_CONVOLVED" value="Y" 
                <?=$PostageStamp->doCheck( $PostageStamp->getOption_SELECT_CONVOLVED() )?>/> Convolved stack 
            </span>

            <span id='spanOptNotDiff'>
            <input type="checkbox" id="option_RESTORE_BACKGROUND" name="option_RESTORE_BACKGROUND" value="Y" 
                <?=$PostageStamp->doCheck( $PostageStamp->getOption_RESTORE_BACKGROUND() )?>/> Restore background model
            </span>

            &nbsp;fwhm_min: <input type='text' id='option_FWHM_MIN' name='option_FWHM_MIN' maxlength='3' size='3' value="<?=$PostageStamp->getOption_FWHM_MIN()?>">
            fwhm_max: <input type='text' id='option_FWHM_MAX' name='option_FWHM_MAX' maxlength='3' size='3' value="<?=$PostageStamp->getOption_FWHM_MAX()?>">


            </td>
            </tr>
            </table>

            </span>

            <hr style='color: #eeeeff;'/>
        </td>
    </tr>
   <tr>
        <td align="left">
            Request Name <?=$PostageStamp->getstrOptional()?>: &nbsp;
            <input type="text" id="textName" name="textName" maxlength="25" size="16" value="<?=$PostageStamp->getTextName()?>"/>
            <hr style='color: #eeeeff;'/>
        </td>
   </tr>

    <tr>
        <td align="center">

            <input type="hidden" value="" id="btnDoSubmit" name="btnDoSubmit">
<?php if ($PostageStamp->getdebug_ui() == 1) { ?>
            <input type='checkbox' id='PSTAMP_TEST_MODE' name='PSTAMP_TEST_MODE' value='Y' <?= ( isset($_REQUEST['PSTAMP_TEST_MODE']) ? 'checked' : '' ) ?>> PSTAMP_TEST_MODE &nbsp;&nbsp;&nbsp;
            <input type="button" value="Preview Request" id="submit-button" name="submitPreview" onclick="javascript: doSubmit('preview');">
            <input type="button" value="Submit Request" id="submit-req-button" name="submitRequest" onclick="javascript: doSubmit('run');">
            <input type='checkbox' name='showDebugOutput' value='Y' <?= ( isset($_REQUEST['showDebugOutput']) ? 'checked' : '' ) ?>>Show debug output
<?php } else { ?>
            <input type="button" value="Submit Request" id="submit-req-button" name="submitRequest" onclick="javascript: doSubmit('run');">
<?php } ?>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="submit" value="Clear Query" name="resetUser" onclick="javascript: return doReset();"/>

<p><span id='actionNote' style='font-size: 12px; color: green;'></span></p>
        </td>
    </tr>
</table>
</form>


<!-- output table -->
<br/>
<?php if ($previewGraph) { ?>
<table style="margin: 0 auto;" border="0" BORDERCOLOR="green" cellpadding="3" cellspacing="0" width="1000">
<tr>
<td align="left" style='font-size: 12px; background-color:#eeeeee;'>
<?php } ?>

<?php
if ($previewGraph) {
    //$PostageStamp->previewGraph();
    print $msg;
    if (strpos($msg, "RESULT: SUCCESS") > 0) {
        print "<script type=\"text/javascript\">$('#actionNote').html(\"Request submission succeeded. <a href='postage_stamp_results.php' target='_ps_req_list'>Open PS Results page in new window to see result</a>\");</script>";
    }
    else if ($PostageStamp->action == "preview") {
        print "<script type=\"text/javascript\">$('#actionNote').html(\"<font color='red'>Preview mode. Request is not sent, but shown here.</font>\");</script>";
    }
    else {
        print "<script type=\"text/javascript\">$('#actionNote').html(\"<font color='red'>Something has gone wrong with your request parameters</font>\");</script>";
    }
} 
else if ($upload) {
    if ($PostageStamp->getuploadErrStr() != '') {
        $msg = $PostageStamp->getUploadErrStr();
    } else {
        $msg = $PostageStamp->getCoordCount_upload() . " rows uploaded.";
    }
    print "<script type='text/javascript'>document.getElementById('actionNote').innerHTML='$msg';</script>";
}
else if ($loadDB) {
    //print $PostageStamp->getdbContent();
    if ($PostageStamp->getuploadErrStr() != '') {
        $msg = $PostageStamp->getUploadErrStr();
    } else {
        $s = ($PostageStamp->getCoordCount_db() > 1) ? "s" : "";
        $msg = $PostageStamp->getCoordCount_db() . " row$s loaded.";
    }
    print "<script type='text/javascript'>document.getElementById('actionNote').innerHTML='$msg';</script>";
}

//$a = serialize( $PostageStamp ); print $a;

?>

<?php if ($previewGraph) { ?>
</td></tr></table>
<?php } ?>

</div>
<!-- End Content -->
</div>

<!-- End Main -->
<?php require ("bottom.php"); ?>
</body>
</html>
