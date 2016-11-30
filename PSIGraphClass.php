<?php
/**
 * @class PSIGraphClass
 * This PSI class is meant for handling jquery and html plotting and graphing for myDB tables
 *
 * @version Beta
 * license GPL version 3 or any later version.
 * copyleft 2010 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Conrad Holmberg
 * @since Beta version 2010
 */
class PSIGraphClass
{

    /// Private variables
    private $PSISession; // MyDB instance needed for performing queries for plots and graphs.
    private $jobsSOAPClient; // SOAP Client Object used to make SOAP calls to the DRL Jobs Service.

    // Form variables
    private $selectMyDBTable;
    private $columnList;
    private $selectGraphType;

    private $isBarGraph;
    private $isHistogram;
    private $isProjectionGraph;

    private $selectXAxis;
    private $selectXAxisMinOper;
    private $selectXAxisMaxOper;
    private $textXAxisMin;
    private $textXAxisMax;

    private $selectYAxis;
    private $selectYAxisMinOper;
    private $selectYAxisMaxOper;
    private $textYAxisMin;
    private $textYAxisMax;

    private $checkAstroFilterIDs = array();
    private $selectPlotLimit;
    private $selectColumnOrder;
    private $radioColumnAscDec;
    private $selectFilterIDColumn;

    private $inverseX;
    private $inverseY;

    // Lists needed for graphing options
    private $graphTypesList = array( "Scatter", "Line", "Line Stepped", "Line Plotted", "Bar", "Histogram", 'HammerProjection', 'SinusoidalProjection', 'MollweideProjection', 'FlatProjection', 'NorthPoleProjection', 'SouthPoleProjection');

    /**
    * Default constructor assigns needed env variables.
    *
    *
    * @param PSISession - creates instance of the  PSIGraphClass class.
    * @param MyDB - Object needed to perform quereies against the mydb.
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
        }
        else {
            error_log("Cannot constuct PSIGraphClass class instance.  PSISession object is NULL.");
            exit();
        }
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
         die ( "Unkown method call in PSIGraphClass: $method.  ".
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
    * returns the Java Script needed for for a hammer plot
    *
    * @param astrofilters
    * @return javascript for hammer projection
    */
    public function drawProjection ( $astroFilters ) {

      // Display Results
      $resultSet = self::executeGraphQuery( self::buildGraphQueryString(), $errorString );
      $projectionType = $this->selectGraphType;

      // If there is a problem with the query return jsut the error
      // instead of a graph
      $jsOutput = $this->getProjectionHtml();
      $jsOutput .= <<<EOF
      <script type="text/javascript">
	fields = new Array();
	values = new Array();

EOF;
      if ( isset( $errorString ) ) return $errorString;

      // Parse $resultSet.
      // Note: $resultSet's only key is 'return'.
      foreach ($resultSet as $key => $val) {

	// Used for holding the index value of the column
	$raIndex = -1;
	$decIndex = -1;
	$filterIndex = -1;
	$filterValue = '';
	// Assign columns In our case Ra = XAxis and Dec = YAxis
	$raColumn = $this->selectXAxis;
        $decColumn = $this->selectYAxis;
	$filterIDColumn = $this->selectFilterIDColumn;
	// Split the query results using "\n".
	$queryResult = explode ("\n", $val);

	// Count the number of rows returned.
	$numRows = count ($queryResult);

	// Count the number of fields.
	$numFields = substr_count ($queryResult[0], ":");

	$data = explode (",", $queryResult[0]);

	//Find that columns that correspond to ra, dec, and filterID (optional).
	for ($i = 0; $i < $numFields; $i++) {
	  // Assign the corresponding index
	  if ( !empty($filterIDColumn) && preg_match("/$filterIDColumn/i",$data[$i])  ) {
	    $filterIndex = $i;
	  }
	  else if ( !empty( $raColumn )  && preg_match("/$raColumn/i",$data[$i])  ) {
	    $raIndex = $i;
	  }
	  else if ( !empty( $decColumn )  && preg_match("/$decColumn/i",$data[$i])  ) {
	    $decIndex = $i;
	  }
	} // for $numFields

	// Split each row value using "," and display the data.
	for ($row = 1; $row < $numRows; $row++) {
	    $data = explode (",", $queryResult[$row]);
	    // ignore header row
	      // special case for filter ID
	      if ( $filterIndex != -1 )
		$filterValue = "filter:\"".$astroFilters[$data[$filterIndex]]."\",";
	      //append a row to plot if we have all the data
              //if ( $data[$raIndex] != "" && $data[$decIndex] != "" ) { // this works too.
              if ( isset($data[$raIndex]) and isset($data[$decIndex]) ) {
		$jsOutput .= "\t\tvalues = {ra_deg:\"".$data[$raIndex]."\", dec_deg:\"".$data[$decIndex]."\",$filterValue selected:\"0\"};\n";
		$jsOutput .= "\t\tfields[".($row-1)."]= values;\n";
	      }
	} //for $row
      } // foreach $val
      $jsOutput .= <<<EOF
	 </script>

         <script type="text/javascript" src="javascript/slider/range.js"></script>
         <script type="text/javascript" src="javascript/slider/timer.js"></script>
         <script type="text/javascript" src="javascript/slider/slider.js"></script>
         <link type="text/css" rel="StyleSheet" href="javascript/slider/winclassic.css" />
         <script type="text/javascript" src="javascript/hammer.js"></script>
         <!--[if ! IE]>--> <script type="text/javascript" src="javascript/map.js"></script> <!--<![endif]-->
         <!--[if IE]> <script type="text/javascript" src="javascript/map.ie.js"></script> <![endif]-->
         <script type="text/javascript">
         var projMap;
         window.onload = function() { projMap = new ProjectionMap( new $projectionType() ); };
         </script>
EOF;
      return $jsOutput;
    } //getProjection

