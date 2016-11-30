<?php
/**
 * @class PSPSSchemaClass
 * The PSPS Schema Class is meant to store values from a number of forms to build a query
 * base on PSPS schema information.  It is also capable of show tables, columns, etc
 * with the PSPS Schema.
 *
 * @version Beta
 * GPL version 3 or any later version.
 * copyleft 2010 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Conrad Holmberg, Xin Chen
 * @since Beta version 2010
 */
class PSPSSchemaClass
{

    // Variables here contain all the needed info for PSI such as tables, columns, filters, etc.
    private $PSISession; // PSISession Contains all the needed variables like sessionID and default schema to execute the needed query.
    private $jobsSOAPClient; // SOAP instance that handles the Jobs Web Service to the DRL.
    private $tableHash = array();
    private $tableColumnsHash = array();
    private $tableTypePriorityHash = array();
    private $tablePriorityHash = array();
    #private $surveyHash = array();
    private $astroFilterHash = array();
    private $flagTables = array();  // Used for storing flags from a particular table
    private $flagTablesHash = array(); // Stores all details of a flag table (name, value, description)
    // Form variables
    private $selectFlagTable; // Flags that user has selected (Seperate from query builder)
    // All the variables here contain form values.  Naming convention is formtype|description
    // The following variables are accessed by reflection (PHP "overloading"); the names must be entirely lower case.
    private $selectColumnViewFormat;
    private $selectSurvey;
    private $checkAstroFilterIDs = array();
    private $checkTables = array();
    private $checkTableColumnsHash = array();
    private $formTableColumnFilterHash = array(); // Very important for query filters ( i.e., X > 0 AND X < 100 etc )
    private $radioSpacialConstraint;
    private $textBoxRa;
    private $textBoxDec;
    private $textBoxSize;
    private $selectBoxUnits;
    private $textConeRa;
    private $textConeDec;
    private $textConeRadius;
    private $selectConeUnits;
    private $selectRowLimit;

    //! SQL Query constants
    const SURVEY_QUERY =  'SELECT surveyID, name, description FROM survey ORDER BY surveyID'; //! Survey Query
    const ASTRO_FILTER_QUERY = "SELECT filterID, filterType FROM filter ORDER BY filterID"; //! Telescope Filters Query

