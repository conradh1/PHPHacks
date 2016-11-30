<?php
/**
 * @class PSIAjaxGoodiesClass
 * The PSI AJAX Goodies Class is meant to provide generic handle for
 * Ajax calls and specific xml output depending on that action
 * such as tables and form objects
 *
 * @version Beta
 * GPL version 3 or any later version.
 * @copyleft 2013 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Conrad Holmberg
 * @since Beta version 2010
 */
class PSIAjaxGoodiesClass
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
    * Transforms columns from a table into XML for output from a AJAX call.
    *
    *
    * @param PSPSSchema A PSPS Schema instance
    */
    public function flagTableDetails2XML ( $PSPSSchema ) {

      $flagTable = $PSPSSchema->getSelectFlagTable();
      $flagTableHash = $PSPSSchema->getFlagTableHash( $flagTable );
      $XMLWriter = new XMLWriter();
      // Output directly to the user

      header("Content-Type: text/xml");
      $XMLWriter->openURI('php://output');
      $XMLWriter->startDocument();
      $XMLWriter->setIndent(4);

      // declare it as a PSI Response document
      $XMLWriter->startElement('PSIResponse');

      $XMLWriter->startElement( 'table' );
      $XMLWriter->writeAttribute( 'name', $flagTable );

      foreach ( $flagTableHash as $flag ) {
        $XMLWriter->startElement( 'flag' );
        $XMLWriter->writeElement( 'name', $flag['Name'] );
        $XMLWriter->writeElement( 'value', $flag['Value'] );
        $XMLWriter->writeElement( 'description', $flag['Description'] );
        $XMLWriter->endElement();
      } #foreach

      $XMLWriter->endElement(); // Table
      $XMLWriter->endElement(); // PSIResponse
      $XMLWriter->endDocument();

      $XMLWriter->flush();
    } // ajaxFlagTableDetails2XML

    /**
    * Transforms columns from a table into XML for output from a AJAX call.
    *
    * @param table table (MyDB in this case)
    * @param columnList array containing all the columns of the table
    */
    public function myDBColumns2XML ( $table, $columnList ) {

      $XMLWriter = new XMLWriter();
      // Output directly to the user

      header("Content-Type: text/xml");
      $XMLWriter->openURI('php://output');
      $XMLWriter->startDocument();

      $XMLWriter->setIndent(4);

      // declare it as a PSI Response document
      $XMLWriter->startElement('PSIResponse');

      $XMLWriter->startElement( 'table' );
      $XMLWriter->writeAttribute( 'name', $table );
      if ( isset( $columnList ) && is_array( $columnList ) ) {
        foreach ( $columnList as $column ) {
          $XMLWriter->writeElement( 'column', $column);
        } #foreach
      }
      $XMLWriter->endElement(); // Table
      // End PSIResponse
      $XMLWriter->endElement();

      $XMLWriter->endDocument();

      $XMLWriter->flush();
    } //ajaxMyDBColumns2XML
} #PSIAjaxGoodiesClass


?>
