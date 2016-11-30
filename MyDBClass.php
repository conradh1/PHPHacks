<?php
/**
 * @class MyDBClass
 * This PSI class performs various functions on MyDB via SOAP calls to the DRL.
 * Examples include uploading CSV files, extracting tables, and deleting them.
 *
 * @version Beta
 * license GPL version 3 or any later version.
 * copyleft 2010 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Conrad Holmberg
 * @since Beta version 2010
 */
class MyDBClass
{

    /// Private variables
    private $PSISession; // PSI Session instance needed in almost all classes
    private $jobsSOAPClient; // SOAP Client Object used to make SOAP calls to the DRL Jobs Service.
    private $tableList = array();
    private $tablesDetailsHash = array();
    private $actionMessage;
    // Variables used in forms
    private $selectTableActionsList;
    private $action;
    private $table;  // current Table that the MyDB is to perform an action on.
    private $tableCount;  // Total number of tables in MyDB
    private $radioTableType; // Various Form variables
    private $selectFileFormat;
    private $textNewUploadTable;
    private $checkTables = array();

    //! Constants for the type of things a user can do with MyDB
    const ACTION_DO_NOTHING = 0;
    const ACTION_SHOW_TABLES = 1; //! Default action that shows all the personal database tables.
    const ACTION_SHOW_TABLES_DETAILS = 2; //! Action to show details on all MyDB tables (ie., size, row count).
    const ACTION_SHOW_TABLE_COLUMNS = 3; //! Action to show columns from a particualr table.
    const ACTION_TABLE_COUNT_ROWS = 4; //! Action to count the number of rows in a my db table
    const ACTION_TABLE_STRUCTURE_VIEW = 5; //! Action to show table columns and data types.
    const ACTION_TABLE_TOP_10_ROWS = 6; //! Action to show table columns and data types.
    const ACTION_TABLE_EXTRACT = 7; //! Action that extracts a table into a file format
    const ACTION_TABLE_UPLOAD = 8; //! Action to upload a file of a given format to a table.
    const ACTION_TABLE_DELETE_REQUEST = 9; //! Action to confirm a delete request
    const ACTION_TABLE_DELETE = 10; //! Action deletes a table