     /**
    * Constructor that assigns all the schema information
    *
    * @param  $this->PSISession - Environment variables
    */
    public function __construct( $PSISession )
    {
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
            self::init();
        }
        else {
            error_log("Cannot constuct PSPSSchemaClass instance.  PSISession object is NULL.");
            exit();
        }

    } //__construct


    /**
    * initilzation of need variables on called on constructor
    *
    * @return nothing
    */
    private function init() {
        // Set environment variables MUST BE DONE IN THIS ORDER!
        self::initDefaultFormValues(); // Assign default values to forms
        self::initTablePriorityHash();
        self::initTableTypePriorityHash();
        self::initTableHash(); // assign tables.
        self::initTableColumnsHash(); // assign hash with column details.
        #self::initSurveyHash(); // Assign Surveys TODO Place this back in when surveys are merged.
        self::initAstroFilterHash(); // Assign Filters
        self::initQualityFlags(); // assign hash with column details for all quality flag tables.
    } #init
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
         die ( "Unkown method call in PSPSSchemaClass: $method.  ".
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
    } //__call

    /**
    * Executes a fast query to the DRL for PSPS Schema purposes
    * between MySQL and MS-SQL Server (i.e., show tables )
    *
    * @param query The string that contains the sql query to be executed to the Default PSPS database.
    * @param  errorString The error fault string passed by reference
    * @return resultSet
    */
    public function executePSPSSchemaQuery( $query, $taskname ) {

        $resultSet = NULL;

        try {
            $resultSet = $this->jobsSOAPClient->executeQuickJob (
                                   array ('sessionID'   => $this->PSISession->getSessionID(),
                                          'schemaGroup' => $this->PSISession->getDefaultPSPSSchemaGroup(),
                                          'context'     => $this->PSISession->getDefaultPSPSSchema(),
                                          'query'       => $query,
                                          'taskname'   =>  $taskname,
                                          'isSystem'   =>  1  ) );
        }
        catch (SoapFault $soapFault) {
            $this->PSISession->showUserServerError();
            die ( "Error with SOAP call executeQuickJob in PSPS Schema Class, (faultcode: {$soapFault->faultcode},".
                  "faultstring: {$soapFault->faultstring})\nQuery: $query");
            return;
        }
        return ( $resultSet );
    } #executeMyDBQuery

    /**
    * Assigns any default values to form variables
    *
    * @return nothing
    */
    private function initDefaultFormValues() {

        $this->selectSurvey = 0;
        $this->selectColumnViewFormat = 'short';
        $this->radioSpacialConstraint = 'None';  // Default spacial constraint
        $this->textBoxRa = '';
        $this->textBoxDec = '';
        $this->textBoxSize = '';
        $this->selectBoxUnits = 'box_arcsec';
        $this->textConeRa = '';
        $this->textConeDec = '';
        $this->textConeRadius = '';
        $this->selectConeUnits = 'cone_arcsec';
        $this->selectRowLimit = '100';
    } //initDefaultFormValues

    /**
    * Query for the astronomical surveys and assign it to a global hash with ID, Name, and Description
    *
    * @return nothing
    */
    private function initSurveyHash() {

      $resultSet = self::executePSPSSchemaQuery (self::SURVEY_QUERY, 'PSPS Schema Survey Query');

      foreach ($resultSet as $key => $val) {
        // Split the query results using "\n".
        $queryResult = explode ("\n", $val);

        // Count the number of rows returned.
        $numRows = count ($queryResult);

        // Split each row value using "," and display the data.
        for ($row = 1; $row < $numRows; $row++) {
          $schemaData = explode (",", $queryResult[$row] );
          # Assing the columns SurveyID, Name. Description without double quotes for strings
          $this->surveyHash[ $schemaData[0] ] = array ( "Name" => preg_replace( '/^\"|\"$/', '', $schemaData[1]),
                                                        "Description" => preg_replace( '/^\"|\"$/', '',$schemaData[2] )
                                                       );
        } //for $row
      } // foreach $val
    } //initSurveyHash

    /**
    * Query for the filters and assign it to a global hash with ID and Name
    *
    * @return nothing
    */
    private function initAstroFilterHash() {

//       $resultSet = self::executePSPSSchemaQuery ( self::ASTRO_FILTER_QUERY, 'PSPS Schema Filter Query' );
// 
//       foreach ($resultSet as $key => $val) {
//         // Split the query results using "\n".
//         $queryResult = explode ("\n", $val);
// 
//         // Count the number of rows returned.
//         $numRows = count ($queryResult);
// 
//         // Split each row value using "," and display the data.
//         for ($row = 1; $row < $numRows; $row++) {
//           $schemaData = explode (",", $queryResult[$row] );
//           # Assing the filter ID with the letter without double quotes
//           $this->astroFilterHash[ $schemaData[0] ] = preg_replace( '/^\"|\"$/', '', $schemaData[1]);
//         } //for $row
//       } // foreach $val
       // HACK This will probably never change so hard code it for now.
       $this->astroFilterHash["1"] = "g";
       $this->astroFilterHash["2"] = "r";
       $this->astroFilterHash["3"] = "i";
       $this->astroFilterHash["4"] = "z";
       $this->astroFilterHash["5"] = "y";
    } //initAstroFilterHash

    /**
    * Query for the list order priority of tables and assign to a Hash.
    * 
    * @return nothing
    * @By: Xin Chen. 11/27/2015
    */
    private function initTablePriorityHash() {
      $resultSet = self::executePSPSSchemaQuery ( 
          "EXEC ListPSPSSchema_TablePriority", 
          "PSPS Schema ListPSPSSchema_TablePriority Query" );

      foreach ($resultSet as $key => $val) {
        $queryResult = explode ("\n", $val); // Split the query results using "\n".
        $numRows = count ($queryResult); // Count the number of rows returned.

        for ($row = 1; $row < $numRows; $row++) {
          $fields = explode(",", $queryResult[$row]);
          //print $queryResult[$row] . ":" . $fields[0] . "->" . $fields[1] . "..<br/>";
          $this->tablePriorityHash[$fields[0]] = (int) $fields[1];
        }
      }

      //print "<hr/>"; var_dump($this->tablePriorityHash);
    }

    /**
    * Query for the list order priority of tables and assign to a Hash.
    *
    * @return nothing
    * @By: Xin Chen. 11/27/2015
    */
    private function initTableTypePriorityHash() {
      $resultSet = self::executePSPSSchemaQuery ( 
          "EXEC ListPSPSSchema_TableTypePriority", 
          "PSPS Schema ListPSPSSchema_TableTypePriority Query" );

      foreach ($resultSet as $key => $val) {
        $queryResult = explode ("\n", $val); // Split the query results using "\n".
        $numRows = count ($queryResult); // Count the number of rows returned.

        for ($row = 1; $row < $numRows; $row++) {
          $fields = explode(",", $queryResult[$row]);
          //print $queryResult[$row] . ":" . $fields[0] . "->" . $fields[1] . "..<br/>";
          $this->tableTypePriorityHash[$fields[0]] = (int) $fields[1];
        }
      }

      //print "<hr/>"; var_dump($this->tableTypePriorityHash);
    }

    /**
    * Query for the tables and assign it to a Hash.
    *
    * @return nothing
    * @By: Xin Chen. 1/19/2011
    */
    private function initTableHash() {
      $resultSet = self::executePSPSSchemaQuery ( 
          "EXEC ListExtendedProperty_Table", 
          "PSPS Schema ListExtendedProperty_Table Query" );

      $table; $name; $value; $table_prev = "";
      $type; $desc; $alias;
      $this->tableHash = array ();

      foreach ($resultSet as $key => $val) {
        $queryResult = explode ("\n", $val); // Split the query results using "\n".
        $numRows = count ($queryResult); // Count the number of rows returned.

        for ($row = 1; $row < $numRows; $row++) {
          $schemaData = explode (",", $queryResult[$row] );
          $table = preg_replace( '/^\"|\"$/', '', $schemaData[1] );
          $name = preg_replace( '/^\"|\"$/', '', $schemaData[2] );
          $value = PSIHTMLGoodiesClass::decode( $schemaData[3] );

          if ($row == 1) { $table_prev = $table; }
          if ($table != $table_prev) {
            //print "--set hash for $table_prev: $desc, $alias, $type--<br>";
            $this->tableHash[$table_prev] = array ( "Description" => $desc,
                                                    "Alias" => $alias,
                                                    "Type" => $type,
                                                    "Name" => $table_prev
                                                  );
             // Add flag tables
             if ( preg_match('/Flags/i', $table_prev))
              array_push($this->flagTables,  $table_prev);
             $table_prev = $table;
             $desc = $alias = $type = "";

          } #if

          if ($name == "Type") { $type = $value; }
          elseif ($name == "Description") { $desc = $value; }
          elseif ($name == "Alias") { $alias = $value; }
        } //for $row

        if ($row == $numRows) { // the last one.
          //print "--set hash for $table_prev: $desc, $alias, $type--<br>";
          $this->tableHash[$table_prev] = array ( "Description" => $desc,
                                                  "Alias" => $alias,
                                                  "Type" => $type,
                                                  "Name" => $table_prev
                                                );
          // Add flag tables
          if ( preg_match('/Flags?$/i', $table_prev))
            array_push($this->flagTables,  $table_prev);
        }
      } // foreach $val
      uasort($this->tableHash, array($this, 'sort_compare_type'));
    } // initTableHash


    /**
    * Sets a specific order of PSPS Schema Tables
    *
    * @return: Table Hash in a particular order: first by type, then by name.
    * @By: Xin Chen. 3/2/2011
    */
    private function sort_compare_type($a, $b) {
        // This is old way of sort. 
        // Order table types by hardcoded values in a local function getTypePriority.
        // Order tables alphabetically.
        //$cmp = $this->getTypePriority($a['Type']) - $this->getTypePriority($b['Type']);
        //if ($cmp != 0) return $cmp;
        //return strnatcmp($a['Name'], $b['Name']);

        // New way of sort. 11/27/2015.
        // Order both table types and tables by priority defined in WMD.
        //print $a['Type'] . " := " . $this->tableTypePriorityHash[$a['Type']] . " - " .
        //      $b['Type'] . " := " . $this->tableTypePriorityHash[$b['Type']] . "<br/>";
        $cmp = $this->tableTypePriorityHash[$a['Type']] - $this->tableTypePriorityHash[$b['Type']];
        if ($cmp != 0) return $cmp;
        return $this->tablePriorityHash[$a['Name']] - $this->tablePriorityHash[$b['Name']];
    }

    /**
    * The artificial order of table types.
    * This is deprecated as of 11/27/2015 when new sort order is defined. See sort_compare_type().
    *
    * @return: type priority number.
    * @By: Xin Chen. 3/2/2011
    */
