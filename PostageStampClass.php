<?php
/**
 * @class PostageStampClass
 * This Query Handle class stores all the needed information such as schema,
 * in order to maintain queries for the user and interacts with the DRL.
 *
 * @version Beta
 * GPL version 3 or any later version.
 * copyleft 2013 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Thomas Chen, Conrad Holmberg
 * @since Beta version 2013
 */
require_once("PagingClass.php");
require_once ("EncryptClass.php");

class PostageStampClass
{
    // The following variables are accessed by reflection (PHP "overloading");

    private $debug_ui;
    private $DEBUG;
    private $PSTAMP_TEST_MODE;

    private $row_label;
    private $row_label_len;

    //! Variables here for schemas
    private $PSISession;
    private $psSOAPClient; // SOAP Client Object used to make SOAP calls to the DRL Jobs Service.

    public $action; //! Variable used with constants for what to do (i.e., query, download, etc)

    //! Form varaibles
    private $selectSurveyID;
    private $selectReleaseName;
    private $selectImageType;
    private $selectImageSource;

    private $radioCoordRangeType;

    private $idRadioCoordRangeEntireFilter;

    private $coordSrc;

    private $coordFieldsCount;
    private $coordCount;
    private $coordRas = array();
    private $coordDecs = array();
    private $coordUnits = array();
    private $coordFilters = array();
    private $coordStartMJDs = array();
    private $coordEndMJDs = array();

    // For coordinates search
    private $textCoordCenterRa;
    private $textCoordCenterDec;
    private $selectCoordUnit;

    //private $checkCoordFilterIDs = array();
    private $checkCoordFilterIDList;
    private $textMjdMin;
    private $textMjdMax;

    private $useMjdDate;

    private $textCoordRangeRa;
    private $textCoordRangeDec;
    private $textCoordPixelWidth;
    private $textCoordPixelHeight;

    private $textExposureID;
    private $textImageSource;

    private $textSkyCell;
    private $textTessellationID;

    private $textOta;

    private $textSkyCellOptional;

    private $textDiffDet;
    private $textOptionMask;
    private $textName;
    private $strOptional;

    private $coordCount_upload;
    private $uploadContent;
    private $uploadErrStr;

    private $coordCount_db;
    private $dbContent;
    private $selectMyDBTable;
    private $selectMyDBRows;
    private $selectMyDBRa;
    private $selectMyDBDec;
    private $selectMyDBFilter;
    private $selectMyDBStartDate;
    private $selectMyDBEndDate;

    private $uploadInfoStr;

    // options.
    private $option_SELECT_IMAGE;
    private $option_SELECT_MASK;
    private $option_SELECT_VARIANCE;
    private $option_SELECT_JPEG;

    private $option_DIFF_TYPE;
    private $option_SELECT_INVERSE;
    private $option_ENABLE_REGENERATION;

    private $option_SELECT_CMF;
    private $option_SELECT_PSF;
    private $option_SELECT_BACKMDL;
    private $option_SELECT_UNCOMPRESSED;
    private $option_SELECT_CONVOLVED;
    private $option_RESTORE_BACKGROUND;
    private $option_USE_IMFILE_ID; // not implemented on GUI for now.

    private $option_FWHM_MIN;
    private $option_FWHM_MAX;

    private $showMoreOpt;

    //! Constants for the type of things a user can do with MyDB
    const ACTION_DO_NOTHING = 0; //! Default assign action
    const ACTION_PROVIDE_PREVIEW = 1; //! Action for preivew of a postage stamp request.
    const ACTION_SUBMIT_POSTAGE_STAMP = 2;