    /**
    *
    * @return html needed for a projection
    */
    private function getProjectionHtml() {
?>
    <font size=-1>Drag/move by mouse. Use slider to rotate/zoom.
    Hold S to select by mouse. Hold D to de-select by mouse. Use C to clear all selection.</font><br>
    <canvas id="map" width="900" height="600" style="z-index: 100; position: relative; border: 1px solid #333333; background-color: #000000;">
    <p>Your browser doesn't support canvas.</p>
    </canvas>
    <canvas id="selectCanvas" style="z-index: 200; position: absolute; -ms-filter:'progid:DXImageTransform.Microsoft.Alpha(Opacity=0)'; filter: alpha(opacity=0);"></canvas>

    <!--Tool bar. Start.-->
    <table border='0' id="MapToolbar" align='center' cellpadding=0 cellspacing=0 style='font-size:11pt;'><tr>
    <td width='205'>
    <div class="slider" id="slider-1" tabIndex="1">
    <input class="slider-input" id="slider-input-1" name="slider-input-1"/></div>
    </td>
    <td width=25>
    <image src='javascript/slider/play.gif' width='25' title='Spin' onClick="javascript:btnSpin_OnClick(this);">
    </td>
    <td width=110 align='left'>
    &nbsp;Rotation: <div id='vRotation' style='display: inline;'>0</div>
    </td>
    <td width='205'>
    <div class="slider" id="slider-2" tabIndex="1">
    <input class="slider-input" id="slider-input-2" name="slider-input-2"/></div>
    </td>
    <td width=100 align='left'>
    Zoom: <div id='vZoom' style='display: inline;'>10</div>
    </td>
    <td align='left' width='80'>
    <input type=button value='Reset' onClick="javascript:useDefault();" title="Click to reset to default value">
    </td>
    <td width='175'><div id="example"></div></td>
    </tr>
    <tr>
    <td colspan='2' valign='top'>Selected Frames (ra, dec): <div id="FrameCount" style="display: inline;"></div>
        <br><TEXTAREA ID='FrameList' rows=5 style='width: 230px;' READONLY></TEXTAREA></td>
    <td colspan='5'> <div id="tmp"></div><!--Used to print debug information.--> </td>
    </tr>
    </table>
    <!--Tool bar. End.-->

<?php
    } //getProjectionHtml()