/*
    private function getTypePriority($t) {
      $p = 100;
      $t = strtolower($t);
      if ($t == "fundamental ipp data products") { $p = 1; }
      elseif ($t == "derived data products") { $p = 2; }
      elseif ($t == "observational metadata") { $p = 3; }
      elseif ($t == "system metadata") { $p = 4; }
      elseif ($t == "internal tables") { $p = 5; }
      elseif ($t == "table views") { $p = 6; }
      return $p;
    }
*/

    /**
    * Query for the Columns and assign it to a Hash.
    *
    * @return nothing
    * @By: Xin Chen. 1/19/2011
    */
    private function initTableColumnsHash() {
        $debug = 0;
        $resultSet = self::executePSPSSchemaQuery ( 
            "EXEC ListExtendedProperty_Column", 
            "PSPS Schema ListExtendedProperty_Column Query" );

        $table; $colname; $name; $value; $colname_prev = ""; $table_prev = "";
        $datatype = ""; $unit = ""; $desc = ""; $size = ""; $default = ""; $check = "";
        //print_r( $resultSet );
        foreach ($resultSet as $key => $val) {
          $queryResult = explode ("\n", $val); // Split the query results using "\n".
          $numRows = count ($queryResult); // Count the number of rows returned.
          $columnsHash = array();

          for ($row = 1; $row < $numRows; $row++) {
                $schemaData = explode (",", $queryResult[$row] );
                $table = preg_replace( '/^\"|\"$/', '', $schemaData[0] );
                $colname = preg_replace( '/^\"|\"$/', '', $schemaData[1] );
                $name = preg_replace( '/^\"|\"$/', '', $schemaData[2] );
                $value = PSIHTMLGoodiesClass::decode( $schemaData[3] );
                //$len = sizeof($schemaData);
                //for ($i = 4; $i < $len; $i ++) { $value .= "," . $schemaData[$i]; }

                if ($row == 1) { $colname_prev = $colname; $table_prev = $table; }

                // colnames don't match, or equal but tablenames don't match (so cols are in different tables)
                if ($colname != $colname_prev || $table_prev != $table) {
                    if($debug) print "--set hash for $colname_prev: $datatype, $unit, $desc, $size, $default, $check--<br>";
                    $columnsHash[$colname_prev] = array (
                        "DataType" => $datatype,
                        "Unit" => $unit,
                        "Description" => $desc,
                        "Size" => $size,
                        "Default" => $default,
                        "Check" => $check
                    );
                    $colname_prev = $colname;
                    $datatype = $unit = $desc = $size = $default = $check = "";
                }
                if ($table_prev != $table) {
                    if($debug) print "==set hash for $table_prev==<br>";
                    $this->tableColumnsHash[$table_prev] = $columnsHash;
                    $table_prev = $table;
                    $columnsHash = array();
                }

                if ($name == "DataType") { $datatype = $value; }
                elseif ($name == "Unit") { $unit = $value; }
                elseif ($name == "Description") { $desc = $value; }
                elseif ($name == "Size") { $size = $value; }
                elseif ($name == "Default") { $default = $value; }
                elseif ($name == "Check") { $check = $value; }
          } //for $row

          if ($row == $numRows) { // the last one.
                if($debug) print "--set hash for $colname_prev: $datatype, $unit, $desc, $size, $default, $check--<br>";
                $columnsHash[$colname_prev] = array (
                    "DataType" => $datatype,
                    "Unit" => $unit,
                    "Description" => $desc,
                    "Size" => $size,
                    "Default" => $default,
                    "Check" => $check
                );
                if($debug) print "==set hash for $table_prev==<br>";
                // Note that if columns of a table are not consecutive, this would
                // only include the last chunk of columns and make output incomplete.
                // So the sql query should use "order by table_name".
                $this->tableColumnsHash[$table_prev] = $columnsHash;
          }
      } // foreach $val
    } // initTableColumnsHash

    /**
    * finds all the quality flags values in all tables
    *
    * @return nothing
    */
    private function initQualityFlags() {

      # Execute query to get quality flag tables from schema
      $resultSet = self::executePSPSSchemaQuery( self::getQualityFlagQuery(), 'PSPS Schema Quality Flags Query');

      //if ( isset( $resultSet ) ) {
      if ( !isset( $errorString ) ) {
        $tmpFlagTableName = ''; # used for identifying change in FlagTable column
        $tmpFlagTableHash = array();
        # indexs for positions in query
        $flagTableNameIndex;
        $flagNameIndex;
        $flagDescriptionIndex;
        $flagValueIndex;

        foreach ($resultSet as $key => $val) {
          $queryResult = explode ("\n", $val); // Split the query results using "\n".
          // Add each image flag table
          $resultHeaders = explode (",", $queryResult[0] ); #split the column for just the headers.
          # get postions in the row of the needed column in case some mucks with the sql.
          for ( $i = 0; $i < count($resultHeaders); $i++ ) {
            if ( preg_match('/FlagTable/i', $resultHeaders[$i]) || preg_match('/TableName/i', $resultHeaders[$i]) )
              $flagTableNameIndex = $i;
            else if ( preg_match('/Name/i', $resultHeaders[$i]) )
              $flagNameIndex = $i;
            else if ( preg_match('/Value/i', $resultHeaders[$i]) )
              $flagValueIndex = $i;
            else if ( preg_match('/Description/i', $resultHeaders[$i]) )
              $flagDescriptionIndex = $i;
          } #for
          for ($row = 1; $row < count($queryResult); $row++) {
            $resultData = explode (",", $queryResult[$row] ); #split the column for each row
            #assign value to each column
            $flagTableName = preg_replace( '/^\"|\"$/', '', $resultData[$flagTableNameIndex]);
            $flagName = preg_replace( '/^\"|\"$/', '', $resultData[$flagNameIndex]);
            $flagValue =  $resultData[$flagValueIndex];
            $flagDescription = preg_replace( '/^\"|\"$/', '', $resultData[$flagDescriptionIndex]);

            if (empty($tmpFlagTableName)) {
              $tmpFlagTableName = $flagTableName;
            }

            if ( strcmp($tmpFlagTableName, $flagTableName) != 0) {
            # Add any schemas in that group to the hash and reset
              $this->flagTablesHash[$tmpFlagTableName] = $tmpFlagTableHash;
              $tmpFlagTableHash = array();
              $tmpFlagTableName = $flagTableName;
            }
            array_push( $tmpFlagTableHash , array (  'Name' => $flagName,
                                                     'Value' => $flagValue,
                                                     'Description' => $flagDescription ));
          } #for row
          $this->flagTablesHash[$flagTableName] = $tmpFlagTableHash; #push last table in
        } #foreach
      } #if
      else
         echo "<font color=\"red\">Unable to find Quality Flag table(s) due to error: $errorString.</font><br/><br/>\n";
    } #initQualityFlags()
    /**
    * returns a query that would gives columsn for every quality flag table
    *
    *
    * @return query needed for geting all quality flags
    */
    private function getQualityFlagQuery () {

      $query = '';
      if ( !empty( $this->flagTables ) ) {
        foreach ( $this->flagTables as $flagTable ) {
          $query .= <<<EOF
                    select
                    (SELECT TABLE_NAME  FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' AND TABLE_NAME = '$flagTable') as FlagTable,
                    $flagTable.name as Name,  $flagTable.value as Value, $flagTable.description as Description
                    from $flagTable
                    union
EOF;
        } #foreach
        $query = preg_replace('/union\s?$/', ' order by FlagTable, Value', $query); #get rid of the last union and repleace with order clause
      } #if

      return $query;

    } //getQualityFlagQuery

    /**
    * Returns hash of all the flags in a table (ie., name, value, desription
    *
    * @param  flagTable the name of the flag table
    * @return Table Hash
    */
    public function getFlagTableHash( $flagTable ) {
       if ( array_key_exists( $flagTable, $this->flagTablesHash ) )
        return $this->flagTablesHash[ $flagTable];
       else
        return NULL;
    } // function getSchemaHash

    /**
    * Returns an array of just table names
    *
    * @return array of table names
    */
    public function getTables() {
        $tables = array();
        foreach($this->tableHash as $table => $value) {
            // Hand off the table name only
            $tables[] = $table;
        }
        return $tables;
    } //getTables

    /**
    * Returns an the hash of a particular table
    *
    * @param  table Table name that the returns the hash containing columns
    * @return Table Hash
    */
    public function getTableHash( $table ) {
        if ( isset( $this->tableHash[$table] ))
            return $this->tableHash[$table];
        else
            return NULL;
    } //getTableHash

    /**
    * Returns an array of just table columns
    *
    * @param  table Table name that the returns the hash containing columns
    * @return Array containing columns of that table
    */
    public function getTableColumns( $table ) {

        $columns = array();
        // Push each Column Name into an array from the Hash and return the array
        if ( isset( $this->tableColumnsHash[$table] ) ) {
            foreach($this->tableColumnsHash[$table] as $columnName => $columnDetails) {
                $columns[] = $columnName;
            }
            return $columns;
        }
        else {
            return NULL;
        }
    } //getTableColumns

    /**
    * Returns the columns as a hash with an array just its details
    *
    * @param  table Table name that the returns the hash containing columns
    * @return Hash containing just the columns
    */
    public function getTableColumnsHash( $table ) {
        if ( isset ( $this->tableColumnsHash[$table] ))
            return ($this->tableColumnsHash[$table]);
        else
            return NULL;
    }

    /**
    * Returns a hash with just the columns and their descriptions
    *
    * @param  table Table name that the returns the hash containing columns
    * @return Hash containing just the columns as key with descriptions as the value
    */
    public function getTableColumnDescriptionsHash( $table ) {

        $columnsHash = array();
        if ( !empty( $this->tableColumnsHash[$table] ) ) {
            foreach( $this->tableColumnsHash[$table] as $columnName => $columnDetails) {
                // Hand off the Column Name first and the Description second.
                $columnsHash[$columnName] = $columnDetails["Description"];
            }
        }
        return $columnsHash;
    } //getTableColumnDescriptionsHash

    /**
    * Sets the checked tables and assigns it to the associated local variable.
    *
    * @param  tables Selected Tables array
    * @return nothing
    */
    public function setCheckTables( $tables = array() ) {
            $this->checkTables =  $tables;
    } //setCheckTableColumns

    /**
    * Sets the checked columns from a table and places it in a nested Hash
    *
    * @param  table Table name that is the key
    * @param columns Column list from a selected table.
    * @return nothing
    */
    public function setCheckTableColumns( $table, $columns = array() ) {
        $this->checkTableColumnsHash[$table] =  $columns;
    } //setCheckTableColumns

    /**
    * ets the checked columns from a table and places it in a nested Hash
    *
    * @param  table Table name
    * @return An array of columns chosen by the user in a web form
    */
    public function getCheckTableColumns( $table ) {
        if ( !empty( $this->checkTableColumnsHash[$table] ) )
            return $this->checkTableColumnsHash[$table];
        else
            return NULL;
    } //getCheckTableColumns

    /**
    * mysql_query() wrapper. takes two arguments. first
    * is the query with '?' placeholders in it. second argument
    * is an array containing the values to substitute in place
    * of the placeholders (in order, of course).
    *
    * @param  query String that holds the query
    * @param  phs array with the variables.
    * @return query with variables inserted
    */
    private function mysql_prepare ($query, $phs = array()) {
        foreach ($phs as $ph) {
            $ph = "'" . mysql_real_escape_string($ph) . "'";
            $query = substr_replace(
                $query, $ph, strpos($query, '?'), 1
            );
        }
        return mysql_query($query);
    } //mysql_prepare

    /**
    * Clears form values
    *
    * @return nothing
    */
    public function clearFormValues () {
        // Reset the values
        self::initDefaultFormValues();
        $this->checkAstroFilterIDs = array();
        $this->checkTables = array();
        $this->selectSurvey = '';
        $this->checkAstroFilterIDs = array();
        $this->checkTables = array();
        $this->checkTableColumnsHash = array();
        $this->formTableColumnFilterHash = array();
    } //clearFormValue

    /**
    * Returns an array of just survey IDs
    *
    * @return array of surveyID numbers
    */
    public function getSurveyIDs() {
        $surveys = array();
        if ( isset( $this->surveyHash )) {
            foreach( $this->surveyHash as $surveyID => $survyDetails ) {
                // Hand off the table name only
                $surveys[] = $surveyID;
            }
        }
        return $surveys;
    } //getSurveyIDs

    /**
    * Returns an the hash of a particular survey
    *
    * @param  surveyID SurveyID that the returns the hash containing the description, name, and schema
    * @return Table Hash
    */
    public function getSurveyHash( $surveyID ) {
        if ( isset( $this->surveyHash[$surveyID] ))
            return $this->surveyHash[$surveyID];
        else
            return NULL;
    } //getTableHash

    /**
    * Returns am array with the selected filter letters
    *
    * @return String with the filters
    */
    public function getCheckAstroFilterLetters() {

        $filterLetters = array();
        $i = 0;
        if ( !empty( $this->checkAstroFilterIDs ) ) {
            foreach ( $this->checkAstroFilterIDs as $filter) {
                $filterLetters[$i] = $this->astroFilterHash[$filter];
                $i++;
            }
        }
        return $filterLetters;
    } //getCheckAstroFilterLetters()

    /**
    * getTableAlias Assigns short names for Tables
    *
    * @param table  table name to get alias from
    * @return alias
    */
    public function getTableAlias( $table ) {
        return $this->tableHash[ $table ]['Alias'];
    } // function getTableAlias()

    /**
    * Builds a query dynamically based on the following form information:
    *   -Selected Astro Filters
    *   -Tables
    *   -Columns
    *   -Filters
    *   -Spacial Constraints
    *
    *
    * @return a nicely formated query :)
    */
    function buildQuery () {
        $select = array();
        $from = array();
        $where = array();
        $join = '';
        $rowLimit = '';

        if (!empty ($this->checkTables)) {
            foreach ($this->checkTables as $table) {
                $columns = self::getCheckTableColumns( $table );
                //go through each column
                if (!empty ($columns)) {
                    //try to join tables
                    if ( count ($this->checkTables) > 1 && $this->checkTables[0] != $table) {
                        // Add JOIN condition
                        $join .= self::buildQueryJoin( $this->checkTables[0], $table );
                    }

                    // Assign select and where clauses
                    foreach ($columns as $column) {
                        //Add is column to constraint if selected
                        if (!empty( $this->formTableColumnFilterHash[$table][$column]['checkColumn'] )) {
                            $select[] = self::getTableAlias ($table).".".$column." AS ".self::getTableAlias ($table)."_".$column;
                        }

                        self::getColumnFilter ( $where,
                                                $table,
                                                $column,
                                                $this->formTableColumnFilterHash[$table][$column]['selectColumnLogicOper'],
                                                $this->formTableColumnFilterHash[$table][$column]['selectMinOper'],
                                                $this->formTableColumnFilterHash[$table][$column]['textMinValue'],
                                                $this->formTableColumnFilterHash[$table][$column]['selectRangeLogicOper'],
                                                $this->formTableColumnFilterHash[$table][$column]['selectMaxOper'],
                                                $this->formTableColumnFilterHash[$table][$column]['textMaxValue']);

                    } // foreach $column
                } //if (!empty ($columns))
            } // foreach $table
            // If there is only one table add from cluase to that else just the join table
	    $from[] = " FROM ".$this->checkTables[0]." AS ".self::getTableAlias($this->checkTables[0])." \n"; //assign single table
        } //if (!empty ($this->checkTables))



        if ( $this->radioSpacialConstraint == 'Cone') {
            $join .= self::getConeSearch ($this->textConeRa,
                                           $this->textConeDec,
                                           $this->textConeRadius,
                                           $this->selectConeUnits);
        } //if

        else if ($this->radioSpacialConstraint == 'Box') {
            $join .= self::getBoxSearch ($this->textBoxRa,
                                   $this->textBoxDec,
                                   $this->textBoxSize,
                                   $this->selectBoxUnits);
        } // if

        // Assign survey filter
        if ( isset( $this->selectSurvey ) && $this->selectSurvey > 0 ) {
             $surveyFilter = self::getSurveyFilter();
             if ( !empty( $surveyFilter ) )
                $where[] = self::getSurveyFilter();
         }
        // Figure out the Astro Filter join
        if (!empty( $this->checkAstroFilterIDs ) ) {
            $astroFilter = self::getAstroFilterJoin();
            if ( !empty( $astroFilter ) )
                $where[] = $astroFilter;
        }

        // Add Row Limit
        if ( !empty( $this->selectRowLimit ) ) {
                $rowLimit = "TOP ".$this->selectRowLimit." ";
        }
        // Add something if there are no columns selected
        if ( count( $select ) == 0 ) {
            $select[] = '42==42';
        }

        // Let's combine all the factors into one big query
        $query = "SELECT ".$rowLimit.join(', ',$select)."\n".join(', ', $from).$join;

        # Case non-empty where clause
        if ( count( $where ) > 0 ) {
           #Get rid of the first columns that starts with a logical operator of AND/OR
           $where[0] = preg_replace('/^\s*(AND)|(OR)|(\^)/', '', $where[0]);
           $query .= ' WHERE '.join( "\n ",$where );
        }
        return $query;
    } //buildQuery


    /**
    * Applies the min max filters for variables
    *
    * @param  $where = array()  passed by reference
    * @param  $table Given table to apply the filter to
    * @param  $column The column of that filter
    * @param  $logicOpr1 AND/OR operator between columns
    * @param  $minOper > >= or =
    * @param  $minValue
    * @param  $logicOpr2 AND/OR operator between filters on a single column
    * @param  $maxOper < <= or =
    * @param  $maxValue
    * @return nothing
    */
    private function getColumnFilter ( &$where = array(),
                                       $table,
                                       $column,
                                       $columnLogicOpr,
                                       $minOper,
                                       $minValue,
                                       $rangeLogicOper,
                                       $maxOper,
                                       $maxValue ) {

        // Assign just the min limit
        if ( ($minOper != "" and $minValue != "") and ( $maxValue == "" )) {
            $where[] = "$columnLogicOpr ".self::getTableAlias( $table ).".$column ".self::assignSymbol( $minOper )." $minValue";
        }
        // Assign just the max limit
        if ( ($maxOper != "" and $maxValue != "") and ( $minValue == "") ) {
            $where[] = "$columnLogicOpr ". self::getTableAlias ($table) . ".$column ".self::assignSymbol($maxOper)." $maxValue";
        }
	// Assign both min and max limit
        if ( ($maxOper != "" and $maxValue != "") and ($minOper != "" and $minValue != "") ) {
            $where[] = "$columnLogicOpr ( ".self::getTableAlias( $table ).".$column ".self::assignSymbol( $minOper )." $minValue $rangeLogicOper ".
                       self::getTableAlias ($table) . ".$column ".self::assignSymbol($maxOper)." $maxValue )";
        }
    } // function getColumnFilter().

    /**
    * Creates the Survey filter
    *
    * @return string with the survey filter
    */
    private function getSurveyFilter() {

        // Only add filter in case it's not 3PI ( 0 ID ) and myPS1 ( 255 ID )
        if (!empty ($this->selectSurvey)) {
            if ( in_array( self::JOIN_TABLE, $this->checkTables ) ) {
                return sprintf ("%s.surveyID = %s ", self::getTableAlias( self::JOIN_TABLE ), $this->selectSurvey);
            }
            else if ( in_array( 'Detection', $this->checkTables ) ) {
                return sprintf ("%s.surveyID = %s ", self::getTableAlias( 'Detection' ), $this->selectSurvey);
            }
            else if ( in_array( 'DetectionOrphan', $this->checkTables ) ) {
                return sprintf ("%s.surveyID = %s ", self::getTableAlias( 'DetectionOrphan' ), $this->selectSurvey);
            }
        }
        return;
    } //getSurveyFilter()


    /**
    * Creates the Filter Join
    * @param joinTable The name of the table for the joins.
    * @param table The table joining to joinTable.
    *
    * @return join string for the query
    */
    private function buildQueryJoin ( $joinTable, $table ) {
      $join = "";
      $joinColumns = array('objID','diffObjID','ippObjID','frameID','diffImageID');
      $commonColumn;
      $tableColumns = self::getTableColumns($table);  // Get all Table Columns
      $joinTableColumns = self::getTableColumns($joinTable);  // Get all Join Table Columns

      if ( $table == $joinTable ) return $join;  // Case table and table to be joined are the same

      foreach ( $joinColumns as $joinColumn ) {
	if ( in_array( $joinColumn, $joinTableColumns ) && in_array( $joinColumn, $tableColumns ) ) {
	  $commonColumn = $joinColumn;
	  break;
	}
      }

      if ( !empty( $commonColumn )) {
	$join .= "JOIN $table AS ".self::getTableAlias($table).' ON '.self::getTableAlias($table).'.'.$joinColumn.' = '.
                  self::getTableAlias( $joinTable ).'.'."$joinColumn\n";
      }
      return $join;
    }

    /**
    * Creates the Filter Join
    *
    * @return string with the filters ( ie., where X > 0 AND X < 100 )
    */
    private function getAstroFilterJoin () {
        // This sets $filterTable to the *last* table in the $checkTables array.
        // Since the Object table doesn't have a filterID field, this will
        // never select the Object table to filter with.
        $filterTable;
        if (!empty ($this->checkTables)) {
            foreach ($this->checkTables as $table) {
                $columns = self::getTableColumns( $table );
                $checkColumns = self::getCheckTableColumns( $table );
                if ( $table != self::JOIN_TABLE && in_array( 'filterID', $columns ) && !empty($checkColumns)) {
                    $filterTable = $table;
                    break;
                }
            }
        }

        // In MSSQL it is *much* faster to use:  filterID IN (1, 2, 3)
        // than:  (filterID = 1 OR filterID = 2 OR filterID = 3)
        if (!empty ($this->checkAstroFilterIDs) && !empty ($filterTable)) {
            return sprintf (" AND %s.filterID IN (%s) ",
                            self::getTableAlias ($filterTable),
                            join (', ', $this->checkAstroFilterIDs));
        }
        return;
    } //getAstroFilterJoin()

    /**
    * Adds SQL for a box
    * @param $boxRa
    * @param $boxDec
    * @param $boxSize
    * @param $boxUnits
    * @return String with a function for a box
    */

    private function getBoxSearch ( $boxRa, $boxDec, $boxSize, $boxUnits) {
        $boxSizeDegrees = 0;
        $boxTable;
        $joinTable;

        # Find out if the user actually selected an ra and dec to be used in the box search
        if (!empty ( $this->checkTables ) ) {
            foreach ( $this->checkTables as $table ) {
                $columns = self::getTableColumns( $table );
                if ( in_array( 'ra', $columns ) && in_array( 'dec', $columns ) ) {
                    $boxTable = $table;
                }
                if ( in_array( 'objID', $columns ) ) {
                    $joinTable = self::getTableAlias( $table ).'.objID';
                }
            }
        }
        if ( isset( $boxTable ) && isset( $joinTable ) ) {
            # Box size must be in degrees
            if ($boxUnits == 'box_arcsec') {
                $boxSizeDegrees = floatval($boxSize / 3600);
            }
            else if ($boxUnits == 'box_arcmin') {
                $boxSizeDegrees = floatval($boxSize / 60);
            }
            else if ($boxUnits == 'box_degrees') {
                $boxSizeDegrees = floatval($boxSize);
            }
            if ( isset( $boxTable ) ) {
                $boxDec1 = floatval( $boxDec - 0.5 * $boxSizeDegrees );
                $boxDec2 = floatval( $boxDec + 0.5 * $boxSizeDegrees );
                $boxRa1 = floatval( $boxRa - 0.5 * $boxSizeDegrees/cos(self::Degrees2Radians($boxDec)));
                $boxRa2 = floatval( $boxRa + 0.5 * $boxSizeDegrees/cos(self::Degrees2Radians($boxDec)));
                return " JOIN fGetObjFromRect( $boxRa1, $boxRa2, $boxDec1, $boxDec2 ) box ON box.objid = $joinTable";
            }
        }
        return;
    } //getBoxSearch()

    /**
    * Used for a box function to convert degrees into Radians
    * @param degrees
    * @return float with radians
    */
    private function Degrees2Radians($degrees)
    {
        /*** Do the math ***/
        return floatval( $degrees * pi() / 180 );
    }

    /**
    * Adds SQL for a cone
    * @param $coneRa
    * @param $coneDec
    * @param $coneSize
    * @param $coneUnits
    * @return String with a function for a cone
    */
    private function getConeSearch ( $coneRa, $coneDec, $coneSize, $coneUnits ) {
        $arcDegree = 0;
        $joinTable;
        $coneTable;
        # Find out if the user actually selected an ra and dec to be used in the box search
        if (!empty ( $this->checkTables ) ) {
            foreach ( $this->checkTables as $table ) {
                $columns = self::getTableColumns( $table );
                if ( in_array( 'ra', $columns ) && in_array( 'dec', $columns ) ) {
                    $coneTable = $table;
                }
                if ( in_array( 'objID', $columns ) ) {
                    $joinTable = self::getTableAlias( $table ).'.objID';
                }
            }
        }

        if ( isset( $coneTable ) && isset( $joinTable ) ) {
            // Function for cone is measured in degrees
            if ($coneUnits == 'cone_arcsec') {
                $arcDegree = floatval($coneSize / 3600);
            }
            else if ($coneUnits == 'cone_arcmin') {
                $arcDegree = floatval($coneSize / 60);
            }
            else if ($coneUnits == 'cone_degrees') {
                $arcDegree = floatval($coneSize);
            }
            if ( !empty( $coneRa ) && !empty( $coneDec) && !empty( $arcDegree ) && !empty( $coneUnits ) ) {
                return " JOIN fgetNearbyObjEq($coneRa, $coneDec, $arcDegree) cone ON cone.objid = $joinTable";
            }
        }
        return;
    } //function getConeSearch()

    /**
    * assignSymbol($str) assigns the comparison symbols.
    * @param $operator Operator to be converted
    * @return String with a function for a cone
    */
    private function assignSymbol ($operator) {

        if ($operator == 'eq')   return '=';
        if ($operator == 'gt')   return '>';
        if ($operator == 'gteq') return '>=';
        if ($operator == 'lt')   return '<';
        if ($operator == 'lteq') return '<=';
        if ($operator == 'neq') return '<>';

        die ("assignSymbol($operator): received an unexpected input!");
    } // function assignSymbol()
}//PSPSSchemaClass
