<?php
/**
 * Logout the user from PSI and return to the login page.
 *
 * GPL version 3 or any later version.
 * @copyleft 2010 University of Hawaii Institute for Astronomy
 * @project Pan-STARRS
 * @name logout.php
 * @author Daniel Chang
 * @since Beta version 2010
 */

require_once ("session.php");

$PSISession->logout();
session_destroy();

$header = "Location: index.php";
header ($header); // redirect to the index page once the session is killed.
?>
