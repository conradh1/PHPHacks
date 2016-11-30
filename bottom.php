<?php
  /**
 * Script footer - Note That the help email is here.
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg
 * @since Beta version 2010
 */

$emailHelp = $PSISession->getHelpEmail();
?>
<div id="footer">
    <hr/>
    <table id="tfooter" width="100%">
        <tr>
            <td>
                Please email your comments, questions, and help inquires to <a href="mailto:<?=$emailHelp?>"><?=$emailHelp?></a>.
                For the main help wiki, click:
                <a target="_blank" href="<?=$PSISession->getMainWikiURL()?>"><img src="images/question.gif" alt="Click for help on this topic." /></a>
            </td>
        </tr>
    </table>
</div>
<!-- End Footer -->
