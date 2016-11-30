<?php
/**
 * @class QueuedJobClass
 * This PSI class deals with the queries that have been queued by CasJobs.
 * Examples of functions include
 *
 * @version Beta
        * license GPL version 3 or any later version.
 * copyleft 2010 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Conrad Holmberg
 * @since Beta version 2010
 */
require_once("PagingClass.php");

class QueuedJobClass
{

    /// Private variables
    private $PSISession;  // PSI Session instance needed in almost all classes
    private $jobID;  // Variables used for getJobs SOAP call constraints.
    private $searchKeywords; // text field in form for keyword searches
    private $radioSearchBy; // radio button on what to search by.
    private $jobTimeStart;
    private $jobTimeEnd;
    private $jobStatus;
    private $submitFilter; //actual value of submit button for toggling to show the search form
    private $page;        // Display page, using for paging.

    //Global constants for DRL states with Jobs (aka Queued Queries).
    const DEFAULT_DAYS_AGO = 30;
    const JOB_READY =     0;
    const JOB_STARTED =   1;
    const JOB_CANCELING = 2;
    const JOB_CANCELLED = 3;
    const JOB_FAILED =    4;
    const JOB_FINISHED =  5;
    const JOB_ALL =       6;

    private $jobStatusHash =
        array ( self::JOB_ALL       => 'All',
                self::JOB_READY     => 'Ready',
                self::JOB_STARTED   => 'Started',
                self::JOB_CANCELING => 'Canceling',
                self::JOB_CANCELLED => 'Cancelled',
                self::JOB_FAILED    => 'Failed',
                self::JOB_FINISHED  => 'Finished' );

    /**
    * Default constructor assigns needed env variables.
    *
    *
    * @param PSISession - Instance of the PSISession class.
    */
    public function __construct( $PSISession ) {

        if ( !empty($PSISession) ) {
            $this->PSISession = $PSISession;
            self::initDefaults();
        }
        else {
            error_log("Cannot constuct QueuedJobClass class instance.  PSISession object is NULL.");
            exit();
        }
    } // function __construct

    /**
    * Assigns all default needed variables
    * @return nothing
    */
    private function initDefaults() {

      $this->jobID = '';
      $this->radioSearchBy =  'TaskName';
      $this->searchKeywords  = '';
      # default value for how many days ago to view queued queries.
      $DefaultBackDate = date ("Y-m-d", time() - self::DEFAULT_DAYS_AGO * 24 * 60 * 60);
      $this->jobTimeStart = $DefaultBackDate;
      $this->jobTimeEnd = '';
      $this->jobStatus = self::JOB_ALL;
      $this->page = 0;
    }

