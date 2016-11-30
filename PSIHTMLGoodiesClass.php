<?php
/**
 * @class PSIHTMLGoodiesClass
 * The PSI HTML Goodies Class is meant to provide generic HTML output for things
 * such as tables and form objects
 *
 * @version Beta
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Conrad Holmberg
 * @since Beta version 2010
 */
class PSIHTMLGoodiesClass
{

    /**
    * Default constructor for the pretty HTML
    *
    *
    */
    public function __construct( )
    {

    } //__construct


    /**
    * displays the Query Results in a html table to the user
    *
    * @param  resultSet Soap result set string
    * @return Hash containing just the columns
    ************************************************/
    public function showQueryResultSet ( $resultSet ) {
        $htmlTable;
        // Display a Download button if there was no error.
        if (!is_soap_fault ($resultSet)) {
            $tdAllign = array();
            $htmlTable = <<<EOF
                    <table class="results" border="1" cellpadding="3" cellspacing="0" style="margin: 0 auto">

EOF;

            // Parse $resultSet.
            // Note: $resultSet's only key is 'return'.
            foreach ($resultSet as $key => $val) {
                // Split the query results using "\n".
                $queryResult = explode ("\n", $val);
                //$queryResult = explode (chr(31), $val);

                // Count the number of rows returned.
                $numRows = count ($queryResult);

                // Count the number of fields.
                $numFields = substr_count ($queryResult[0], ":");
                //print ("rows=$numRows, fields=$numFields<br>");
                // Split each row value using "," and display the data.
                for ($row = 0; $row < $numRows; $row++) {
                    $data = explode (",", $queryResult[$row]);
                    //$data = explode (chr(30), $queryResult[$row]);
                    if ($row == 0) {
                        $htmlTable .= <<<EOF
                          <tr>
                             <th align="center" colspan="$numFields">Query Results</th>
                          </tr>

EOF;
                    }
                    $rowColor = ($row % 2) ? 'green-row' : 'white-row';
                    $htmlTable .= <<<EOF
                          <tr class="$rowColor">

EOF;
                    for ($i = 0; $i < $numFields; $i++) {
                        if ($row == 0) {
                            // Assign alignment
                            array_push( $tdAllign, self::getTDAlignment( $data[$i] ));
                            // Display the header.
                            $htmlTable .= <<<EOF
                              <th align="center">$data[$i]</th>

EOF;
                        }
                        else {
                            // Display the data, with alternate rows having white and
                            // light green backgrounds.
                            $s = self::htmlEncode( self::decode( $data[$i] ) );
                            $htmlTable .= <<<EOF
                              <td align="$tdAllign[$i]" valign="top">$s</td>
EOF;
                        } // if ($row == 0)
                    } // for $i
                    $htmlTable .= <<<EOF
                          </tr>

EOF;
                } //for $row
            } // foreach $val
            $htmlTable .= <<<EOF
                </table>

EOF;
        } #if
        return $htmlTable;
    } // function showQueryResultSet().

    /**
    * Decode an encoded string from server.
    * Decoding schema:
    *   \. -> \n
    *   \; -> ,
    *   \\ -> \
    * Note: can't use the following: 
    *   return str_replace("\\\\", "\\", str_replace("\;", ",", str_replace("\.", "\n", $s)));
    * because it fails for the below case:
    *   s = "\;". encode(s) = "\\;", decode(encode(s)) = "\,".
    * 
    * The encoding is done at server side in C#. In PHP, the quivalent encoding function is:
    *   function encode($s) {
    *     return str_replace("\n", "\.", str_replace(",", "\;", str_replace("\\", "\\\\", $s)));
    *   }
    * 
    * @param  s An (encoded) string.
    * @return String The decoded string.
    * @By: Thomas Chen. 11/18/2011
    ************************************************/
    public static function decode($s) {
      // Use "===", since "==" treats 0 as false. (http://www.php.net/manual/en/function.strpos.php)
      if (strpos($s, "\\") === false) return $s;

      $t = "";
      $len = strlen($s);
      for ($i = 0; $i < $len; $i ++) {
        $c = $s[$i]; //print "$i: $c<br>";
        if ($c == "\\") {
          $d = $s[$i + 1];
          if ($d == "\\") { $t .= "\\"; $i ++; }
          else if ($d == ";") { $t .= ","; $i ++; }
          else if ($d == ".") { $t .= "\n"; $i ++; }
          else { $t .= "(\\:err)"; } // This shouldn't happen.
        }  
        else { $t .= $c; }
      }
      return $t;
    }