    /**
    * returns the Java Script needed for a graph
    *
    * @param divName Name of the div tag for plotting
    * @return
    */
    public function drawGraph ( $divName ) {
      $graphQuery = self::buildGraphQueryString();
      $erroString = NULL;
      $yAxisRange = '';
      $xAxisRange = '';
      $inverseX = '';
      $inverseY = '';
      // Assign JavaScript array
      $data = self::getGraphJsArray( $graphQuery, $errorString );

      // If there is a problem with the query return jsut the error
      // instead of a graph
      if ( isset( $errorString ) ) return $errorString;

      // Return nothing if there are no results
      if ( !isset( $data ) )
	return "Nothing to graph.";
      //print "debug".buildGraphQueryString();
      $plotType = self::getPlotJSType( $this->selectGraphType );

      // Format axis.
      $transform = "transform: function (v) { return -v; }, inverseTransform: function (v) { return -v; }";
      $xaxisAttr = "";
      $yaxisAttr = "";

      if ($this->inverseX) { $xaxisAttr .= " $transform "; }
      if ($this->inverseY) { $yaxisAttr .= " $transform "; }

/* //!! 2011-09-01
      if ( !empty($this->textXAxisMin) && !empty($this->textXAxisMax) ) {
        if ($xaxisAttr != "") { $xaxisAttr .= ", "; }
        $xaxisAttr .= "min: " . $this->textXAxisMin . ", max: " . $this->textXAxisMax;
      }
*/
      if ($this->textXAxisMin != "") {
        if ($xaxisAttr != "") { $xaxisAttr .= ", "; }
        $xaxisAttr .= "min: " . $this->textXAxisMin;
      }

      if ($this->textXAxisMax != "") {
        if ($xaxisAttr != "") { $xaxisAttr .= ", "; }
        $xaxisAttr .= "max: " . $this->textXAxisMax;
      }

/* //!! 2011-09-01
      if ( !empty($this->textYAxisMin) && !empty($this->textYAxisMax) ) {
          if ($yaxisAttr != "") { $yaxisAttr .= ", "; }
          $yaxisAttr .= "min: " . $this->textYAxisMin . ", max: " . $this->textYAxisMax;
      }
*/
      if ($this->textYAxisMin != "") {
          if ($yaxisAttr != "") { $yaxisAttr .= ", "; }
          $yaxisAttr .= "min: " . $this->textYAxisMin;
      }

      if ($this->textYAxisMax != "") {
          if ($yaxisAttr != "") { $yaxisAttr .= ", "; }
          $yaxisAttr .= "max: " . $this->textYAxisMax;
      }

      if ($this->isHistogram) {
        if ($this->customizeHistogramTick) {
          if ($xaxisAttr != "") $xaxisAttr .= ", ";
          $xaxisAttr .= " ticks: [" . $this->ticksStr . "], tickDecimals: $this->xtickDecimals ";
        }

        if ($yaxisAttr != "") $yaxisAttr .= ", ";
        $yaxisAttr .= " tickDecimals: 0 ";
      }

      if ($xaxisAttr != "") { $xaxisAttr = ",xaxis: { $xaxisAttr }"; }
      if ($yaxisAttr != "") { $yaxisAttr = ",yaxis: { $yaxisAttr }"; }
      if ($this->isBarGraph || $this->isHistogram) { $yaxisAttr = ''; }

      //Set the label
      $label = '';
      if ( isset($this->selectXAxis) ) {
	$label = $this->selectXAxis;
      }
      if ( ! $this->isBarGraph && ! $this->isHistogram && isset($this->selectYAxis) ) {
	$label .= ', '.$this->selectYAxis;
      }
      $jsOutput =  <<<EOF
	      <script type="text/javascript">
		// VO Plot code
		$(function () {

		var d1 = $data;

		$.plot($("#$divName"), [
		{  	label: "$label",
			data: d1,
			color: "#FFFFFF",
			$plotType
		}
		], {
		    grid: {
		      backgroundColor: { colors: ["#000000", "#000111"] }
		    }
                    $xaxisAttr
                    $yaxisAttr
		  });
		});

	      </script>
EOF;

      return $jsOutput;
    } //getGraph

