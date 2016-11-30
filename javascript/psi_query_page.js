jQuery(document).ready(function() {

        // on the Upload form
        $('.toggle_upload_menu_button').click(function() {
          $(this).parent().next().slideToggle('fast');
          $(this).children().first().toggle().next().toggle();
            return false;
        });

        // toggles for query example menu
        $('.toggle_load_example_menu_item').hide();
        // Hide collapse buttons at first
        $('.toggle_load_example_button').each(function(i) {
          $(this).children().first().next().hide();
        });

        //Switch the "Open" and "Close" state per click
        // on the Example form
        $('.toggle_load_example_button').click(function() {
            $(this).parent().next().slideToggle('fast');
            $(this).children().first().toggle().next().toggle();
            return false;
        });

        // toggles for quality flag menu
        $('.toggle_flag_menu_item').hide();
        // Hide collapse buttons at first
        $('.toggle_flag_menu_button').each(function(i) {
          $(this).children().first().next().hide();
        });

        //Switch the "Open" and "Close" state per click
        // on the Quality Flag form
        $('.toggle_flag_menu_button').click(function() {
            $(this).parent().next().slideToggle('fast');
            $(this).children().first().toggle().next().toggle();
            // Clear any current flag tables
            $("#idDivFlagForm").html('');
            return false;
        });

        $('.toggle_menu_item').hide();

        // Hide collapse buttons at first
        $('.toggle_menu_button').each(function(i) {
          $(this).children().first().next().hide();
        });

        //Switch the "Open" and "Close" state per click
        $('.toggle_menu_button').click(function() {
          $(this).parent().next().slideToggle('fast');
          $(this).children().first().toggle().next().toggle();
          return false;
        });

        //Call to get columns if user switches MyDB tables

        //Call to get columns if user switches MyDB tables
        $('#idSelectFlagTable').change(function() {
          $.get("query_page.php?ajaxAction=getFlagTableDetailsXML&selectFlagTable="+$("#idSelectFlagTable").val(), function(xml) {
            var flagObjectList = new Array()
            $(xml).find('flag').each(function() {
              var flagName = $(this).find('name').text()
              var flagValue  = $(this).find('value').text()
              var flagDescription = $(this).find('description').text()
              flagObjectList.push( new QualityFlag( flagName, parseInt(flagValue), flagDescription ))
            });
            var qualityFlagTable = buildQualityFlagForm( $("#idSelectFlagTable").val(), flagObjectList );
            $("#idDivFlagForm").html( qualityFlagTable );
          }, 'xml')
        })
      }); //JQuery

      // Object for building quailty flag form
      function QualityFlag( name, value, description) {
        this.name=name
        this.value=value
        this.description = description.replace(/\\;/, ',')
      } //QualityFlag

      // builds a form for the quality flags
      function buildQualityFlagForm( flagTableName, flagObjectList ) {
        var htmlString = ''
        var maxHeight = 400
        // Find out how big we should make our table
        var tableHeight = (flagObjectList.length*35)+40;
        if ( tableHeight > maxHeight )
          tableHeight = maxHeight
        var innerTableHeight = tableHeight - 25
        //style for div tags
        htmlString+= '<style type="text/css">'
        htmlString+= '.qualityFlagTable'
        htmlString+= '{'
        htmlString+= '  float:center;'
        htmlString+= '  height: '+tableHeight.toString()+'px;'
        htmlString+= '  width: 840px;'
        htmlString+= '  padding:3px;'
        htmlString+= '}'
        htmlString+= '.qualityFlagTableContent'
        htmlString+= '{'
        htmlString+= '  float:center;'
        htmlString+= '  height: '+innerTableHeight.toString()+'px;'
        htmlString+= '  overflow:auto;'
        htmlString+= '}'
        htmlString+= '</style>'
        htmlString+= '<div class="qualityFlagTable">'
        htmlString+= '<div class="qualityFlagTableContent">'
        htmlString+= '<form name="formFlagsCalculator" id="idFormQualityFlags" action="">'
        htmlString+= '<table>'
        //header information
        htmlString+= '<tr><th colspan="4">'+flagTableName+'</th>'
        htmlString+= '<tr>'
        htmlString+= '<th>Select<input name="checkAllFlags" type="checkbox" id="idCheckAllFlags" onclick="javascript:setAllFlags();"/></th>'
        htmlString+= '<th>Value</th>'
        htmlString+= '<th>Name</th>'
        htmlString+= '<th>Description</th>'
        htmlString+= '</tr>'

        // print out each flag
        for (i=0; i< flagObjectList.length; i++) {
          var flagObject = flagObjectList[i]
          htmlString+= '<tr>'
          htmlString+= '<td align="right">'
          htmlString+= '<input name="checkFlag" type="checkbox" onclick="javascript:totalQualityFlags();" value="'+flagObject.value.toString()+'" id="idCheckFlag"/>'
          htmlString+= '</td>'
          htmlString+= '<td align="right">'+flagObject.value.toString()+'</td>'
          htmlString+= '<td align="left">'+flagObject.name+'</td>'
          htmlString+= '<td align="left">'+flagObject.description+'</td>'
          htmlString+= '</tr>'
        }
        htmlString += '</table>'
        htmlString+= '</div>'
        htmlString+= '</div>'
        //flag totals
        htmlString += '<table>'
        htmlString+= '<tr>'
        htmlString+= '<th align="right">Decimal:</th>'
        htmlString+= '<th colspan="3" align="left">'
        htmlString+= '<input name="flagTotalInt" onpaste="javascript:dismantleFlagValue(document.getElementsByName(\'flagTotalInt\')[0].value, \'int\');" onkeyup="javascript:dismantleFlagValue(document.getElementsByName(\'flagTotalInt\')[0].value, \'int\');" size="25"/>'
        htmlString+= '</th>'
        htmlString+= '</tr>'
        htmlString+= '<tr>'
        htmlString+= '<th align="right">Binary:</th>'
        htmlString+= '<th colspan="3" align="left">'
        htmlString+= '<input name="flagTotalBin" onpaste="javascript:dismantleFlagValue(document.getElementsByName(\'flagTotalBin\')[0].value, \'bin\');" onkeyup="javascript:dismantleFlagValue(document.getElementsByName(\'flagTotalBin\')[0].value, \'bin\');" size="64"/>'
        htmlString+= '</th>'
        htmlString+= '</tr>'
        htmlString+= '<tr>'
        htmlString+= '<th align="right">Hex:</th>'
        htmlString+= '<th colspan="3" align="left">'
        htmlString+= '<input name="flagTotalHex" onpaste="javascript:dismantleFlagValue(document.getElementsByName(\'flagTotalHex\')[0].value, \'hex\');" onkeyup="javascript:dismantleFlagValue(document.getElementsByName(\'flagTotalHex\')[0].value, \'hex\');" size="25"/>'
        htmlString+= '</th>'
        htmlString+= '</tr>'
        htmlString += '</table>'
        htmlString += '</form>'
        return( htmlString )
      } //buildQualityFlagForm

      // Add up totals for each checked flags
      function totalQualityFlags() {
        var checkedFlags =  document.getElementsByName('checkFlag');
        var totalCheckedFlags = 0
        for (var i = 0; i < checkedFlags.length; i++){
          if ( checkedFlags[i].checked ) {
            totalCheckedFlags += parseInt(checkedFlags[i].value);
          }
        }
        // assign flag totals to input tags
        document.getElementsByName('flagTotalInt')[0].value = totalCheckedFlags.toString();
        document.getElementsByName('flagTotalBin')[0].value = totalCheckedFlags.toString(2);
        document.getElementsByName('flagTotalHex')[0].value = '0x'+totalCheckedFlags.toString(16); //prefix 0x added
      } //totalQualityFlags

      // Take a number and check off the existing flags
      function dismantleFlagValue( flagValue, flagDataType ) {
        // make sure passed value is an integer
        flagInt = 0;
        var checkedFlags =  document.getElementsByName('checkFlag');
        if ( flagDataType == "int") {
          if ( !flagValue.match(/^\d+$/)) return;  //make sure value is a digit
          flagInt = parseInt(flagValue);
        }
        if ( flagDataType == "bin") {
          if ( !flagValue.match(/^[10]+$/)) return; //make sure value is binary
          flagInt = parseInt(flagValue, 2);
        }
        if ( flagDataType == "hex") {
          flagValue = flagValue.replace(/^0x/, ''); // get rid of common prefix
          if ( !flagValue.match(/^[A-Fa-f0-9]+$/)) return; //make sure value is hex
          flagInt = parseInt(flagValue, 16);
        }
        for (var i = 0; i < checkedFlags.length; i++){
          if ( flagInt & parseInt(checkedFlags[i].value) )
            checkedFlags[i].checked = true;
          else
            checkedFlags[i].checked = false;
        }
        // assign value across each data type
        document.getElementsByName('flagTotalInt')[0].value = flagInt.toString();
        document.getElementsByName('flagTotalBin')[0].value = flagInt.toString(2);
        document.getElementsByName('flagTotalHex')[0].value = '0x'+flagInt.toString(16); //prefix 0x added
      } //dismantleFlagValue

      function setAllFlags() {
        var setAllFlags =  document.getElementsByName('checkAllFlags');
        var checkFlags =  document.getElementsByName('checkFlag');
        for (var i = 0; i < checkFlags.length; i++){
           checkFlags[i].checked = setAllFlags[0].checked
        }
        totalQualityFlags();
      } //checkAllFlags
