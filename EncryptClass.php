<?php
/**
 * @class EncryptClass
 * This class does encryption and decryption of a given string.
 *
 * So far is used in PostageStampClass.php and psimg.php.
 *
 * @version Beta
 * GPL version 3 or any later version.
 * copyleft 2013 University of Hawaii Institute for Astronomy
 * project Pan-STARRS
 * @author Thomas Chen
 * @since Beta version 2013
 */

class EncryptClass
{
    private $a;
    private $salt;
    private $DEBUG;

    public function __construct() {
        $this->a = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMN-/.OPQRSTUVWXYZ1234567890";

        // PHP function call date('n') returns the month (1..12).
        // Use this to add to confusion.
        // Salt represents how many positions the encrypted letter is away from the original letter.
        $this->salt = (date('n') + 13) % strlen($this->a);
        //print "month: " . date('n') . ". salt: $this->salt\n";

        $this->DEBUG = 0;
    }

    /**
      * Encrypt a string.
      * Do a letter to letter replacement for each letter in input string s.
      *
      * @param s The string to encrypt.
      * @return The encrypted version of input string.
      */
    public function encrypt($s) {
        $a = $this->a;
        $alen = strlen($a); 

        for ($i = 0, $len = strlen($s); $i < $len; ++ $i) {
            $n = strpos($a, $s[$i]);
            $na = ($n + $this->salt) % $alen;
            if ($this->DEBUG) { 
                print "$s[$i]: $n ($na),"; 
            }
            $s[$i] = $a[$na];
        }

        return $s;
    }

    /**
      * Decrypt a string. 
      * Reverse the encrypt process.
      *
      * @param s The string to encrypt.
      * @return The encrypted version of input string.
      */
    public function decrypt($s) {
        $a = $this->a;
        $alen = strlen($a); 

        for ($i = 0, $len = strlen($s); $i < $len; ++ $i) {
            $n = strpos($a, $s[$i]);
            $na = ($n + $alen - $this->salt) % $alen;
            if ($this->DEBUG) { print "$n ($na),"; }
            $s[$i] = $a[$na];
        }

        return $s;
    }

    /**
      * If a string is an url and starts with "http://", strip the "http://" part and do encryption.
      *
      * @param url An url string.
      * @return The encrypted url (without leading "http://").
      */
    public function encryptUrl($url) {
        $h = "http://";
        if ( $this->startsWith(strtolower($url), $h) ) {
            $url = substr($url, strlen($h));
        }

        return $this->encrypt($url);
    }

    /**
      * An encrypted url string, that was stripped the "http://" part before encryption,
      * now when decrypt, add back the leading "http://" part.
      *
      * @param url An encrypted url string.
      * @return The decypted url string with "http://" at the beginning.
      */
    public function decryptUrl($url) {
        return "http://" . $this->decrypt($url);
    }

    /**
      * Returns true if haystack starts with needle.
      *
      * @param haystack A string
      * @param needle, another string.
      * @return A boolean value, representing whether haystack starts with needle.
      */
    private function startsWith($haystack, $needle) {
        return !strncmp($haystack, $needle, strlen($needle));
    }
}

?>