    /**
    * Encode a string so it can be displayed properly as html content.
    *
    * @param  s Input string.
    * @return String The html-encoded string.
    * @By: Thomas Chen. 11/18/2011
    ************************************************/
    public static function htmlEncode($s) {
      $s = str_replace("<", "&lt;", $s);
      $s = str_replace("\n", "<br>", $s);
      $s = str_replace("\t", "&nbsp;&nbsp;", $s);
      $s = str_replace(" ", "&nbsp;", $s);
      return $s;
    }

    /**
    * Returns table alignment based on the datatype
    *
    * @param  dataType Data type that needs alignment such as Integer, float, string, etc.
    * @return String containing the alignment right, center, or left.
    ************************************************/
    private function getTDAlignment ( $dataType ) {

        $alignment = 'left';
        $dataType = preg_replace ("/\[.+\]\:/", "", $dataType);
        $dataType = strtoupper($dataType);  //Switch to uppercase

        if ( $dataType == 'INTEGER' or $dataType == 'FLOAT' or $dataType == 'REAL' or $dataType == 'BYTE') {
            $alignment = 'right';
        }
        # Assume strings and dates are aligned left

        return ( $alignment );
    }   //getTDAlignment

    /**
    * based on based and a hash for values shows a select menu
    *
    * @param name Simply the name of the select item
    * @param id id tag in the select (optinoal)
    * @param optionsHash A hash containing options value -> description
    * @param defaultValue Default value
    * @param instructionValue Optional Default value instruction
    * @return String containing a form select (drop down menu).
    ************************************************/
    public function showFormSelect( $name, $id=NULL, $optionArray, $defaultValue, $instructionValue ) {

      if ( !empty($id) )
        $formSelect = "<select name=\"$name\" id=\"$id\">\n";
      else
        $formSelect = "<select name=\"$name\">\n";

      if ( !empty( $instructionValue ) )
        $formSelect .= "\t<option value=\"\">$instructionValue</option>\n";
      if ( is_array( $optionArray ) ) {
        if (is_array($optionArray) && count(array_filter(array_keys($optionArray),'is_string')) == count($optionArray)) {
          // Select form for associative array
          foreach ( $optionArray as $value => $description ) {
            $selected = ( !empty($defaultValue) && $value == $defaultValue ) ? ' selected="selected"' : '';
            $formSelect .= "\t\t<option value=\"$value\"$selected>$description</option>\n";
          } //foreach
        }
        else {
          // select form for indexed array
          $arraySize = count($optionArray);
          for ( $i = 0; $i < $arraySize; $i++ ) {
            $selected = ( !empty($defaultValue) && $optionArray[$i] == $defaultValue ) ? ' selected="selected"' : '';
            $formSelect .= "\t\t<option value=\"".$optionArray[$i]."\"$selected>".$optionArray[$i]."</option>\n";
          } //foreach
        }
     }
      $formSelect .= "</select>\n";
      return $formSelect;
    } // function showFormSelect

  
    /**
    * Returns an error from the fault string in a html readable format.
    * for downloading.
    *
    * @param errorString Error string that contains the error from the SOAP Service
    * @return Error string in pretty html
    ************************************************/
    public function showErrorResult ( $errorString ) {

        $stackErrorString = preg_replace ("/\n/", "<br/>\n", $errorString);
        $prettyErrorString = $stackErrorString;

        // Let's try to get rid of the full stack trace
        if (preg_match("/--->([^--->]+)--->/",$stackErrorString, $regMatches) ) {
            $prettyErrorString = "Query failed due to errors.  Response: [<strong>$regMatches[1]</strong>]";
        }

        // HACK Since some of the errors that CasJobs returns suck,
        // we're going to replace them with something better
        if ( $stackErrorString == 'Error Fetching http headers' ) {
            $prettyErrorString = 'Your query has failed.  Your query has timed out, please try submitting it as a Slow Queue.';
        }
        $errorString = <<<EOF
            <hr width="90%" />
              <br />
                <table class="results" border="1" cellpadding="3" cellspacing="0" width="90%" style="margin: 0 auto">
                 <tr>
                    <th align="center">Query Results</th>
                  <tr>
                    <td style="color: red">$prettyErrorString</td>
                  </tr>
               </table>
              <br/>
EOF;
        // Only show the stack error if there actually is a difference in the parsing done above
        if ( $prettyErrorString != $stackErrorString ) {
            $errorString .= <<<EOF
            <div class="toggle_menu">
                <div class="toggle_menu_button">
                    <img src="images/plus.gif" alt="+" />
                    <img src="images/minus.gif" alt="-" />
                    <strong>Click to expand/collapse Full Stack Trace</strong>
                </div>
            </div>
            <div class='toggle_menu_item'>
              <table class="results" border="1" cellpadding="3" cellspacing="0" width="90%" style="margin: 0 auto">
                  <tr>
                    <td style="color: red">$stackErrorString</td>
                  </tr>
              </table>
            </div>
            <br/>
EOF;
        } #if
        return $errorString;
    } // function showErrorResult().

