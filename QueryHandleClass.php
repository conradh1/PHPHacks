<?php
/**
 * @class QueryHandleClass
 * This Query Handle class stores all the needed information such as schema,
 * in order to maintain queries for the user and interacts with the DRL.
 *
 * @version Beta
 * GPL version 3 or any later version.
 * copyleft 2010 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley, drchang@ifa.hawaii.edu
 * @since Beta version 2010
 */
class QueryHandleClass
{
    // The following variables are accessed by reflection (PHP "overloading");

    //! Variables here for schemas
    private $schemaGroups = array();
    private $schemasHash = array();
    //! Default values
    private $userSchemaGroup;
    private $userSchema;
    private $userQueue;
    private $userMyDbTable;
    private $userQuery; # The query that the user creates
    private $userQueryName;
    private $userResultSet;
    private $selectExample; # used for select drop down.
    private $userQueryExampleHash;
    //! Download query file variables
    private $downloadFilename;
    private $downloadFileFormat;
    //! Env Variable
    private $PSISession;
    private $action; //! Variable used with constants for what to do (i.e., query, download, etc)

    //! Constants for the type of things a user can do with MyDB
    const ACTION_DO_NOTHING = 0; //! Default assign action
    const ACTION_EXECUTE_QUERY = 1; //! Action for a quick/long query request
    const ACTION_DOWNLOAD_RESULTS = 2; //! Action to download results from a quick query
    const ACTION_UPLOAD_QUERY_FILE = 3; //! Action to upload a query file to the query page
    const ACTION_SHOW_QUERY_EXAMPLE = 4; //! Action to load an example
    const ACTION_LOAD_QUERY = 5; //! Action to load query from a previous queued job.
    const ACTION_DO_AJAX = 6; //!ACtion to do ajax call
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
        }
        else {
            error_log("Cannot constuct QueryHandleClass class instance.  PSISession object is NULL.");
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
            $error = "Unkown method call in QueryHandleClass: $method.  ".
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

        // Immediately assign constructor arguments and default vaules
        $this->userSchemaGroup = $this->PSISession->getDefaultPSPSSchemaGroup();
        $this->userSchema     = $this->PSISession->getDefaultPSPSSchema();
        $this->userQueue       = 'fast';
        $this->userQueryName   = 'PSI Query';
        $this->userMyDbTable   = 'mydbtable';
        $this->userQuery       = '';
        $this->executableQuery       = '';
        $this->action = QueryHandleClass::ACTION_DO_NOTHING;
        // Call init methods
        self::initSchemas();  // Get PSPS Schema Groups and Schemas
    } // function initDefaults

   /**
    * Initializes $this->schemaGroupsHash and $this->schemasHash from the DRL
    * @return nothing
    */
    private function initSchemas() {


        // ############# Assign the Schemas ####################
        // Go through each schema group and get the queues it offers.
        try {
          $jobsSOAPClient = new SoapClient( $this->PSISession->getJobsService(), array('exceptions' => TRUE) );

          // Note:  We use the keyword ALL in the schema Group parameter to get all the scheams in every group.
          $result = $jobsSOAPClient->getSchemas
                          (array( 'sessionID'   => $this->PSISession->getSessionID(),
                                  'schemaGroup' => 'ALL' )); #get all the schema schemas regardless of group.
        }
        catch (SoapFault $soapFault) {
          $this->PSISession->showUserServerError();
          die ( "Error calling getSchemas() from Jobs Service(), (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }

        $tmpSchemaGroupName = '';
        $tmpSchemaHash = array();

        // Assign the schemas to $schemas as a hash;
        foreach ( $result as $key => $schemas ) {}

        foreach ( $schemas as $schema ) {
          if (empty($tmpSchemaGroupName)) {
            $tmpSchemaGroupName = $schema->DbGroup;
          }
          if ( strcmp($tmpSchemaGroupName, $schema->DbGroup) != 0) {
            # Add any schemas in that group to the hash and reset
              $this->schemasHash[$tmpSchemaGroupName] = $tmpSchemaHash;
              array_push( $this->schemaGroups, $tmpSchemaGroupName );
              $tmpSchemaHash = array();
              $tmpSchemaGroupName = $schema->DbGroup;
          }
          // HACK Since there are schemas for both long and short queries we
          // try to rid redundancy.
          if ( !array_key_exists( $schema->Context, $tmpSchemaHash ) ) {
            $tmpSchemaHash[$schema->Context] =
                  array (  'Description' => empty($schema->Description) ? 'N/A' : trim($schema->Description),
                           'Type' => empty($schema->DbType) ? 'N/A' : $schema->DbType,
                           'Group' => $schema->DbGroup,
                           'Timeout' => $schema->Timeout
                        );
          }
        } // foreach $queues as $queue.

        # for the last schema in the list
        if ( !array_key_exists(  $schema->DbGroup, $this->schemasHash ) ) {
          $this->schemasHash[$schema->DbGroup] = $tmpSchemaHash;
          array_push( $this->schemaGroups, $schema->DbGroup );
        }
    } // function initSchemas


    /**
    * Returns a list of queues from a particular schema
    *
    * @param  schemaGroup Name of the schema group that contains queues
    * @return Table Hash
    */
    public function getSchemas( $schemaGroup ) {
        $schemas = array();
        $schemaHash = $this->schemasHash[ $schemaGroup ];
        foreach( $schemaHash as $schema => $value ) {
            // Hand off the table name only
            $schemas[] = $schema;
        }
        return $schemas;
    } // function getSchemas

    /**
    * Returns hash of a particular schema
    *
    * @param  schemaGroup Schema group name that we want the hash of
    * @param  schema Schema name that we want the hash of
    * @return Table Hash
    */
    public function getSchemaHash( $schemaGroup, $schema ) {
        if ( !empty($this->schemasHash[ $schemaGroup ][ $schema ]) )
          return $this->schemasHash[ $schemaGroup ][ $schema ];
        else
          return NULL;
    } // function getSchemaHash


    /**
    * Executes a query based on its queue type (slow, fast, or syntax check)
    * via SOAP call to the DRL.
    *
    * @param  errorString The error fault string passed by reference
    * @return resultSet
    */
    public function executeUserQuery( &$errorString ) {
        $resultSet;
        if ( $this->userQueue == 'fast' || $this->userQueue == 'syntax')
            $resultSet = self::executeFastQuery( $errorString );
        else if ( $this->userQueue == 'slow')
            $resultSet = self::executeSlowQuery( $errorString );
        return $resultSet;
    } #executeUserQuery

    /**
    * This is silly but encapulates the obsure JobTypes SOAP call.
    * So we get our file formats from the PSI Session
    *
    * @return An array contains the file formats that can be downloaded
    */
    public function getDownloadFileFormats() {
        return ( $this->PSISession->getJobTypes() );
    } //getDowloadFileFormats


    /**
    * Executes a slow query to the DRL
    * between MySQL and MS-SQL Server (i.e., show tables )
    *
    * @param  errorString The error fault string passed by reference
    * @return resultSet
    */
    public function executeSlowQuery( &$errorString ) {
      try {
        $jobsSOAPClient = new SoapClient( $this->PSISession->getJobsService(), array('exceptions' => TRUE) );
        $resultSet;

        //Changes something like 'show tables' to something ms-sql can execute
        $executableQuery = self::rewriteSlowQuery( self::TranslateQuery( $this->userQuery ) );

        $resultSet = $jobsSOAPClient->submitJob (
                                array ( 'sessionID'   => $this->PSISession->getSessionID(),
                                        'schemaGroup' => $this->userSchemaGroup,
                                        'context'     => $this->userSchema,
                                        'query'       => $executableQuery,
                                        'taskname'    => $this->userQueryName,
                                        'estimate'    => 60) );
      }
      catch (SoapFault $soapFault) {
        $errorString = $soapFault->faultstring;
        return;
      }
      return ( $resultSet );
    } #executeSlowQuery

    /**
    * Executes a fast query to the DRL
    * between MySQL and MS-SQL Server (i.e., show tables )
    *
    * @param  errorString The error fault string passed by reference
    * @return resultSet
    */
    public function executeFastQuery( &$errorString ) {
      try {
        $jobsSOAPClient = new SoapClient( $this->PSISession->getJobsService(), array('exceptions' => TRUE) );
        $resultSet;
        //Changes something like 'show tables' to something ms-sql can execute
        $executableQuery = self::TranslateQuery( $this->userQuery );
        if ( $this->userQueue == 'syntax' )
          $executableQuery = self::addSyntaxCheck2Query( $executableQuery );


          $resultSet = $jobsSOAPClient->executeQuickJob (
                                   array ('sessionID'   => $this->PSISession->getSessionID(),
                                          'schemaGroup' => $this->userSchemaGroup,
                                          'context'     => $this->userSchema,
                                          'query'       => $executableQuery,
                                          'taskname'    => $this->userQueryName) );
      }
      catch (SoapFault $soapFault) {
          $errorString = $soapFault->faultstring;
          return;
      }
      return ( $resultSet );
    } #executeQuickJob


    /**
      * Assigns variables needed for the query page.
      *
      * @param none
      * @return none
      */
      public function initQueryFormVariables () {

        // Check for the query
        if (isset ($_REQUEST['query'])) {
          $this->userQuery = $_REQUEST['query'];
        }

        // Check for the schemaGroup (aka catalog )
        if (!empty ($_REQUEST['selectSchema']))
          list ( $this->userSchemaGroup, $this->userSchema) = preg_split("/[\|]/", $_REQUEST['selectSchema']);

        // Check the fast/slow queue
        if (!empty ($_REQUEST['queue']))
          $this->userQueue = $_REQUEST['queue'];

        // Check the myDB Table name
        if (!empty ($_REQUEST['myDbTable']))
          $this->userMyDbTable = $_REQUEST['myDbTable'];

        // If user selected an example query to load
        if ( isset( $_REQUEST['selectExample'] ))
          $this->selectExample = $_REQUEST['selectExample'];
        else
          $this->selectExample = '';

        if ( isset( $_REQUEST['queryName'] ))
          $this->userQueryName = $_REQUEST['queryName'];
        else if ( !isset($_REQUEST['queryName'] ) &&
                   isset ($_REQUEST['submitQuery']  ))
          $this->userQueryName = '';

        # Assign variables from quick download form
        # Defulat value for file name
        if ( isset($_REQUEST['textDownloadFileName']) )
          $this->downloadFilename =  $_REQUEST['textDownloadFileName'];
        else
          $this->downloadFilename = '';
        # Default value for file format
        if ( isset( $_REQUEST['selectDownloadFileFormat'] ) && !empty( $_REQUEST['selectDownloadFileFormat'] ) )
          $this->downloadFileFormat =  $_REQUEST['selectDownloadFileFormat'];
        else
          $this->downloadFileFormat = '';

        if ( !empty ( $this->userQuery )  and
              !empty ($this->userSchemaGroup ) and
              !empty ( $this->userSchema )     and
              isset ($_REQUEST['submitQuery']) and
              $_REQUEST['submitQuery'] == 'Submit Query' ) {

              #$resultSet = $QueryHandle->executeUserQuery( $errorString );
              $this->action = QueryHandleClass::ACTION_EXECUTE_QUERY;
              return;
        }
        // Determine if a quick download is requested.
        else if ( isset ($_REQUEST['submitDownload']) ) {
          $this->action = QueryHandleClass::ACTION_DOWNLOAD_RESULTS;
          return;
        }
        // Determine if a query file upload is requested.
        else if ( isset ($_REQUEST['submitUpload']) ) {
          $this->action = QueryHandleClass::ACTION_UPLOAD_QUERY_FILE;
          return;
        }
        // Determine if load example is requested.
        else if ( isset ($_REQUEST['submitQueryExample']) ) {
          $this->action = QueryHandleClass::ACTION_SHOW_QUERY_EXAMPLE;
          return;
        }
        // Determine if load example is requested.
        else if ( isset ($_REQUEST['ajaxAction']) ) {
          $this->action = QueryHandleClass::ACTION_DO_AJAX;
          return;
        }
        else if ( isset ($_REQUEST['loadQueryAction']) ) {
         $this->action = QueryHandleClass::ACTION_LOAD_QUERY;
         return;
        }
        else
           $this->action = QueryHandleClass::ACTION_DO_NOTHING;
    } //initQueryFormVariables

    /**
    * Rewrites a slow query so that the 'select into' is added so that
    * results are sent to the mydb.
    *
    * @param query The query string to be rewritten
    * @return rewritten query
    ************************************************/
    public function rewriteSlowQuery( $query ) {

        // Clean up query first
        $query = trim( $query );

        // If the query is of the form "INTO mydb.*",
        // then skip it.
       if (preg_match ('/(INTO\s+mydb\.)/Ui',$query))
            return $query;

        // If the query is of the form "SELECT * FROM *",
        // then rewrite it as "SELECT * INTO mydb.tempN FROM *"
        // (adding the INTO clause).
        //if (preg_match('/^([\s\S]*SELECT[\s\S]+)(FROM[\s\S]+)$/i',$query, $matches)) {
        // use /U to make regular expression match non-greedy.
        if (preg_match('/^([\s\S]*SELECT[\s\S]+)(FROM[\s\S]+)$/iU',$query, $matches)) {
            $selectClause = $matches[1];
            $fromClause   = $matches[2];
            $mydbtable = self::getNextAvailMyDbTableName ( $this->userMyDbTable );
            $query = $selectClause ." INTO mydb.[".$mydbtable."] $fromClause";
        }
        return $query;
    } // function rewriteSlowQuery

    /**
    * Rewrites a slow query so that the 'select into' is added so that
    * results are sent to the mydb.
    *
    * @param  myDbTableBase The base name of the myDB Table
    * @return mydb Table Name
    ************************************************/
    private function getNextAvailMyDbTableName ( $myDbTableBase ) {

        // Query CasJobs to find out which tables are present in the current
        // user's MyDB database.
        $myDBTableList = array();
        try {
          $jobsSOAPClient = new SoapClient( $this->PSISession->getJobsService(), array('exceptions' => TRUE) );
          $query = <<< EOF
                    SELECT TABLE_NAME
                    FROM   INFORMATION_SCHEMA.TABLES
                    WHERE  TABLE_TYPE='BASE TABLE'
                    ORDER BY TABLE_NAME
EOF;
          $parameters =   array ('sessionID'   => $this->PSISession->getSessionID(),
                                   'schemaGroup' => $this->PSISession->getDefaultPSPSSchemaGroup(),
                                   'context'     => 'MyDB',
                                   'query'       => $query,
                                   'taskname'   =>  'Next Available DB',
                                   'isSystem'   =>  1);
          $resultSet = $jobsSOAPClient->executeQuickJob ($parameters);
        }
        catch (SoapFault $soapFault) {
            $this->PSISession->showUserServerError();
            die ( "Error calling executeQuickJob from Jobs Service() regarding mydbs, (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }

        foreach ($resultSet as $key => $val) {
            // Split the query results using "\n".
            $tableNames = explode ("\n", $val);

            foreach ($tableNames as $key => $aTableName) {
                // Skip the column title.
                if ($aTableName == '[TABLE_NAME]:String')
                    continue;
                // All of these table names are surrounded by double quotes,
                // which must be stripped off!
                array_push($myDBTableList, preg_replace('/^"([^"]+)"$/', '$1', $aTableName) );
            } //foreach $aTableName
        } // foreach $val

        //If the table is not in the array return it.
        if ( !in_array( $myDbTableBase, $myDBTableList )) {
          return $myDbTableBase;
        }
        else {
          //find a new myDB table name
          for ($i = 1; $i <= 99; $i++) {
            $newTableName = sprintf ("%s_%02d", $myDbTableBase, $i);
            if ( !in_array( $newTableName, $myDBTableList ))
                return $newTableName;
          } #for
        } #else

        die ("getNextAvailMyDbTableName($myDbTableBase) failed!");
    } // function getNextAvailMyDbTableName

    /**
    * Translates some queries that might be different
    * between MySQL and MS-SQL Server (i.e., show tables )
    *
    * @param query The query to translate
    * @return Traslated Query
    */
    private function TranslateQuery( $query ) {
        $dbType = $this->schemasHash[$this->userSchemaGroup][$this->userSchema]['Type'];
        if ( preg_match( "/mysql/i", $dbType) ) return $query;

        //Clean up query first
        // Trim leading and trailing whitespace.
        $query = trim( $query );
        // If the user entered a "SHOW TABLES" or "DESCRIBE <table>" command
        // for a database which doesn't support MySQL, then convert it into
        // the MS-SQL equivalent.
        $showTablesPattern = preg_match ("/^(\s*)SHOW(\s+)TABLES(\s*)(;?)$/i", $query);
        $showViewsPattern = preg_match ("/^(\s*)SHOW(\s+)VIEWS(\s*)(;?)$/i", $query);
        $describeTablePattern = preg_match ("/^(\s*)DESCRIBE(\s+)([a-zA-Z][a-zA-Z0-9_]*)(\s*)(;?)$/i", $query, $matches);
        if ($describeTablePattern) $describeTablePattern = $matches[3];

        if ($showTablesPattern or $describeTablePattern or $showViewsPattern) {
          if ( preg_match( "/postgres/i", $dbType) ) {
            if ($showTablesPattern) {
              $query = "SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name";
            }
            else if ($describeTablePattern) {
              $query = "SELECT column_name, data_type,  column_default, is_nullable
                        FROM information_schema.columns
                        WHERE table_name ='$describeTablePattern'";
            }
            else if ( $showViewsPattern ) {
              $query = "SELECT table_name FROM information_schema.views
                        WHERE table_schema='public' ORDER BY table_name";
            }
          }
          else if ( preg_match("/MS\s?SQL/i",$dbType) ) { // default: mssql
            if ($showTablesPattern) {
                        $query = <<< EOF
                        SELECT TABLE_NAME
                        FROM INFORMATION_SCHEMA.TABLES
                        WHERE TABLE_TYPE='BASE TABLE'
                        ORDER BY TABLE_NAME
EOF;
            } // if ($showTables)
            if ($describeTablePattern) {
                        $query = <<< EOF
                        SELECT column_name, data_type,  column_default, is_nullable
                        FROM   information_schema.tables AS t
                        JOIN   information_schema.columns AS c
                               ON  t.table_catalog=c.table_catalog
                               AND t.table_schema=c.table_schema
                               AND t.table_name=c.table_name
                        WHERE  t.table_name='$describeTablePattern'
EOF;
            } // if ($describeTablePattern)
            if ( $showViewsPattern ) {
                        $query = <<< EOF
                        SELECT VIEW_NAME = TABLE_NAME
                        FROM INFORMATION_SCHEMA.TABLES
                        WHERE TABLE_TYPE='VIEW'
                        ORDER BY TABLE_NAME
EOF;
            } // if ($showViewsPattern)
          } // if mssql
        } // if ($showTables or $describeTable)
        return $query;
    } #TranslateQuery

     /**
    * Translates some queries that might be different
    * between MySQL, PostgreSQL and MS-SQL Server (i.e., show tables )
    *
    * @param query The query to be parsed for syntax checking
    * @return Nothing, changes query attribute
    */
    private function addSyntaxCheck2Query( $query ) {

        $dbType = $this->schemasHash[$this->userSchemaGroup][$this->userSchema]['Type'];

        // My-SQL Add
        if ( isset ($dbType) and $dbType == 'mysql' ) {
            $query = "EXPLAIN \n".$query;
        }
        else if ( isset ($dbType) and $dbType == 'postgresql' ) {
            $query = "EXPLAIN \n".$query;
        }
        // MS-SQL Server add
        else {
            $query = "SET PARSEONLY ON \n".$query;
        }
        // Assign the changed query
        return $query;
    } //addSyntax2Query

    /**
    * Steams a quick query to the user as a file.
    *
    * @param errorString The error fault string passed by reference
    * @return Nothing, succ
    ************************************************/
    public function handleQueryDownload ( &$errorString ) {

        // Assign locally as it makes the syntax a little cleaner
        $query       = $this->userQuery;
        $schemaGroup = $this->userSchemaGroup;
        $schema     = $this->userSchema;
        $fileName = 'panstarrs'; // Assign defaults
        $fileFormat = 'CSV';

        if ( isset( $this->downloadFilename ) &&  !empty( $this->downloadFilename ))
            $fileName = $this->downloadFilename;

        if ( isset( $this->downloadFileFormat ) && !empty( $this->downloadFileFormat ) )
            $fileFormat = $this->downloadFileFormat;

        // Case the file type is spoofed and PSI cannot accomodate to it.
        if (!array_key_exists( $fileFormat, self::getDownloadFileFormats()))
            return;

        // Case we don't have all the variables needed to stream a file
        if (empty ($query) or empty ($schemaGroup) or empty ($schema))
            return;

        $resultSet = self::executeFastQuery( $errorString );

        // Exit if query fails
        if ( isset( $errorString ) ) return;
        // Add extension if needed
        if ( !preg_match("/\.$fileFormat$/i", $fileName) ) {
          // Output the header for this CSV download.
          $extension = strtolower (substr ($fileFormat, 0, 3));
          # Exception for FITS
          if ( $extension == 'fit' ) { } // $extension .= 's';
          $fileName = "$fileName.$extension";
        }
        header('Pragma: public');
        header('Last-Modified: '. gmdate ('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');
        header('Content-Transfer-Encoding: none');
        // This should work for IE & Opera
        header("Content-Type: application/octetstream; name=\"$fileName\"");
        // This should work for the rest
        header("Content-Type: application/octet-stream; name=\"$fileName\"");
        header("Content-Disposition: inline; filename=\"$fileName\"");


        // Parse $resultSet.
        // Note: $resultSet's only key is 'return'.
        foreach ($resultSet as $key => $val) {

            // Split the query results using "\n".
            $queryResult = explode ("\n", $val);
            $val = NULL; // Release the memory used by $val.
            // Count the number of rows returned.
            $numRows = count ($queryResult);

            // HACK uploads to myDB do not like Byte[] string in headers
            $queryResult[0] = preg_replace ('/\[(\w+)\]:BYTE\[\]/i', '[$1]:BYTE', $queryResult[0]);

            // Get rid of datatypes in headers (i.e., [objid]:bigint becomes objid)
            // Only exception is Comma Seperated Values with Types
            if ( $fileFormat != 'TCSV' )
              $queryResult[0] = preg_replace ('/\[(\w+)\]:\w+/', '$1', $queryResult[0]);


            if ( $fileFormat == 'CSV' || $fileFormat == 'TCSV' ) {
                // Output the Comma Separated Values with the rows terminated
                // by carriage return/line feed.
                for ($i = 0; $i < $numRows; $i++) {
                  // Get rid of data type in headers
                  print $queryResult[$i]."\r\n";
                }
            }
            else if ( $fileFormat == 'TST' ) {
                // Output the Tab Separated Table data with the rows terminated
                // by carriage return/line feed.
                foreach ($queryResult as $key => $aLine) {
                    // Get rid of data type in headers
                    print join ("\t", explode (',', $aLine));
                    print "\r\n";
                }
            }
            else {
                $pid = getmypid ();
                $tmpFileName = "/tmp/psi-$pid.csv";
                $outFileName = "/tmp/psi-$pid.$extension";
                $tmpFile = fopen ($tmpFileName, "w");
                if (!$tmpFile)
                    die ("Unable to open '$tmpFileName' for writing!");

                // Output the Comma Separated Values with the rows terminated
                // by carriage return/line feed.
                foreach ($queryResult as $key => $aLine) {
                    fwrite ($tmpFile, $aLine);
                    fwrite ($tmpFile, "\r\n");
                }
                $queryResult = NULL;   // Release the memory used by $queryResult.
                if (!fclose ($tmpFile))
                    die ("Unable to close '$tmpFileName'!");

                // Convert from CSV to the requested format using stilts.jar.
                $ofmt = strtolower ($fileFormat);
                $options = "ifmt=csv in=$tmpFileName ofmt=$ofmt out=$outFileName cmd=\"tablename psi\"";
                $stilts = $this->PSISession->getStiltsJar();
                `java -jar $stilts tpipe $options`;
                if (!unlink ($tmpFileName))
                    die ("Unable to delete tmp file '$tmpFileName'!");

                // Output the converted data.
                $outFile = fopen ($outFileName, "r");
                if (!$outFile)
                    die ("Unable to open '$outFileName' for reading!");
                while (!feof ($outFile))
                {
                    print fread ($outFile, 32768);
                }
                if (!fclose ($outFile))
                    die ("Unable to close '$outFileName'!");
                if (!unlink ($outFileName))
                    die ("Unable to delete tmp file '$outFileName'!");
            }
        } // foreach $val
        exit;
    } // function handleDownloadQuery()


    /**
    * Upload a query file, put the content into Query textbox.
    *
    * @param  uploadErrorString The error fault string passed by reference
    * @return none
    */
    public function handleUploadQueryFile( &$uploadErrorString ) {

      $filename = '';
      $uploadErrorString = '';
      $MAX_FILE_SIZE = 102400; // In Byte.

      # Get file contents remember form MUST BE POST action for this to work!
      if ($_FILES["fileUpload"]["error"] > 0) {
        $uploadErrorString = self::fileUploadErrorMessage( $_FILES["fileUpload"]["error"] );
      }
      else {
        # Get the file details
        $fileName = $_FILES['fileUpload']['name'];
        $fileType =  $_FILES['fileUpload']['type'];
        $fileSize = $_FILES['fileUpload']['size'];
        # Check for errors based on size and file type
        if ( !(preg_match( '/^text/i', $fileType ) || preg_match( '/sql/i', $fileType ) ) ) {
            $uploadErrorString = "File must be in a some kind of text format.  The file $fileName is $fileType type.";
        }
        else if ( $fileSize > $MAX_FILE_SIZE ) {
            $uploadErrorString = "File max size (" . ($MAX_FILE_SIZE / 1024) . " KB) exceeded.The file $fileName size is ".($fileSize / 1024)." KB.";
        }
        else {
          $this->userQuery = file_get_contents( $_FILES['fileUpload']['tmp_name'] );
        }
      } # else
      if ( $uploadErrorString != '' ) {
          $uploadErrorString = "<br/><font color=\"red\">Upload Error: $uploadErrorString</font>";
      }
      // Assign hidden values to maintain state within the query form.
      if ( isset( $_REQUEST['hiddenSchema'] )) {
        list ( $this->userSchemaGroup, $this->userSchema) = preg_split("/[\|]/", $_REQUEST['hiddenSchema']);
      }
      if ( isset( $_REQUEST['hiddenQueue'] )) {
        $this->userQueue = $_REQUEST['hiddenQueue'];
      }
      if ( isset( $_REQUEST['hiddenMyDbTable'] ) ) {
        $this->userMyDbTable = $_REQUEST['hiddenMyDbTable'];
      }
  } #handleUploadQueryFile

  /**
    * Sets the query page to have values for a particular example
    * @param showExampleQueryErrorString - Error string in case example error cannot be found.
    * @return none
    */
    public function handleShowQueryExample( &$showExampleQueryErrorString ) {
      #Get the help object
      $PSIHelp = $this->PSISession->getHelpObject();
      $queryExamplesHash = $PSIHelp->getQueryExamplesHash();
      $showExampleQueryErrorString = '';
      $title = '';
      $this->userQueryExampleHash = array();

      if ( !empty( $this->selectExample ) ) {
        $showExampleQueryErrorString = "<br/><font color=\"red\">Error, no example selected.</font>";
      }
      foreach ( $queryExamplesHash as $section => $queryHash ) {
        foreach ( $queryHash as $title => $query ) {
          if ( $this->selectExample == $section."_".$title ) {
            $this->userQuery = trim($queryHash[$title]['sql']);
            // Added exmaple to query name
            $this->userQueryName = 'PSI Example';
            $this->userSchema = $queryHash[$title]['database'];
            $this->userQueue = $queryHash[$title]['queue'];
            $this->userQueryExampleHash = $queryHash[$title];
            return;
          }
        }
      }
      if ( empty( $this->userQueryExampleHash )) {
          $showExampleQueryErrorString = "<br/><font color=\"red\">Error, could not find example: $title.</font>";
      }
    } #handleShowQueryExample


    /**
    * Gets the information about a query based on the jobsID
    * @param jobID - The id of the previously execute query
    * @return none
    */
    public function handleLoadQuery( $jobID ) {
      $job = array(); # Array to hold the SOAP call results

        try {
            // SOAP Client Object used to make SOAP calls to the DRL Jobs Service.
            //print "service:".$this->PSISession->getJobsService();
            $jobsSOAPClient = new SoapClient ( $this->PSISession->getJobsService()); //, array('cache_wsdl' => WSDL_CACHE_NONE) ); ///////
            $result = $jobsSOAPClient->getJobsInRange
                (array ('sessionID'   => $this->PSISession->getsessionID(),
                        'schemaGroup' => $this->PSISession->getDefaultPSPSSchemaGroup(),
                        'rangeStart'  => 1,
                        'rangeEnd'    => 2,
                        'conditions'  => "JobID:$jobID;" ));
        }
        catch (SoapFault $soapFault) {
            $this->PSISession->showUserServerError();
            die ( "Error calling getJobsInRange from Jobs Service() regarding mydbs, (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }
        // Assign results into job array
        $job = $result->return[0];
        // Assign local variables from previous job
        // Assign schema
        $this->userSchema = $job->Target;

        // Figure out the schemaGroup
        foreach ( $this->schemaGroups as $schemaGroup ) {
            $schemas = self::getSchemas( $schemaGroup );
            foreach ( $schemas as $schema ) {
              if ( $schema == $this->userSchema ) {
                $this->userSchemaGroup = $schemaGroup;
                break;
              }
            }
        }
        $this->userQuery = trim($job->Query);
        $this->userQueryName = $job->TaskName;
        $queue = $job->Context;
        // HACK We don't know why or how, but CasJobs gives a number for the context.
        if ($queue ==   '1') $queue = 'fast';
        if ( $queue == '500' || $queue == '4320') $queue = 'slow';
        $this->userQueue = $queue;
    } # handleLoadQuery

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
}//QueryHandleClass
