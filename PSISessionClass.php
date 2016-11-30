<?php
/**
 * @class PSISessionClass
 * The PSI Session class stores all the needed env variables and has methods
 * for checking them and accessing them.  Also session variables are held here.
 *
 * @version Beta
 * GPL version 3 or any later version.
 * copyleft 2010 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Conrad Holmberg, drchang@ifa.hawaii.edu
 * @since Beta version 2010
 */

require ("PSIHelpClass.php");

class PSISessionClass
{

    //! Configuration default file location
    const CONF_FILE = 'conf/psi_conf.xml';

    // Configuratin variables
    // The following variables are accessed by reflection (PHP "overloading");
    // Configuration variables
    private $defaultPSPSSchemaGroup;
    private $defaultPSPSSchema;

    //Personal user database variables
    private $defaultMyDBSchemaGroup;
    private $defaultMyDBSchema;

    // MOPS:
    private $defaultMopsSchemaGroup;
    private $defaultMopsExportSchemaGroup;
    private $defaultMopsSchema;
    private $defaultMopsExportSchema;

    private $newsWikiURL;
    private $mainWikiURL;
    private $postageStampStatusURL;
    private $postageStampNoticeURL;
    // DRL SOAP URLs
    private $authService;
    private $jobsService;
    private $usersService;
    private $postageStampService;
    // More PSI configuration variables
    private $vOPlotService;
    private $stiltsJar;
    private $helpObject;
    private $pSPSQueryExamplesURL;
    private $helpEmail;
    // Varibles for user session
    private $sessionID;
    private $userID;
    private $passwordHash;
    private $tmpPass;  // HACK temporary password storage for disclaimer.
    private $wsID;
    private $whenExpires;
    private $jobTypesHash = array();

