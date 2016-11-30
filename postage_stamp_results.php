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
//define('TITLE','PSI Postage Stamp Request List');
define('TITLE', 'PS Request List');

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

/*
// Assign the Query Handle objects
if ( isset( $_SESSION["PostageStampClass"] ) ) {
  $PostageStamp = unserialize( $_SESSION["PostageStampClass"] );
}
else {
  $PostageStamp= new PostageStampClass( $PSISession ); // Make new PSI Session Class
//}

//ALWAYS save the PostageStampClass Object
$_SESSION['PostageStampClass'] = serialize( $PostageStamp );
}
*/

$PostageStamp = new PostageStampClass( $PSISession );

$timeStart = isset( $_REQUEST['timeStart'] ) ? trim($_REQUEST['timeStart']) : "";
$timeEnd   = isset( $_REQUEST['timeEnd'] )   ? trim($_REQUEST['timeEnd']) : "";

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
    <script type="text/javascript" src="javascript/jquery-ui-1.8.2.custom.min.js"></script>
    <script type="text/javascript" src="javascript/psi_jquery.js"></script>
    <script type="text/javascript">
       jQuery(document).ready(function() {
           var req_id = '<?= isset($_REQUEST['req_id']) ? $_REQUEST['req_id'] : "" ?>';
           if (req_id != '') { showJobList(req_id); }

           var job_id = '<?= isset($_REQUEST['job_id']) ? $_REQUEST['job_id'] : "" ?>';
           if (job_id != '') { showFileList(job_id); }

           var file_url = '<?= isset($_REQUEST['file_url']) ? $_REQUEST['file_url'] : "" ?>';
           if (file_url != '') { showFile(file_url); }
       });

       //used for JQuery popup calendar
       //used for JQuery popup calendar
        $(function() {
            $("#timeStart").datepicker( {dateFormat: 'yy-mm-dd'});
        });
        //used for JQuery popup calendar
        $(function() {
            $("#timeEnd").datepicker( {dateFormat: 'yy-mm-dd'});
        });

        jQuery(document).ready(function() {
            $("#checkAllReq").click(function()  {
                $('#PSForm1').find(':checkbox').attr('checked', this.checked);

                var v = $(":checkbox:checked").length;
                if (this.checked) v -= 1;
                $("#deleteCount").val( v );
                //alert($(":checkbox:checked").length);
                toggleActionTitle();
            });
        });

function toggleActionTitle() {
    if ($("#deleteCount").val() == 0) {
         $("#ReqActionTitle").html("Action");
    } else {
         $("#ReqActionTitle").html("<input type='button' id='btnDeleteAll' onclick='javascript: doDeleteReq();' title='Delete all selected rows' value='Delete'>");
    }
}

function doDeleteReq() {
    // Alert user.
    var s = "";
    var id;
    var ct = 0;
    $("input:checkbox[name=cbDelete]:checked").each(function() {
        id = $(this).val();
        s += (s == "") ? id : (", " + id); 
        ++ ct;
    }); 

    var r;
    if (ct == 1) {
        r = confirm("Are you sure to delete request: " + s + "?");
    } else {
        r = confirm("Are you sure to delete these " + ct +  " requests: " + s + "?");
    }

    if (! r) return;

    // Don't allow click on Delete All button twice.
    $("#btnDeleteAll").attr("disabled", "disabled");
    $("#checkAllReq").attr("disabled", "disabled");

    // Now, go ahead with deleting.
    $("input:checkbox[name=cbDelete]:checked").each(function() {
       id = $(this).val();
       $("#req_" + id).html("<blink><font color='red'>wait ...</font></blink>");
       //alert(ct + ". id = " + id);
       s = "";
       $.post("ps.php?action=delete&req_id=" + id, function(data) {
           s += $.trim(data) + "\n"; 
           -- ct;

           //alert('Result: ' + data);
           //document.forms[0].submit();
           if (ct == 0) {
               alert('Result: \n' + s);
               document.forms[0].submit();
           }
       });
    });
}


function changeDeleteCount(v) {
    var v0 = parseInt( $("#deleteCount").val() );
    v0 +=  v ? 1 : -1;

    $("#deleteCount").val( v0 );
    //alert( $("#deleteCount").val() );
    toggleActionTitle();
}

function validate() {
    var d1 = document.getElementById('timeStart').value.trim();
    var d2 = document.getElementById('timeEnd').value.trim();

    return (d1 != '' || d2 != '');
}

function doSubmit() {
    document.getElementById('btnSubmit').value = 'Y';
    document.forms[0].submit();
    return false;
}

function showJobList(id) {
    $("#req_id").val( id );
    $("#div2").html("<blink>Please wait...</blink>");
    $.post("ps.php?req_id=" + id, function(data) { $("#div2").html(data);  });
}

function showFileList(id) {
    $("#job_id").val( id );
    $("#div3").html("<blink>Please wait...</blink>");
    $.post("ps.php?job_id=" + id, function(data) { $("#div3").html(data);  });
}

function showFile(url) {
    //alert('hi');
    //alert($("#divR").width() + ',' + $("#divR").height());

    if (url == "") {
        $("#file_url").val( "" );
        $("#divR").html("");
        return;
    }

    $("#file_url").val( url );
    var w = $("#divR").width();
    var h = $("#divR").height();
    var size = ((w > h) ? h : w);

    $("#divR").html("<blink>Please wait...</blink>");
    $.post("ps.php?url=" + url + "&size=" + size, function(data) { $("#divR").html(data);  });
}