    /**
    * Default constructor assigns needed env variables.
    *
    *
    * @param MyDBClass - creates instance of the  MyDBClass class.
    */
    public function __construct( $PSISession ) {

        if ( !empty($PSISession) ) {
            $this->PSISession = $PSISession;
            // Assign default Schema for MyDB usage.
            try {
              $this->jobsSOAPClient = new SoapClient ( $this->PSISession->getJobsService() );
            }
            catch (SoapFault $soapFault) {
              $this->PSISession->showUserServerError();
              die ("SOAP Fault: (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
            }
            self::initTableDetailsHash();
            $this->actionMessage = self::performAction();
        }
        else {
            error_log("Cannot constuct MyDB class instance.  PSISession object is NULL.");
            exit();
        }
    } // function __construct

    /**
    * Overloaded function used for getters and setters on variables
    *
    * @param method The method name that is parsed out ( i.e., getAction )
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
         die ( "Unkown method call in MyDBClass: $method.  ".
               "Make sure it is matches the properties within the class." );
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
    * Performs an action based on a user request (i.e., delete table )
    *
    * @return Output based on an action like counting table rows.
    *
    */
    private function performAction() {

      $message = '';

      self::initFormVariables();  // Get form variables

      // Try again to see if we have the needed form variables
      if ( empty( $this->action ) )
	  return;

      // Do something based on the action and table name
      switch ( strtolower( $this->action ) ) {
          case MyDBClass::ACTION_DO_NOTHING:
              break;
	  case MyDBClass::ACTION_TABLE_COUNT_ROWS:
	      $message = self::handleTableCountRows( $this->table );
	      break;
	  case MyDBClass::ACTION_TABLE_STRUCTURE_VIEW:
	      $message = self::handleTableStructureView( $this->table );
	      break;
	  case MyDBClass::ACTION_TABLE_TOP_10_ROWS:
	      $message = self::handleTableTop10Rows( $this->table );
	      break;
	  case MyDBClass::ACTION_TABLE_EXTRACT:
	      if ( isset( $this->selectFileFormat ) )
                $message = self::handleTableExtract ( $this->table,
                                                      $this->selectFileFormat
                                                    );
	      break;
	  case MyDBClass::ACTION_TABLE_UPLOAD:
	      if ( !empty( $this->radioTableType ) ) {
    //Special Case for upload table
    if  ( $this->radioTableType == 'new' )
      $this->table = $this->textNewUploadTable;
    $message = self::handleTableUpload ( $this->table );
                self::initTableDetailsHash();  # We have to get all the tables stats again because it's been modified.
	      }
	      else
    $message = "<font color=\"red\">Error. You must specify whether to upload to a new table or an existing one. </font><br />";
	      break;
	  case MyDBClass::ACTION_TABLE_DELETE_REQUEST:
	      $message = self::handleTableDeleteRequest();
	      break;
	  case MyDBClass::ACTION_TABLE_DELETE:
	      $message = self::handleTableDelete();
              self::initTableDetailsHash();  # We have to get all the tables stats again because it's been modified.
	      break;
	  default:
	      $message = "Error: Unkown MyDB action ".$this->action.". Nothing to do.";
      } //switch
      return $message;
    } //performAction

    /**
    * returns the results of a query that get details about all
    * MyDB tables like size and row count.
    *
    */
    private function initTableDetailsHash() {

        // Set query for showing MyDB tables.
        $query = self::getMyDBQuery ( self::ACTION_SHOW_TABLES_DETAILS );
        // Assign the query for the table structure  and execute it
        $resultSet = self::executeMyDBQuery( $query, $errorString );
        $headerArray = array(); # Used to hold the query header.
        $tableDetailsHash = array(); # Used to hold column data in each query row.
        $this->tableList = array();
        $this->tablesDetailsHash = array();

        if ( isset( $resultSet ) ) {
            // Parse $resultSet.
            // Note: $resultSet's only key is 'return'.
            foreach ($resultSet as $key => $val) {
                // Split the query results using "\n".
                $queryResult = explode ("\n", $val);

                // Count the number of rows returned.
                $numRows = count ($queryResult);
                $this->tableCount = $numRows;

                // Count the number of fields.
                $numColumns = substr_count ($queryResult[0], ":");

                // Go through each detail of a MyDB table
                for ($row = 0; $row < $numRows; $row++) {
                    $data = explode (",", $queryResult[$row]);
		    $tableDetailsHash = array();
                    for ($column = 0; $column < $numColumns; $column++) {
                        # Assign values for the header row
                        if ($row == 0) {
                            // Display the header.  Remove the "[XXX]:String" and just show "XXX".
                            $data[$column] = preg_replace ('/^\[/', '', $data[$column]);
                            $data[$column] = preg_replace ('/\]:\S+$/', '', $data[$column]);
                            $headerArray[$column] = trim($data[$column]); #assign each header
                        }
                        else {
			  // Remove the leading and trailing double quotes
                          $data[$column] = preg_replace ('/^"/', '', $data[$column]);
                          $data[$column] = preg_replace ('/"$/', '', $data[$column]);
                          #$data[$column] = PSIHTMLGoodiesClass::htmlEncode( PSIHTMLGoodiesClass::decode( $data[$i] ) );
			  if ( $column == 0 )
                               $this->tableList[] = trim($data[$column]); #assign table name
                          else
			       $tableDetailsHash[$headerArray[$column]] = trim($data[$column]); # assign table data
                        }
                    } // for columns
                    if ( $row != 0 )  //don't add the headers
		      $this->tablesDetailsHash[$this->tableList[sizeof($this->tableList)-1]] = $tableDetailsHash;
                } #for row
            } #foreach
        } #if
        else if ( isset( $errorString ) ) {
	    $this->tableList[0] = 'ERROR';  //assign something to the hash.
            print  <<<EOF
                            <p><font  style="color: red">Unable execute MyDB Table Details Error: $errorString</font></p>
EOF;
            error_log( "Error: Query ".$query." failed.  Stack: $errorString" );
        }
    } //initTableDetailsHash

   /**
    * returns a query to get the list of tables from MyDB.
    *
    *
    * @return hash of the details of a particular table
    */
    public function getTableDetailsHash( $table ) {
        if ( !in_array( $table, $this->tableList ))
	  return NULL;
        else
          return ( $this->tablesDetailsHash[$table] );
    } //getTableDetailsHash

    /**
    * returns a query to get the list of columns from a MyDB table.
    *
    *
    * @return array of MyDB tables
    * @param table Table name of the columns we wish to query
    */
    public function getTableColumnsList( $table ) {

        $this->columnList = array();
        // Set query for showing MyDB tables.
        $query = self::getMyDBQuery ( self::ACTION_SHOW_TABLE_COLUMNS, $table );
        $resultSet = self::executeMyDBQuery( $query, $errorString );

        // Assign the result set to array holding the table names
        if ( isset ( $resultSet ) ) {
            foreach ($resultSet as $key => $val) {
                // Split the query results using "\n".
                $queryResult = explode ("\n", $val);

                // Count the number of rows returned.
                $numRows = count ($queryResult);

                // Parse the results and assign the table array
                // Skip $queryResult[0], because that is the column header.
                for ($row = 1; $row < $numRows; $row++) {
		    $data = explode (",", $queryResult[$row]);
                    $column = $data[0];
                    // Remove the leading and trailing double quotes.
                    $column = preg_replace ('/^"/', '', $column );
                    $column = preg_replace ('/"$/', '', $column );
                    $this->columnList[$row-1] = $column; //Because we ignore first index
                }
            } //foreach
        }
        else if ( isset( $errorString ) ) {
            print <<<EOF
                            <p><font  style="color: red">Unable to obtain MyDB columns. Error: $errorString</font></p>
EOF;
            error_log( "Error: Query ".$query." failed.  Stack: $errorString" );
        }
        return ( $this->columnList );
    } //getTableColumnsList

    /**
    * Counts the number of rows in a table and returns the
    * results in pretty HTML.
    *
    * @param  table The name of the table that is going to be counted.
    *
    * @return number of rows in a table or an error
    */
    public function handleTableCountRows( $table ) {

        $output;

        // Assign the query to count rows and execute it
	if ( empty( $table ) )
            return "<font color=\"red\">Error. You must specify a table if you want to count its rows. </font><br />";
	else if ( !in_array(  $table, $this->tableList ) )
	    return "<font color=\"red\">Error: Table $table does not exist in your MyDB.</font><br/><br/>\n";

        $query = self::getMyDBQuery( MyDBClass::ACTION_TABLE_COUNT_ROWS, $table );
        $resultSet = self::executeMyDBQuery( $query, $errorString );

        // Assign result set
        if ( isset( $resultSet ) ) {
            foreach ($resultSet as $key => $val) {
                // Split the query results using "\n".
                $queryResult = explode ("\n", $val);
                // Skip $queryResult[0], because that is column the header.
                $output = <<<EOF
                            <p>Table <strong>$table</strong> has <strong>$queryResult[1]</strong> Rows</p>
EOF;
            }
        }
        else if ( isset( $errorString ) ) {
            $output =  <<<EOF
                            <p><font  style="color: red">Unable to obtain Row count. Error: $errorString</font></p>
EOF;
            error_log( "Error: Query ".$query." failed.  Stack: $errorString" );
        }
        return $output;
    } //handleTableCountRows

    /**
    * Executes a query based table structure includes columns and datatypes.
    * Then returns results in pretty HTML.
    *
    * @param  table The name of the table that is going to be counted.
    *
    * @return output Pretty HTML table with all the columns and datatypes.
    */
    public function handleTableStructureView ( $table ) {


        // Assign the query for the table structure  and execute it
        $query = self::getMyDBQuery( MyDBClass::ACTION_TABLE_STRUCTURE_VIEW, $table );


        if ( empty( $table ) )
            return "<font color=\"red\">Error. You must specify a table to view its structure. </font><br />";
	else if ( !in_array(  $table, $this->tableList ) )
	    return "<font color=\"red\">Error: Table $table does not exist in your MyDB.</font><br/><br/>\n";
	else
	    return self::getMyDBQueryHTMLTable( $query, "Column Description of MyDB $table" );


    } //handleTableStructureView


    /**
    * Executes a query showing the top 10 rows of a given table.
    *
    * @param  table The name of the table that is going to be counted.
    *
    * @return output Pretty HTML table with all the columns and datatypes.
    */
    public function handleTableTop10Rows ( $table ) {

        // Assign the query for the table structure  and execute it
        $query = self::getMyDBQuery( MyDBClass::ACTION_TABLE_TOP_10_ROWS, $table );

        if ( !isset( $table ) )
            return "<font color=\"red\">Error. You must specify a table to view its rows. </font><br />";
        else if ( !in_array(  $table, $this->tableList ) )
	    return "<font color=\"red\">Error: Table $table does not exist in your MyDB.</font><br/><br/>\n";
        else
	    return self::getMyDBQueryHTMLTable( $query, "First 10 Rows on MyDB Table $table" );
    } //handleTableTop10Rows

    /**
    * Executes a MyDB query based on a query and a title for it and returns the results.
    *
    * @param query the SQL query string that is going to be executed to the DRL.
    * @param queryTitle A title of the query that is printed out (ie., show top 10 rows).
    *
    * @return output Pretty HTML table with all the columns and datatypes.
    */
    public function getMyDBQueryHTMLTable ( $query, $queryTitle ) {

        $output;

        // Assign the query for the table structure  and execute it
        $resultSet = self::executeMyDBQuery( $query, $errorString );

	if ( isset( $resultSet ) ) {
            $output = "<table align= \"center\" border=\"1\" cellspacing=\"0\">\n";
            // Parse $resultSet.
            // Note: $resultSet's only key is 'return'.
            foreach ($resultSet as $key => $val) {
                // Split the query results using "\n".
                $queryResult = explode ("\n", $val);

                // Count the number of rows returned.
                $numRows = count ($queryResult);

                // Count the number of fields.
                $numFields = substr_count ($queryResult[0], ":");

                $output .= "<tr><th colspan =\"$numFields\"> $queryTitle </th></tr>";
                // Skip $queryResult[0], because that is column the header.
                for ($row = 0; $row < $numRows; $row++) {
                    $data = explode (",", $queryResult[$row]);
                    $output .= "<tr>\n";
                    for ($i = 0; $i < $numFields; $i++) {
                        if ($row == 0) {
                            // Display the header.  Remove the "[XXX]:String" and
                            // just show "XXX".
                            $data[$i] = preg_replace ('/^\[/', '', $data[$i]);
                            //$data[$i] = preg_replace ('/\]:String$/', '', $data[$i]);
                            $data[$i] = preg_replace ('/\]:\S+$/', '', $data[$i]);
                            $output .= "<th align=\"center\">$data[$i]</th>\n";
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
                            $output .= "<td>$s</td>\n";
                        } // if ($row == 0)
                    } // for $i
                    $output .= "</tr>\n";
                } #for
            } #foreach
            $output .= "</table>\n";
            $output .= "<br/>\n";
        } #if
        else if ( isset( $errorString ) ) {
            $output =  <<<EOF
                            <p><font  style="color: red">Unable execute $queryTitle Error: $errorString</font></p>
EOF;
            error_log( "Error: Query ".$query." failed.  Stack: $errorString" );
        }

        return $output;
    } // getMyDBQueryHTMLTable

    /**
    * This is silly but encapulates the obsure JobTypes SOAP call.
    * So we get our file formats from the PSI Session
    *
    * @return An array contains the file formats that can be downloaded
    */
    public function getExtractFileFormats() {
        return ( $this->PSISession->getJobTypes() );
    } //getExtractFileFormats

    /**
    * Extracts a MyDB table into a selected file format
    * Then returns results in pretty HTML.
    *
    * @param table The name of the table that is going to be counted.
    * @param extractFormat The file format ( csv, FITS, etc ).
    * @return message that the extract was successful or not
    */
    public function handleTableExtract ( $table, $extractFormat ) {

        if ( empty( $table ) ) {
            $output = "<font color=\"red\">Error: You must specify a valid MyDB table.</font><br/><br/>\n";
            return $output;
        }

	if ( !in_array(  $table, $this->tableList ) ) {
	    $output = "<font color=\"red\">Error: Table $table does not exist in your MyDB.</font><br/><br/>\n";
            return $output;
	}

        if ( empty( $extractFormat )  ) {
            $output = "<font color=\"red\">Error: You must specify a valid file format.</font><br/><br/>\n";
            return $output;
        }

        if ( !array_key_exists( $extractFormat, self::getExtractFileFormats() ) ) {
            $output = "<font color=\"red\">Error: '$extractFormat' is not a valid file format choice " .
                    "for a Queued Download!</font><br/><br/>\n";
	    return $output;
        }

        try {
            $parameters =
                array ('sessionID'   => $this->PSISession->getSessionID(),
                    'schemaGroup' => $this->PSISession->getDefaultPSPSSchemaGroup(),
                    'tableName'   => $table,
                    'type'        => $extractFormat);
            $resultSet = $this->jobsSOAPClient->submitExtractJob ($parameters);
            if ( $resultSet )
                $output =  "<strong>Table $table successfully sent for extraction to $extractFormat format.".
                        " Please check your Queued Jobs for downloading the file once the job has completed.</strong>";
        }
        catch (SoapFault $soapFault) {
            $output = "Error: Could not extract table $table.";
            $output .= "<br/><br/>";
            $output .= "<font color=\"red\">Full Error: ".$soapFault->faultstring."</font>\n";
            error_log( "SOAP Fault: (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        } // catch

        return $output;
    } // handleTableExtract

    /**
    * Extracts a MyDB table into a selected file format
    * Then returns results in pretty HTML.
    *
    * @param  table The name of the table that the file is going to be uploaded to.
    * @return message that the file upload attempt was a success or not.
    */
    public function handleTableUpload ( &$table ) {

        $tableExists = 0;  //Boolean used for SOAP call to determine if the table has to be created or not.

        // Check if the table exists or not.
        if ( in_array( $table, $this->tableList ) ) {
            $tableExists = 1;
        }

        if ( self::checkUploadFile( $table, $output ) ) {
	    // A little voodoo with PHP to get the file contents into a string.
            // HACK This has to be done to send it via SOAP
            $fileString = file_get_contents( $_FILES['fileUpload']['tmp_name'] );
            $uploadFileName = $_FILES['fileUpload']['name'];


            try {
                // Set parameters for the SOAP call to the DRL.
                $parameters =
                    array ('sessionID'   => $this->PSISession->getSessionID(),
                           'schemaGroup' => $this->PSISession->getDefaultMyDBSchemaGroup(),
                           'tableName'   => $table,
                           'data'        => $fileString,
                           'tableExists' => $tableExists );

                $resultSet = $this->jobsSOAPClient->uploadData ($parameters);
                if ( $resultSet ) {
                    $output =  "<p><strong>File $uploadFileName successfully uploaded ".
                               "to the table $table.</strong></p>";
                }
            }
            catch (SoapFault $soapFault) {
                $output = "<font color=\"red\">Error: Could not upload file ".$_FILES['fileUpload']['name'];
                $output .= " to MyDB table $table.</font><br/><br/>";
                $output .= "<font color=\"red\">".$soapFault->faultstring."</font>\n";
                error_log( "SOAP Fault: (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
            }
        } #if checkUploadFile
        return $output;
    } // handleTableUpload

    /**
    * Checks to make sure that the file and table name are okay
    *
    * @param  table The name of the table that is going to be counted.
    * @param  errorString passed by reference and contains the error message if there is one.
    * @return true/false
    */
    private function checkUploadFile ( $table, &$errorString ) {

        if ($_FILES["fileUpload"]["error"] > 0) {
            $errorString = "<font color=\"red\">Error with uploading file: " .
                            self::fileUploadErrorMessage( $_FILES["fileUpload"]["error"] ).
                        "</font><br />";
            return 0;
        }
        else if ( empty( $table ) ) {
            $errorString = "<font color=\"red\">Error. You must specify a table to upload the file to. </font><br />";
            return 0;
        }
        else
            return 1;
    } //checkUploadFile


    /**
    * Translates error code to string.  wtf doesn't PHP have ths built in
    *
    * @param  errorCode error code passed

    * @return error string
    */
    private function fileUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded. Click browse to upload a file.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    } //fileUploadErrorMessage

    /**
    * Extracts a MyDB table into a selected file format
    * Then returns results in pretty HTML.
    *
    * @return message that the extract was successful or not
    */
    public function handleTableDeleteRequest () {
        global $self;
        $deleteTables = '';

        # Make sure that all the tables exist.
	foreach ( $this->checkTables as $checkTable ) {
	   if ( !in_array(  $checkTable, $this->tableList ) ) {
	    $output = "<font color=\"red\">Error: Table $checkTable does not exist in your MyDB.</font>";
            $output .= "<br/><br/>";
            return $output;
	   }
           # Add table to URL for each table.  Executed if user clicks yes.
           $deleteTables .= "<input type=\"hidden\" name=\"checkTables[]\" value=\"$checkTable\"/>\n";
	}

        if ( !isset( $this->checkTables ) || count ( $this->checkTables) < 1) {
            $output = "<font color=\"red\">Error: Nothing to delete, not tables selected.</font>";
            $output .= "<br/><br/>";
            $output = "<font color=\"red\">$output</font>\n";
	    return $output;
        }

        $tables2Delete = join(',', $this->checkTables);

        $output = "Are you <em>sure</em> you want to permanently delete the following tables: <strong>$tables2Delete</strong>?<br/>\n".

                  "<form name=\"formDeleteMyDBTables\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n".
                  "<input type=\"hidden\" name=\"action\" value=\"".self::ACTION_TABLE_DELETE."\"/>\n".
                  "$deleteTables\n".
                  "<input type=\"submit\" name=\"submitDeleteTables\" value=\"OK\"/>&nbsp;&nbsp;&nbsp;&nbsp;\n".
                  "<input type=\"button\" name=\"cancelDeleteTables\" value=\"Cancel\" onclick=\"window.location.href='".$_SERVER['PHP_SELF']."'\"/>".
                  "</form>";
        return $output;
    } //handleTableDeleteRequest

    /**
    * Delets tables from MyDB by executing drop table query for each table selected.
    *
    * @return message that the extract was successful or not
    */
    public function handleTableDelete ( ) {

        // Assign the query for the table structure  and execute it
        $query = self::getMyDBQuery( MyDBClass::ACTION_TABLE_DELETE );
        $resultSet = self::executeMyDBQuery( $query, $errorString );

        //if ( isset( $resultSet ) ) {
        if ( !isset( $errorString ) ) {
            return "Successfully deleted table(s): <strong>".join(', ', $this->checkTables)."</strong> from ".
                   $this->PSISession->getDefaultMyDBSchema().".<br/><br/>\n";
            $this->checkTables = array(); // Clear array
        }
        else
            return "<font color=\"red\">Unable to delete table(s): <strong>".join(', ', $this->checkTables)."</strong> Due to error: $errorString.</font><br/><br/>\n";
    } //handleTableDelete

    /**
    * Executes a fast query to the DRL for MyDB purposes
    * between MySQL and MS-SQL Server (i.e., show tables )
    *
    * @param query The string that contains the sql query to be executed to the MyDB database.
    * @param  errorString The error fault string passed by reference
    * @return resultSet
    */
    public function executeMyDBQuery( $query, &$errorString ) {

        $resultSet = NULL;

        try {
            $resultSet = $this->jobsSOAPClient->executeQuickJob (
                                   array ('sessionID'   => $this->PSISession->getSessionID(),
                                          'schemaGroup' => $this->PSISession->getDefaultMyDBSchemaGroup(),
                                          'context'     => $this->PSISession->getDefaultMyDBSchema(),
                                          'query'       => $query,
                                          'taskname'   =>  'MyDB Function Query',
                                          'isSystem'   =>  1  ) );
        }
        catch (SoapFault $soapFault) {
            $errorString = $soapFault->faultstring;
            return;
        }
        return ( $resultSet );
    } #executeMyDBQuery

    /**
    * Gives the query based on the MyDB action
    *
    * @param  action The action that specifies the query ( i.e., count, drop )
    * @param  table The name of the table that is going to be counted.
    *
    * @return integer of the number of rows in a table -1 for error
    */
    public function getMyDBQuery ( $action, $table=NULL ) {

        $query = '';;

        // Figure out which query we need to return
        switch ( $action  ) {
            case self::ACTION_SHOW_TABLES:
                $query = <<<EOF
                            SELECT ACTION_TABLE_NAME
                            FROM INFORMATION_SCHEMA.TABLES
                            WHERE ACTION_TABLE_TYPE='BASE TABLE'
                            ORDER BY ACTION_TABLE_NAME
EOF;
                break;
	    case self::ACTION_SHOW_TABLES_DETAILS:
		$query = <<<EOF
			    DECLARE @TableRowCounts TABLE ([TableName] VARCHAR(128),
                               [Rows] VARCHAR(128),
                               [Reserved] VARCHAR(128),
                               [Data] VARCHAR(128),
                               [Index] VARCHAR(128),
                               [Unused] VARCHAR(128)) ;
			    INSERT INTO @TableRowCounts ([TableName], [Rows], [Reserved], [Data], [Index], [Unused])
			    EXEC sp_MSforeachtable 'EXEC sp_spaceused ''?''';
			    SELECT [TableName] as 'Table Name', [Rows] as 'Number of Rows', [Reserved], [Data], [Index], [Unused]
			    FROM @TableRowCounts
			    ORDER BY [TableName]
			    GO
EOF;
		break;
            case self::ACTION_TABLE_COUNT_ROWS:
                $query = "SELECT COUNT_BIG(*) FROM [$table]";
                break;
	    case self::ACTION_TABLE_TOP_10_ROWS:
		$query = "SELECT TOP 10 * FROM [$table]";
		break;
            case self::ACTION_TABLE_STRUCTURE_VIEW:
	    case self::ACTION_SHOW_TABLE_COLUMNS:
                $query = <<<EOF
                    SELECT column_name, data_type,  column_default, is_NULLable
                    FROM   information_schema.tables AS t
                    JOIN   information_schema.columns AS c
                        ON  t.table_catalog=c.table_catalog
                        AND t.table_schema=c.table_schema
                        AND t.table_name=c.table_name
                        WHERE  t.table_name='$table'
EOF;
                break;
            case self::ACTION_TABLE_DELETE:
                # Add each table for deleting.
                foreach ( $this->checkTables as $checkTable ) {
		  if ( in_array(  $checkTable, $this->tableList ) )
		      $query .= "DROP TABLE [$checkTable]\n";
		}
                break;
            default:
                error_log ("Unkown query call to $type on MyDB page.");
                return;
        } //switch

        return $query;

    } //getMyDBQuery
    /**
    * Assigns variables needed for graphs
    *
    * @param none
    * @return none
    */
    private function initFormVariables () {

      if ( isset( $_REQUEST['action']) )
        $this->action = $_REQUEST['action'];
      else
        $this->action = NULL;

      if ( isset( $_REQUEST['table']) )
        $this->table = $_REQUEST['table'];
      else
        $this->table = NULL;

      // Used for uploading tables whether new or existing
      if ( isset( $_REQUEST['radioTableType']) )
        $this->radioTableType = $_REQUEST['radioTableType'];
      else
        $this->radioTableType = 'new';

      // Used for extracting tables (i.e., CSV)
      if ( isset( $_REQUEST['selectFileFormat']) )
        $this->selectFileFormat = $_REQUEST['selectFileFormat'];

      // New table name to create when uploading into MyDB
      if ( isset( $_REQUEST['textNewUploadTable']) )
        $this->textNewUploadTable = $_REQUEST['textNewUploadTable'];

      if ( isset( $_REQUEST['checkTables']) )
        $this->checkTables = $_REQUEST['checkTables'];


    } //initMyDBVariables

} //MyDBClass
