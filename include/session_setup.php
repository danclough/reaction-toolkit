<?php

session_start();
include('include/config/config.php');

/*
 * An array of all required settings and their respective defaults.
 * If any of these are not set by the end of this script, they will be set to
 * the defaults defined here.
 */
$requiredSettings = array(
    "r" => 3,
    "s" => 1,
    "g" => 1,
    "i" => "b",
    "f" => "b",
    "o" => "s",
    "t" => "m",
    "st" => 1.5,
    "b" => 1,
    "sy" => DEFAULT_SYSTEMID,
    "rn" => "m"
);

//Filter whatever's in $_POST into $argArray so we can safely reference it.
$argArray = filter_input_array(INPUT_POST);
/*
 * If $_POST is empty, filter will return a null value instead of an empty
 * array.  Kind of counter-intuitive.
 */
if (is_null($argArray)) {
    $argArray = array();
}

/*
 * Initialize session params array if not already present, or if it got screwed
 * up somehow, or client requested a reset.
 */
if (isset($argArray['reset']) || !isset($_SESSION['params']) || !is_array($_SESSION['params'])) {
    session_unset();
    $_SESSION['params'] = array();
    unset($argArray['reset']);
}

/*
 * Check for permalink parameter passed via GET.
 */
if (!empty($_GET) && isset($_GET['p'])) {

    //Permalink detected
    $base64PermaString = filter_input(INPUT_GET, "p");

    //Permalink parameter is just a base64 string of options
    $permaString = base64_decode($base64PermaString);

    //Transform decoded string to an array of arguments
    parse_str($permaString, $permaArgs);

    /*
     * All the below options are always present in the permalink string.
     */
    $_SESSION['params']['r'] = $permaArgs['r'];
    $_SESSION['params']['s'] = $permaArgs['s'];
    $_SESSION['params']['g'] = $permaArgs['g'];
    $_SESSION['params']['i'] = $permaArgs['i'];
    $_SESSION['params']['f'] = $permaArgs['f'];
    $_SESSION['params']['o'] = $permaArgs['o'];
    $_SESSION['params']['t'] = $permaArgs['t'];
    $_SESSION['params']['sy'] = $permaArgs['sy'];

    /*
     * A permalink may or may not specify one of the following settings.
     * For example, the dashboard doesn't use re (reactionID) or c (chain)
     * because they are irrelevant for the dashboard calculations or situational
     * to a specific reaction scenario.  When you build a permalink on the
     * dashboard it won't include extraneous settings.
     */
    if (isset($permaArgs['st'])) {
        $_SESSION['params']['st'] = $permaArgs['st'];
    }
    if (isset($permaArgs['b'])) {
        $_SESSION['params']['b'] = $permaArgs['b'];
    }
    if (isset($permaArgs['rn'])) {
        $_SESSION['params']['rn'] = $permaArgs['rn'];
    }

    /*
     * We're setting re and c directly in the $argArray because they shouldn't
     * persist from page to page like the options above.
     */
    if (isset($permaArgs['re'])) {
        $argArray['re'] = $permaArgs['re'];
    }
    if (isset($permaArgs['c'])) {
        $argArray['c'] = $permaArgs['c'];
    }
} else {

    //No permalink present.  Proceed with filtering and checking for POST data.

    if (isset($argArray['reset'])) {

        //Client requested a reset of their session parameters.
        session_unset();
        unset($argArray['reset']);
    } elseif (isset($argArray['configured'])) {

        //A POST form was submitted.  Change session variables accordingly.

        /*
         * HTML form checkboxes are stupid because if the checkbox is NOT
         * checked, it doesn't show up in the parameters.  So we'll need to
         * assume that all our possible checkboxes are unchecked and initialize
         * them to 0, then only change them later if they are actually defined
         * in the POST data.
         */
        $_SESSION['params']['s'] = 0;
        $_SESSION['params']['g'] = 0;

        //Tower race (select)
        if (isset($argArray['r'])) {
            $_SESSION['params']['r'] = $argArray['r'];
        }
        //Sovereignty (checkbox)
        if (isset($argArray['s'])) {
            $_SESSION['params']['s'] = $argArray['s'];
        }
        //GSF Moon Tax (checkbox)
        if (isset($argArray['g'])) {
            $_SESSION['params']['g'] = $argArray['g'];
        }
        //Input pricing (select)
        if (isset($argArray['i'])) {
            $_SESSION['params']['i'] = $argArray['i'];
        }
        //Fuel pricing (select)
        if (isset($argArray['f'])) {
            $_SESSION['params']['f'] = $argArray['f'];
        }
        //Output pricing (select)
        if (isset($argArray['o'])) {
            $_SESSION['params']['o'] = $argArray['o'];
        }
        //Broker's Fees (text)
        if (isset($argArray['b'])) {
            $_SESSION['params']['b'] = $argArray['b'];
        }
        //Sales Tax (text)
        if (isset($argArray['st'])) {
            $_SESSION['params']['st'] = $argArray['st'];
        }
        //Timeframe (select)
        if (isset($argArray['t'])) {
            $_SESSION['params']['t'] = $argArray['t'];
        }
        //Date range (select)
        if (isset($argArray['rn'])) {
            $_SESSION['params']['rn'] = $argArray['rn'];
        }
        //Market hub (select)
        if (isset($argArray['sy'])) {
            $_SESSION['params']['sy'] = $argArray['sy'];
        }
    }
}

/*
 * For good measure we'll check through the list of required settings and set
 * any uninitialized settings to the appropriate default value.
 */
foreach ($requiredSettings as $setting => $value) {
    if (!array_key_exists($setting, $_SESSION['params'])) {
        //Setting not found in session parameters, so set it to the default.
        $_SESSION['params'][$setting] = $value;
    }
}

//Mark session as being fully initialized.
$_SESSION['init'] = true;