    /**
    * returns an array of plot data
    *
    *
    * @return javascript array
    * @param graphQuery Query to exeute for plot
    * @param errorString
    */
    private function getGraphJsArray( $graphQuery, &$errorString ) {
      $graphJsString;

      // Set query for showing MyDB tables.
      $resultSet = self::executeGraphQuery( $graphQuery, $errorString );

      // Assign the result set to array holding the table names
      if ( isset ( $resultSet ) ) {
	$graphJsString = "[";
	foreach ($resultSet as $key => $val) {
	  // Split the query results using "\n".
	  $graphQueryResult = explode ("\n", $val);

	  // Count the number of rows returned.
	  $numRows = count ($graphQueryResult);

          if ($this->isHistogram) $a = Array(); // used by Histogram only.

	  // Parse the results and assign the table array
	  // Skip $graphQueryResult[0], because that is the column header.
	  for ($row = 1; $row < $numRows; $row++) {
	    $data = explode (",", $graphQueryResult[$row]);
	    $x = $data[0];
	    // Remove the leading and trailing double quotes.
	    $x = preg_replace ('/^"/', '', $x );
	    $x = preg_replace ('/"$/', '', $x );
	    // Case for Bar Graph
            if ( $this->isBarGraph )     { $graphJsString .= "[ $row, $x ], "; }
            else if ($this->isHistogram) { array_push($a, $x);                 }
	    else {
              $y = $data[1];
              $y = preg_replace ('/^"/', '', $y );
              $y = preg_replace ('/"$/', '', $y );
	      $graphJsString .= "[ $x, $y ], ";
            }
	  } //for
	} //foreach

        if ($this->isHistogram) { $graphJsString .= $this->getGraphJsArray_Histogram($a); }

        // Format string for JS Array
        $graphJsString  = preg_replace ('/, $/', ']', $graphJsString );

      } # if ( isset ( $resultSet ) )
      else if ( isset( $errorString ) ) {
	$errorString = <<<EOF
	  <p><font  style="color: red">Unable execute query . Error: $errorString</font></p>
EOF;
	error_log( "Stack: $errorString" );
      }
      return ( $graphJsString );
    } //getTableList

    /**
    * returns an array of plot data for Histogram graph type.
    *
    *
    * @return javascript array
    * @param graphQuery Query to exeute for plot
    * @param errorString
    */
    private function getGraphJsArray_Histogram(&$a) {
      $a_len = count($a);
      if ($a_len <= 0) return "";

      $graphJsString = "";
      sort($a);
      $min = $a[0]; $max = $a[$a_len - 1]; $range = $max - $min;
      $bin_len = $this->histogramBins;
      $this->barWidth = $range / $bin_len;

      // Get ticks.
      $this->ticksStr = $min + $this->barWidth / 2;
      for ($i = 1; $i <= $bin_len; $i ++) {
        $this->ticksStr .= ", " . ($min + $i * $range / $bin_len + $this->barWidth / 2);
      }
      $this->getXTickDecimals($this->barWidth);

      // Get count in each bin.
      $bin = Array($bin_len);
      for ($i = 0; $i < $bin_len; $i ++) { $bin[$i] = 0; }
      for ($i = 0; $i < $a_len - 1; $i ++) {
        $bin[ floor( $bin_len * ($a[$i] - $min) / $range ) ] ++;
      }
      $bin[$bin_len - 1] ++; // last entry.

      // Write output.
      for ($i = 0; $i < $bin_len; $i ++) {
        $graphJsString .= "[ " . ($min + $i * $range / $bin_len) . ", " . $bin[$i] . " ], ";
      }

      return $graphJsString;
    }

    /**
    * returns x axis tickDecimals.
    *
    *
    * @return x axis tickDecimals used in flot for Histogram graph type
    * @param barWidth in Histogram graph.
    */
    private function getXTickDecimals($barWidth) {
      $this->xtickDecimals = 1; // 1 if barWidth > 0.
      if ($barWidth > 0 && $barWidth < 1) {
        $x = $barWidth;
        while ($x < 1) {
          $x *= 10;
          $this->xtickDecimals ++;
        }
        $this->xtickDecimals += 2;
      }
    }

    /**
    * returns needed string for graph type.
    *
    *
    * @return string used in flot for graph type
    * @param graphType graph type like scatter etc
    */
    private function getPlotJSType ( $graphType ) {
      switch ( $graphType ) {
        case $this->graphTypesList[0]:
  	  # Scatter
	  return "points: { show: true }";
        case $this->graphTypesList[1]:
	  # Line
	  return "lines: { show: true, fill: true }";
        case $this->graphTypesList[2]:
	  # Line Stepped
	  return "lines: { show: true, steps: true }";
        case $this->graphTypesList[3]:
	  # Lines plotted
	  return "lines: { show: true },
	  	  points: { show: true }";
        case $this->graphTypesList[4]:
	  # Bar
	  return "bars: { show: true }";
        case $this->graphTypesList[5]:
          # Histogram
          return "bars: { show: true, barWidth: " . $this->barWidth . "}";
        default:
	  return "Unknown graph type: $graphType";
      } //switch
    } //getPlotJSType

