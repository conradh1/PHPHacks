<?php
/**
 * @class MOPSSchemaClass
 *
 * The MOPS Schema Class is meant to store values from a number of forms to
 * build a query based on MOPS schema information. It is also capable of showing
 * tables, columns, etc with the MOPS Schema.
 *
 * This class also includes functions to support generating MOPS views.
 *
 * @version Beta
 * GPL version 3 or any later version.
 * copyleft 2010 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Conrad Holmberg, drchang@ifa.hawaii.edu
 * @since Beta version 2010
 */
class MOPSSchemaClass
{

    // Variables here contain all the needed info for PSI such as tables, columns, filters, etc.
    private $tableHash = array();
    private $tableColumnsHash = array();

    // All the variables here contain form values.  Naming convention is formtype|description
    // The following variables are accessed by reflection (PHP "overloading"); the names must be entirely lower case.
    private $selectColumnViewFormat; // short | full
    private $checkTables = array();
    private $checkTableColumnsHash = array();
    private $selectRowLimit;
    private $catalogSelection; // MOPS catalog (schema) selection, used for queries
    private $formTableColumnFilterHash = array(); // holds constraint related data

    private $defaultMopsSchemaGroup;
    private $defaultMopsSchema;
    private $defaultMopsExportSchema;

    private $JOINARRAY; // contains table names used in joins

    const TABLE_QUERY = "show tables";
    // HACK Notice that the table schema is hardcoded. This might be different from the ODBC db name so it would be another configuration var. 
    const COLUMNS_QUERY  =      "SELECT table_name, column_name, ordinal_position, column_default,
                                is_nullable, data_type, column_type, column_key,
                                character_maximum_length, column_comment
                                FROM information_schema.columns WHERE table_schema = 'psmops_ps1_v6' order by table_name";

    const FIELD_COUNT = 9; // used so that comments can fill the remaining space during parsing such that formatting characters
                           // in the comments don't cause problems

    private $mopsUserInterestingTables = array(); // interesting to users MOPS tables
    private $mopsInitialTableAliases = array(); // associative array of initial table aliases used to form all table aliases
    private $mopsRequiredTableSelections = array();


