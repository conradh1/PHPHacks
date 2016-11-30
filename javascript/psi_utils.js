/**
 * This Javascript file is meant to be used for miscellanous JavaScript stuff.
 *
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg, Haydn Huntley, Daniel Chang
 * @since Beta version 2010
 */

/**
  * Confirms that the user wants to Reset the Query Builder Values
  * @param none
  * @return nothing
*/
function confirmStartOver () {

    if (confirm('Are you sure?')) {
        window.location.href='query_builder_step1.php?action=Start20%Over';
    }
    return;
} //confirmStartOver

/**
  * Confirms that the user wants to Reset the Query Builder Values
  * @param none
  * @return nothing
*/
function confirmStartOverMopsQb () {

    if (confirm('Are you sure?')) {
        window.location.href='mops_query_builder_step1.php?action=Start20%Over';
    }
    return;
} //confirmStartOver

/**
  * Opens a new window with an onClick action from the menubar.
  * @param web_page URL of the page to open a new window to.
  * @return nothing
*/
function openNewPage( web_page ) {
    height = Math.floor(screen.height * 0.8);
    width = Math.floor(screen.width * 0.8);

    window.open (web_page, "newwindow", "directories=0, toolbar=0, status=0, location=0, menubar=0, resizable=1, scrollbars=1, width=" + width + ", height=" + height);
} //openNewPage

/**
  * Clear all data in a form
  * @param ele Form element to be cleared
*/
function clearForm( ele ) {

    tags = ele.getElementsByTagName('input');
    for(i = 0; i < tags.length; i++) {
        switch(tags[i].type) {
            case 'password':
            case 'text':
                tags[i].value = '';
                break;
            case 'checkbox':
            case 'radio':
                tags[i].checked = false;
                break;
        }
    }

    tags = ele.getElementsByTagName('select');
    for(i = 0; i < tags.length; i++) {
        if(tags[i].type == 'select-one') {
            tags[i].selectedIndex = 0;
        }
        else {
            for(j = 0; j < tags[i].options.length; j++) {
                tags[i].options[j].selected = false;
            }
        }
    }

    tags = ele.getElementsByTagName('textarea');
    for(i = 0; i < tags.length; i++) {
        tags[i].value = '';
    }

} //clearForm


/**
  * Assigns form values in the query page to its equiv hidden values.
  * For example using the upload query feature, the values on the form (i.e., database) should stay the same.
  * @param ele element to be assigned query form values
*/
function assignQueryPageValues( ele ) {
  // Value for the schema
  ele.hiddenSchema.value = document.formQuery.selectSchema.options[document.formQuery.selectSchema.selectedIndex].value;
  ele.hiddenMyDbTable.value = document.formQuery.myDbTable.value;
  // value for the queue (i.e., slow, fast)
  for (var i=0; i < document.formQuery.queue.length; i++) {
    if ( document.formQuery.queue[i].checked) {
      ele.hiddenQueue.value = document.formQuery.queue[i].value;
    }
  }
  //alert( "debug: "+ele.hiddenQueue.value );
} //assignQueryPageValues