    /**
    * Get the news Title from the Wiki page and post
    * a pretty link and header for it.
    * **Must be in TRAC text format**
    *
    * @param  newsUrl URL to the TRAC wiki page
    * @return string with link to news and header
    ************************************************/
    public function showNewsFeed ( $newsUrl ) {

        $newsBlob = array();
        $newsBlob = explode("\n", file_get_contents( $newsUrl."?format=txt" ));
        $newsTitle;
        $newsDescription;
        $newsFeed;

        if ( isset($newsBlob) ) {
            foreach ( $newsBlob as $newsLine ) {
                // Look for the Title and assign it once
                if ( !isset( $newsTitle ) ) {
                    if ( preg_match("/^={1,2}([^=]+)={1,2}/",$newsLine, $titleMatches) ) {
                        $newsTitle = $titleMatches[1];
                    }
                }
                // Look for the description in the wiki page and assign it once.
                if ( !isset( $newsDescription ) ) {
                    if ( preg_match("/^\'\'\'([^\']+)\'\'\'/",$newsLine, $titleMatches) )
                        $newsDescription = $titleMatches[1];
                }
            } #foreach
        } #if
        if ( isset ( $newsTitle ) ) {
            $newsFeed = <<<EOF
                <p>
                    <font size="5px" color="#4B4BFF">$newsTitle</font>
                    &nbsp;&nbsp;<strong>$newsDescription</strong>
                    &nbsp;&nbsp;Read more <a target="_blank" href="$newsUrl">here</a>.
                </p>
EOF;
        }
        return $newsFeed;
    } #showNewsFeed

    /**
    * Returns html and Javascript needed to
    * show a input tag with a countdown.
    * usually needed for the qyery page.
    *
    * @param  formname
    * @return Javascript needed for a countdown.
    ************************************************/
    public function showJSCountDown ( ) {
      $countDownCode = <<<EOF
                    <form name="counter">
                        <input type="text" size="8" name="d2">
                     </form>

                    <script>
                     <!--
                        //
                        var milisec=0
                        var seconds=30
                        document.counter.d2.value='30'

                        function display(){
                          if (milisec<=0){
                              milisec=9
                              seconds-=1
                          }
                          if (seconds<=-1){
                              milisec=0
                              seconds+=1
                          }
                          else
                            milisec-=1
                          document.counter.d2.value=seconds+"."+milisec
                          setTimeout("display()",100)
                    }
                    display()
                    -->
                    </script>

EOF;

    } //showJSCountDown


    /**
    * Returns a select menu item with the available file formats
    * for downloading for extracting
    *
    * @param name Simply the name of the select item
    * @param  fileTypes List of file types that are available for downloading
    * @return String containing a menu for the file types to download.
    ************************************************/
    public function showFileFormatSelect ( $name, $fileTypes ) {

        $fileFormatSelectList = "<select name=\"$name\">\n";
        $fileFormatSelectList .= "<option>Select a File Format...</option>\n";
        if ( is_array( $fileTypes ) ) {
            foreach ( $fileTypes as $type => $description ) {
                $fileFormatSelectList .= "<option value=\"$type\">$type - $description</option>\n";
            } //foreach
        }
        $fileFormatSelectList .= "</select>\n";
        return $fileFormatSelectList;
    } // function showFileFormatSelect

} #PSIHTMLGoodiesClass
?>
