<?php
/**
* profile.php
* Profile page with update functions
*
* A user may change their password and/or email address.
* They must also enter their correct current password.
* @author Conrad Holmberg, Daniel Chang
* @since Beta version 2010
* @copyleft 2010 University of Hawaii Institute for Astronomy
* @project Pan-STARRS
*/
require_once ("session.php");
define('TITLE','PSI Profile Page');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
?>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="<?= TITLE ?>" content="Pan-STARRS Science Profile Page"/>
    <meta name="keywords" content="Pan-STARRS Science login Web SQL"/>
    <script type="text/javascript" src="javascript/psi_utils.js"></script>
    <title><?= TITLE ?></title>
    <link href="css/default.css" rel="stylesheet" />
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico" />
    <?php require_once("menubar_header.php"); ?>
</head>
<body>
<?php
  require_once("top.php");
  require_once("menubar.php");
?>
<div id="main">
<?php

// Define variables, includes
$sessionID = $PSISession->getSessionID(); // session ID from PSI instance
$PSIHelp = $PSISession->getHelpObject();
$result = 0; // SOAP client result
$userID = $PSISession->getUserID();
global $userClient;

// If the submitted data is valid then change the profile data using the SOAP client.
try {
  $usersClient = new SoapClient( $PSISession->getUsersService() ); // SOAP users client
}
catch (SoapFault $soapFault) {
  $PSISession->showUserServerError();
  die ("SOAP Fault: (faultcode: {$soapFault->faultcode}, faultstring: {$soapFault->faultstring})");
}
// Predefine vars
$user_id = ""; // a user's ID
$full_name = ""; // a user's full name
$current_password = ""; // the user's current password
$current_email = ""; // the user's current e-mail address
$new_password = ""; // a new password entered by the user
$repeat_new_password = ""; // a confirmation of the new password
$new_email = ""; // a new e-mail address entered by the user
$new_email_level = ""; // new email notification level
$current_email_level = ""; // current email notification level

// Form variables
if ( isset($_POST['submit'] )) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $repeat_new_password = $_POST['repeat_new_password'];
    $new_email = $_POST['new_email'];
    $new_email_level = $_POST['new_email_notification_level'];
}

// Switches for validation
$correct_password = 0; // 1 if the correct password was entered, 0 otherwise
$correct_repeat_new_password = 0; // repeat password matches
$valid_new_password = 1; // default to valid password
$valid_new_email = 1; // default to valid new email

$password_changed = 0; // flag for a change password
$email_changed = 0; // flag for a changed e-mail address
$email_level_changed = 0; // flag for a changed email level

// Perform validation of input
$valid = 0; // indicates that the submission is valid and is ready to be submitted to the SOAP service

// Retrieve user data
$user_data = $usersClient->getUsers(array('sessionID' => $sessionID,
                                          'conditions' => "userid:" . $userID));

// Set values from user data.
$full_name = $user_data->return[0]->Name; // a user's full name
$current_email = $user_data->return[0]->Email; // user's current e-mail
$current_email_level = $user_data->return[0]->EmailLevel; // user's current email level
$_SESSION['emailLevel'] = $current_email_level;

$exception = ""; // Handle SOAP errors

if ( $PSISession->getPasswordHash()  === sha1($current_password) ) { $correct_password=1; }

if( $new_password === $repeat_new_password ) { $correct_repeat_new_password = 1; }

// Check the user submitted input
$valid_new_password = validate_password($new_password); // Validate the new password
$valid_new_email = validate_email($new_email); // Validate the new email address
?>

<?php
if ( $correct_password and isset($_POST['submit'] )) { // User entered their correct password and the form was submitted

    if ( $valid_new_password ) {
        // Update the password hash in the session
        if( strlen($new_password) ) { // update session password hash if necessary
            $PSISession->setPasswordHash( sha1( $new_password ) );
            $_SESSION["PSISessionClass"] = serialize( $PSISession );
            $password_changed = 1;
        }
    }

    if ( !$valid_new_email ) {
        $new_email = ""; // email is not changed
    } else {
        if ( strlen($new_email) ) { // If email address was updated set the flag
            $email_changed = 1;
        }
    }

    if ( !($_SESSION['emailLevel'] == $new_email_level) ) {
        $email_level_changed = 1; // set flag of email level change
    }

    $valid = 1; // indicates repeat entries match but input could still be invalid even when matching

    // Extra fail cases: repeat entries do not match
    if ( !( $new_password === $repeat_new_password )) { $valid = 0; }

    // @todo Improve validation logic
    if ( !$valid_new_password ) { $valid = 0; }
    if ( !$valid_new_email ) { $valid = 0; }
}