    /**
    * Default constructor for QueryHandleClass
    *
    * @param  $PSISession - Environment variables
    *
    */
    public function __construct( $PSISession )
    {
        if ( !empty($PSISession) ) {
            $this->PSISession = $PSISession;
            try {
              $this->psSOAPClient = new SoapClient ( $this->PSISession->getPostageStampService() );
            }
            catch (SoapFault $soapFault) {
              $this->PSISession->showUserServerError();
              die ("SOAP Fault: (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
            }
        }
        else {
            error_log("Cannot constuct PostageStampClass class instance.  PSISession object is null.");
            exit();
        }

        self::initDefaults();
    } // function __construct


    /**
    * Overloaded function used for getters and setters on variables
    *
    * @param method The method name that is parsed out ( i.e., getTables, setSurvey )
    * @param arguments The arguments variables for the get/set call.
    * @return nothing
    */
    function __call($method, $arguments) {
        // Switch to lowercase and get rid of get/set prefix
        $prefix = strtolower(substr($method, 0, 3));
        //Get the property name by getting rid of the get/set and making the first character lowercase
        $property =  substr($method, 3);
        $property{0} = strtolower($property{0});

        // Case property is not present
        if ( !property_exists($this, $property) ) {
	    $error = "Unkown method call in PostageStampClass: $method.  ".
                  "Make sure it is matches the properties within the class.";
	    error_log( $error );
            die ($error );

        }
        // Case no call match return nothing
        if (empty($prefix) || empty($property)) {
            return;
        }

        // Simply return value of global
        if ($prefix == "get" && isset($this->$property)) {
          return $this->$property;
        }

        // Assign argument value to global
        if ($prefix == "set") {
          $this->$property = $arguments[0];
        }
    } // function __call

    /**
    * Assigns all default needed variables
    * @return nothing
    */
    private function initDefaults() {
        $this->debug_ui = 0; // if 0, no "Preview" and "Show debug output"
        $this->DEBUG = 0;
        $this->PSTAMP_TEST_MODE = 0;

        $this->row_label = "RowNum";
        $this->row_label_len = 10;

        $this->action = '';

        // Immediately assign constructor arguments and default vaules
        $this->selectSurveyID = '3PI';
        $this->selectReleaseName = '3PI.PV3'; //' '; // default value.
        $this->selectImageType = 'stack';
        $this->selectImageSource =   'coord';

        $this->textExposureID =  '';
        $this->textImageSource =  '';
        $this->textSkyCell =   '';
        $this->textTessellationID =   '';
        $this->textOta = '';

        // For coordinates search
        $this->radioCoordRangeType = 'pixel';

        $idRadioCoordRangeEntireFilter = '';

        $this->coordSrc = 'form';

        $this->coordCount = '1';
        $this->coordRas = array();
        $this->coordDecs = array();
        $this->coordUnits = array();
        $this->coordFilters = array();
        $this->coordStartMJDs = array();
        $this->coordEndMJDs = array();
        {
            $this->coordRas[] = '';
            $this->coordDecs[] = '';
            $this->coordUnits[] = '';
            $this->coordFilters[] = '';
            $this->coordStartMJDs[] = '';
            $this->coordEndMJDs[] = '';
        }

        $this->textCoordCenterRa = '';
        $this->textCoordCenterDec = '';
        $this->selectCoordUnit = '';

        //$this->checkCoordFilterIDs = array();
        $this->checkCoordFilterIDList = '';
        $this->textMdjMin = '';
        $this->textMdjMax = '';

        $this->useMjdDate = '0';

        $this->textCoordRangeRa = '';
        $this->textCoordRangeDec = '';
        $this->textCoordPixelWidth = '';
        $this->textCoordPixelHeight = '';

        $this->textExposureID = '';
        $this->textImageSource = '';
        $this->textSkyCell = '';
        $this->textTessellationID = '';
        $this->textSkyCellOptional = '';
        $this->textDiffDet = '';
        $this->textOptionMask = '';
        $this->textName = '';
        $this->strOptional = " <span style='font-size: 12px; color: #999999;'>(optional)</span>";

        $this->coordCount_upload = 0;
        $this->uploadContent = '';
        $this->uploadErrStr = '';

        $this->dbContent = '';
        $this->selectMyDBTable = '';
        $this->selectMyDBRows = '1';
        $this->selectMyDBRa = '';
        $this->selectMyDBDec = '';
        $this->selectMyDBStartDate = '';
        $this->selectMyDBEndDate = '';

        // options.
        $this->option_SELECT_IMAGE= '1';
        $this->option_SELECT_MASK= '0';
        $this->option_SELECT_VARIANCE= '0';
        $this->option_SELECT_JPEG= '1';

        $this->option_DIFF_TYPE= '';
        $this->option_SELECT_INVERSE= '0';
        $this->option_ENABLE_REGENERATION= '0';

        $this->option_SELECT_CMF= '0';
        $this->option_SELECT_PSF= '0';
        $this->option_SELECT_BACKMDL= '0';
        $this->option_SELECT_UNCOMPRESSED= '0';
        $this->option_SELECT_CONVOLVED= '0';
        $this->option_RESTORE_BACKGROUND= '0';
        $this->option_USE_IMFILE_ID = '0';

        $this->option_FWHM_MIN = '';
        $this->option_FWHM_MAX = '';

        $this->showMoreOpt = 0;
        
    } // function initDefaults

   

    /**
      * Assigns variables needed for the query page.
      *
      * @param none
      * @return none
      */
      public function initFormVariables() {
        if ( isset( $_REQUEST['debug'] ) )
          $this->DEBUG = $_REQUEST['debug'];
        if ( isset( $_REQUEST['PSTAMP_TEST_MODE'] ) )
          $this->PSTAMP_TEST_MODE = 1;
        if ( isset( $_REQUEST['selectSurveyID']) )
          $this->selectSurveyID = $_REQUEST['selectSurveyID'];
        if ( isset( $_REQUEST['selectReleaseName']) )
          $this->selectReleaseName = $_REQUEST['selectReleaseName'];
        if ( isset( $_REQUEST['selectImageType']) )
          $this->selectImageType = $_REQUEST['selectImageType'];
        if ( isset( $_REQUEST['selectImageSource']) ) 
          $this->selectImageSource = $_REQUEST['selectImageSource'];
        if ( isset( $_REQUEST['textExposureID']) )
          $this->textExposureID = $_REQUEST['textExposureID'];
        if ( isset( $_REQUEST['textSkyCell']) )
          $this->textSkyCell = $_REQUEST['textSkyCell'];
        if ( isset( $_REQUEST['textTessellationID']) )
          $this->textTessellationID = $_REQUEST['textTessellationID'];
        if ( isset( $_REQUEST['radioCoordRangeType']) )
          $this->radioCoordRangeType = $_REQUEST['radioCoordRangeType'];
        //if ( isset( $_REQUEST['checkCoordFilterIDs']) )
        //  $this->checkCoordFilterIDs = $_REQUEST['checkCoordFilterIDs'];
        //else 
        //  $this->checkCoordFilterIDs = array();

        if ( isset( $_REQUEST['idRadioCoordRangeEntireFilter']) ) 
            $this->idRadioCoordRangeEntireFilter = $_REQUEST['idRadioCoordRangeEntireFilter'];

        if ( isset( $_REQUEST['radioCoordSrc'] ) ) 
            $this->coordSrc = $_REQUEST['radioCoordSrc'];
        
        if ( isset( $_REQUEST['CoordCount'] ) ) {
          $this->coordCount = $_REQUEST['CoordCount'];
        
          $this->coordRas = array(); 
          $this->coordDecs = array();
          $this->coordUnits = array();
          $this->coordFilters = array();
          $this->coordStartMJDs = array();
          $this->coordEndMJDs = array();

          $actualCount = 0;
          for ($i = 1; $i <= $this->coordCount; ++ $i) {
            if ( ! isset($_REQUEST["textCoordCenterRa_$i"]) ) { // to handle deleted rows.
              continue;
            }
            ++ $actualCount;

            $this->coordRas[] = $this->getHttpVal($_REQUEST["textCoordCenterRa_$i"]);
            $this->coordDecs[] = $this->getHttpVal($_REQUEST["textCoordCenterDec_$i"]);
            $this->coordUnits[] = $this->getHttpVal($_REQUEST["selectCoordUnit_$i"]);
            $this->coordFilters[] = $this->getHttpVal($_REQUEST["selectFilter_$i"]);
            $this->coordStartMJDs[] = $this->getHttpVal($_REQUEST["textMjdMin_$i"]);
            $this->coordEndMJDs[] = $this->getHttpVal($_REQUEST["textMjdMax_$i"]);
          }
          $this->coordCount = $actualCount;
        }

        $this->checkCoordFilterIDList = '';
        if ( isset( $_REQUEST['checkCoordFilterID_g'] ) )
          $this->checkCoordFilterIDList .= $_REQUEST['checkCoordFilterID_g'];
        if ( isset( $_REQUEST['checkCoordFilterID_r'] ) )
          $this->checkCoordFilterIDList .= $_REQUEST['checkCoordFilterID_r'];
        if ( isset( $_REQUEST['checkCoordFilterID_i'] ) )
          $this->checkCoordFilterIDList .= $_REQUEST['checkCoordFilterID_i'];
        if ( isset( $_REQUEST['checkCoordFilterID_z'] ) )
          $this->checkCoordFilterIDList .= $_REQUEST['checkCoordFilterID_z'];
        if ( isset( $_REQUEST['checkCoordFilterID_y'] ) )
          $this->checkCoordFilterIDList .= $_REQUEST['checkCoordFilterID_y'];

        if ( isset( $_REQUEST['textMdjMin']) )
          $this->textMdjMin = $_REQUEST['textMdjMin'];
        if ( isset( $_REQUEST['textMdjMax']) )
          $this->textMdjMax = $_REQUEST['textMdjMax'];

        if ( isset( $_REQUEST['useMjdDate'] ) )
          $this->useMjdDate = $_REQUEST['useMjdDate'];

        if ( isset( $_REQUEST['textCoordCenterRa']) )
          $this->textCoordCenterRa = $_REQUEST['textCoordCenterRa'];
        if ( isset( $_REQUEST['textCoordCenterDec']) )
          $this->textCoordCenterDec = $_REQUEST['textCoordCenterDec'];
        if ( isset( $_REQUEST['selectCoordUnit']) )
          $this->selectCoordUnit = $_REQUEST['selectCoordUnit'];

        if ( isset( $_REQUEST['textCoordRangeRa']) )
          $this->textCoordRangeRa = $_REQUEST['textCoordRangeRa'];
        if ( isset( $_REQUEST['textCoordRangeDec']) )
          $this->textCoordRangeDec = $_REQUEST['textCoordRangeDec'];

        if ( isset( $_REQUEST['textCoordPixelWidth']) ) {
          $this->textCoordPixelWidth = trim( $_REQUEST['textCoordPixelWidth'] );
          if ($this->textCoordPixelWidth == '') $this->textCoordPixelWidth = '0';
        }
        if ( isset( $_REQUEST['textCoordPixelHeight']) ) {
          $this->textCoordPixelHeight = trim( $_REQUEST['textCoordPixelHeight'] );
          if ($this->textCoordPixelHeight == '') $this->textCoordPixelHeight = '0';
        }

        if ( isset( $_REQUEST['textExposureID']) )
          $this->textExposureID = $_REQUEST['textExposureID'];
        if ( isset( $_REQUEST['textImageSource']) )
          $this->textImageSource = $_REQUEST['textImageSource'];
        if ( isset( $_REQUEST['textSkyCell']) )
          $this->textSkyCell = $this->getHttpVal( $_REQUEST['textSkyCell'], '' );
        if ( isset( $_REQUEST['textTessellationID']) )
          $this->textTessellationID = $this->getHttpVal( $_REQUEST['textTessellationID'], '' );

        if ( isset( $_REQUEST['textOta']) )
          $this->textOta = $this->getHttpVal( $_REQUEST['textOta'], '' );

        if ( isset( $_REQUEST['textSkyCellOptional']) ) 
          $this->textSkyCellOptional = $this->getHttpVal( $_REQUEST['textSkyCellOptional'], '' );

        if ( isset( $_REQUEST['textOptionMask']) )
          $this->textOptionMask = $this->getHttpVal( $_REQUEST['textOptionMask'], '0' );

        if ( isset( $_REQUEST['btnDoSubmit'] ) )
            $this->action = $_REQUEST['btnDoSubmit'];
        if ( isset( $_REQUEST['textName'] ) )
            $this->textName = $_REQUEST['textName'];
        $this->uploadErrStr = '';
        //if ( isset( $_REQUEST['btnDoSubmit'] ) && $_REQUEST['btnDoSubmit'] == 'upload' ) {
        if ( $this->action == 'upload' ) {
            $this->handleUpload();
        } else {
            if ( isset( $_REQUEST['textAreaUpload'] ) )
                $this->uploadContent = $this->getHttpVal( $_REQUEST['textAreaUpload'] );
        }
 
        if ( isset($_REQUEST['textAreaDb']) )
            $this->dbContent = $this->getHttpVal( $_REQUEST['textAreaDb'] );
        if ( isset( $_REQUEST['selectMyDBTable']) )
            $this->selectMyDBTable = $_REQUEST['selectMyDBTable'];
        if ( isset( $_REQUEST['selectMyDBRows']) )
            $this->selectMyDBRows = $_REQUEST['selectMyDBRows'];
        if ( isset( $_REQUEST['selectMyDBRa']) )
            $this->selectMyDBRa = $_REQUEST['selectMyDBRa'];
        if ( isset( $_REQUEST['selectMyDBDec']) )
            $this->selectMyDBDec = $_REQUEST['selectMyDBDec'];
        if ( isset( $_REQUEST['selectMyDBFilter']) )
            $this->selectMyDBFilter = $_REQUEST['selectMyDBFilter'];
        if ( isset( $_REQUEST['selectMyDBStartDate']) )
            $this->selectMyDBStartDate = $_REQUEST['selectMyDBStartDate'];
        if ( isset( $_REQUEST['selectMyDBEndDate']) )
            $this->selectMyDBEndDate = $_REQUEST['selectMyDBEndDate'];

        // options.
        $this->option_SELECT_IMAGE = isset( $_REQUEST['option_SELECT_IMAGE']) ? '1' : '0';
        $this->option_SELECT_MASK  = isset( $_REQUEST['option_SELECT_MASK'])  ? '1' : '0';
        $this->option_SELECT_VARIANCE = isset( $_REQUEST['option_SELECT_VARIANCE']) ? '1' : '0';
        $this->option_SELECT_JPEG = isset( $_REQUEST['option_SELECT_JPEG']) ? '1' : '0';

        $this->option_DIFF_TYPE = isset( $_REQUEST['option_DIFF_TYPE']) ? $_REQUEST['option_DIFF_TYPE'] : '';
        $this->option_SELECT_INVERSE = isset( $_REQUEST['option_SELECT_INVERSE']) ? '1' : '0';
        $this->option_ENABLE_REGENERATION = isset( $_REQUEST['option_ENABLE_REGENERATION']) ? '1' : '0';

        $this->option_SELECT_CMF = isset( $_REQUEST['option_SELECT_CMF']) ? '1' : '0';
        $this->option_SELECT_PSF = isset( $_REQUEST['option_SELECT_PSF']) ? '1' : '0';
        $this->option_SELECT_BACKMDL = isset( $_REQUEST['option_SELECT_BACKMDL']) ? '1' : '0';
        $this->option_SELECT_UNCOMPRESSED = isset( $_REQUEST['option_SELECT_UNCOMPRESSED']) ? '1' : '0';
        $this->option_SELECT_CONVOLVED = isset( $_REQUEST['option_SELECT_CONVOLVED']) ? '1' : '0';
        $this->option_RESTORE_BACKGROUND = isset( $_REQUEST['option_RESTORE_BACKGROUND']) ? '1' : '0';
        $this->option_USE_IMFILE_ID = isset( $_REQUEST['option_USE_IMFILE_ID'] ) ? '1' : '0';

        if ( isset( $_REQUEST['option_FWHM_MIN'] ) )
            $this->option_FWHM_MIN = ( $_REQUEST['option_FWHM_MIN'] );
        if ( isset( $_REQUEST['option_FWHM_MAX'] ) )
            $this->option_FWHM_MAX = ( $_REQUEST['option_FWHM_MAX'] );

        if ( isset($_REQUEST['btnShowMoreOptionsVal']) ) {
            $this->showMoreOpt = $_REQUEST['btnShowMoreOptionsVal'];
        }

      } //initQueryFormVariables

    /**
      * Parse the uploaded coordinate file. 
      *
      * regular expression:
      * float number: http://www.regular-expressions.info/floatingpoint.html
      * php reg: http://www.wellho.net/regex/php.html
      *
      * @param v A string which is the uploaded coordinate file.
      * @return formatted version of the uploaded coordinates.
      */
    private function parseCoordUpload( $v ) {
        $this->uploadErrStr = '';

        $rows = explode("\n", $v);
        $len = Count($rows);

        $row_label_len = $this->row_label_len; // length of the first column (for number of row).
        $u = '';
        $i = 0;
        $ct = 0;
        $fields_ct = -1;
        if ($this->startsWith(strtolower($rows[$i]), "ra")) ++ $i;
        if ($this->startsWith($rows[$i], "-----")) ++ $i;
 
        for (; $i < $len; ++ $i) {
            $r = trim($rows[$i]);
            if ($r == '' || $this->startsWith($r, "-")) continue;
            if (preg_match('/^([-+]?[0-9]*\.?[0-9]*)[ \t,|]+([-+]?[0-9]*\.?[0-9]*)[ \t,|]+([agrizyw])[ \t,|]+([0-9]{0,5}\.?[0-9]{0,5})[ \t,|]+([0-9]{0,5}\.?[0-9]{0,5})[ \t,|]*$/', $r, $matches)) {
                if ($fields_ct == -1) { $fields_ct = count($matches) - 1; }
                else if ($fields_ct != count($matches) - 1) {
                    $u .= $r . " <---- first error occurs on this row\n";
                    $this->uploadErrStr = "Rows don't have the same number of fields.";
                    break;
                }

                ++ $ct;
                $u .= $this->writeField( $ct, $row_label_len ) . 
                      $this->writeField( $this->handleNumDot($matches[1]) ) . 
                      $this->writeField( $this->handleNumDot($matches[2]) ) . 
                      $this->writeField($matches[3]) . 
                      $this->writeField( $this->handleNumDot($matches[4]) ) . 
                      $this->writeField( $this->handleNumDot($matches[5]) ) . "\n";
            }
            else if (preg_match('/^([-+]?[0-9]*\.?[0-9]*)[ \t,|]+([-+]?[0-9]*\.?[0-9]*)[ \t,|]+([agrizyw])[ \t,]*$/', $r, $matches)) {
                if ($fields_ct == -1) { $fields_ct = count($matches) - 1; }
                else if ($fields_ct != count($matches) - 1) {
                    $u .= $r . " <---- first error occurs on this row\n";
                    $this->uploadErrStr = "Rows don't have the same number of fields.";
                    break;
                }

                ++ $ct;
                $u .= $this->writeField( $ct, $row_label_len ) . 
                      $this->writeField( $this->handleNumDot($matches[1]) ) . 
                      $this->writeField( $this->handleNumDot($matches[2]) ) .
                      $this->writeField($matches[3]) . "\n";
            }
            else if (preg_match('/^([-+]?[0-9]*\.?[0-9]*)[ \t,|]+([-+]?[0-9]*\.?[0-9]*)[ \t,]*$/', $r, $matches)) {
                if ($fields_ct == -1) { $fields_ct = count($matches) - 1; }
                else if ($fields_ct != count($matches) - 1) {
                    $u .= $r . " <---- first error occurs on this row\n";
                    $this->uploadErrStr = "Rows don't have the same number of fields.";
                    break;
                }

                ++ $ct;
                $u .= $this->writeField( $ct, $row_label_len ) . 
                      $this->writeField( $this->handleNumDot($matches[1]) ) . 
                      $this->writeField( $this->handleNumDot($matches[2]) ) . "\n";
            }
            else {
                $u .= $r . " <---- first error occurs on this row\n";
                $this->uploadErrStr = "Row with unknown format. Should be: Ra, Dec, [Filter, [Start Date, End Date]]. <br/>Ra/Dec: in degree. Filter: a (all), g, r, i, z or y. Date: MJD format.";
                break;
            }
        }

        if ($fields_ct == 2) {
            $u = $this->writeField( $this->row_label, $row_label_len ) . 
                 $this->writeField("Ra") . $this->writeField("Dec") .
                 "\n" . str_repeat("-", 40 + $row_label_len) . "\n" . $u;
        } else if ($fields_ct == 3) {
            $u = $this->writeField( $this->row_label, $row_label_len ) .  
                 $this->writeField("Ra") . $this->writeField("Dec") . $this->writeField("Filter") .
                 "\n" . str_repeat("-", 60 + $row_label_len) . "\n" . $u;
        } else if ($fields_ct == 5) {
            $u = $this->writeField( $this->row_label, $row_label_len ) .  
                 $this->writeField("Ra") . $this->writeField("Dec") . $this->writeField("Filter") .
                 $this->writeField("Start_Date") . $this->writeField("End_Date") . 
                 "\n" . str_repeat("-", 100 + $row_label_len) . "\n" . $u;
        }

        $this->coordFieldsCount = $fields_ct;
        $this->coordCount_upload = $ct;
        //print $this->coordCount . ", " . $this->coordFieldsCount ;

        if ($ct == 0) {
            $this->uploadErrStr = "File to upload contains no valid data.";
            return $u = '';
        }

        return $u;
    }

    /**
      * Format number. If there is no digit before or after decimal point, add 0 as digit. 
      * E.g., 1) change .2343 -> 0.2343, 2) change 1. -> 1.0.
      *
      * @param n A decimal number.
      * @return The formatted decimal number.
      */
    private function handleNumDot($n) {
        if ($this->startsWith($n, ".")) $n = "0" . $n;
        if ($this->endsWith($n, ".")) $n = $n . "0";
        return $n;
    }

    /**
      * Write field with fixed width.
      *
      * @param v The field string.
      * @param max_len Maximal length of the string to display.
      * @return The formatted field string.
      */
    private function writeField( $v, $max_len = 20 ) {
        $len = strlen($v);
        if ($len >= $max_len) {
            return $v . " ";
        } else {
            return $v . str_repeat(" ", $max_len - $len);
        }
    }

    /**
      * When the condition is satisfied, use SkyCellID as an optional field.
      *
      * @param none
      * @return A boolean value on whether to use optional skycellID.
      */
    public function useOptionalSkyCellID() {
        $type = $this->selectImageType;
        $src  = $this->selectImageSource;
        return ( $type == 'warp' || $type == 'stack' || $type == 'diff' ) &&
               ( $src == 'coord' || $src == 'exposure' || $src == 'id' ); 
    }

    /**
      * When the condition is satisfied, optional OTA field.
      *
      * @param none
      * @return A boolean value on whether to use optional OTA ID.
      */
    public function useOptionalOTAID() {
       //return (!empty($this->textOta) && $this->selectImageType == 'chip');
       return $this->selectImageType == 'chip';
    }

    /**
      * Format a value: if isset, trim the string; if empty, use default value. 
      *
      * @param v The input value.
      * @param v_default The default value when input value is empty.
      * @return The formatted value of input.
      */
    private function getHttpVal($v, $v_default = '') {
        $r = '';
        if ( isset($v) ) {
            $r = trim( $v );
            if ($r == '') $r = $v_default;
        }
        return $r;
    }

    /**
      * Determines if it is ok to ignore Ra/Dec coordinates from input.
      * This should match javascript function ignoreRaDec() in postage_stamp.php.
      * 
      * @param $imageSrc Value of Image Source
      * @param coordRangeEntire Value of coordRangeEntire.
      * @return true if it is ok to ignore Ra/Dec coordinates.
      */
    private function ignoreRaDec($imageSrc, $coordRangeEntire) {
        if ($coordRangeEntire != 'entire') {
            $ignore = False;
        } else if ($imageSrc == 'coord') {
            $ignore = False;
        } else {
            $ignore = True;
        }
        return $ignore;
    }

    /**
      * Construct query string.
      *
      * @param none
      * @return The constructed query string.
      */
    private function getQueryStr() {
        $s = "";
        $s .= "SurveyID=" . $this->selectSurveyID;
        // Use trim() since empty space is possible as requested by Bill. 4/26/2013.
        $s .= "&ReleaseName=" . trim( $this->selectReleaseName ); 
        $s .= "&ImageType=" . $this->selectImageType;
        $s .= "&ImageSource=" . $this->selectImageSource;

        if ($this->coordSrc == 'form') { // always add this. let IPP decide to use or not.
            if ($this->ignoreRaDec($this->selectImageSource, $this->radioCoordRangeType)) {
                $s .= "&coordList=1,0,0,a,null,null";
                $s .= "&coordCount=1";
            } else {
                $s .= "&coordList=" . $this->getCoordList_form();
                $s .= "&coordCount=" . $this->coordCount;
            }
        } else if ($this->coordSrc == 'upload') {
            $s .= "&coordList=" . $this->getCoordList_upload( $this->uploadContent );
            $s .= "&coordCount=" . $this->coordCount_upload;
        } else if ($this->coordSrc == 'db') {
            $s .= "&coordList=" . $this->getCoordList_upload( $this->dbContent );
            $s .= "&coordCount=" . $this->coordCount_upload;
        } else {
            $s .= "&coordList=1,0,0,a,null,null";
            $s .= "&coordCount=1";
        }

/*
          if ($this->selectImageSource == "coord") {
            if ($this->coordSrc == 'form') {
              $s .= "&coordList=" . $this->getCoordList_form();
              $s .= "&coordCount=" . $this->coordCount;
            } else if ($this->coordSrc == 'upload') {
              $s .= "&coordList=" . $this->getCoordList_upload( $this->uploadContent );
              $s .= "&coordCount=" . $this->coordCount_upload;
            } else if ($this->coordSrc == 'db') {
              $s .= "&coordList=" . $this->getCoordList_upload( $this->dbContent );
              $s .= "&coordCount=" . $this->coordCount_upload;
            }
          }
          else {
            # add fake coordinates for entire image
            $s .= "&coordList=1,0,0,a,null,null";
            $s .= "&coordCount=1";
          }
*/

        //$s .= "&rangeType=" . $this->radioCoordRangeType;

        if ($this->radioCoordRangeType == "sky") {
            $s .= "&rangeType=sky";
            $s .= "&rangeRa=" . $this->textCoordRangeRa;
            $s .= "&rangeDec=" . $this->textCoordRangeDec;
        } else if ($this->radioCoordRangeType == "entire") {
            $s .= "&rangeType=pixel";
            $s .= "&pixelW=0";
            $s .= "&pixelH=0";
            $s .= "&entireFilter=" . $this->idRadioCoordRangeEntireFilter;
        } else if ($this->radioCoordRangeType == "pixel") {
            $s .= "&rangeType=pixel";
            $s .= "&pixelW=" . $this->textCoordPixelWidth;
            $s .= "&pixelH=" . $this->textCoordPixelHeight;
        } else {
            // should not happen
        }

        if ($this->selectImageSource == "exposure") {
            $s .= "&exposureID=" . $this->textExposureID;
        } else if ($this->selectImageSource == "id") {
            $s .= "&ImageSourceID=" . $this->textImageSource;
        } else if ($this->selectImageSource == "skycell") {
            $s .= "&skycellID=" . $this->textSkyCell;
            $s .= "&tessellationID=" . $this->textTessellationID;
        } else {
            // should not happen
        }

        if ( $this->useOptionalSkyCellID() ) {
            $s .= "&skycellID=" . $this->textSkyCellOptional;
        }
        if ( $this->useOptionalOTAID() ) {
           $s .= "&otaID=" . $this->textOta;
        }
        if ( !empty($this->textName) ) {
          $s .= "&name=" . $this->textName;
        }
        else {
          $s .= "&name=" . $this->PSISession->getUserID();
        }
        //$s .= "&optionMask=" . $this->textOptionMask;
        $s .= "&username=" . $this->getUserEmail();

        //if ($this->DEBUG == 1) $s .= "&useMjdDate=" . $this->useMjdDate;

        $s .= $this->getOptionMasks();

        $s .="&ACTION=" . $this->action;

        $s .="&PSTAMP_TEST_MODE=" . $this->PSTAMP_TEST_MODE;

        return $s;
    }

    /**
      * Construct a string for option masks.
      *
      * @param none
      * @return The constructed string for option masks.
      */
    private function getOptionMasks() {
        $s = '';

        $s .= '&PSTAMP_SELECT_IMAGE=' . $this->option_SELECT_IMAGE;
        $s .= '&PSTAMP_SELECT_MASK=' . $this->option_SELECT_MASK;
        $s .= '&PSTAMP_SELECT_VARIANCE=' . $this->option_SELECT_VARIANCE;
        $s .= '&PSTAMP_SELECT_JPEG=' . $this->option_SELECT_JPEG;

        $s .= '&PSTAMP_DIFF_TYPE=' . $this->option_DIFF_TYPE;
        $s .= '&PSTAMP_SELECT_INVERSE=' . $this->option_SELECT_INVERSE;
        $s .= '&PSTAMP_ENABLE_REGENERATION=' . $this->option_ENABLE_REGENERATION;

        if ($this->showMoreOpt == '0') { // when hidding advanced options, set their values to 0.
            $s .= '&PSTAMP_SELECT_CMF=0';
            $s .= '&PSTAMP_SELECT_PSF=0';
            $s .= '&PSTAMP_SELECT_BACKMDL=0';
            $s .= '&PSTAMP_SELECT_UNCOMPRESSED=0';
            $s .= '&PSTAMP_SELECT_CONVOLVED=0';
            $s .= '&PSTAMP_RESTORE_BACKGROUND=0';
            $s .= '&PSTAMP_USE_IMFILE_ID=0';
            $s .= '&PSTAMP_FWHM_MIN=0';
            $s .= '&PSTAMP_FWHM_MAX=0';
        } else {
            $s .= '&PSTAMP_SELECT_CMF=' . $this->option_SELECT_CMF;
            $s .= '&PSTAMP_SELECT_PSF=' . $this->option_SELECT_PSF;
            $s .= '&PSTAMP_SELECT_BACKMDL=' . $this->option_SELECT_BACKMDL;
            $s .= '&PSTAMP_SELECT_UNCOMPRESSED=' . $this->option_SELECT_UNCOMPRESSED;
            $s .= '&PSTAMP_SELECT_CONVOLVED=' . $this->option_SELECT_CONVOLVED;
            $s .= '&PSTAMP_RESTORE_BACKGROUND=' . $this->option_RESTORE_BACKGROUND;
            $s .= '&PSTAMP_USE_IMFILE_ID=' . $this->option_USE_IMFILE_ID;
            $s .= '&PSTAMP_FWHM_MIN=' . (($this->option_FWHM_MIN == '') ? 0 : $this->option_FWHM_MIN);
            $s .= '&PSTAMP_FWHM_MAX=' . (($this->option_FWHM_MAX == '') ? 0 : $this->option_FWHM_MAX);
        }

        return $s;
    }

    /**
      * Format uploaded coordinate list.
      *
      * @param data Uploaded coordinate list.
      * @return Formatted string of the coordinate list.
      */
    private function getCoordList_upload( $data ) {
        $c = "";
        $ct = 0;

        $rows = explode("\n", $data); 
        $len = Count($rows);

        // first 2 lines are for header: 
        //     Ra   Dec ...
        //     --------------------------
        // Note that when there are 5 fields, 2 "\t" are used to separate start_date and end_date
        // and maintain a nice view on UI. In that case, Count($fields) is 5, but the last element
        // is obtained using index 5.

        $fields = preg_split("/[\s]+/", trim($rows[0]));
        $fields_ct = Count($fields);
        //print "fields count = $fields_ct<br/>"; print_r($fields);

        $r = trim($rows[2]);
        $ct = 1;
        $fields = preg_split("/[\s]+/", $r);

        if ($fields_ct == 3) $c .= "$ct,$fields[1],$fields[2],a,null,null";
        else if ($fields_ct == 4) $c .= "$ct,$fields[1],$fields[2],$fields[3],null,null";
        else if ($fields_ct == 6) $c .= "$ct,$fields[1],$fields[2],$fields[3],$fields[4],$fields[5]";

        for ($i = 3; $i < $len; ++ $i) {
            $r = trim($rows[$i]);
            $ct = $i - 1;
            $fields = preg_split("/[\s]+/", $r);

            if ($fields_ct == 3) $c .= "#$ct,$fields[1],$fields[2],a,null,null";
            else if ($fields_ct == 4) $c .= "#$ct,$fields[1],$fields[2],$fields[3],null,null";
            else if ($fields_ct == 6) $c .= "#$ct,$fields[1],$fields[2],$fields[3],$fields[4],$fields[5]";
        }

        $this->coordCount_upload = $ct;

        return $c;
    }

    /**
      * Format coordinate list from web form.
      *
      * @param nothing
      * @return Formatted string of the coordinate list.
      */
    private function getCoordList_form() {
        $c = "";
        //
        $i = 1;
        {
            $c .= $i; // row number.

//             if ($this->DEBUG) {
//                 $c .= "," . $this->coordRas[$i - 1];
//                 $c .= "," . $this->coordDecs[$i - 1]; 
//                 $c .= "," . $this->coordUnits[$i - 1];
//             }
        
            $c .= "," . $this->formatCoord( $this->coordRas[$i - 1], $this->coordUnits[$i - 1] );
            $c .= "," . $this->formatCoord( $this->coordDecs[$i - 1], $this->coordUnits[$i - 1] );
            $c .= "," . $this->coordFilters[$i - 1];
            $c .= "," . (($this->useMjdDate == 1) ? $this->formatDate( $this->coordStartMJDs[$i - 1] ) : "null");
            $c .= "," . (($this->useMjdDate == 1) ? $this->formatDate( $this->coordEndMJDs[$i - 1] ) : "null");
        }

        for ($i = 2; $i <= $this->coordCount; ++ $i) {
            $c .= "#";

            $c .= $i; // row number.

//             if ($this->DEBUG) {
//                 $c .= "," . $this->coordRas[$i - 1];
//                 $c .= "," . $this->coordDecs[$i - 1];
//                 $c .= "," . $this->coordUnits[$i - 1];
//             }
    
            $c .= "," . $this->formatCoord( $this->coordRas[$i - 1], $this->coordUnits[$i - 1] );
            $c .= "," . $this->formatCoord( $this->coordDecs[$i - 1], $this->coordUnits[$i - 1] );
            $c .= "," . $this->coordFilters[$i - 1];
            $c .= "," . (($this->useMjdDate == 1) ? $this->formatDate( $this->coordStartMJDs[$i - 1] ) : "null");
            $c .= "," . (($this->useMjdDate == 1) ? $this->formatDate( $this->coordEndMJDs[$i - 1] ) : "null");
        }   

        return $c;
    }

    /**
      * Format coordinate.
      *
      * @param coord Coordinate.
      * @param unit  Unit of the coordinate.
      * @return Formatted string of the coordinate.
      */
    private function formatCoord($coord, $unit) {
        return $coord; 

        //
        // remove the following, since only "deg" is used now, $unit always equals "deg".
        //
        //if ($unit == 'deg') {
        //    return $coord;
        //} else if ($unit == 'arc-min') {
        //    return $coord / 60.0;
        //} else if ($unit == 'arc-sec') {
        //    return $coord / 3600.0;
        //}
    }

    /**
      * Return date in MJD format.
      * YYYY-MM-DD to MJD conversion: http://en.wikipedia.org/wiki/Julian_day
      *
      * @param date The date to format.
      * @return The formatted date.
      */
    private function formatDate_old($date) {
        if ($date == '') {
            $date = "null";
        } else if (preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $date, $match)) {
            $year = intval( $match[1] ); 
            $month = intval( $match[2] ); 
            $day = intval( $match[3] );
            //print ("::$year, $month, $day<br>");

            $a = (14 - $month) / 12.0;
            $y = $year + 4800 - $a;
            $m = $month + 12 * $a - 3;
            $JDN = $day + (153 * $m + 2) / 5.0 + $y * (365 + 0.25 - 0.01 + 0.0025) - 32045;
            //$JD = $JDN;
            $date = $JDN - 2400000.5; // $MJD.
        }
        return $date;
    }

