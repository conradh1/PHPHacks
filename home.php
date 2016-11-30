<?php
/**
 * This is the home page that gives the content
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @author Conrad Holmberg
 * @since Beta version 2010
 */
require_once ("session.php");
define('TITLE','PSI Menu Page');
$PSIHelp = $PSISession->getHelpObject();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Interface Main Page" />
    <meta name="keywords" content="Pan-STARRS Science Interface Web SQL" />
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <title><?= TITLE ?></title>
    <link rel="stylesheet" href="css/default.css" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
    <?php require_once ("menubar_header.php"); ?>
</head>
<body>
<?php

require_once ("top.php");
require_once ("menubar.php");
$PSIHTMLGoodies = new PSIHTMLGoodiesClass();

?>
<div id="main">
    <div class="content">
        <div style="text-align: center">
            <h3>Hello <?= $PSISession->getUserID() ?>, PSI provides the following options:</h3>
	</div>
            <div align="left">
            <ul>
                <li><strong>Main</strong>
                    <ul>
                      <li>
                         <strong><a href="home.php">Index Map</a></strong>&ndash; Returns you to this index map in PSI which you are on now.
                      </li>
                      <li><strong><a href="index.php">News</a></strong>
                          &ndash; Get the latest on what has been loaded and other announcements.
                      </li>
                      <li><strong><a href="<?=$PSISession->getHelpWikiURL()?>" target="_blank">Help</a></strong>
                        &ndash; Provide help information.
                      </li>
                      <li><strong><a href="profile.php">Profile</a></strong>
                        &ndash; Change your password, email  address, and email job completion reporting.
                      </li>
                      <li><strong><a href="logout.php">Logout</a></strong>
                        &ndash; Exit from the Pan-STARRS Science Interface.
                      </li>
                    </ul>
                </li>
                <li><strong><a href="query_page.php??timeStart=2010-11-20">Query Page</a></strong>
                        &ndash; Enter an SQL query to
                        submit to any of the available
                        databases. You
                        can select execution from the fast or
                        slow queue processes.
                </li>
                <li><strong><a href="queued.php">Queued Jobs</a></strong>
                            &ndash; Review the status of queries or other jobs that have been
                            submitted to PSPS. You can cancel queries that
                            have not finished. Queries that have been
                            submitted previously may easily be edited and
                            resubmitted.
                </li>
                <li><strong><a href="mydb.php">MyDB</a></strong>
                        &ndash; Examine, download, extract and delete the tables in your MyDB database.
                </li>
                <li><strong><a href="graph.php">Graphing</a></strong>
                        &ndash; Gives the ability to graph data (i.e., scatter, hammer projection) from myDB tables.
                </li>
                <li><strong>Postage Stamp</strong>
                  <ul>
                    <li><strong><a href="postage_stamp.php">Request Form</a></strong>
                        &ndash; An interface to make Postage Requests in a variety of methods.
                    </li>
                    <li><strong><a href="postage_stamp_results.php">Results Page</a></strong>
                        &ndash; Look at results of postage stamp requests, including images (if requested).
                    </li>
                    <li><strong><a href="postage_stamp_status.php">Server Status Page</a></strong>
                        &ndash; A summary of all the current requests being processed.
                    </li>
                    <li><strong><a href="postage_stamp_releases.php">Summary of Releases</a></strong>
                        &ndash; Summary of postage stamp releases information.
                    </li>
                  </ul>
                </li>
                <li><strong>Published Science Products Subsystem (PSPS)</strong>
                  <ul>
                  <li><strong><a href="query_builder_step1.php">PSPS Query Builder</a></strong>
                    &ndash; A menu driven query builder that allows you
                            to select attributes from menus and then
                            generates an SQL query for you. You can
                            review the SQL query and then submit it.
                  </li>
                  <li><strong><a href="javascript:openNewPage('schema_browser.php' );">PSPS Schema Browser</a></strong>
                        &ndash; Examine the schema of the Pan-STARRS Science database.
                  </li>
                  </ul>
                </li>
                <li><strong>Moving Object Processing System (MOPS)</strong>
                  <ul>
                    <li><strong><a href="mops_query_builder_step1.php">MOPS Query Builder</a></strong>
                        &ndash; Enter an SQL query to
                        submit to any of the available
                        MOPS databases. You
                        can select execution from the fast or
                        slow queue processes.
                    </li>
                    <li><strong><a href="javascript:openNewPage('<?=$PSISession->getMopsSchemaURL()?>' );">MOPS Schema Browser</a></strong>
                        &ndash; Examine the MOPS Schema.
                    </li>
                    <li><strong><a href="http://chenx.web02.psps.ifa.hawaii.edu/PSI/MopsViewNeoReport.php?type=SubmittedNeos">250 most recent PS1 NEO submissions</a></strong>
                    </li>
                    <li><strong><a href="http://chenx.web02.psps.ifa.hawaii.edu/PSI/MopsViewNeoReport.php?type=DiscoveredNeos">PS1 NEO Discoveries</a></strong>
                    </li>
                  </ul>
                </li>
            </ul>
            </div>
    </div>
    <!-- End Content -->
</div>
<!-- End Main -->
<br/>
<?php require ("bottom.php"); ?>
</body>
</html>