     /**
    * Overloaded function used for getters and setters on variables
    *
    * @param method The method name that is parsed out ( i.e., getJobID, setJobID )
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
         die ( "Unkown method call in QueuedJobClass: $method.  ".
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
    * Gets a list of queued jobs within a total range (i.e., 1-50)
    *
    * @param jobConditions string that onctains the search conditions (i.e., jobUID., startDate, etc)/
    * @param rangeStart - Starting integer within the query results.
    * @param rangeEnd - Ending integer within the query results.
    * @return array containing a list of jobs
    */
    public function getQueuedJobsInRange ( $jobConditions, $rangeStart, $rangeEnd ) {
        $queuedJobsList = array(); # Array to hold the SOAP call results

        try {
            // SOAP Client Object used to make SOAP calls to the DRL Jobs Service.
            //print "service:".$this->PSISession->getJobsService();
            $jobsSOAPClient = new SoapClient ( $this->PSISession->getJobsService()); //, array('cache_wsdl' => WSDL_CACHE_NONE) ); ///////
            $result = $jobsSOAPClient->getJobsInRange
                (array ('sessionID'   => $this->PSISession->getsessionID(),
                        'schemaGroup' => $this->PSISession->getDefaultPSPSSchemaGroup(),
                        'rangeStart'  => $rangeStart,
                        'rangeEnd'    => $rangeEnd,
                        'conditions'  => $jobConditions ));
        }
        catch (SoapFault $soapFault) {
            $this->PSISession->showUserServerError();
            die ( "Error calling getJobsInRange from Jobs Service() regarding mydbs, (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }

        $queuedJobsList = array_reverse ($result->return);
        return $queuedJobsList;
    } // getQueuedJobsInRange


    public function getQueuedJobsCountByStatus ( $jobConditions ) {
        try {
            // SOAP Client Object used to make SOAP calls to the DRL Jobs Service.
            $jobsSOAPClient = new SoapClient ( $this->PSISession->getJobsService());//, array('cache_wsdl' => WSDL_CACHE_NONE) );
            $result = $jobsSOAPClient->getJobsCountByStatus
                (array ('sessionID'   => $this->PSISession->getsessionID(),
                        'schemaGroup' => $this->PSISession->getDefaultPSPSSchemaGroup(),
                        'conditions'  => $jobConditions ));
        }
        catch (SoapFault $soapFault) {
            $this->PSISession->showUserServerError();
            die ( "Error calling getJobsCount from Jobs Service() regarding mydbs, (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }

        return $result->return;
    } // getQueuedJobsCountByStatus


    /**
    * Returns a string based on search conditions for the getJobs SOAP call such as status, dates, and jobID.
    *
    * @param jobID - The filter for the JobID (must be an integer). Can pass NULL is not used.
    * @param searchKeywords - Keywords used for searching for jobs (i.e., error message, name, etc).
    * @param jobTimeStart - The date format from the starting date format(YYYY-MM-DD).Can pass NULL is not used.
    * @param jobTimeEnd - The date format to the ending date format(YYYY-MM-DD). Can pass NULL is not used.
    *
    * @param jobStatus - The status of the job job (i.e., executing, cancelled, failed, etc). Can pass NULL is not used.

    * @return A formatted string with given conditions (ie., jobID: x|y; status: 4)
    */
    private function getJobConditions ( $jobID, $searchBy, $searchKeywords, $jobTimeStart, $jobTimeEnd,  $jobStatus  ) {
        $jobConditions = ''; # Assume no conditions.

        # Handle JobID
        if ( isset($jobID) ) {
          # Case just single number or id range seperated by a comma
          if ( preg_match('/^\d+$/', $jobID) || preg_match('/^\d+,\d$/', $jobID)) {
            $jobConditions .= "JobID:$jobID; ";
          }
          # Case JobID range seperated by dash
          else if ( preg_match('/^\d+-\d+$/', $jobID) ) {
            $jobIDSearch = preg_replace( '/-/', ',', $jobID);
            $jobConditions .= "JobID:$jobIDSearch; ";
          }
        }

        # Add job Status conditions
        if ( isset($jobStatus) &&
              array_key_exists ( $jobStatus, $this->jobStatusHash ) &&
              $jobStatus != self::JOB_ALL )
            $jobConditions .= "Status:$jobStatus; ";

        # Add date conditions and match date format yyyy-mm-dd
        if ( isset( $jobTimeStart ) &&
             preg_match('/^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/', $jobTimeStart )) {
             $jobConditions .= "TimeSubmit:$jobTimeStart,;";
        }
        if ( isset( $jobTimeEnd ) &&
             preg_match('/^(19|20)\d\d[-](0[1-9]|1[012])[-](0[1-9]|[12][0-9]|3[01])$/', $jobTimeEnd )) {
             # Determine if a time start was already added
             if ( preg_match( '/TimeSubmit:/', $jobConditions ) ) {
                $jobConditions = preg_replace ('/;$/', '', $jobConditions); #get rid of end comma
                $jobConditions .= "$jobTimeEnd; ";
             }
             else
                $jobConditions .= "TimeSubmit:,$jobTimeEnd; ";
        }

        #Handle  keywords TaskName or Query
        if ( !empty($searchBy) && !empty($searchKeywords) ) {
          $searchKeywords = preg_replace ('/%/', '[%]', $searchKeywords); #add escape characters for %
          $searchKeywords = preg_replace ('/_/', '[_]', $searchKeywords); #add escape characters for _
          $jobConditions .= "$searchBy:%$searchKeywords%; "; # Add wildcard %
        }
        return $jobConditions;
    } #getJobConditions

    /**
    * Returns the number of jobs for each status (i.e., failed, completed)
    * @param countByStatus
    * @param status integer value of the job status
    *
    * @return integer of the number josbs for that status
    *
    */
    private function getStatusCount(&$countByStatus, $status) {
        if ($countByStatus == "") return 0;
        $len = sizeof($countByStatus);
        if ($len == 0) return 0;

        for ($i = 0; $i < $len; $i += 2) {
            if ($status == $countByStatus[$i]) return $countByStatus[$i + 1];
        }
        return 0;
    }

    /**
    * Sends a SOAP call to the DRL to get a list of Jobs for a particular user
    * based on certain filters (i.e., time started, completed, status).
    * Then a structured array is returned with the details of the user jobs including urls for canceling, editing, and plotting.
    *
    * @param queuedJobStatusTotalsList Passed by reference, this is an array containing the totals for all different
                                       queued query types (i.e., executing, cancelled, failed, etc).

    * @return array structure with the Jobs based on given filters
    */
    public function getQueuedJobsTable ( &$queuedJobStatusTotalsList ) {
        // Get number of jobs for each status.
        $countStr = self::getQueuedJobsCountByStatus(self::getJobConditions ( $this->jobID, $this->radioSearchBy, $this->searchKeywords, $this->jobTimeStart, $this->jobTimeEnd,  "") );
        //print $countStr."<br>"; // e.g.: 0:0;1:0;2:0;3:0;4:1;5:883

        $curStatusCount = 0;
        $queuedJobStatusTotalsList = array (0, 0, 0, 0, 0, 0, 0, 0);
        if ($countStr != "") {
            $countByStatus = preg_split("/;|:/", $countStr);
            $len = sizeof($countByStatus);
            $sum = 0;
            for ($i = 0; $i < $len; $i += 2) { $sum += $countByStatus[$i + 1]; }

            // Get count for all queued job types.
            for ($i = 0; $i < sizeof($queuedJobStatusTotalsList) - 1; $i ++) {
                $queuedJobStatusTotalsList[$i] = $this->getStatusCount($countByStatus, $i);
            }
            $queuedJobStatusTotalsList[self::JOB_ALL] = $sum; // all jobs.
            $curStatusCount = $queuedJobStatusTotalsList[$this->jobStatus];
        }
        # get conditions string for SOAP call
        $jobConditions = self::getJobConditions ( $this->jobID,
                                                  $this->radioSearchBy,
                                                  $this->searchKeywords,
                                                  $this->jobTimeStart,
                                                  $this->jobTimeEnd,
                                                  $this->jobStatus );
        #print $jobConditions;
        $paging = new PagingClass( $curStatusCount, $this->page, 25, 10 );
        $queuedJobs = self::getQueuedJobsInRange ( $jobConditions, $paging->getStart(), $paging->getEnd() );
        $outputTable = $paging->writeNavBar();
        $outputTable .= <<<EOF
            <table border="1" cellpadding="3" cellspacing="0" style="margin: 0 auto; font-size: 14px;">

EOF;
        # Handle the case where there are no queued Jobs.
        if ( empty( $queuedJobs ) ) {
$outputTable .= <<<EOF
              <tr>
                <td><strong>No Queued Jobs founds with that search.</strong></td>
              </tr>

EOF;
        }
        else {
          # TODO add task name
          $outputTable .= <<<EOF
              <tr>
                <th>JobID</th>
                <th>Name</th>
                <th>Submitted</th>
                <th>Elapsed</th>
                <th>Type</th>
                <th>Status</th>
                <th>Rows</th>
                <th>Context</th>
                <th>Target</th>
                <th>Query</th>
                <th>Result</th>
              </tr>

EOF;
        }
        $rowCount = 0;  // Used for alternating row colors

        $outputTableRows = "";
        foreach ($queuedJobs as $key => $job)
        {
            // Setup the link to edit/re-do the query.
            $editLink = '';
            if ($job->Type == 'QUERY') {
                $editLink = self::getEditLink( $job->JobID );
            }

            // Setup the link to cancel the job.
            $cancelLink = '';
            if ($job->Status == self::JOB_STARTED) {
              $cancelLink = self::getCancelLink( $job->JobID );
            }

            // Get time elapsed to execute the query.
            $timeElapsed = '';
            if ( $job->Type == 'QUERY' and $job->Status == self::JOB_FINISHED)
              $timeElapsed = self::getTimeElapsed ( $job->TimeStart, $job->TimeEnd );

            // Format time submitted.
            $timeSubmit = self::getFormattedTime( $job->TimeSubmit );

            $type = $job->Type;
            if ($type == 'QUERY') $type = 'Query';

            //Assign Status string corresponding to the code
            $this->jobStatusString = self::getJobStatusString( $job->Status );

            // Remember bullshit inconsistency between schema == context
            $queue = $job->Context;
            // HACK We don't know why or how, but CasJobs gives a number for the context.
            if ($queue ==   '1') $queue = 'Fast';
            if ( $queue == '500' || $queue == '4320') $queue = 'Slow';

            // Get results which are error strings in CasJobs!
            $result = $this->htmlEncode( $job->Error );
            // Plot link used for client side plotting.
            $plotLink = '';
            if (!empty ($job->OutputLoc)) {
                $result = "<a href=\"$job->OutputLoc\" target=\"_blank\">$job->OutputLoc</a>";
                $plotLink = self::getPlotLink( $job->OutputLoc );
            }

            // Display the data, with alternate rows colors.
            $rowColor = ($rowCount++ % 2) ? 'white-row' : 'green-row';

            // Encode the query to display in HTML TODO add task name
            $queryDisplay = htmlspecialchars( substr($job->Query, 0, 250) );
            if ( strlen( $queryDisplay ) >= 250) $queryDisplay .= '...'; #indicate the query is bigger than 250 characters
            $outputTableRow = <<<EOF
              <tr class="$rowColor">
                <td>$job->JobID</td>
                <td>$job->TaskName</td>
                <td>$timeSubmit</td>
                <td>$timeElapsed</td>
                <td>$type</td>
                <td>$this->jobStatusString</td>
                <td>$job->Rows</td>
                <td>$queue</td>
                <td>$job->Target</td>
                <td align="left">$queryDisplay $editLink</td>
                <td align="left">$result $cancelLink <br/>$plotLink</td>
              </tr>

EOF;
            $outputTableRows = $outputTableRow . $outputTableRows;
        } // foreach
            $outputTable .= $outputTableRows . <<<EOF
            </table>

EOF;
        $outputTable .= $paging->writeNavBar();
        return $outputTable;
    } #getQueuedJobsTable

    /**
    * Returns a string that encode html tags so it can be displayed on a web page correctly.
    *
    * @param s The string to do html encoding. 
    *
    * @return A string where tag starting character '<' is replaced with its html code.
    */
    private function htmlEncode( $s ) {
       $s = str_replace( "<", "&lt;", $s);
       return $s;
    }

    /**
    * Returns a link to the query page that shows the gives query that was execute previously.
    *
    * @param jobID The jobID of the query is only needed
    * @return A link to edit the query given.
    */
    private function getEditLink ( $editJobID ) {

        if ( !isset($editJobID) ) return;
        $editLink  = <<<EOF
                         <div align="right">
                           <font size="-1">
                             <a href="query_page.php?loadQueryAction=true&amp;jobID=$editJobID">edit</a>
                           </font></div>
EOF;
        return $editLink;
    } #getEditLink

    /**
    * Returns a link to the query page that shows the gives query that was execute previously.
    *
    * @param cancelJobID - The Job ID that is to be cancelled.
    *
    * @return
    */
    private function getCancelLink ( $cancelJobID) {

        if ( !isset($cancelJobID ) ) return;
        // If NULL assign blank string.
        $jobTimeStart = $this->jobTimeStart;
        $jobTimeEnd = $this->jobTimeEnd;
        $jobID = $this->jobID;
        $jobStatus = $this->jobStatus;

        $cancelLink = "queued.php?cancelJobID=$cancelJobID";
        $cancelLink .= "&selectJobStatus=$jobStatus&jobID=$jobID&jobTimeStart=$jobTimeStart&jobTimeEnd=$jobTimeEnd";
        $cancelLink = "<a href=\"$cancelLink\">Cancel</a>";
        $cancelLink = "<font size=\"-1\">$cancelLink</font>";
        $cancelLink = "<div align=\"right\">$cancelLink</div>";

        return $cancelLink;
    } #getCancelLink

    /**
    * Calculate the elapsed time for queries which have completed successfully.
    *
    * @param jobTimeStart Time when the query was started.
    * @param jobTimeEnd Time when the query was completed.
    * @return The time elapsed for a query
    */
    private function getTimeElapsed ( $jobTimeStart, $jobTimeEnd ) {

        $timeElapsed = '';

        // Change MS-SQL's "T" into a space.
        $jobTimeStart = preg_replace ("/T/", " ", $jobTimeStart);
        $jobTimeEnd   = preg_replace ("/T/", " ", $jobTimeEnd);

        // Convert to time so we can figure out how long the query took.
        $jobTimeStart = strtotime ($jobTimeStart);
        $jobTimeEnd   = strtotime ($jobTimeEnd);

        // Subtract the end time from the start time
        if ($jobTimeStart != -1 and $jobTimeEnd != -1) {
            $timeElapsed = $jobTimeEnd - $jobTimeStart;
            $timeElapsed = sprintf ( "%d:%02d", (int) ($timeElapsed / 60), ($timeElapsed % 60));
        }
        return $timeElapsed;
    } #getTimeElapsed

    /**
    * Gives a nice date format without the year if the query is this year and without the day it
    * is viewed the same day it was executed.
    *
    * @param jobSubmitTime Original date of job.
    *
    * @return Formatted Date
    */
    private function getFormattedTime ( $jobSubmitTime ) {
        // Dates used to replace uneeded year and current date.
        $yearToday = date ("Y-", time());
        $dateToday = date ("Y-m-d ", time());
        $timeSubmit = $jobSubmitTime;

        // Change MS-SQL's "T" into a space.
        $timeSubmit = preg_replace ("/T/", " ", $timeSubmit);
        // Trim off today's date if it matches.
        $timeSubmit = preg_replace ("/^$dateToday/", '', $timeSubmit);
        // Trim off today's year if it matches.
        $timeSubmit = preg_replace ("/^$yearToday/", '', $timeSubmit);

        return $timeSubmit;
    } #getFormattedTime

    /**
    * Returns a pretty string based on the status code of the Queued query.
    *
    * @param statusCode Integer of the queued job status ( 0 = ready, 1 = started, etc).
    *
    * @return Pretty formatted string according to the status code.
    */
    private function getJobStatusString ( $jobStatus ) {

        $jobStatusString = "<font color=\"red\">Unkown Status</font>";  //Assume bad code by default.
        // Assign string according to code if it exists.
        if ( array_key_exists ( $jobStatus, $this->jobStatusHash ) )
            $jobStatusString = $this->jobStatusHash[ $jobStatus ];

        // Dates used to replace uneeded year and current date.
        if ($jobStatus == self::JOB_CANCELING or
            $jobStatus == self::JOB_CANCELLED or
            $jobStatus == self::JOB_FAILED)
            $jobStatusString = "<font color=\"red\">$jobStatusString</font>";
        if ($jobStatus == self::JOB_FINISHED)
            $jobStatusString = "<font color=\"green\">$jobStatusString</font>";

        return $jobStatusString;
    } #getJobStatusString

    /**
    * Returns a for a link for plotting a xml file with VOPlot
    *
    * @param outputFileLocation The URL of the output file from the job
    *
    * @return URL to plot the output file.
    */
    private function getPlotLink ( $outputFileLocation ) {
        // Add link for plotting a xml file with path to VOTable file
        // This will print a link such as:
        // http://web01.psps.ifa.hawaii.edu/cgi-bin/voplot/loadjvt.pl?qm02/CasJobsOutput/VOTable/framemeta_0000_conradh.xml
        // NOTE: MUST BE ON THE SAME SERVER!!!\
        $domain = $_SERVER['HTTP_HOST'];
        $plotLink = '';
        if ( preg_match("/\.xml$/",$outputFileLocation) ) {
            $plotURL = $this->PSISession->getVOPlotService().'?'.preg_replace("/^http:\/\/$domain\//",'', $outputFileLocation );
            $plotLink = "<a href=\"$plotURL\" target=\"_new\">Plot Graph</a>";
        }
        return $plotLink;
    } #getPlotLink


    /**
    * Sends a SOAP call to the DRL to cancel a job of a particular jobID number
    * It structured array with the details of the user jobs (i.e., time started, completed,
    *
    * @param cancelJobID - The filter for the JobID (must be an integer). Can pass NULL is not used.
    *
    * @return Cancel Job message either success or failure
    */
    public function cancelQueuedJob ( $cancelJobID ) {
        $cancelJobMessage = '';

        if (isset ( $cancelJobID ) )  {
            try {
              // SOAP Client Object used to make SOAP calls to the DRL Jobs Service.
              $jobsSOAPClient = new SoapClient ( $this->PSISession->getJobsService() );
              $result = $jobsSOAPClient->cancelJob (array
                                                   ('sessionID'   => $this->PSISession->getsessionID(),
                                                    'schemaGroup' => $this->PSISession->getDefaultPSPSSchemaGroup(),
                                                    'jobID'       => $cancelJobID));
              $cancelJobMessage = "Successfully Cancelled Job $cancelJobID";
            } #try
            catch (SoapFault $fault) {
              $cancelJobMessage = $fault->faultstring;
              error_log( "Error calling cancelJob() from Jobs Service, (faultcode: {$soapFault->faultcode}, faultstring: $cancelJobMessage)");
              #pretty up fault string
              $cancelJobMessage = preg_replace ("/\n/", "<br/>\n", $cancelJobMessage);
              $cancelJobMessage = "<font color=\"red\">Attempt to cancel job failed to to error: $cancelJobMessage</font>";
            } #catch
        } #if
        else {
            $cancelJobMessage = "<font color=\"red\">Cannot Cancel jobID=$cancelJobID -- invalid jobID</font>";
        }
        return $cancelJobMessage;
    } // cancelQueuedJob

    /**
      * Assigns variables needed for queued Jobs
      *
      * @param none
      * @return none
      */
      public function initQueuedJobsFormVariables () {

        if ( isset( $_REQUEST['submitFilter'] ) ) {
          $this->submitFilter = $_REQUEST['submitFilter'];
          # special case to reset all values default
          # can still be overridden below
          if ( $this->submitFilter == 'Default' ) {
            self::initDefaults();
          }
        }
        else
          $this->submitFilter = '';

        if ( isset( $_REQUEST['selectJobStatus'] ) )
          $this->jobStatus =  $_REQUEST['selectJobStatus'];

        if ( isset($_REQUEST['jobID'] ) )
          $this->jobID = $_REQUEST['jobID'];

        if ( isset($_REQUEST['radioSearchBy'] ) )
          $this->radioSearchBy = $_REQUEST['radioSearchBy'];

        if ( isset($_REQUEST['searchKeywords'] ) )
          $this->searchKeywords = $_REQUEST['searchKeywords'];

        if ( isset( $_REQUEST['timeStart'] ) )
          $this->jobTimeStart = $_REQUEST['timeStart'];

        if ( isset( $_REQUEST['timeEnd'] ) )
          $this->jobTimeEnd = $_REQUEST['timeEnd'];

        if ( isset( $_REQUEST['pg'] ) )
          $this->page = $_REQUEST['pg'];
        else
          $this->page = '';



      } //initQueuedJobsFormVariables


} //QueuedJobClass