    /**
    * returns the query string used for graphing based on form information
    *
    *
    * @return graohQuery
    */
    public function buildGraphQueryString () {
      $select = array();
      $from = ' FROM ['.$this->selectMyDBTable.']';
      $where = array();
      $operator = '';

      // push array in this order: x,y,filter.

      if ( !empty ( $this->selectXAxis ) )
	array_push( $select, $this->selectXAxis);
      if ( ! $this->isBarGraph && ! $this->isHistogram && !empty ( $this->selectYAxis ) )
	array_push( $select, $this->selectYAxis);

      // Case Projection graph add filterID
      if ( !empty($this->selectFilterIDColumn) && $this->isProjectionGraph ) {
	    array_push( $select, $this->selectFilterIDColumn);
      }

      $graphQuery = 'SELECT ';

      if ( isset( $this->selectPlotLimit ) && $this->selectPlotLimit ) {
	$graphQuery .= 'TOP '.$this->selectPlotLimit.' ';
      }

      if ( isset( $select ) && is_array( $select ) ) {
	$graphQuery .= join( $select, ', ' );
      }
      $graphQuery .= $from;

      // Add range limits
      //if (  !empty( $this->textXAxisMin ) ) { // 2011-09-01
      if ( $this->textXAxisMin != "" ) {
	$this->selectXAxisMinOper == 'gt' ? $operator = ' > ' : $operator = ' >= ';
	array_push( $where, $this->selectXAxis.$operator.$this->textXAxisMin );
      }

      //if ( !empty( $this->textXAxisMax ) ) { // 2011-09-01
      if ( $this->textXAxisMax != "" ) {
	$this->selectXAxisMaxOper == 'lt' ? $operator = ' < ' : $operator = ' <= ';
	array_push( $where, $this->selectXAxis.$operator.$this->textXAxisMax );
      }

      //if ( ! $this->isBarGraph && ! $this->isHistogram && !empty( $this->textYAxisMin ) ) { // 2011-09-01
      if ( ! $this->isBarGraph && ! $this->isHistogram && $this->textYAxisMin != "" ) {
	$this->selectYAxisMinOper == 'gt' ? $operator = ' > ' : $operator = ' >= ';
	array_push( $where, $this->selectYAxis.$operator.$this->textYAxisMin );
      }

      //if ( ! $this->isBarGraph && ! $this->isHistogram && !empty( $this->textYAxisMax ) ) { // 2011-09-01
      if ( ! $this->isBarGraph && ! $this->isHistogram && $this->textYAxisMax != "" ) {
	$this->selectYAxisMaxOper == 'lt' ? $operator = ' < ' : $operator = ' <= ';
	array_push( $where, $this->selectYAxis.$operator.$this->textYAxisMax );
      }

      if ( $this->isProjectionGraph && !empty ($this->checkAstroFilterIDs) && !empty( $this->selectFilterIDColumn)) {
	array_push( $where, $this->selectFilterIDColumn." IN (".join (', ', $this->checkAstroFilterIDs).")" );
      }

      if ( count( $where ) > 0 ) {
	$graphQuery .= ' WHERE '.join( " AND ",$where );
      }

      // Add sort
      if ( isset( $this->selectColumnOrder ) && $this->selectColumnOrder  != '' ) {
	$graphQuery .= ' ORDER BY '.$this->selectColumnOrder;
	if ( isset( $this->radioColumnAscDec ) && $this->radioColumnAscDec  == 'desc' ) {
	  $graphQuery .= ' DESC ';
	}
      }

      //print "<br/>debug:|$graphQuery|<br/>";
      return $graphQuery;
  } //buildGraphQueryString

  /**
    * Executes a fast query to the DRL for graphing purposes
    * between MySQL and MS-SQL Server (i.e., show tables )
    *
    * @param query The string that contains the sql query to be executed to the MyDB database.
    * @param  errorString The error fault string passed by reference
    * @return resultSet
    */
    public function executeGraphQuery( $query, &$errorString ) {

        $resultSet;

        try {
            $resultSet = $this->jobsSOAPClient->executeQuickJob (
                                   array ('sessionID'   => $this->PSISession->getSessionID(),
                                          'schemaGroup' => $this->PSISession->getDefaultMyDBSchemaGroup(),
                                          'context'     => $this->PSISession->getDefaultMyDBSchema(),
                                          'query'       => $query,
                                          'taskname'   =>  'PSI Graph Function Query',
                                          'isSystem'   =>  1  ) );
        }
        catch (SoapFault $soapFault) {
            $errorString = $soapFault->faultstring;
            return;
        }
        return ( $resultSet );
    } #executeGraphQuery

