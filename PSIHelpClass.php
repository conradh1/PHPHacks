<?php
/**
 * @class PSIHelpClass
 * The PSI Help class is meant to give all the references to help articles in the wiki
  *
 * @version Beta
 * GPL version 3 or any later version.
 * copyleft 2010 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Conrad Holmberg
 * @since Beta version 2010
 */
class PSIHelpClass
{

    //! Configuration default file location
    const CONF_FILE = 'conf/psi_help_conf.xml';

    // Configuratin variables
    private $helpArticlesHash = array();
    private $queryExamplesHash = array();
    /**
     * Default constructor for PSI Session
     */
    public function __construct( $PSPSQueryExamplesURL )
    {
        // Attempt to open confuration file
        if (file_exists(self::CONF_FILE)) {
          if (! $xml = simplexml_load_file(self::CONF_FILE) )  {
             die( "Error: Help Configuration file: " . self::CONF_FILE . " has invalid XML.  Please validate via W3C.");
          }
        }
        else {
          die( "Error: Failed to open help configuration file: " . self::CONF_FILE );
        }

        if ( !empty( $PSPSQueryExamplesURL ) ) {
	  self::buildQueryExamplesHash( $PSPSQueryExamplesURL );
        }
        else {
          error_log("Cannot constuct PSIHelp class instance.  PSISession object is null.");
          exit();
        }
        // Sort through the xml conf file and assign
        // Links for web services and external sources
        for ( $i = 0; $i < sizeof($xml->reference); $i++) {
            // Assign each variable from configuration file into hash
            $helpRef = (string)$xml->reference[$i]->category.'-'.(string)$xml->reference[$i]->name;
            $this->helpArticlesHash[$helpRef] = (string)$xml->reference[$i]->wiki;
        } #for
    } // function __construct

    /**
     * Attempts to login and grab a sessionID
     * @param $name - Name of wiki reference
     * @return true/false for log
    */
    public function getqueryExamplesHash(  ) {
      return $this->queryExamplesHash;
    } //getqueryExamplesHash
    /**
     * Attempts to login and grab a sessionID
     * @param $name - Name of wiki reference
     * @return true/false for log
    */
    public function getWikiURL( $name ) {
      if ( isset( $this->helpArticlesHash[$name] ) ) {
        $wikiHelpArticleURL = "<a target=\"_blank\" href=\"".$this->helpArticlesHash[$name]."\"><img src=\"images/question.gif\" alt=\"Click for help on this topic.\" /></a>";
      }
      else {
         $wikiHelpArticleURL = "<font color=\"red\">Error: Unkown help article: \"$name\".</font>";
      }
      return $wikiHelpArticleURL;
    } //getWikiURL

    /**
     * Builds the plugin query examples
     * Structure would look like this:
     <!-- <name>Counts</name>

  //<query>
   // <author>PSPS</author>
    <title>Frame count</title>
    <shortDescription>Count frames</shortDescription>
    <longDescription>Returns counts of frames in each survey for a particular catalog</longDescription>
    <sql>SELECT Survey.name AS Survey, Survey.description AS Description, COUNT(frameID) AS TotalFrames
FROM FrameMeta
INNER JOIN Survey ON FrameMeta.surveyID = Survey.surveyID
GROUP BY Survey.name, Survey.surveyID, Survey.description
ORDER BY Survey.name</sql>
    <queue>Fast queue</queue>
    <database>PS1_3PI</database>
  </query> -->
     * @param $PSPSQueryExamplesURL - Name of wiki reference
     * @return true/false for log
    */
    private function buildQueryExamplesHash( $PSPSQueryExamplesURL ) {
      // Attempt to open PSPS plugins file containing query examples.
      if (! $xml = simplexml_load_file( $PSPSQueryExamplesURL ) ) {
        die( "Error: Example XML file: " . $PSPSQueryExamplesURL . " has invalid XML.  Please validate via W3C.");
      }

      //$xml = new SimpleXMLElement( $PSPSQueryExamplesURL, NULL, TRUE);
      //$xml = new SimpleXMLElement( 'conf/psi_query_examples.xml');


      //self::assignPluginValues($xml);
      for ( $i = 0; $i < sizeof( $xml->section ); $i++) {
            // Assign each variable from configuration file into hash
            $sectionName = (string)$xml->section[$i]['name'];
	    for ( $j = 0; $j < sizeof( $xml->section[$i]->query ); $j++) {
	      $queryTitle = (string)$xml->section[$i]->query[$j]->title;
	      $this->queryExamplesHash[$sectionName][ $queryTitle ] =
                    array ( "shortDescription" => (string)$xml->section[$i]->query[$j]->shortDescription,
                           "longDescription" => (string)$xml->section[$i]->query[$j]->longDescription,
                           "author" => (string)$xml->section[$i]->query[$j]->author,
                           "sql" => (string)$xml->section[$i]->query[$j]->sql,
                           "queue" => strtolower( (string)$xml->section[$i]->query[$j]->queue ),
                           "database" => (string)$xml->section[$i]->query[$j]->database
			  );

	    } //foreach query
        } #foreach section
    } //buildQueryExamplesHash

   private function assignPluginValues($xmlObj,$depth=0) {
      foreach($xmlObj->children() as $child) {
        $tagName = $child->getName();
	#print "debug: $depth $tagName $child <br/>";
        switch ( $tagName ) {
	  case "section":
            if ( !empty($this->queryList)) {
              print "<strong>queries</strong><br/>";
	      print_r($this->queryList);
	    }
            $this->queryHeader .= "$depth ".$child->name."=>";
            $queryList = array();
            break;
          case "name": //ignore this tag as it's used on several levels.
            break;
          case "query":
            if (!empty($this->queryHeader)) {
	      print "<br/><strong>Header</strong> ".$this->queryHeader." <br/>";
            }
            $this->queryHeader = '';
            #print "<strong>query: </strong><br/>";
            break;
          case "author":
	  case "title":
	  case "title":
	  case "shortDescription":
	  case "longDescription":
	  case "sql":
	  case "queue":
	  case "database":
             //print "$tagName => $child <br/>";
             $this->queryList[$tagName] = $child;
	    break;
	  default:
	    break;
        } //switch
	self::assignPluginValues($child,$depth+1);
      }
  }
} //PSIHelpClass