    /**
      * Format date. Translated from Bill's perl version.
      * Note: mktime($hr, $min, $sec, $mon, $day, $year);
      * See: http://www.epochconverter.com/programming/functions-php.php
      *
      * @param date The date to format.
      * @return The formatted date.
      */
    private function formatDate($date) {
        if ($date == '') {
            $date = "null";
        } else if (preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $date, $match)) {
            $year = intval( $match[1] );
            $month = intval( $match[2] );
            $day = intval( $match[3] );
            //print ("::$year, $month, $day<br>");
        
            date_default_timezone_set('UTC');
            $ticks = mktime(0, 0, 0, $month, $day, $year); 
            $date = 40587.0 + ($ticks / 86400.);
        }
        return $date;
    }

    /**
      * Submit preview request.
      *
      * @param none
      * @return Result of preview request.
      */
    public function previewGraph() {
        $queryStr = $this->getQueryStr();

        // If preview mode, show parameters, but do not send to ipp.
        if ($this->action == "preview") {
            return $queryStr;  
        }

        $resultSet;
        try {
            $resultSet = $this->psSOAPClient->requestPreview (
                             array ('sessionID'   => $this->PSISession->getSessionID(),
                                    'request'     => $queryStr
                                    ) 
                          );
        }
        catch (SoapFault $soapFault) {
            $errorString = $soapFault->faultstring;
            print "<br>SoapFault Error: $errorString <br>";
            return;
        }

        //return ( $resultSet );
        //print "SOAP call return: ";
        //print_r( $resultSet );

        foreach ($resultSet as $key => $val) {
            //print "$key ==> $val<br>";
            if ($key == "return") {
                return $this->showResult($val);
            }
        }
    }

    /**
      * Display query result.
      *
      * @param v Query result.
      * @return Query result formatted to display as html.
      */
    private function showResult( $v ) {
        $s = "";

        $cols = explode("|", $v);
        // print_r( $cols );
        if ($cols[0] == "SUCCESS") {
            $s .= "<font color='green'>";
            $s .= "RESULT: $cols[0]<br/>";
            $s .= "REQ_ID: $cols[1]<br/>";
            $s .= "REQ_NAME: $cols[2]";
            if ( isset($_REQUEST['showDebugOutput']) ) {
                //if ($cols[3] != "") $s .= "<br/>Error: $cols[2]";
                $s .= "<br/><br/>--Debug Output--<br/>$cols[4]";
            }
            $s .= "</font>";
        } else if ($this->startsWith($cols[0], "ERROR")) {
            $s .= "<font color='red'>";
            $s .= "RESULT: $cols[0]<br/>";
            $s .= "$cols[3]<br/>";
            if ( isset($_REQUEST['showDebugOutput']) ) {
                $s .= "<br/>--Debug Output--<br/>$cols[4]";
            }
            $s .= "</font>";
        } else {
            $s .= "Unknown return format. Please contact system administrator.<br/>" . $v;
        }
        return $s;
    }

    /**
      * Print coordindate rows to the web form.
      *
      * @param none
      * @return Print rows for coordinate in the web form.
      */
    public function addCoordRows() {
        for ($i = 1; $i <= $this->coordCount; ++ $i) {
            print $this->addCoordRow($i);
        }
    } 

    /**
      * Add a coordindate row to the web form.
      *
      * @param i Row number
      * @return A row for coordinate in the web form.
      */
    private function addCoordRow($i) {
        $btnDelete = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $br = "";
        if ($i > 1) {
            $btnDelete = "<input type='button' name='btnDelete' value='Remove' onclick='javascript: deleteCoordRow($i);' />";
            $br = "<br/>";
        }

        if ($i <= $this->coordCount) 
        {
            $ra = $this->coordRas[$i - 1];
            $dec = $this->coordDecs[$i - 1];
            $unit = $this->coordUnits[$i - 1];
            $unit_sec = ($unit == 'arc-sec') ? 'selected="selected"' : "";
            $unit_min = ($unit == 'arc-min') ? 'selected="selected"' : "";
            $unit_deg = ($unit == 'deg') ? 'selected="selected"' : "";

            $filter = $this->coordFilters[$i - 1];
            $filter_a = ($filter == 'a') ? 'selected="selected"' : "";
            $filter_g = ($filter == 'g') ? 'selected="selected"' : "";
            $filter_r = ($filter == 'r') ? 'selected="selected"' : "";
            $filter_i = ($filter == 'i') ? 'selected="selected"' : "";
            $filter_z = ($filter == 'z') ? 'selected="selected"' : "";
            $filter_y = ($filter == 'y') ? 'selected="selected"' : "";
            $filter_w = ($filter == 'w') ? 'selected="selected"' : "";

            $mjdMin = $this->coordStartMJDs[$i - 1];
            $mjdMax = $this->coordEndMJDs[$i - 1];
        }

        $optional = $this->strOptional;

        $s = <<<EOF
<div id='newCoordRow_$i'>
Ra: <input type='text' id="textCoordCenterRa_$i" name="textCoordCenterRa_$i" maxlength='20' size='10' value="$ra"/> 
Dec: <input type='text' id="textCoordCenterDec_$i" name="textCoordCenterDec_$i" maxlength='20' size='10' value="$dec"/> 
<span style='font-size: 12px; color: red;'>deg (J2000)</span> &nbsp; 
<input type='hidden' id="idCoordUnit_$i" name="selectCoordUnit_$i" value="deg" />

<!--
<span style='font-size: 12px;'>Unit: </span>
<select id="idCoordUnit_$i" name="selectCoordUnit_$i">
  <option value='deg' $unit_deg>deg</option>
  <option value='arc-sec' $unit_sec>arc-sec</option>
  <option value='arc-min' $unit_min>arc-min</option>
</select>&nbsp;
-->

Filters: 
<select id='selectFilter_$i' name='selectFilter_$i'>
  <option value='a' $filter_a>all</option>
  <option value='g' $filter_g>g</option>
  <option value='r' $filter_r>r</option>
  <option value='i' $filter_i>i</option>
  <option value='z' $filter_z>z</option>
  <option value='y' $filter_y>y</option>
  <option value='w' $filter_w>w</option>
</select>
&nbsp; 
<span id='spanMjdDate_$i'>
Start Date $optional: <input type='text' id='textMjdMin_$i' name='textMjdMin_$i' size='10' maxlength='20' value="$mjdMin" /> 
End Date $optional: <input type='text' id='textMjdMax_$i' name='textMjdMax_$i' size='10' maxlength='20' value="$mjdMax" /> 
</span>
$btnDelete
</div>
EOF;
        return $s;
    }

    /**
      * Get user's email.
      *
      * @param none
      * @return The user's email.
      */
    private function getUserEmail() {
        if ( ! isset($_SESSION["PostageStamp_UserEmail"]) ) {
            $_SESSION["PostageStamp_UserEmail"] = $this->retrieveUserEmail();
        }
        return $_SESSION["PostageStamp_UserEmail"];
    }

    /**
      * Retrieve user's email by making a soap call.
      *
      * @param none
      * @return User's email.
      */
    private function retrieveUserEmail() {
        global $PSISession;

        try {
            $usersClient = new SoapClient( $PSISession->getUsersService() ); // SOAP users client
        }
        catch (SoapFault $soapFault) {
            $PSISession->showUserServerError();
            die ("SOAP Fault: (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }

        $userID = $PSISession->getUserID();
        $sessionID = $PSISession->getSessionID();
        $user_data = $usersClient->getUsers(array('sessionID' => $sessionID,
                                          'conditions' => "userid:" . $userID));
        return $user_data->return[0]->Email; 
    }

    /**
      * Handle uploaded file containing coordinate list.
      *
      * @param none
      * @return none
      */
    public function handleUpload() {
      $filename = '';
      $this->uploadErrStr = '';
      $MAX_FILE_SIZE = 102400; // In Byte.

      if ($_FILES["fileUpload"]["error"] > 0) {
          $this->uploadErrStr = self::fileUploadErrorMessage( $_FILES["fileUpload"]["error"] );
      }
      else {
        # Get the file details
        $fileName = $_FILES['fileUpload']['name'];
        $fileType =  $_FILES['fileUpload']['type'];
        $fileSize = $_FILES['fileUpload']['size'];
        # Check for errors based on size and file type
        /*if ( !preg_match( '/^text\//', $fileType ) ) {
            $this->uploadErrStr = "File must be in a some kind of text format. The file $fileName is $fileType type.";
        }
        else if ( $fileSize > $MAX_FILE_SIZE ) {
            $this->uploadErrStr = "File max size (" . ($MAX_FILE_SIZE / 1024) . 
                " KB) exceeded. The file $fileName size is ".($fileSize / 1024)." KB.";
        }
        */
        if ( $fileSize > $MAX_FILE_SIZE ) {
            $this->uploadErrStr = "File max size (" . ($MAX_FILE_SIZE / 1024) .
                " KB) exceeded. The file $fileName size is ".($fileSize / 1024)." KB.";
        }
        else {
            $this->uploadContent = $this->parseCoordUpload( file_get_contents( $_FILES['fileUpload']['tmp_name'] ) );
        }
      } # else
      if ( $this->uploadErrStr != '' ) {
          $this->uploadErrStr = "<font color=\"red\">Upload Error: $this->uploadErrStr. Please upload again.</font>";
      }
  } #handleUpload

    /**
      * Build a query to load from database.
      *
      * @param none
      * @return The constructed query.
      */
    public function doLoadDB_buildQuery() {
      $sql = "SELECT TOP " . $this->selectMyDBRows .  " [" . $this->selectMyDBRa . "]" .
             ", [" . $this->selectMyDBDec . "]";
      if ($this->selectMyDBFilter != '') {
          $sql .= ", [" . $this->selectMyDBFilter . "]";
      }
      if ($this->selectMyDBStartDate != '') {
          $sql .= ", [" . $this->selectMyDBStartDate . "]";
      }
      if ($this->selectMyDBEndDate != '') {
          $sql .= ", [" . $this->selectMyDBEndDate . "]";
      }
      
      $sql .= " FROM [" . $this->selectMyDBTable . "]";

      //print $sql . "<br>";
      return $sql;
    }

    /**
      * Parse returned dataset from database load request.
      *
      * @param resultSet Dataset returned from database request.
      * @return none. Result is assigned to variables to be displayed on web UI.
      */
    public function parseLoadDBRet( $resultSet ) {
      //print $resultSet;
      $row_label_len = $this->row_label_len; // length of the first column (for number of row).
      $output = "";
      $hasError = 0;
      $ct = 0;
 
      if ( isset( $resultSet ) ) { 
            ///$output = "<table align= \"center\" border=\"1\" cellspacing=\"0\">\n";
            // Parse $resultSet.
            // Note: $resultSet's only key is 'return'.
            foreach ($resultSet as $key => $val) {
                // Split the query results using "\n".
                $queryResult = explode ("\n", $val);

                // Count the number of rows returned.
                $numRows = count ($queryResult);
        
                // Count the number of fields.
                $numFields = substr_count ($queryResult[0], ":");
        
                ///$output .= "<tr><th colspan =\"$numFields\"> $queryTitle </th></tr>";
                // Skip $queryResult[0], because that is column the header.
                for ($row = 0; $row < $numRows; $row++) {
                    $data = explode (",", $queryResult[$row]);
                    ///$output .= "<tr>\n";
                    for ($i = 0; $i < $numFields; $i++) {
                        if ($row == 0) {
                            if ($i == 0) {
                                $output .= $this->writeField( $this->row_label, $row_label_len );
                            }
                            // Display the header.  Remove the "[XXX]:String" and
                            // just show "XXX".
                            $data[$i] = preg_replace ('/^\[/', '', $data[$i]);
                            //$data[$i] = preg_replace ('/\]:String$/', '', $data[$i]);
                            $data[$i] = preg_replace ('/\]:\S+$/', '', $data[$i]);
                            ///$output .= "<th align=\"center\">$data[$i]</th>\n";
                            $title = $this->updateTitle( $data[$i], $i );
                            $output .= $this->writeField( $title );
                        }
                        else {
                            // Remove the leading and trailing double quotes, except
                            // for in the "column_defaults" column.
                            if ($i != 2) { 
                                $data[$i] = preg_replace ('/^"/', '', $data[$i]);
                                $data[$i] = preg_replace ('/"$/', '', $data[$i]);
                            }
                            //$output .= "<td>$data[$i]</td>\n";
                            $s = PSIHTMLGoodiesClass::htmlEncode( PSIHTMLGoodiesClass::decode( $data[$i] ) );
                            ///$output .= "<td>$s</td>\n"; 
                            // Error validation.
                            if ($i == 0 || $i == 1) {
                                if ( ! preg_match('/^[-+]?[0-9]*\.?[0-9]+([eE][-+][0-9]+)?$/', $s) ) {
                                    $this->uploadErrStr = "Invalid " . (($i == 0) ? "Ra" : "Dec" ) . " format, should be a float number";
                                    $hasError = 1;
                                }
                            }
                            else if ($i == 2) {
                                if ( strlen($s) == 1 && strstr("gripza", $s) ) {}
                                else if ( strlen($s) == 1 && strstr("012345", $s) ) { $s = $this->filterConversion($s); }
                                else if ($s == '') { $s = 'a'; }
                                else {
                                    $this->uploadErrStr = "Invalid filter value: should be one of g, r, i, z, y, w or a; or an integer 0 to 6, which is automatically converted to a, g, r, i, z, y, w";
                                    $hasError = 1;
                                }
                                //if ($filterConvStatus < $status) $filterConvStatus = $status;
                            }
                            else if ($i == 3 || $i == 4) {
                                if ( $s != "" && ! preg_match('/^[0-9]{0,5}\.?[0-9]{1,5}$/', $s) ) {
                                    $this->uploadErrStr = "Invalid date. Should be in MJD format";
                                    $hasError = 1;
                                }
                            }
                            else {
                                // shouldn't happen.
                            }

                            if ($i == 0) {
                                $output .= $this->writeField($ct + 1, $row_label_len);
                            }

                            $output .= $this->writeField($s);

                            if ($hasError == 1) {
                                $output .= " <-- First error happens here";
                                break;
                            }
                        } // if ($row == 0)
                    } // for $i
                    ///$output .= "</tr>\n";
                    $output .= "\n";

                    if ($row == 0) $output .= str_repeat("-", $numFields * 20 + $row_label_len) . "\n"; 
                    else ++ $ct;

                    if ($hasError == 1) break;
                } #for
            } #foreach
            ///$output .= "</table>\n";
            ///$output .= "</center>\n";
            ///$output .= "<br/>\n"; 
        }

      if ( $this->uploadErrStr != '' ) { 
          $this->uploadErrStr = "<font color=\"red\">Load Error: $this->uploadErrStr. Please load again.</font>";
      }

      //return $output;
      $this->dbContent = $output;
      $this->coordCount_db = $ct;
    }

    /**
      * Convert boolean value to checkbox attribute 'checked'.
      *
      * @param v Boolean value.
      * @return Checkbox attribute 'checked' or empty string.
      */
    public function doCheck($v) {
      return ($v == '1') ? 'checked' : '';
    }

    /**
      * Format title to display.
      *
      * @param v Value
      * @param i Field count.
      * @return Formatted title.
      */
    private function updateTitle($v, $i) {
      if ($i == 0) return "Ra($v)";
      if ($i == 1) return "Dec($v)";
      if ($i == 2) return "Filter($v)";
      if ($i == 3) return "Start_Date($v)";
      if ($i == 4) return "End_Date($v)";
      return $v;
    }

    /**
      * Convert number value to corresponding filter value.
      *
      * @param v number
      * @return Filter value.
      */
    private function filterConversion($v) {
      if ($v == '1') return 'g';
      if ($v == '2') return 'r';
      if ($v == '3') return 'i';
      if ($v == '4') return 'z';
      if ($v == '5') return 'y';
      if ($v == '6') return 'w';
      if ($v == '0') return 'a';

      return $v;
    }

  //////////////////////////////////////////////////////////////
  // getRequestsForUser()
  //////////////////////////////////////////////////////////////

    /**
      * Get request list of a user within a time range.
      *
      * @param timeStart Start time.
      * @param timeEnd   End time.
      * @return Request list of a user within the given time range.
      */
    public function getReqListForUser( $timeStart, $timeEnd ) {
        $username = $this->getUserEmail(); // $this->getQueryStr();
        //print "requestPreview query string: $queryStr<br>";
        //print_r ($this->psSOAPClient);
        //print "functions: "; var_dump( $this->psSOAPClient->__getFunctions() ); print "<br>";

        $resultSet;
        try {
            $resultSet = $this->psSOAPClient->getRequestsForUser (
                             array ('sessionID'   => $this->PSISession->getSessionID(),
                                    'username'    => $username,
                                    'date_min'    => $timeStart,
                                    'date_max'    => $timeEnd
                                    )  
                          );
        }
        catch (SoapFault $soapFault) {
        $errorString = $soapFault->faultstring;
            return "<br>SoapFault Error: $errorString <br>";
        }
      
        //return ( $resultSet );
        //print "SOAP call return: ";
        //print_r( $resultSet );
        foreach ($resultSet as $key => $val) {
            //print "$key ==> $val<br>";
            if ($key == "return") {
                return $this->showReqList( $val );
            }
        }

    }  

    /**
      * Show request list, formatted from given string returned from soap request.
      *
      * @param v A string, returned from soap request.
      * @return Formatted request list.
      */
    private function showReqList($v) {
        //print "$v";

        // $rows[0]: empty, $rows[1]: DEBUG_OUTPUT, $rows[2]: RESULT.
        $rows = preg_split("/DEBUG_OUTPUT=|RESULT=/", $v);

        $result = explode("\n", $rows[2]);
        $len = count($result);

        if ( isset($_REQUEST['showDebugOutput']) ) {
            print "--Debug Output--<br/>";
            print "$rows[1]<br/>";
        }

        if ($len == 1) {
            $s = "(No data available)<br/>";
            return $s;
        }

        $page = 0;
        if ( isset($_REQUEST['pg']) ) $page = $_REQUEST['pg'];
        else if (isset($_REQUEST['_pg']) && $_REQUEST['_pg'] != "") $page = $_REQUEST['_pg'];

        $page_size = 10;

        $page_start = $page * $page_size + 1;
        $page_end = $page_start + $page_size - 1;

        $s = "<br/><table width='900' border='1' cellspacing='0' cellpadding='2' class='results'>";
        $s .= $this->showReqTitleRowWithAction( trim($result[0]), "style='background: black; color: white; font-weight: bold;'" );

        // First row is header, others rows are data.
        $ct = 0;
        //$ct = $len;
        $t = "";
        for ($i = 1; $i < $len; ++ $i) {
        //for ($i = $len - 1; $i > 0; -- $i) {
            $r = trim($result[$i]);
            if ($r != "") {
                ++ $ct;
                //-- $ct;
                if ($ct < $page_start || $ct > $page_end) { continue; }

                if ($ct % 2 == 0) $style = "style='background: #eeffee;'";
                else $style = "";

                //$t = $this->showReqListRow($r, $style) . $t; // most recent first.
                $t .= $this->showReqListRow($r, $style);
            }
        }

        $s .= "$t</table>";

        $paging = new PagingClass( $ct, $page, $page_size, 10 );
        $s = $paging->writeNavBar() . $s . $paging->writeNavBar();

        return $s;
    }

    /**
      * Return a table row for the titles.
      *
      * @param v A bar (|) delimited string for the titles
      * @param style Style string.
      * @return A table row for the titles.
      */
    private function showTitleRow($v, $style) {
        $r = explode("|", $v);
        $ct = count($r) - 1;
        $s = "";

        for ($i = 1; $i < $ct; ++ $i) {
            $s .= "<td align='center' $style>$r[$i]</td>";
        }
        $s = "<tr>$s</tr>";

        return $s;
    }

    /**
      * Return a table row for the titles, with checkbox for action.
      *
      * @param v A bar (|) delimited string for the titles.
      * @param style Style string.
      * @param Name Optional value for name (value not used here).
      * @return none
      */
    private function showReqTitleRowWithAction($v, $style, $name='') {
        $r = explode("|", $v);
        $ct = count($r) - 1;
        //$s = "";

        $checked = (isset($_REQUEST['checkAllReq']) ? 'checked="checked"' : '');
        $s = "<td align='center' $style><input type='checkbox' id=\"checkAllReq\" name=\"checkAllReq\" $checked><span id='ReqActionTitle'>Action</span></td>";

        for ($i = 1; $i < $ct; ++ $i) {
            $s .= "<td align='center' $style>$r[$i]</td>";
        }
        $s = "<tr>$s</tr>";

        return $s;
    }

    /**
      * Show a row from the request list.
      *
      * @param v A bar (|) delimited string for the titles.
      * @param style Style string
      * @return returned string for the request list row.
      */
    private function showReqListRow($v, $style) {
        $r = explode("|", $v);
        $ct = count($r) - 1;
        $s = "";

        if ($ct >= 3) {
            $state = $r[3];
            $id = trim($r[1]);
            if ($state == 'new' || $state == 'run') {
                $s = "<a href='#' onclick='javascript: setReqState($id, \"cancel\");'>Cancel</a>";
            } else if ($state == 'parsed') {
                $s = "<a href='#' onclick='javascript: setReqState($id, \"run\");'>Run</a>&nbsp;" .
                     "<a href='#' onclick='javascript: setReqState($id, \"cancel\");'>Cancel</a>";
            } else if ($state == 'cancel' || $state == 'stop') {
                $s = "<a href='#' onclick='javascript: setReqState($id, \"delete\");'>Delete</a>";
                $s = "<input type='checkbox' id='cbDelete' name='cbDelete' value='$id' onclick='javascript:changeDeleteCount(this.checked);'>" . $s;
            } else {
                $s = "<font color='#999999'>none</font>";
            }
            $s = "<td align='center' $style><span id='req_$id'>$s</span></td>";
        }

        for ($i = 1; $i < $ct; ++ $i) {
            if ($i == 1) {
                $s .= "<td align='center' $style><a href='javascript:showJobList(" 
                      . trim($r[$i]) . ");'>$r[$i]</a></td>";
            } else {
                $s .= "<td align='center' $style>$r[$i]</td>";
            }
        }
        $s = "<tr>$s</tr>";

        return $s;
    }


    //////////////////////////////////////////////////////////////
    // getJobStatusForRequest()
    //////////////////////////////////////////////////////////////
        
    /**
      * Return the job status for a request.
      *
      * @param req_id Request ID.
      * @return Job status for a request.
      */
    public function getJobStatusForRequest( $req_id ) {
        $resultSet;
        try {
            $resultSet = $this->psSOAPClient->getJobStatusForRequest (
                             array ('sessionID'   => $this->PSISession->getSessionID(),
                                    'req_id'      => $req_id
                                    )
                          );
        }       
        catch (SoapFault $soapFault) {
            $errorString = $soapFault->faultstring;
            return "<br>SoapFault Error: $errorString <br>";
        }
                
        //print_r ($resultSet);
        foreach ($resultSet as $key => $val) {
            if ($key == "return") { 
                return $this->showJobList( $val ); // set page_size to 10000
            } 
        }
    }  

    /**
      * Format and display the returned job list (contained in variable v).
      *
      * @param v A string returned from soap request.
      *
      *  An example of string v's value is:
        getJobStatusForRequest.DEBUG_OUTPUT=JOB_LIST=| job_id| rownum|state|fault|Error|stage|stage_id|filter|component|Frame ID|exp_id| dep_id|dep_state|fault_count| | 65760431| 1|stop| 0||stack|2359745|g|skycell.1683.097||| || 0| | 65760432| 1|stop| 0||stack|2364145|y|skycell.1683.097||| || 0| | 65760433| 1|stop| 0||stack|2364845|i|skycell.1683.097||| || 0| | 65760434| 1|stop| 0||stack|2365545|r|skycell.1683.097||| || 0| | 65760435| 1|stop| 0||stack|2365945|z|skycell.1683.097||| || 0| | 65760436| 1|stop| 0||stack|2359257|g|skycell.1769.008||| || 0| | 65760437| 1|stop| 0||stack|2363856|y|skycell.1769.008||| || 0| | 65760440| 1|stop| 0||stack|2364356|i|skycell.1769.008||| || 0| | 65760441| 1|stop| 0||stack|2365656|z|skycell.1769.008||| || 0| | 65760443| 1|stop| 0||stack|2366056|r|skycell.1769.008||| || 0| PUBLIC_URI=http://datastore.ipp.ifa.hawaii.edu/pstampresults/chenx_212642
      *
      * So, after split,
      * $rows[0] is: getJobStatusForRequest.
      * $rows[1] (i.e., DEBUG_OUTPUT) is: (empty)
      * $rows[2] (i.e., JOB_LIST) is: | job_id| rownum|state|fault|Error|stage|stage_id|filter|component|Frame ID|exp_id| dep_id|dep_state|fault_count| | 65760431| 1|stop| 0||stack|2359745|g|skycell.1683.097||| || 0| | 65760432| 1|stop| 0||stack|2364145|y|skycell.1683.097||| || 0| | 65760433| 1|stop| 0||stack|2364845|i|skycell.1683.097||| || 0| | 65760434| 1|stop| 0||stack|2365545|r|skycell.1683.097||| || 0| | 65760435| 1|stop| 0||stack|2365945|z|skycell.1683.097||| || 0| | 65760436| 1|stop| 0||stack|2359257|g|skycell.1769.008||| || 0| | 65760437| 1|stop| 0||stack|2363856|y|skycell.1769.008||| || 0| | 65760440| 1|stop| 0||stack|2364356|i|skycell.1769.008||| || 0| | 65760441| 1|stop| 0||stack|2365656|z|skycell.1769.008||| || 0| | 65760443| 1|stop| 0||stack|2366056|r|skycell.1769.008||| || 0|
      * $rows[3] (i.e., PUBLIC_URI) is: http://datastore.ipp.ifa.hawaii.edu/pstampresults/chenx_212642
      *
      * @return The job list in HTML format.
      */
    private function showJobList($v) {
        //print "$v";

        // $rows[0]: empty, $rows[1]: DEBUG_OUTPUT, $rows[2]: RESULT.
        //$rows = preg_split("/DEBUG_OUTPUT=|RESULT=/", $v); // old interface.

        // split using 3 strings as delimiters: "DEBUG_OUTPUT=", "JOB_LIST=", "PUBLIC_URI=".
        // see function comment for more details.
        $rows = preg_split("/DEBUG_OUTPUT=|JOB_LIST=|PUBLIC_URI=/", $v); 

        $result = explode("\n", $rows[2]); // Split JOB_LIST ($rows[2]) by new line.
        $len = count($result);
        
        if ( isset($_REQUEST['showDebugOutput']) ) {
            print "--Debug Output--<br/>";
            print "$rows[1]<br/>";
        }   

        if ($len == 1) {
            $s = "(No data available)<br/>";
            return $s;
        }

        if (count($rows) == 4 && $rows[3] != "") { // PUBLIC_URI
            print " [<a href='#' onclick='javascript: var w = window.open(\"$rows[3]\", \"_req_download\", \"height=600,width=900,left=100,top=100,location=1,toolbar=1,menubar=1,status=1,scrollbars=1,resizable=1,titlebar=1\"); w.focus();'>Display data store results set: PS1SC institutions only</a>] &nbsp; <a href='#' onclick='javascript: window.prompt(\"Copy link to clipboard: Ctrl+C, or right click on the highlighted link and select Copy.\", \"$rows[3]\", \"width:300px\")' title='Copy link of the full result set to clipboard'><img src='images/icon_link.gif' border='0'></a>";
        }

        $page = 0;
        if ( isset($_REQUEST['pg']) ) $page = $_REQUEST['pg'];

        $page_size = 10000;

        $page_start = $page * $page_size + 1;
        $page_end = $page_start + $page_size - 1;

        $s = "<br/><table width='900' border='1' cellspacing='0' cellpadding='2' class='results'>";
        $s .= $this->showJobTitleRowWithAction( trim($result[0]), "style='background: black; color: white; font-weight: bold;'" );

        // First row is header, others rows are data.
        $ct = 0;
        for ($i = 1; $i < $len; ++ $i) {
            $r = trim($result[$i]);
            if ($r != "") {
                ++ $ct;
                if ($page_size != 10000)
                    if ($ct < $page_start || $ct > $page_end) { continue; }

                if ($ct % 2 == 0) $style = "style='background: #eeffee;'";
                else $style = "";

                $s .= $this->showJobListRow($r, $style);
            }
        }

        $s .= "</table>";

        $paging = new PagingClass( $ct, $page, $page_size, 10 );
        if ($page_size != 10000)
            $s = $paging->writeNavBar() . $s . $paging->writeNavBar();

        return $s;
    }

    /**
      * The job title row with action checkboxes.
      *
      * @param v A bar (|) delimited string for job row titles.
      * @param style String string.
      * @param name optional value for name.
      * @return A table row for titles.
      */
    private function showJobTitleRowWithAction($v, $style, $name='') {
        $r = explode("|", $v);
        $ct = count($r) - 1;
        //$s = "";

        $s = "<td align='center' $style>Action</td>";

        for ($i = 1; $i < $ct; ++ $i) {
            $s .= "<td align='center' $style>$r[$i]</td>";
        }
        $s = "<tr>$s</tr>";

        return $s;
    }

    /**
      * A table row for job list.
      *
      * @param v A bar (|) delimited string for job row titles.
      * @param style String string.
      * @return Job list row.
      */
    private function showJobListRow($v, $style) {
        $r = explode("|", $v);      
        $ct = count($r) - 1;        
        $s = "";          
        $url_base = "";

        if ($ct >= 3) {
            $state = $r[3];
            $id = trim($r[1]);
            if ($state == 'run') {
                $s = "<a href='#' onclick='javascript: setJobState($id, \"cancel\");'>Cancel</a>";
            } else if ($state == 'parsed') {
                $s = "<a href='#' onclick='javascript: setJobState($id, \"run\");'>Run</a>&nbsp;" .
                     "<a href='#' onclick='javascript: setJobState($id, \"cancel\");'>Cancel</a>";
            } else {
                $s = "<font color='#999999'>none</font>";
            }
            $s = "<td align='center' $style><span id='job_$id'>$s</span></td>";
        }

        for ($i = 1; $i < $ct; ++ $i) {
            if ($i == 1 && $r[3] == "stop" && $r[4] == "0") {
                $s .= "<td align='center' $style><a href='javascript:showFileList("
                      . trim($r[$i]) . ");'>$r[$i]</a></td>";
            } else {
                $s .= "<td align='center' $style>$r[$i]</td>";
            }
        }
        $s = "<tr>$s</tr>";

        return $s;
    }


  //////////////////////////////////////////////////////////////
  // getFileListForRequest()
  //////////////////////////////////////////////////////////////
            
    /**
      * Return file list of a job.
      *
      * @param job_id Job ID.
      * @return html string to display on web UI for a job's file list.
      */
    public function getFileListForJob( $job_id ) {
        $resultSet;
        try {
            $resultSet = $this->psSOAPClient->getFileListForJob (
                             array ('sessionID'   => $this->PSISession->getSessionID(),
                                    'job_id'      => $job_id
                                    )
                          );
        }       
        catch (SoapFault $soapFault) {
            $errorString = $soapFault->faultstring;
            return "<br>SoapFault Error: $errorString <br>";
        }

        foreach ($resultSet as $key => $val) {
            if ($key == "return") {
                if ($this->DEBUG || 
                    (isset( $_REQUEST['debug'] ) && $_REQUEST['debug'] == '1') ) { print $val . "</br>"; }
                return $this->showFileList( $val ); // set page_size to 10000
            }
        }
    }   

    /**
      * Display file list.
      *
      * @param v A string returned from soap request call to IPP.
      * @return Formatted returned result from soap request call to IPP.
      */
    private function showFileList($v) {
        //print "$v";
        
        // $rows[0]: empty, $rows[1]: DEBUG_OUTPUT, $rows[2]: RESULT.
        //$rows = preg_split("/DEBUG_OUTPUT=|RESULT=/", $v); // old interface.
        $rows = preg_split("/ERROR_OUTPUT=|DEBUG_OUTPUT=|INTERNAL_BASE_URI=|PUBLIC_BASE_URI=|FILE_LIST=/", $v);
        
        // count($rows) is 6.
        $result = explode("\n", $rows[5]);
        $len = count($result);
        
        if ( isset($_REQUEST['showDebugOutput']) ) {
            print "--Error Output--<br/>";
            print "$rows[1]<br/>";

            print "--Debug Output--<br/>";
            print "$rows[2]<br/>";
        }   

        if ($len == 1) {
            $s = "(No data available)<br/>";
            return $s;
        }

        $internal_url_base = $rows[3];
        $enc = new EncryptClass();
        $url_base = $enc->encryptUrl($internal_url_base);
        //print "<br/>pub_url_base: $rows[4] (not useful), <br/>internal_url_base: $internal_url_base (to hide)<br/>encrypted internal url base: $url_base<br/>";

        $page = 0;
        if ( isset($_REQUEST['pg']) ) $page = $_REQUEST['pg'];

        $page_size = 10000;

        $page_start = $page * $page_size + 1;
        $page_end = $page_start + $page_size - 1;

        $s = "<br/><table width='900' border='1' cellspacing='0' cellpadding='2' class='results'>";
        $s .= $this->showTitleRow( trim($result[0]), "style='background: black; color: white; font-weight: bold;'" );

        // First row is header, others rows are data.
        $ct = 0;
        for ($i = 1; $i < $len; ++ $i) {
            $r = trim($result[$i]);
            if ($r != "") {
                ++ $ct;
                if ($page_size != 10000)
                    if ($ct < $page_start || $ct > $page_end) { continue; }

                if ($ct % 2 == 0) $style = "style='background: #eeffee;'";
                else $style = "";

                //$s .= $this->showFileListRow($r, $style, $internal_url_base);
                $s .= $this->showFileListRow($r, $style, $url_base);
            }
        }

        $s .= "</table>";

        $paging = new PagingClass( $ct, $page, $page_size, 10 );
        if ($page_size != 10000)
            $s = $paging->writeNavBar() . $s . $paging->writeNavBar();

        return $s;
    }

    /**
      * Show a row of the file list.
      *
      * @param v A bar (|) delimited string for job row titles.
      * @param style String string.
      * @return File list row.
      */
    private function showFileListRow($v, $style, $internal_url_base) {
        $r = explode("|", $v);
        $ct = count($r) - 1;
        $s = "";
        $url_base = "";

        for ($i = 1; $i < $ct; ++ $i) {
            if ($i == 1 && $this->endsWith(strtolower( $r[$i] ), ".jpg")) {
                // Use this to automatically load image.
                $t = "<script type='text/javascript'>showFile(\"$internal_url_base/" . trim($r[$i]) . "\");</script>";
                // Use link on file name.
                //$s .= "<td align='center' $style><a href='#' onclick='javascript:showFile(\"$internal_url_base/" 
                //      . trim($r[$i]) . "\");'>$r[$i]</a>$t</td>";
                // Do not use link on file name.
                $s .= "<td align='center' $style> $r[$i] $t</td>";
            } else {
                $t = "<script type='text/javascript'>showFile(\"\");</script>";
                $s .= "<td align='center' $style>$r[$i]$t</td>";
            }
        }
        $s = "<tr>$s</tr>";

        return $s;
    }


    //////////////////////////////////////////////////////////////
    // setRequestState
    //////////////////////////////////////////////////////////////
            
    /**
      * Set state of a request.
      *
      * @param req_id Request ID
      * @state State value to be set for the request
      * @return Response from the set state request.
      */
    public function setRequestState( $req_id, $state ) {
        $resultSet;
        try {
            $resultSet = $this->psSOAPClient->setRequestState (
                             array ('sessionID'   => $this->PSISession->getSessionID(),
                                    'req_id'      => $req_id,
                                    'state'       => $state
                                    )
                          );
        }   
        catch (SoapFault $soapFault) {
            $errorString = $soapFault->faultstring;
            print "<br>SoapFault Error: $errorString <br>";
            return;
        }       
 
        //print "SOAP call return: ";
        //print_r( $resultSet );
 
        foreach ($resultSet as $key => $val) {
            //print "$key ==> $val<br>";
            if ($key == "return") {
                print( $val );
            }
        }
    }  


    //////////////////////////////////////////////////////////////
    // setJobState
    //////////////////////////////////////////////////////////////
            
    /**
      * Set state of a job.
      *
      * @param req_id Job ID
      * @state State value to be set for the job
      * @return Response from the set state request.
      */
    public function setJobState( $job_id, $state ) {
        $resultSet;
        try {
            $resultSet = $this->psSOAPClient->setJobState (
                             array ('sessionID'   => $this->PSISession->getSessionID(),
                                    'job_id'      => $job_id,
                                    'state'       => $state
                                    )
                          );
        }   
        catch (SoapFault $soapFault) {
            $errorString = $soapFault->faultstring;
            print "<br>SoapFault Error: $errorString <br>";
            return;
        }       
 
        //print "SOAP call return: ";
        //print_r( $resultSet );
 
        foreach ($resultSet as $key => $val) {
            //print "$key ==> $val<br>";
            if ($key == "return") {
                print( $val );
            }
        }
    }  


    //////////////////////////////////////////////////////////////
    // getReleaseInfo(). This function is expensive. Prefer to
    // read from local cache.
    //////////////////////////////////////////////////////////////

    /**
      * Return release information of a survey.
      *
      * @param surveyID Survey ID.
      * @return Release information of the survey.
      */
    public function getReleaseInfo($surveyID) {
        $resultSet;
        try {
            $resultSet = $this->psSOAPClient->getReleaseInfo (
                             array ('sessionID'   => $this->PSISession->getSessionID(),
                                    'surveyID'      => $surveyID,
                                    )
                          );
        }
        catch (SoapFault $soapFault) {
            $errorString = $soapFault->faultstring;
            print "<br>SoapFault Error: $errorString <br>";
            return;
        }

        foreach ($resultSet as $key => $val) {
            //print "$key ==> $val<br>";
            if ($key == "return") {
                //print( $val );
                return $val;
            }
        }
    }

    /**
      * Return release information of a survey from load cache. Use lazy initializatin, singleton pattern.
      *
      * @param none
      * @return Release information.
      */
    public function getReleaseInfo_Local() {
        if (! isset( $_SESSION['PostageStampReleaseInfo'] )) { 
            //echo "lazy init<br/>";
            $_SESSION['PostageStampReleaseInfo'] = $this->getReleaseInfo("");
        } 
        return $_SESSION['PostageStampReleaseInfo'];
    }

    /**
      * Build the release list for a survey.
      *
      * @param none
      * @return Rlease list of a survey.
      */
    public function buildSurveyReleaseList() {
        $v = $this->getReleaseInfo_Local();
        $s = "";

        $rows = preg_split("/ERROR_OUTPUT=|RESULT=/", $v);

        $result = explode("\n", $rows[2]);
        $len = count($result);

        if ( isset($_REQUEST['showDebugOutput']) ) {
            print "--Debug Output--<br/>";
            print "$rows[1]<br/>";
        }

        if ($len == 1) {
            $s = "(No data available)<br/>";
            return $s;
        }

        // First row is header, others rows are data.
        $s = "<OPTION value=\"\">-- Select One --</OPTION>";
        $survey = "";
        $releases = "";
        $survey_release_list = "var h = new Object();\n";
        $total_stack = "var ts = new Object();\n";
        $short_stack = "var ss = new Object();\n";
        for ($i = 0; $i < $len; ++ $i) {
            $r = trim($result[$i]);
            if ($r != "") {
                $cols = $this->getReleaseInfoListRow($r);
                if ($i == 0) {
                    //print_r($cols);
                    // This check makes sure the parsing is correct.
                    if ($r != "|surveyID|release_name|exposure_count|deep_stacks|nightly_stacks|reference_stacks|priority|release_state|")
                    {
                        $s = "<font color='red'>IPP API getReleaseInfo() changed. Please contact system administrator.</font>";
                        return $s;
                    }
                } else {
                    if ($survey != $cols[0]) {
                        if ($survey != "") {
                            //$survey_release_list .= "var r_$survey = [$releases];<br/>";
                            $survey_release_list .= "    h['$survey'] = [$releases];\n";
                        }
                        $survey = $cols[0];
                        $s .= "<OPTION value=\"$survey\">$survey</OPTION>";
                        $releases = "'$cols[1]'";
                        $total_stack .= "    ts['$cols[1]'] = " . ($cols[3] + $cols[5]) . ";\n";
                        $short_stack .= "    ss['$cols[1]'] = " . ($cols[4]) . ";\n";
                    }
                    else {
                        $releases .= ", '$cols[1]'";
                        $total_stack .= "    ts['$cols[1]'] = " . ($cols[3] + $cols[5]) . ";\n";
                        $short_stack .= "    ss['$cols[1]'] = " . ($cols[4]) . ";\n";
                    }
                }
            }
        }
        $survey_release_list .= "    h['$survey'] = [$releases];\n";

        $s = "<span id='spanSurveyID'>Survey ID: <select id=\"idSurveyID\" name=\"selectSurveyID\" onchange=\"javascript: updateReleaseName()\">$s</select></span> ";
        $s .= "<span id='spanReleaseName'>Release Name <span style=\"font-size: 12px; color: #999999;\">(optional)</span>: <select id=\"idReleaseName\" name=\"selectReleaseName\"><OPTION value=''>-- Select One --</OPTION></select></span>";

        $js_survey_release_list = <<<EOF
<script type='text/javascript'>
    $survey_release_list
    $total_stack
    $short_stack
</script>
EOF;

        //echo $js_survey_release_list;

        $survey_release_list = str_replace("\n", "\n<br/>", $survey_release_list);

        $s .= $js_survey_release_list;
        return $s;
    }

    /**
      * Return a row of the release information of a survey.
      *
      * @param v A bar (|) delimited string for the release information.
      * @return Release information constructed from the input string.
      */
    private function getReleaseInfoListRow($v) {
        $r = explode("|", $v);
        $ct = count($r) - 1;
        $s = array();
        $url_base = "";

        for ($i = 1; $i < $ct; ++ $i) {
            array_push($s, $r[$i]);
        }

        return $s;   
    }

    /**
      * Return a list of the release information of a survey.
      *
      * @param none
      * @return Release list constructed from the input string.
      */
    public function showReleaseInfoList() {
        $v = $this->getReleaseInfo_Local();
        //print "$v";
        
        // $rows[0]: empty, $rows[1]: DEBUG_OUTPUT, $rows[2]: RESULT.
        $rows = preg_split("/ERROR_OUTPUT=|RESULT=/", $v); 
        
        $result = explode("\n", trim( $rows[2] ));
        $len = count($result);
        
        if ( isset($_REQUEST['showDebugOutput']) ) {
            print "--ERROR Output--<br/>";
            print "$rows[1]<br/>";
        }   
        
        if ($len == 1) {
            $s = "(No data available)<br/>";
            return $s;
        }   
        
        $s = "<table width='900' border='1' cellspacing='0' cellpadding='2' class='results'>";
        $s .= $this->showTitleRow( trim($result[0]), "style='background: black; color: white; font-weight: bold;'" );

        // First row is header, others rows are data.
        $ct = 0;
        for ($i = 1; $i < $len; ++ $i) {
            $r = trim($result[$i]);
            if ($r != "") {
                ++ $ct;

                if ($ct % 2 == 0) $style = "style='background: #eeffee;'";
                else $style = "";

                $s .= $this->showReleaseInfoListRow($r, $style);
            }
        }

        $s .= "</table>";

        return $s;
    }

    /**
      * Return a row in the list of the release information of a survey.
      *     
      * @param v A bar (|) delimited string for release information list.
      * @param style The style string.
      * @return Release list constructed from the input string.
      */
    private function showReleaseInfoListRow($v, $style) {
        $r = explode("|", $v); // split $v by delimiter "|".
        $ct = count($r) - 1;
        $s = "";
        $url_base = "";

        for ($i = 1; $i < $ct; ++ $i) {
            $s .= "<td align='center' $style>$r[$i]</td>";
        }
        $s = "<tr>$s</tr>";

        return $s;
    }



    /**
      * Returns a boolean value on whether a string starts with another.
      *
      * @param haystack Haystack string
      * @param needle   Needle string
      * @return A boolean value wether haystack starts with needle.
      */
    private function startsWith($haystack, $needle) {
        return !strncmp($haystack, $needle, strlen($needle));
    }  

    /**
      * Returns a boolean value on whether a string ends with another.
      * From: http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions
      *
      * @param haystack Haystack string
      * @param needle   Needle string
      * @return A boolean value wether haystack ends with needle.
      */
    private function endsWith($haystack, $needle) {
        return substr($haystack, -strlen($needle)) == $needle;
    }
    
}// PostageStampClass