// End checking the user submitted input

$invalid_soap_result = 0; // flag for SOAP client result
$soap_fault_msg = ""; // message for SOAP error

// Update the password using the SOAP service
if ( $valid == 1 ) {
    try { // catch all error conditions
        $result = $usersClient->updateAccount( array('sessionID' => $sessionID,
                                                     'userid' => $userID,
                                                     'name' => "",
                                                     'email' => $new_email,
                                                     'password' => $new_password,
                                                     'privileges' => "",
                                                     'emaillevel' => $new_email_level,
                                                     'gvisible' => "")
                                             );
    }
    catch ( Exception $exception ) { $invalid_soap_result = 1;
                                     $soap_fault_msg = $exception->faultstring;}
}
?>

<?php
// If the form was submitted then display the response, otherwise display the form
if ( isset($_POST['submit']) and $valid == 1 and $result ) { // display the form response
?>

<div id="content">
    <div style="text-align: center;">
    <h2><?=$PSIHelp->getWikiURL('PSI-UserProfile')?>&nbsp;<?=TITLE?></h2>
    <p>
        Thank you for your submission. Your profile has been updated.<br/>

<?php
if( $password_changed ) {
?>

Your password has been changed.<br/>

<?php
} // End password changed
else {
?>

Your password has <strong>not</strong> been changed.<br/>

<?php
} // End password not changed
?>

<?php
if ( $email_changed ) {
?>

Your email address is now set to <?= $new_email ?>.<br/>

<?php
} // End email changed
else {
?>

Your email address has <strong>not</strong> been changed.<br/>

<?php
} // End email not changed
?>

<?php
if ( $email_level_changed ) {
?>

Your email notification level is now set to <?= $new_email_level ?>.<br/>

<?php
} // End email level changed
else {
?>

Your email notification level <strong>not</strong> been changed.<br/>

<?php
} // End email level not changed
?>

    </div> <!-- end div style -->
</div> <!-- end content -->

<?php
} // End display the form response
elseif ( isset($_POST['submit']) and $invalid_soap_result == 1 ) { // If the form was submitted but a result was not obtained.
?>

<div id="content">
    <div style="text-align: center;">
    <h2><?=$PSIHelp->getWikiURL('PSI-UserProfile')?>&nbsp;<?= $title ?></h2>

    <p>
        Your submission could not be processed.<br/>
        <?= $soap_fault_msg ?><br/>

    </div> <!-- End div style -->
</div>

<?php
} // End submitted without result
else { // Display the input form
?>

<div id="content">

    <div>
        <h2><?=$PSIHelp->getWikiURL('PSI-UserProfile')?>&nbsp;<?=TITLE?></h2>
    </div>

    <div id="description">
      <ul style="padding-left:0pt;">
        <li>You may change your password and your email address by entering new values.</li>
        <li>You may change your email notification level by selecting a new value.</li>
        <li>Your current password must also be entered to update your information.</li>
        <li>Passwords must be between 5 and 20 characters.</li>
      </ul>
    </div>

    <div style="text-align: center;">

    <form name="form" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <table style="margin: 0 auto;" cellpadding="2" cellspacing="2">
            <tr>
                <td align="right">User ID:&nbsp;</td>
                <td align="left"><?= $userID ?></td>
            </tr>
            <tr>
                <td align="right">Full Name:&nbsp;</td>
                <td align="left"><?= $full_name ?></td>
            </tr>
            <tr>
                <td align="right">Email:&nbsp;</td>
                <td align="left"><?= $current_email ?></td>
            </tr>

            <tr>
                <td align="right">Current password:&nbsp;</td>
                <td align="left"><input type="password" name="current_password" size="20"/>
<?php
if(!$correct_password and isset($_POST['submit'])) {
?>
                    <span class="inputerror">Incorrect current password</span>
<?php
}
?>
                </td>
            </tr>
            <tr>
                <td align="right">New password:&nbsp;</td>
                <td align="left"><input type="password" name="new_password" size="20"/>
<?php
if ( !$valid_new_password ) {
?>
                    <span class="inputerror">New password of length <?= strlen($new_password) ?> is not valid</span>
<?php
}
?>
                </td>
            </tr>
            <tr>
                <td align="right">Repeat new password:&nbsp;</td>
                <td align="left"><input type="password" name="repeat_new_password" size="20"/>
<?php
if ( !$correct_repeat_new_password ) {
?>
                    <span class="inputerror">New password doesn&rsquo;t match</span>
<?php
}
?>
                </td>
            </tr>
            <tr>
                <td align="right">New email address:&nbsp;</td>
                <td align="left"><input type="text" name="new_email" size="40"/>
<?php
if ( !$valid_new_email ) {
?>
                    <span class="inputerror">New email is not valid</span>
<?php
}
?>
                </td>
            </tr>
            <tr>
                <td align="right" valign="top">Email notification level:&nbsp;</td>
                <td>
                    <select name="new_email_notification_level">
                        <option value="0" <?= match_email_level("0",$current_email_level) ?>>0 - Do not send automated mail</option>
                        <option value="1" <?= match_email_level("1",$current_email_level) ?>>1 - Send automated mail when non-quick jobs fail</option>
                        <option value="2" <?= match_email_level("2",$current_email_level) ?>>2 - Send automated mail when non-quick jobs complete successfully</option>
                        <option value="3" <?= match_email_level("3",$current_email_level) ?>>3 - Send automated mail in either of the above two cases</option>
                    </select>
                </td>
            </tr>
        </table>

        <input id="submit-button" type="submit" name="submit" value="Update Profile"/>
    </form>
      </div>
</div><!-- End Content -->
</div>
<!-- End Main -->

<?php
} // End display form
require ("bottom.php");
?>
</body>
</html>