    /**
     * Default constructor for PSI Session
     */
    public function __construct()
    {
        // Attempt to open confuration file
        if (file_exists(self::CONF_FILE)) {
          if (! $xml = simplexml_load_file(self::CONF_FILE)) {
             die( "Error: Configuration file: " . self::CONF_FILE . " has invalid XML.  Please validate via W3C.");
          }
        }
        else {
            die( "Error: Failed to open configuration file: " . self::CONF_FILE );
        }

        // Set to NULL because we have to assign this when
        // the user logs in.
        $this->sessionID = NULL;

        // Sort through the xml conf file and assign
        // Links for web services and external sources
        for ( $i = 0; $i < sizeof($xml->variable); $i++) {
            // Assign each variable from configuration file into hash
            $envVariableName = (string)$xml->variable[$i]->name;
            $envVariableValue = (string)$xml->variable[$i]->value;
            //$envVariableDescription = (string)$xml->url[$i]->description;

            //Assign each variable
            switch ( strtolower( $envVariableName ) ) {
                case "defaultpspsschemagroup":
                    $this->defaultPSPSSchemaGroup = $envVariableValue;
                    break;
                case "defaultpspsschema":
                    $this->defaultPSPSSchema  = $envVariableValue;
                    break;
                case "defaultmydbschemagroup":
                    $this->defaultMyDBSchemaGroup = $envVariableValue;
                    break;
                case "defaultmydbschema":
                    $this->defaultMyDBSchema  = $envVariableValue;
                    break;
                case "defaultmopsschemagroup": // default MOPS schema group
                    $this->defaultMopsSchemaGroup = $envVariableValue;
                    break;
                case "defaultmopsschema": // default MOPS catalog
                    $this->defaultMopsSchema = $envVariableValue;
                    break;
                case "defaultmopsexportschema": // default MOPS export schema
                    $this->defaultMopsExportSchema =  $envVariableValue;
                    break;
                case "newswikiurl": // URL to the PSPS News Wiki
                    $this->newsWikiURL = $envVariableValue;
                    break;
                case "mainwikiurl": // URL to the PSPS News Wiki
                    $this->mainWikiURL = $envVariableValue;
                    break;
                case "postagestampstatusurl": // URL to the IPP Postage Stamp Status page.
                    $this->postageStampStatusURL = $envVariableValue;
                    break;
                case "postagestampnoticeurl": // URL to the IPP Postage Stamp Status page.
                    $this->postageStampNoticeURL = $envVariableValue;
                    break;
                case "pspsqueryexamplesurl": // URL to the PSPS help pages
                    $this->pSPSQueryExamplesURL = $envVariableValue;
                    break;
                case "helpwikiurl": // URL to the PSPS help pages
                    $this->helpWikiURL = $envVariableValue;
                    break;
                case "mopsschemaurl": // URL to the MOPS Schema information
                    $this->mopsSchemaURL = $envVariableValue;
                    break;
                case "authservice": // Authentication service for DRL
                    $this->authService = $envVariableValue;
                    break;
                case "jobsservice": // Jobs service for DRL.
                    $this->jobsService = $envVariableValue;
                    break;
                case "usersservice": // Users service for DRL.
                    $this->usersService = $envVariableValue;
                    break;
                case "postagestampservice": // Users service for DRL.
                    $this->postageStampService = $envVariableValue;
                    break;
                case "voplotservice": // VO plot service
                    $this->vOPlotService = $envVariableValue;
                    break;
                case "stiltsjar": // Stilts jar for extracting quick queries
                    $this->stiltsJar = $envVariableValue;
                    break;
                case "helpemail":  // Email for help
                    $this->helpEmail = $envVariableValue;
                    break;
                default:
                    die ( "Error: Unkown configuration variable $envVariableName with value $envVariableValue" );
            } #switch
        } #for

        // Check for missing configuration variables
        if  (!isset( $this->defaultPSPSSchemaGroup ))
          die ( "Error: Missing configuration variable DefaultPSPSSchemaGroup." );

        if  (!isset( $this->defaultPSPSSchema ))
          die ( "Error: Missing configuration variable DefaultPSPSSchema." );

        if  (!isset( $this->defaultMyDBSchemaGroup ))
          die ( "Error: Missing configuration variable DefaultMyDBSchemaGroup." );

        if  (!isset( $this->defaultMyDBSchema ))
          die ( "Error: Missing configuration variable DefaultMyDBSchema." );

        if  (!isset( $this->defaultMopsSchema ))
          die ( "Error: Missing configuration variable DefaultMopsSchema." );

        if  (!isset( $this->defaultMopsExportSchema ))
	  die ( "Error: Missing configuration variable DefaultMopsExportSchema." );

	if  (!isset( $this->newsWikiURL ))
	  die ( "Error: Missing configuration variable NewsWikiURL." );

	if  (!isset( $this->mainWikiURL ))
          die ( "Error: Missing configuration variable MainWikiURL." );

	if  (!isset( $this->postageStampStatusURL ))
	  die ( "Error: Missing configuration variable PostageStampStatusURL." );

	if  (!isset( $this->postageStampNoticeURL ))
          die ( "Error: Missing configuration variable PostageStampNoticeURL." );

        if  (!isset( $this->pSPSQueryExamplesURL ))
	  die ( "Error: Missing configuration variable PSPSPlugins  This is needed as the PSPS Help examples!" );

        if  (!isset( $this->helpWikiURL ))
	  die ( "Error: Missing configuration variable HelpWikiURL." );

	if  (!isset( $this->mopsSchemaURL ))
	  die ( "Error: Missing configuration variable MopsSchemaURL." );

        if  (!isset( $this->authService ))
	  die ( "Error: Missing configuration variable AuthService." );

        if  (!isset( $this->jobsService ))
	  die ( "Error: Missing configuration variable JobsService." );

        if  (!isset( $this->usersService ))
	  die ( "Error: Missing configuration variable UsersService." );

        if  (!isset( $this->postageStampService ))
          die ( "Error: Missing configuration variable PostageStampService." );

        if  (!isset( $this->vOPlotService ))
	  die ( "Error: Missing configuration variable VOPlotService." );

        if  (!isset( $this->stiltsJar ))
	  die ( "Error: Missing configuration variable StiltsJar." );

        if  (!isset( $this->helpEmail ))
	  die ( "Error: Missing configuration variable HelpEmail." );


	# If the configuration is okay, load help class.
	$this->helpObject =  new PSIHelpClass( $this->pSPSQueryExamplesURL );

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
         die ( "Unkown method call in PSISessionClass: $method.  ".
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
     * Attempts to login and grab a sessionID
     * @param $userID
     * @param $password
     * @param $errorString - Passed by reference
     * @return true/false for login
    */
    public function login( $userID, $password, &$errorString ) {

	$errorString = ''; // Clear error string
	// The user failed to enter both a username and/or password
	if ( empty($userID) || empty($password) ) {
	  $errorString = "Login Failed... You must enter a username and password.";
	  error_log( $errorString );
	  return 0;
	}

	// Try to get the SOAP object of the DRL
        try {
          $authClient = new SoapClient( $this->authService, array('exceptions' => TRUE) );
        }

        catch (SoapFault $soapFault) {
           self::showUserServerError();
           die ("SOAP Fault: (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }

	// Try to login into the SOAP
        try {
            $resultSet = $authClient->login (array ( 'userid'   => $userID,
                                                     'password' => $password));

            if ( isset( $resultSet ) ) {
                $sessionID = $resultSet->return;
                // If the userID and password are correct, then start the session
                self::startSession( $sessionID, $userID, sha1($password) );
                return 1;
            }
            else {
                return 0;
            }
        }
        catch (SoapFault $soapFault) {
            error_log( "Error calling SOAP function login: ".$soapFault->faultstring.
                       "Most likely bad user name or password." );
	    $errorString = $soapFault->faultstring;
            $errorString = $this->getExternalErrorString($errorString);
            return 0;
        }
    } //login

    /**
    * @return error string to be displayed on PSI login page, hide internal details like path.
    */
    private function getExternalErrorString($e) {
        if ($this->startsWith($e, "login() error invalid DFetch userid/password!")) {
           $e = "Incorrect username or password"; // Invalid userid/password.
        }
        elseif ($this->startsWith($e, "404 Not Found at /var/www/html/DFetch/AuthService.cgi")) {
           $e = "Login server not running. Please check back later"; // CasJobs not running.
        }
        elseif ($this->startsWith($e, "Client Error")) {
           $e = "Login client not running. Please check back later"; // DFetch not running.
        }
        else { // use default error string.
           $e = "Other system error: $e. Please check back later"; // Other types of error.
        }

        $e = "Login Failed... $e.";
        return $e;
    }

    /**
    * @return true if string $str starts with string $needle.
    */
    private function startsWith($str, $needle){
        return substr($str, 0, strlen($needle)) === $needle;
    }

    /**
    * Attempts to logout
    * @return true/false for login
    */
    public function logout() {
         try {
            // logout the current session from the server, but sessionID is still set
            $authClient = new SoapClient( $this->authService, array('exceptions' => TRUE) );
            $resultSet = $authClient->logout(array('sessionID' => $this->sessionID ) ) ;
         }
         catch (SoapFault $soapFault) {
            self::showUserServerError();
            die( "Error calling SOAP function login(), faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring}).");
        }
    } //logout

    /**
     * Makes a SOAP call for the disclaimer to accept or reject
     * @param $answer // Answer to the disclaimer Y/N
      * @param $errorString - Passed by reference
     * @return true/false for success else fail
    */
    public function acceptTerms( $answer, &$errorString ) {

	$errorString = ''; // Clear error string
	// The user failed to enter both a username and/or password
	if ( empty($answer) ) {
	  $errorString = "Accept Terms failed...You need to supply an asnwer Y/N.";
	  error_log( $errorString );
	  return 0;
	}

	// Try to get the SOAP object of the DRL
        try {
          $authClient = new SoapClient( $this->authService, array('exceptions' => TRUE) );
        }

        catch (SoapFault $soapFault) {
           self::showUserServerError();
           die ("SOAP Fault: (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }

	// Try to login into the SOAP
        try {
            $resultSet = $authClient->acceptTerms(array ( 'userid'   => $this->userID,
                                                               'answer' => $answer ));
	    // They accept the answer, allow login
	    if ( $answer == 'Y' ) {
	      self::login( $this->userID, $this->tmpPass , $errorString );
	      $this->tmpPass = NULL;  // HACK never access this variable again.
	    }
        }
        catch (SoapFault $soapFault) {
            error_log( "Error calling SOAP function acceptTerms: ".$soapFault->faultstring.
                       " with arguments ".$this->userID.".and $answer ."  );
	    $errorString = $soapFault->faultstring;
            return 0;
        }
    } //acceptTerms
    /**
    * Starts session and assigns session ID.
    * @param $sessionID session ID
    * @param $userID
    * @param $passwordHash
    * @return Hash with schema values
    */
    private function startSession($sessionID, $userID, $passwordHash ) {

        $this->sessionID = $sessionID;
        $this->userID = $userID;
        $this->passwordHash = $passwordHash;
        $this->whenExpires = NULL;
        // Call init methods
        self::initCachedWsID();
        self::initJobTypes();  // Get the job types for file extraction/download.
    } // function startSession


    /**
     * Returns true if the sessionID is current, otherwise false
     *   to login again.
     * @return Boolean if the session is current or not.
     */
    public function isSessionCurrent() {
        // return false if the session has not even been set yet.
        if ( !isset($this->sessionID) )
          return 0;

        date_default_timezone_set ("Pacific/Honolulu");
        $now = date ("Y-m-d H:i:s", time ());
        if (isset ($this->whenExpires) and
            $this->whenExpires > $now  and
            isset ($this->sessionID)   and
            strlen ($this->sessionID) == 40)
            return 1;

        try {
            $authSOAPClient = new SoapClient ( $this->authService );
            $result = $authSOAPClient->isSessionCurrent(array ('sessionID' => $this->sessionID));

            $this->whenExpires = $result->return;
            if ($this->whenExpires == '') return 0;
        }
        catch (SoapFault $soapFault) {
            self::showUserServerError();
            die( "Error calling SOAP function isSessionCurrent(), faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring}).");
        }
        return 1;
    } // function isSessionCurrent


    public function unsetSessionId() {
        unset($this->sessionID);
    }


    /**
    * Returns error assuring the user they are not at fault
    * @return Boolean if the session is current or not.
    */
    public function showUserServerError() {

      print "<h3 class=\"outputerror\">".
             "Server side error.  If this persists, please contact systems admintration: ".
             "<a href=\"mailto:{$this->helpEmail}\">{$this->helpEmail}</a>".
             ".</h3>";

    }


    /**
    * Sets the WSID web services ID obtain from CasJobs
    * @return nothing
    */
    private function initCachedWsID() {

        try {
            $usersSOAPClient = new SoapClient( $this->usersService, array('exceptions' => TRUE) );
            //Assign new SOAP Clients
           $result = $usersSOAPClient->getUsers
                (array ('sessionID'   => $this->sessionID,
                        'conditions'  => 'userID: self',
                        'whichSystem' => 'DFetch'));
        }
        catch (SoapFault $soapFault) {
            self::showUserServerError();
            die( "Error calling getUsers(), (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})" );
        }
        $cjUsers = $result->return;
        $cjUser = array_shift ($cjUsers);

        $this->wsID = $cjUser->WebServicesID;

    } // function initCachedWsID

    /**
     * Initializes $this->jobTypesHash from the DRL and a
     * assigns it to a private variable.
     */
    private function initJobTypes() {

        try {
          $jobsSOAPClient = new SoapClient( $this->jobsService, array('exceptions' => TRUE) );
          $resultSet = $jobsSOAPClient->getJobTypes
                        ( array ('sessionID'   => $this->sessionID,
                                'schemaGroup' => $this->defaultPSPSSchemaGroup ) );
        }
        catch ( SoapFault $soapFault ) {
            self::showUserServerError();
            die ( "Error calling getJobTypes from Jobs Service(), (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
        }

        foreach ($resultSet->return as $key => $cjJobType) {
            // Skip JobType='QUERY'.// ** HACK ** Omit dataset and plot download types
            // This should be done via CasJobs somewhere.
            $jobType = $cjJobType->Type;
            if ( $jobType == 'QUERY' || $jobType == "DataSet" || $jobType == "plot")
                continue;
            $this->jobTypesHash[$jobType] = $jobType." - ".$cjJobType->Description;
        }
    } // function initJobTypes

    /**
     * Returns an array of jobTypes
     *
     * @return array of job types
     */
    public function getJobTypes() {
        return $this->jobTypesHash;
    } // function getJobTypes

}//PSISessionClass
