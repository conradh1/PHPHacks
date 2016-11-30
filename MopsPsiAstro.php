<?php
/**
 * Astronomical conversion routines used in MOPS views.
 */
class MopsPsiAstro {

    public $StrSep;
    public $StrZero;

    public function __construct() {
        $this->StrSep = ":";
        $this->StrZero = 2;
    } // __construct

    /**
     * turn2str
     * Converted to PHP from Astro::Time
     *
     *  $str = turn2str($turn, $mode, $sig);
     *  $str = turn2str($turn, $mode, $sig, $strsep);
     *
     *  Convert fraction of a turn into string representation
     *      $turn   Angle in turns
     *      $mode   Mode of string to convert to:
     *                  'H' for hours
     *                  'D' for degrees
     *      $sig    number of significant figures
     *      $strsep String separator (override for default $Astro::Time::StrSep)
     *
     *  Note:
     *      The behavior can be modified by the following two variables:
     *      $Astro::Time::StrZero   Minimum number of leading digits (zero padded
     *                              if needed)
     *      $Astro::Time::StrSep    (Overridden by optional fourth argument)
     *          Deliminator used in string (Default ':')
     *          This may also equal one of a number of special values:
     *              'HMS'           12H45M12.3S or 170D34M56.2S
     *              'hms'           12h45m12.3s or 170d34m56.2s
     *              'deg'           170d34'56.2"
     *
     * @param turn
     * @param mode
     * @param sig
     * @param strsep
     * @return string
     */
    public function turn2str ($turn, $mode, $sig, $strsep) {

        $mode = strtoupper($mode);
        if (($mode != 'H') && ($mode != 'D')) {
            print 'turn2str: $mode must equal \'H\' or \'D\'';
            return NULL;
        }

        if($strsep == "") {
            $strsep = $StrSep;
        }

        $angle = "";
        $str = "";
        $sign = "";
        $wholesec = "";
        $secfract = "";
        $min = "";

        if ($mode == 'H') {
            $angle = $turn * 24;
        } else {
            $angle = $turn * 360;
        }

        if ($angle < 0.0) {
            $sign = -1;
            $angle = -$angle;
        } else {
            $sign = 1;
        }

        $wholeangle = (int)($angle);

        $angle -= $wholeangle;
        $angle *= 3600;

        # Get second fraction
        $wholesec = (int)($angle);
        $secfract = $angle - $wholesec;

        $wholesec %= 60;
        $min = ($angle-$wholesec - $secfract)/60.0;
        $secfract = (int)($secfract * pow(10, $sig) + 0.5); # Add 0.5 to ensure rounding

        # Check we have not rounded too far
        if ($secfract >= pow(10, $sig)) {
            $secfract -= pow(10, $sig);
            $wholesec++;
            if ($wholesec >= 60.0) {
                $wholesec -= 60;
                $min++;
                if ($min >= 60.0) {
                    $min -= 60;
                    $wholeangle++;
                }
            }
        }

        $angleform = "";
        if ($this->StrZero > 0) {
            $angleform = "%0$this->StrZero";
        } else {
            $angleform = '%';
        }

        $sep1="";
        $sep2="";
        $sep3="";

        if ($strsep == 'HMS') {
            if ($mode == 'H') {
                $sep1 = 'H';
            } else {
                $sep1 = 'D';
            }
            $sep2 = 'M';
            $sep3 = 'S';
        } elseif ($strsep == 'hms') {
            if ($mode == 'H') {
                $sep1 = 'h';
            } else {
                $sep1 = 'd';
            } // if mode
            $sep2 = 'm';
            $sep3 = 's';
        } elseif ($strsep == 'deg') { # What if $mode == 'H'??
                $sep1 = 'd';
                $sep2 = "'";
                $sep3 = '"';
        } else {
            $sep1 = $sep2 = $strsep;
            $sep3 = '';
        }

        if ($sig > 0) {
            $str = sprintf("${angleform}d$sep1%02d".
                           "$sep2%02d.%0${sig}d$sep3",
                            $wholeangle, $min, $wholesec, $secfract);
        } else {
            $str = sprintf("${angleform}d$sep1%02d".
                           "$sep2%02d$sep3",
                            $wholeangle, $min, $wholesec);
        }

        if ($sign == -1) {
            $str = '-'.$str;
        }
        return $str;
    } // function

    /**
     *  $str=deg2str($deg, $mode, $sig);
     *
     * Convert degrees into string representation
     *     $deg   angle in degrees
     *     $mode  mode of string to convert to:
     *         'H' for hours
     *         'D' for degrees
     *     $sig   number of significant figures
     * See note for turn2str
     *
     * @param deg
     * @param mode
     * @param sig
     * @param strsep
     * @return string
     */
    public function deg2str ($deg, $mode, $sig, $strsep) {
        return $this->turn2str($deg/360, $mode, $sig, $strsep);
    }

    /**
     * mjd2cal
     * Adapted from Astro::Time
     *
     * ($day, $month, $year, $ut) = mjd2cal($mjd);
     *
     * Converts a modified Julian day number into calendar date (universal
     * time). (based on the slalib routine sla_djcl).
     * $mjd     Modified Julian day (JD-2400000.5)
     * $day     Day of the month.
     * $month   Month of the year.
     * $year    Year
     * $ut      UT day fraction
     *
     * @param mjd
     * @return calendar date
     */

    public function mjd2cal($mjd) {

        $ut = fmod($mjd,1.0);

        if ($ut < 0.0) {
            $ut += 1.0;
            $mjd -= 1;
        }

        // use integer; # Calculations require integer division and modulation

        # Get the integral Julian Day number
        $jd = (int)($mjd + 2400001);

        # Do some rather cryptic calculations

        // @note original statements
        // $temp1 = 4 *( $jd + ((6*( ( (4 * $jd - 17918) /146097) ) ) /4 +1) /2 - 37 );
        // $temp2 = 10*((($temp1-237)%1461)/4)+5;

        $temp1 = (4 * $jd - 17918);
        $temp1 = (int)($temp1 / 146907);
        $temp1 = (int)(6 * $temp1);
        $temp1 = (int)($temp1 / 4) + 1;
        $temp1 = (int)($temp1 / 2);
        $temp1 = (int)($jd) + $temp1 - 37;
        $temp1 = (int)(4 * $temp1);

        $temp2 = $temp1 - 237;
        $temp2 = (int)($temp2 % 1461);
        $temp2 = (int)($temp2 / 4);
        $temp2 = (int)(10 * $temp2 + 5);

        $year = (int)($temp1 / 1461) - 4712;

        // @note original statement
        // $month = (($temp2/306+2)%12)+1;
        $month = (int)($temp2 / 306);
        $month = $month + 2;
        $month = (int)($month % 12);
        $month = $month + 1;

        // @note original statement
        // $day = ($temp2%306)/10+1;
        $day = (int)($temp2 % 306);
        $day = (int)($day / 10);
        $day = $day + 1;

        return array($day, $month, $year, $ut);
    } // end mjd2cal




} // class
?>
