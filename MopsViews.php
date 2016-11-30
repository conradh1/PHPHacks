<?php
/**
 * MOPS views methods
 */
class MopsViews {

    const NeoSubmissionLimit = 250;
    private $desigArray = array(); // all designations that are to be reported

    public function __construct() {
    }

    /**
     * Retrieve NEO submission totals
     *
     * @param PSISession
     * @return NEO submission totals as an array of arrays
     */
    public function getNeoSubmissionTotals ( $PSISession ) {
        $result = array();
        $new_result = array();
        $terms = array();

        $sql = "select disposition, count(distinct desig) ct
                from mpc_sub group by disposition
                order by ct desc";

        $result = $this->runMopsQuery($PSISession, $PSISession->getDefaultMopsExportSchema(), "get NEO submission totals", $sql);


        $terms = array('S' => 'SUBMITTED', 'T' => 'SUBMITTED (INCIDENTAL)', 'C' => 'DISCOVERY, NEO (non-PHA)',
                       'A' => 'DISCOVERY, PHA', 'R' => 'RECOVERY, NEO', 'N' => 'DISCOVERY, NON-NEO',
                       'K' => 'KNOWN', 'D' => 'DOWNGRADED', 'O' => 'OUTREACH', 'J' => 'REJECTED');

        // the order of display in the report page is
        // C, A, R, T, N, O, K, J, D
        // S is not used

        for($i = 0; $i < count($result); $i++){
            if( array_key_exists( $result[$i][0], $terms) ) {
                $new_result[$this->removeQuotes($result[$i][0])][0] = $terms[$this->removeQuotes($result[$i][0])];
                $new_result[$this->removeQuotes($result[$i][0])][1] = $result[$i][1];
            }
        }
        return $new_result;
    } // getNeoSubmissionTotals


    /**
     * Get NEO submissions that have an MPC designation.
     *
     * @param PSISession
     * @return DB result
     */
    public function getNeoSubmissions ( $PSISession ) {
        $sql = "select epoch_mjd, survey_mode,
                ra_deg, dec_deg, filter_id, mag, obscode,
                desig, mpc_desig,
                digest, dbname, tracklet_id, disposition from mpc_sub
                where disposition not in ('T', 'J', 'O', 'D')
                group by desig
                order by epoch_mjd desc
                limit " . self::NeoSubmissionLimit;
        $result = $this->runMopsQuery($PSISession, $PSISession->getDefaultMopsExportSchema(), "get NEO submissions", $sql);

        $this->storeDesigs($result, "submissions");

        return $result;
    }


    public function getNeoDiscoveriesToDate ( $PSISession ) {
        $sql = "select epoch_mjd, survey_mode,
                ra_deg, dec_deg, filter_id, mag, obscode,
                desig, mpc_desig,
                digest, dbname, tracklet_id, disposition from mpc_sub
                where disposition in ('C', 'A', 'R')
                group by desig
                order by epoch_mjd desc";
                
        $result = $this->runMopsQuery($PSISession, $PSISession->getDefaultMopsExportSchema(), "get NEO discoveries to date", $sql);
        
        $this->storeDesigs($result, "discovery");
        
        return $result;
    }


    /**
     * Get detections related to NEO submissions
     *
     * @param PSISession
     * @param type in submissions, discovery
     * @return DB result
     */
    public function getNeoSubmissionDetections ( $PSISession, $type ) {
        $sql = "SELECT desig, epoch_mjd, ra_deg, dec_deg, filter_id, mag, obscode
                FROM mpc_sub
                WHERE desig IN (";

        if ($type == "submissions") {
            $this->desigArray = $_SESSION['MopsViewDesignationsSubmissions'];
        }
        if ($type == "discovery") {
            $this->desigArray = $_SESSION['MopsViewDesignationsDiscovery'];
        }

        foreach ( $this->desigArray as $desig ) {
            $sql .= "'$desig',";
        }

        $sql = rtrim($sql, ","); // remove last ","

        $sql .= ")
                ORDER BY epoch_mjd";

        $result = $this->runMopsQuery($PSISession, $PSISession->getDefaultMopsExportSchema(), "get NEO submissions", $sql);
        return $result;
    }


    /**
     * Get MOPS disposition
     *
     * @param code
     * @return expanded disposition
     */
    public function getMopsDisposition ( $code ) {
        $code = $this->removeQuotes($code);
        $terms = array('S' => 'SUBMITTED', 'T' => 'SUBMITTED (INCIDENTAL)', 'C' => 'DISCOVERY, NEO (non-PHA)',
                       'A' => 'DISCOVERY, PHA', 'R' => 'RECOVERY, NEO', 'N' => 'DISCOVERY, NON-NEO',
                       'K' => 'KNOWN', 'D' => 'DOWNGRADED', 'O' => 'OUTREACH', 'J' => 'REJECTED');
        if (array_key_exists($code,$terms)) {
            return $terms[$code];
        } else {
            return "Label Error";
        }
    }


    /**
     * Run a query using the MOPS databases.
     *
      *
     * @param PSISession - PSI Session Class
     * @param schema The MOPS db to connect to.
     * @param description - string description of querying
     * @param sql the querying
     * @param returnAA - optional passed by reference associative array
     * @return if true return an associative array
     *
     * @note associative arrays are not currently working
     */
    public function runMopsQuery ( $PSISession, $schema, $description, $sql, $returnAA=false ) {
        $result = array();
        $associativeResult = array();
        $data = array();
        $jobsSoapClient = "";

        try {
            $jobsSoapClient = new SoapClient ( $PSISession->getJobsService(), array('exceptions' => TRUE) );
        }
        catch ( SoapFault $soapFault ) {
            $PSISession->showUserServerError();
            die ( "Error calling getJobTypes from Jobs Service(), (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }

        try {
            $parameters =
                array ('sessionID'   =>  $PSISession->getSessionID(),
                       'schemaGroup' =>  $schema,
                       'context'     =>  $PSISession->getDefaultMopsExportSchema(),
                       'query'       =>  $sql,
                       'taskname'    =>  $description,
                       'isSystem'    =>  1);

            $resultSet = $jobsSoapClient->executeQuickJob($parameters);

            foreach ($resultSet as $key => $val) {

                // Split the query results using "\n".
                $queryResult = explode ("\n", $val);

                // Count the number of rows returned.
                $numRows = count ($queryResult);

                // Split each row value using "," and display the data.
                // @note Row 0 is a header, therefore it is skipped.
                for ($row = 1; $row < $numRows; $row++) {
                    $data = explode (",", $queryResult[$row]);
                    print "";
                    for($i=0;$i<count($data);$i++){ // iterate over columns of data
                        $result[$row-1][$i] = $this->removeQuotes($data[$i]);
                    }
                    print "";
                } //for $row
             } // foreach resultSet
             return $result;
        } // try
        catch (SoapFault $soapFault) {
             $PSISession->showUserServerError();
            die ( "Error with SOAP call executeQuickJob to extended properties from Jobs Service(), (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        } // catch
    } // runMopsQuery

    
    /**
     * Remove quotes from table names returned from mops metadata queries
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
     * Store designations of NEO submissions for faster querying of detections.
     *
     * @param results
     * @param type
     * @return none
     */
    private function storeDesigs ($result, $type) {
        foreach ( $result as $row ) {
            $this->desigArray[] = $row[7]; // push designation onto array 
        }
        if($type == "submissions"){
            $_SESSION['MopsViewDesignationsSubmissions'] = $this->desigArray;
        }
        if($type == "discovery"){
            $_SESSION['MopsViewDesignationsDiscovery'] = $this->desigArray;
        }
    }
}
?>