  /**
  * Assigns variables needed for graphs
  *
  * @param none
  * @return none
  */
  public function initGraphVariables () {

    if ( isset( $_REQUEST['selectMyDBTable']) )
      $this->selectMyDBTable = $_REQUEST['selectMyDBTable'];

    if ( isset( $_REQUEST["selectGraphType"] ) )
      $this->selectGraphType = $_REQUEST["selectGraphType"];

    $this->isHistogram = preg_match("/histogram/i", $this->selectGraphType);
    $this->isBarGraph = preg_match("/bar/i", $this->selectGraphType );
    $this->isProjectionGraph = preg_match("/projection/i", $this->selectGraphType );

    if ( isset($_REQUEST["histogramBins"]) )
      $this->histogramBins = $_REQUEST["histogramBins"];
    else
      $this->histogramBins = 10;

    if ( isset($_REQUEST["customizeHistogramTick"]) )
      $this->customizeHistogramTick = 1;
    else
      $this->customizeHistogramTick = 0;

    if ( isset( $_REQUEST["selectXAxis"] ) )
      $this->selectXAxis = $_REQUEST["selectXAxis"];

    if ( isset( $_REQUEST["selectYAxis"] ) )
      $this->selectYAxis = $_REQUEST["selectYAxis"];

    if ( isset( $_REQUEST["selectFilterIDColumn"] ) )
      $this->selectFilterIDColumn = $_REQUEST["selectFilterIDColumn"];

    if ( !empty($_REQUEST['checkAstroFilterIDs'] ) ) {
      $this->checkAstroFilterIDs = $_REQUEST['checkAstroFilterIDs'];
    }
    else {
      $this->checkAstroFilterIDs = array();
    }
    if ( isset( $_REQUEST["selectPlotLimit"] ) )
      $this->selectPlotLimit = $_REQUEST["selectPlotLimit"];
    else
      $selectPlotLimit = '100000';  // There is no way I'm letting people plot more than that!

    if ( isset( $_REQUEST["selectXAxisMinOper"] ) )
      $this->selectXAxisMinOper = $_REQUEST["selectXAxisMinOper"];
    else
      $this->selectXAxisMinOper = 'gt';

    if ( isset( $_REQUEST["selectXAxisMaxOper"] ) )
      $this->selectXAxisMaxOper = $_REQUEST["selectXAxisMaxOper"];
    else
      $this->selectXAxisMaxOper = 'lt';

    if ( isset( $_REQUEST["textXAxisMin"] ) )
      $this->textXAxisMin = $_REQUEST["textXAxisMin"];

    if ( isset( $_REQUEST["textXAxisMax"] ) )
      $this->textXAxisMax = $_REQUEST["textXAxisMax"];

    if ( isset( $_REQUEST["selectYAxisMinOper"] ) )
      $this->selectYAxisMinOper = $_REQUEST["selectYAxisMinOper"];
    else
      $this->selectYAxisMinOper = 'gt';

    if ( isset( $_REQUEST["selectYAxisMaxOper"] ) )
      $this->selectYAxisMaxOper = $_REQUEST["selectYAxisMaxOper"];
    else
      $this->selectYAxisMaxOper = 'lt';

    if ( isset( $_REQUEST["textYAxisMin"] ) )
      $this->textYAxisMin = $_REQUEST["textYAxisMin"];

    if ( isset( $_REQUEST["textYAxisMax"] ) )
      $this->textYAxisMax = $_REQUEST["textYAxisMax"];

    if ( isset( $_REQUEST["selectColumnOrder"] ) )
      $this->selectColumnOrder = $_REQUEST["selectColumnOrder"];

    if ( isset( $_REQUEST["radioColumnAscDec"] ) )
      $this->radioColumnAscDec = $_REQUEST["radioColumnAscDec"];

    $this->inverseX = 0;
    if ( isset( $_REQUEST["cbInverseX"] ) && $_REQUEST["cbInverseX"] == 'Y' )
      $this->inverseX = 1;

    $this->inverseY = 0;
    if ( isset( $_REQUEST["cbInverseY"] ) && $_REQUEST["cbInverseY"] == 'Y' )
      $this->inverseY = 1;

  } //initGraphVariables();

} //PSIGraphClass