function setReqState(id, action) {
   var r = confirm("Are you sure to " + action + " request " + id + "?");
   if (r) {
       $("#req_" + id).html("<blink><font color='red'>wait ...</font></blink>");
       $.post("ps.php?action=" + action + "&req_id=" + id, function(data) {   
           alert('Result: ' + data);
           //location.reload(); // This does not include parameters.
           document.forms[0].submit();
       });
   }
}

function setJobState(id, action) {
   var r = confirm("Are you sure to " + action + " job " + id + "?");
   if (r) {
       $("#job_" + id).html("<blink><font color='red'>wait ...</font></blink>");
       $.post("ps.php?action=" + action + "&job_id=" + id, function(data) {
           alert('Result: ' + data);
           document.forms[0].submit();
       });
   }
}

    </script>

    <!--[if ! IE]>--> <script type="text/javascript" src="javascript/ps.js"></script> <!--<![endif]-->
    <!--[if IE]> <script type="text/javascript" src="javascript/ps_ie.js"></script> <![endif]-->

    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link href="css/jquery-ui-1.8.2.custom.css" rel="stylesheet" type="text/css" media="screen" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
    <?php require_once("menubar_header.php"); ?>
</head>
<body>
<?php require_once("top.php"); ?>
<?php require_once("menubar.php"); ?>
<div id="main">
<div style="text-align: center;">
<h2><?=$PSIHelp->getWikiURL('PSI-PostageStampRequestList')?>&nbsp;<?=TITLE?></h2>
<form method="GET" action="<?= $_SERVER['PHP_SELF'] ?>">

<input type='hidden' name='IsPostBack' value='Y'/>

<?php
$_pg = '';
if ( isset($_REQUEST["pg"]) ) { $_pg = $_REQUEST['pg']; }
else if ( isset($_REQUEST["_pg"]) ) { $_pg = $_REQUEST['_pg']; }
?>

<input type='hidden' id='_pg' name='_pg' value='<?= $_pg ?>'/>
<input type='hidden' id='req_id' name='req_id' value=''/>
<input type='hidden' id='job_id' name='job_id' value=''/>
<input type='hidden' id='file_url' name='file_url' value=''/>

<?php
?>

<table style="margin: 0 auto;" border="0" cellpadding="3" cellspacing="3" width="1000">
          <tr>
            <td align="middle">

<?php if ($PostageStamp->getdebug_ui() == 1) { ?>
                <input type='checkbox' name='showDebugOutput' value='Y' <?= ( isset($_REQUEST['showDebugOutput']) ? 'checked' : '' ) ?>/>Show debug output <br/>
<?php } ?>

                Submitted After (yyyy-mm-dd):
                <input id="timeStart"
                       class="datetime"
                       type="text"
                       name="timeStart"
                       value="<?= $timeStart ?>" size="13"/>

                Submitted Before (yyyy-mm-dd):
                 <input id="timeEnd"
                        class="datetime"
                        type="text"
                        name="timeEnd"
                        value="<?= $timeEnd ?>" size="13"/>

            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

            <input type="hidden" value="" id="btnSubmit" name="btnSubmit" />
            <input type="button" value="Refresh" id="submit-button" name="submit-button" onclick="javascript: doSubmit();" />
            <!--input type="submit" value="Clear Query" name="resetUser" /-->
        </td>
    </tr>
</table>
</form>


<!-- output table -->
<br/>

</div>
<!-- End Content -->
</div>

<!--Container.start-->

<div id="container" style="position: relative; left: 10px; border: solid; border-color: #cccccc; border-width: 1px;">

<div id="leftpane" style="width:300px; float: left;">

<div id="div1" style="width:300px;height:350px;overflow:auto; padding: 5px; border-color: #cccccc; border-width: 1px;">

<b>Request List</b><br/>

<center>

<form method="GET" action="<?= $_SERVER['PHP_SELF'] ?>" id="PSForm1">
<?php
print $PostageStamp->getReqListForUser( $timeStart, $timeEnd );
?>

<input type='hidden' id='deleteCount' name='deleteCount' value="0">
</form>

</center>

</div>

<!--/td><td-->

<div id="dragbar1" style="background-color:#eeeeee;  width: 300px; height: 10px; cursor: row-resize; 
background-image: url(images/handle-h.png); background-repeat: no-repeat; background-position: center; ">
</div>

<div id="div2" style="width:300px;height:350px; text-align:center; overflow:auto; padding: 5px; border-color: #cccccc; border-width: 1px;">

<b>Job List</b><br/>

</div>

<div id="dragbar2" style="background-color:#eeeeee;  width: 300px; height: 10px; cursor: row-resize; 
background-image: url(images/handle-h.png); background-repeat: no-repeat; background-position: center; ">
</div>

<div id="div3" style="width:300px;height:350px; text-align:center; overflow:auto; padding: 5px; border-color: #cccccc; border-width: 1px;">

<b>File List</b><br/>

</div>

</div> <!--end of left page-->

<div id="dragbarV" style="background-color:#eeeeee;  width: 10px; height: 700px; cursor: col-resize; 
background-image: url(images/handle-v.png); background-repeat: no-repeat; background-position: center; float: left; ">
</div>

<div id="divR" style="border: none; width: 800px; height: 700px; float: left; padding: 5px; border-color: #cccccc; border-width: 1px;">
<b>Image File</b><br/>
</div>

</div> <!--end of container-->

<!--Container.end-->


<!-- End Main -->
<?php require ("bottom.php"); ?>
</body>
</html>