    /**
     * Constructor that assigns all the schema information
     *
     * @param  $PSISession - Environment variables
     */
    public function __construct( $PSISession )
    {
        $this->mopsUserInterestingTables = array('derivedobject_attrib', 'derivedobjects', 'det_rawattr_v2',
                                                 'detections', 'fields', 'known', 'orbits', 'tracklet_attrib',
                                                 'tracklets');

        $this->mopsInitialTableAliases = array( 'derivedobject_attrib' => 'da', 'derivedobjects' => 'do', 'det_rawattr_v2' => 'dr',
                                                'detections' => 'd', 'fields' => 'f', 'known' => 'k', 'orbits' => 'o', 'tracklet_attrib' => 'ta',
                                                 'tracklets' => 't');

        // format: selected-tables => required-tables
        $this->mopsRequiredTableSelections = array( array( array('known'), array('tracklets') ),
                                                    array( array('detections','tracklets'), array ('tracklet_attrib') ),
                                                    array( array('derivedobjects'), array('derivedobject_attrib') ),
                                                    array( array('known','det_rawattr_v2'), array('detections','tracklet_attrib','tracklets') ),
                                                    array( array('det_rawattr_v2','tracklets'), array('detections','tracklet_attrib') ),
                                                    array( array('derivedobjects','det_rawattr_v2'), array('detections','tracklet_attrib','tracklets','derivedobject_attrib') )
                                                  );
        try {
            $jobsSoapClient = new SoapClient ( $PSISession->getJobsService(), array('exceptions' => TRUE) );
        }
        catch ( SoapFault $soapFault ) {
            $PSISession->showUserServerError();
            die ( "Error calling getJobTypes from Jobs Service(), (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }

        // Preferred specification of MOPS schema group and schemas.
        $this->defaultMopsSchemaGroup = $PSISession->getDefaultMopsSchemaGroup();
        $this->defaultMopsSchema = $PSISession->getDefaultMopsSchema();
        $this->defaultMopsExportSchema = $PSISession->getDefaultMopsExportSchema();

        $this->catalogSelection = "";
        $this->JOINARRAY=array();

        // Set environment variables
        self::initDefaultFormValues(); // Assign default values to forms
        self::initTableHash( $PSISession, $jobsSoapClient ); // assign tables.
        self::initTableColumnsHash( $PSISession, $jobsSoapClient ); // assign hash with columns.
    } //__construct


    /**
     * If a table is user selected, automatically select the required tables based on the table.
     *
     * @param selectedTables passed by reference
     * @return boolean value indicating if tables were added
     */
    public function selectRequiredTables ( &$selectedTables ) {
        $addedTablesFlag = false;

        foreach ($this->mopsRequiredTableSelections as $required) {

            if($this->allTablesArePresent($required[0], $selectedTables)){
                foreach($required[1] as $r) {
                    if(!in_array($r,$selectedTables)){
                        $selectedTables[] = $r;
                        $addedTablesFlag = true;
                    }
                }
            }
        } // foreach this->mopsRequiredTableSelections

        return $addedTablesFlag;

    } // function selectRequiredTables


    /**
     * If all the tablesInQuestion are present in selectedTables then return true
     *
     * @param tablesInQuestion
     * @param selectedTables
     * @return boolean indicating if all tables are present
     */
    private function allTablesArePresent( $tablesInQuestion, $selectedTables) {

        foreach($tablesInQuestion as $t) {
            if(!in_array($t, $selectedTables)){
                return false;
            }
        }
        return true;
    }


    /**
     * Gettor
     *
     * @return default MOPS schema group
     */
    public function getDefaultMopsSchemaGroup() {
        return $this->defaultMopsSchemaGroup;
    }


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
            die ( "Unkown method call in MOPSSchemaClass: $method.  ".
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
     * Assigns any default values to form variables
     *
     * @return nothing
     */
    private function initDefaultFormValues() {
        $this->selectColumnViewFormat = 'short';
    } // initDefaultFormValues


    /**
     * @deprecated
     * Initialize table aliases.
     */
    public function initializeTableAlias($tableName) {
        if(array_key_exists( $this->removeQuotes($tableName), $this->mopsInitialTableAliases ) ) {
            return $this->mopsInitialTableAliases[$this->removeQuotes($tableName)];
        } else {
            return "";
        }
    }


    /**
     * Query for the tables and assign it to a hash.
     *
     * @param PSISession Contains all the needed variables like sessionID and default schema to execute the needed query.
     * @param jobsSoapClient SOAP instance that handles the Jobs Web Service to the DRL.
     * @return nothing
     */
    private function initTableHash( $PSISession, $jobsSoapClient ) {

        try {
            $parameters =
                array ('sessionID'   => $PSISession->getSessionID(),
                       'schemaGroup' => $this->defaultMopsSchemaGroup,
                       'context'     =>  $this->defaultMopsSchema,
                       'query'       => self::TABLE_QUERY,
                       'taskname'    => 'get MOPS tables',
                       'isSystem'    => 1
                       );

            $resultSet = $jobsSoapClient->executeQuickJob ($parameters);
            $resultSet = $resultSet->return;

            $resultSet = preg_split("/\n/", $resultSet); // first entry is a header

            for($i=0; $i < count($resultSet); $i++) {
            //foreach($resultSet as $row) {

                if($i!=0){
                    # @note MOPS tables aren't receiving meaningful descriptions.
                    $this->tableHash[$this->removeQuotes($resultSet[$i])] = array ( "Description" => $this->removeQuotes($resultSet[$i]),
                                                                                    "Alias" => "",
                                                                                    "Type" => ""
                                                                                   );
                } // if
            } // foreach $val
        } // try
        catch (SoapFault $soapFault) {
             $PSISession->showUserServerError();
            die ( "Error with SOAP call executeQuickJob to extended properties from Jobs Service(), (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }
    } // initTableHash


    /**
     * Settor
     */
    public function setFormTableColumnFilterHash ( $columnFilterHash ) {
        $this->formTableColumnFilterHash = $columnFilterHash;
    }


    /**
     * Gettor
     */
    public function getFormTableColumnFilterHash() {
       return $this->formTableColumnFilterHash;
    }


    /**
     * Get the table metadata and assign it to a hash within the PSI session.
     * This includes getting the column names.
     *
     * Write to a hash structure of the form
     *
     *     key = table name => array(column metadata)
     *
     * @param PSISession Contains all the needed variables like sessionID and default schema to execute the needed query.
     * @param jobsSoapClient SOAP instance that handles the Jobs Web Service to the DRL.
     */
    private function initTableColumnsHash( $PSISession, $jobsSoapClient ) {

        // retrieve nine fields from the information schema
        // @note psmops_ps1_v6 is hard coded here.
        // @todo remove hardcoding
        $columnsHash = array(); // contains columns and associated data; declare here so it can be accessed later

        // @todo remove hardcoded

        try {
            $parameters =
                array ('sessionID'   => $PSISession->getSessionID(),
                       'schemaGroup' => $this->defaultMopsSchemaGroup,
                       'context'     => $this->defaultMopsSchema,
                       'query'       => self::COLUMNS_QUERY,
                       'taskname'    => 'get MOPS columns',
                       'isSystem'    => 1
                       );

            $resultSet = $jobsSoapClient->executeQuickJob($parameters);
            $resultSet = $resultSet->return;

            $resultSet = preg_split("/\n/", $resultSet); // first entry is a header

            // declarations
            $table;
            $colname;
            $name;
            $value;
            $colname_prev = "";
            $table_prev = "";
            $datatype = "";
            $unit = "";
            $desc = "";
            $size = "";
            $default = "";
            $check = "";
            $schemaData = array();

            for($i=0; $i < count($resultSet); $i++) {
                $numRows = count ($resultSet);

                $columnsHash = array(); // contains columns and associated data

                // @note Row 0 contains header data therefore it is skipped
                for ($row = 1; $row <= $numRows; $row++)
                {
                    // @note the following if is like a bandaid so the rest of the
                    //       logic herein works correctly without server errors being reported in the log
                    //       it's a one-off situation dealing with the last table
                    if($row!=$numRows){
                        $schemaData = explode (",", $resultSet[$row], 10);
                    }

                    $table = $this->removeQuotes( preg_replace( '/^\"|\"$/', '', $schemaData[0] ) ); // table should be the same many times
                    if($row==1){
                        $table_prev=$table;
                    }
                    if($table_prev != $table) {
                        $this->tableColumnsHash[$table_prev] = $columnsHash;
                        $columnsHash = array();
                        $table_prev=$table;
                    }

                    $colname = preg_replace( '/^\"|\"$/', '', $schemaData[1] );

                    $columnsHash[$colname] = array( "DataType" => $schemaData[6],
                                                    "Unit" => "",
                                                    "Description" => $schemaData[9],
                                                    "Default" => $schemaData[3],
                                                    "Size" => $schemaData[8]
                                                  );
                } //for $row
            } // foreach $val

            // Assign the last table.
            $this->tableColumnsHash[$table_prev] = $columnsHash;
        } // try
        catch (SoapFault $soapFault) {
            $PSISession->showUserServerError();
            die ( "Error with SOAP call executeQuickJob to table columns query from Jobs Service(), (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }
    } // initTableColumnsHash


    /**
     * Returns an array of just table names
     *
     * @return array of table names
     */
    public function getTables() {
        $tables = array();
        foreach($this->tableHash as $table => $value) {
            // Hand off the table name only
            $tables[] = $this->removeQuotes($table);
        }
        return $tables;
    } //getTables


    /**
     * Return an array of tables useful for the majority of MOPS users
     *
     * @return array of table names
     */
    public function getUserInterestingTables() {
        $tables = array();
        foreach($this->tableHash as $table => $value) {

            if(in_array($this->removeQuotes($table), $this->mopsUserInterestingTables)){
                $tables[] = $this->removeQuotes($table);
            }
        }
        return $tables;
    }


    /**
     * Returns the hash of a particular table
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
        if (isset( $this->tableColumnsHash[$table] ) ) {
            foreach($this->tableColumnsHash[$table] as $columnName => $columnDetails) {
                $columns[] = $this->removeQuotes($columnName);
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
     * The exception here is that the Object table is always placed first
     *
     * @note MOPS QB doesn't use the object table
     *
     * @param  tables Selected Tables array
     * @return nothing
     */
    public function setCheckTables( $tables = array() ) {
        $this->checkTables =  $tables;
    } //setCheckTableColumns

    /**
     * Get the checked tables
     */
    public function getCheckTables () {
        return $this->checkTables;
    }


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
     * Gets the checked columns from a table and places it in a nested Hash
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
        $this->checkTables = array();
        $this->selectSurvey = '';
        $this->checkTables = array();
        $this->checkTableColumnsHash = array();
        $this->formTableColumnFilterHash = array();
        $this->JOINARRAY = array();
    } //clearFormValue


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
     * @return a nicely formatted query :)
     */
    function buildQuery () {
        $select = array();
        $from = array();
        $where = array();
        $join = '';
        $rowLimit = '';
        $tables = array();

        if (!empty ($this->checkTables)) {

            foreach ($this->checkTables as $table) {

                // only include tables that have selected columns
                $columns = self::getCheckTableColumns( $table );

                if (!empty ($columns)) {

                    $tables[] = $this->removeQuotes($table);

                    // Assign select and where clauses
                    //go through each column
                    foreach ($columns as $column) {
                        //Add is column to constraint if selected
                        if (!empty( $this->formTableColumnFilterHash[$table][$column]['checkColumn'] )) {
                            $select[] = $this->removeQuotes($table) . "." . $this->removeQuotes($column);
                        }

                        self::getColumnFilter ( $where,
                                                $table,
                                                $column,
                                                $this->formTableColumnFilterHash[$table][$column]['selectMinOper'],
                                                $this->formTableColumnFilterHash[$table][$column]['textMinValue'],
                                                $this->formTableColumnFilterHash[$table][$column]['selectMaxOper'],
                                                $this->formTableColumnFilterHash[$table][$column]['textMaxValue']);
                    } // foreach $column
                } //if (!empty ($columns))
            } // foreach $table
        } //if (!empty ($this->checkTables))

        // If there is only one table add from clause to that, otherwise only add the join table

        //Calculate joins before FROM
        $join = $this->joinTables($this->checkTables);

        $fromStatement = "FROM ";
        $tblCounter = 0;
        $joinarray = array();
        $uniqueJoinArray= array_unique($this->JOINARRAY);
        $tblTotal = count($tables) - count($uniqueJoinArray);

        // build the FROM statement using all tables and not just tables that have selected columns
        foreach ($this->checkTables as $t) {
            $tblCounter++;

            # don't add a table to the FROM statement if it is used in a JOIN
            if(!(in_array($t, $this->JOINARRAY))){
                $fromStatement .= $t;

                //if($tblCounter < $tblTotal){
                    $fromStatement .= ", ";
                //} // if

            } // if
        } // foreach tables

        $fromStatement = preg_replace("/\,\s*$/","",$fromStatement);
        $from[] = $fromStatement;

        // Add something if there are no columns selected
        if ( count( $select ) == 0 ) {
            $select[] = '42==42';
        }

        // Let's combine all the factors into one big query
        $query = "SELECT " . join(', ',$select) . "\n" . join(', ', $from) . "\n" . $join;

        # Case non-empty where clause
        if ( count( $where ) > 0 ) {
            $query .= 'WHERE ' . join( " AND ", $where );
            $query .= "\n";
        }

        // Add Row Limit
        if ( !empty( $this->selectRowLimit ) ) {
                $rowLimit = "LIMIT " . $this->selectRowLimit;
                $query .= $rowLimit;
                $query .= "\n";
        }

        return $query;
    } //buildQuery


    /**
     * Applies the min max filters for variables
     *
     * @param  $where = array()  passed by reference
     * @param  $table Given table to apply the filter to
     * @param  $column The column of that filter
     * @param  $minOper
     * @param  $minValue
     * @param  $maxOper
     * @param  $maxValue
     * @return nothing
     */
    private function getColumnFilter ( &$where = array(),
                                       $table,
                                       $column,
                                       $minOper,
                                       $minValue,
                                       $maxOper,
                                       $maxValue ) {

        $tableColumnHash = $this->getTableColumnsHash( $table );

        // Assign just the min limit
        if ($minOper != "" and $minValue != "" ) {
            if(preg_match("/var/", $tableColumnHash[$column]['DataType']) ||
               preg_match("/char/", $tableColumnHash[$column]['DataType']) ||
               preg_match("/byte/", $tableColumnHash[$column]['DataType']) ) {
                $minValue = "'" . $minValue . "'";
            }
            $where[] = $this->removeQuotes($table) . "." . $this->removeQuotes($column) . " " . self::assignSymbol( $minOper ) . " $minValue";
        }

        // Assign just the max limit
        if ($maxOper != "" and $maxValue != "" ) {
            if(preg_match("/var/", $tableColumnHash[$column]['DataType']) ||
               preg_match("/char/", $tableColumnHash[$column]['DataType'])){
                $maxValue = "'" . $maxValue . "'";
            }
            $where[] = $this->removeQuotes($table) . "." . $this->removeQuotes($column) . " " . self::assignSymbol( $maxOper ) . " $maxValue";
        }
    } // function getColumnFilter()


    /**
     * Function assignSymbol($str) assigns the comparison symbols.
     *
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


    /**
     * Remove quotes, single quotes and double quotes, from table names returned from mops metadata queries
     *
     * @param label
     * @return unquoted string
     */
    public function removeQuotes ($label) {
        $newString = $label;
        $newString = preg_replace("/\'/", "", $label);
        $newString = preg_replace("/\"/", "", $label);
        return $newString;
    }


    /**
     * Gettor
     *
     * @return set current catalog selection
     */
    public function setCatalogSelection ($cs) {
        $this->catalogSelection = $cs;
    }


    /**
     * Gettor
     *
     * @return current catalog selection
     */
    public function getCatalogSelection () {
        return $this->catalogSelection;
    }


    /**
     * Automatically join MOPS tables in a reasonable way.
     *
     * @param tables Array of table names
     * @return SQL statement of table joins
     */
    private function joinTables ($tables) {
        $joins = "";

        $this->JOINARRAY = array();
        $joins = $this->makeJoin($tables);

        return $joins;
    }


    /**
     * Make the join between two MOPS tables based on the table names
     *
     * @note There may be some issues with join order that are not fully addressed.
     *
     * @param tables array of two table names
     * @return SQL join statement given any two MOPS table names
     */
    private function makeJoin ($tables) {

        $join = "";

        if($this->allTablesArePresent(array('detections','tracklet_attrib'), $tables)) {
            $join .= "INNER JOIN detections ON detections.det_id = tracklet_attrib.det_id\n";
            $this->JOINARRAY[] = "detections";
        }

        if($this->allTablesArePresent(array('detections','det_rawattr_v2'), $tables)) {
            $join .= "INNER JOIN det_rawattr_v2 ON det_rawattr_v2.det_id = detections.det_id\n";
            $this->JOINARRAY[] = "det_rawattr_v2";
        }

        if($this->allTablesArePresent(array('tracklets','tracklet_attrib'), $tables)) {
            $join .= "INNER JOIN tracklets ON tracklets.tracklet_id = tracklet_attrib.tracklet_id\n";
            $this->JOINARRAY[] = "tracklets";
        }

        if($this->allTablesArePresent(array('known','tracklets'), $tables)) {
            $join .= "INNER JOIN known ON known.known_id = tracklets.known_id\n";
            $this->JOINARRAY[] = "known";
        }

        if($this->allTablesArePresent(array('derivedobject_attrib','tracklets'), $tables)) {
            $join .= "INNER JOIN derivedobject_attrib ON derivedobject_attrib.tracklet_id = tracklets.tracklet_id\n";
            $this->JOINARRAY[] = "derivedobject_attrib";
        }

        if($this->allTablesArePresent(array('derivedobjects','derivedobject_attrib'), $tables)) {
            $join .= "INNER JOIN derivedobjects ON derivedobjects.derivedobject_id = derivedobject_attrib.derivedobject_id\n";
            $this->JOINARRAY[] = "derivedobjects";
        }

        return $join;
    }

    /**
     * Decide whether a given catalog name should be displayed.
     *
     * @return TRUE if catalog will be displayed, otherwise FALSE
     */
    public function showCatalog ( $name ) {
        if ( preg_match("/MOPS_MD_Stack/i", $name) ) {
            return TRUE;
        }
	else if ( preg_match("/MOPS_\w+magic/i", $name) ) {
            return TRUE;
        }
        return FALSE;
    }
} //MOPSSchemaClass