<?php

/**
* Validate a password
* @todo Validate password characters
* @param pw password
* @return 1 if valid, 0 otherwise
*/
function validate_password ($pw) {
  // @todo Remove whitespace

  if( strlen($pw) == 0 ){
      return 1; // Null case
  }
  if( strlen($pw) >= 5 and strlen($pw) <= 20 ) { // Check password length
      return 1;
  }
  return 0; // password is not valid
} // End validate_password

/**
* Validate an email address
*
* Source used from http://www.linuxjournal.com/article/9585?page=0,3
*
* @param email Email address
* @return 1 if valid, 0 otherwise
*/
function validate_email ($email) {
    // @todo Remove whitespace from the user submission

    if( strlen($email) == 0 ) {
        return 1; // Null case
    }

// Original regex
//    if ( eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$", $email )) {
//        return 1;
//    }
//    return 0; // e-mail address is not valid

    $isValid = 1;
    $atIndex = strrpos($email, "@");
    if (is_bool($atIndex) && !$atIndex) {
        $isValid = 0;
    }
    else {
        $domain = substr($email, $atIndex+1);
        $local = substr($email, 0, $atIndex);
        $localLen = strlen($local);
        $domainLen = strlen($domain);
        if ($localLen < 1 || $localLen > 64) {
            // local part length exceeded
            $isValid = 0;
        }
        else if ($domainLen < 1 || $domainLen > 255) {
            // domain part length exceeded
            $isValid = 0;
        }
        else if ($local[0] == '.' || $local[$localLen-1] == '.') {
            // local part starts or ends with '.'
            $isValid = 0;
        }
        else if (preg_match('/\\.\\./', $local)) {
            // local part has two consecutive dots
            $isValid = 0;
        }
        else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
            // character not valid in domain part
            $isValid = 0;
        }
        else if (preg_match('/\\.\\./', $domain)) {
            // domain part has two consecutive dots
            $isValid = 0;
        }
        else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
          // character not valid in local part unless
          // local part is quoted
          if (!preg_match('/^"(\\\\"|[^"])+"$/',
              str_replace("\\\\","",$local))) {
              $isValid = 0;
          }
        }
        if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
            // domain not found in DNS
            $isValid = 0;
        }
    }
    return $isValid;
} // End validate_email

/**
* Set selection of email level based on email level setting
* @param option The option on the form
* @param level Email level
* @return Selection text
*/
function match_email_level ( $option, $level ) {
    if ( $option == $level) {
        return "selected=\"selected\"";
    }
    return '';
} // End match_email_level
