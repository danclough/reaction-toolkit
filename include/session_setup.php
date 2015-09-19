<?php
session_start();
include('include/config/config.php');
if (!isset($_SESSION['params']) || !is_array($_SESSION['params'])) {
    session_unset();
    $_SESSION['params'] = array();
}
if (empty($_POST) && !empty($_GET) && isset($_GET['p'])) {
    // Permalink detected.
    $argCode = filter_input(INPUT_GET,"p");
    $argString = base64_decode($argCode);
    parse_str($argString,$permaArgs);
    $_SESSION['params']['r'] = $permaArgs['r'];
    $_SESSION['params']['s'] = $permaArgs['s'];
    $_SESSION['params']['g'] = $permaArgs['g'];
    $_SESSION['params']['i']  = $permaArgs['i'];
    $_SESSION['params']['f']  = $permaArgs['f'];
    $_SESSION['params']['o']  = $permaArgs['o'];
    $_SESSION['params']['b']  = $permaArgs['b'];
    $_SESSION['params']['st']  = $permaArgs['st'];
    $_SESSION['params']['t']  = $permaArgs['t'];
    $_SESSION['params']['rn'] = $permaArgs['rn'];
    $_SESSION['params']['sy'] = $permaArgs['sy'];
    if (isset($permaArgs['re'])) {
        $_POST['re'] = $permaArgs['re'];
    }
    if (isset($permaArgs['c'])) {
        $_POST['c'] = $permaArgs['c'];
    }
    $argArray = filter_input_array(INPUT_POST);
} else {
    // No permalink present, initialize or load session variables.
    $argArray = filter_input_array(INPUT_POST);
    if (isset($argArray['reset'])) {
        session_unset();
        unset($argArray['reset']);
    }
    if (!isset($_SESSION['params']['r'])) {
        //This is a blank session or a reset.  Let's set some defaults.
        // Race
        $_SESSION['params']['r'] = 3;
        // Sov
        $_SESSION['params']['s'] = 1;
        // GSF
        $_SESSION['params']['g'] = 1;
        // Input Price
        $_SESSION['params']['i'] = "b";
        // Fuel Price
        $_SESSION['params']['f'] = "b";
        // Output Price
        $_SESSION['params']['o'] = "s";
        // Timeframe
        $_SESSION['params']['t'] = "m";
        // Sales Tax Percent
        $_SESSION['params']['st'] = 1.5;
        // Broker Fee Percent
        $_SESSION['params']['b'] = 1;
        // System
        $_SESSION['params']['sy'] = DEFAULT_SYSTEMID;
        // Date Range
        $_SESSION['params']['rn'] = "m";
        // Initialized?
        $_SESSION['init'] = true;
    } elseif (isset($argArray['configured'])) {
        // An options form was submitted, so change session variables accordingly.
        if (isset($argArray['r'])) {
            $_SESSION['params']['r'] = $argArray['r'];
        }
        $_SESSION['params']['s'] = 0;
        if (isset($argArray['s'])) {
            $_SESSION['params']['s'] = $argArray['s'];
        }
        $_SESSION['params']['g'] = 0;
        if (isset($argArray['g'])) {
            $_SESSION['params']['g'] = $argArray['g'];
        }
        if (isset($argArray['i'])) {
            $_SESSION['params']['i'] = $argArray['i'];
        }
        if (isset($argArray['f'])) {
            $_SESSION['params']['f'] = $argArray['f'];
        }
        if (isset($argArray['o'])) {
            $_SESSION['params']['o'] = $argArray['o'];
        }
        if (isset($argArray['b'])) {
            $_SESSION['params']['b'] = $argArray['b'];
        }
        if (isset($argArray['st'])) {
            $_SESSION['params']['st'] = $argArray['st'];
        }
        if (isset($argArray['t'])) {
            $_SESSION['params']['t'] = $argArray['t'];
        }
        if (isset($argArray['rn'])) {
            $_SESSION['params']['rn'] = $argArray['rn'];
        }
        if (isset($argArray['sy'])) {
            $_SESSION['params']['sy'] = $argArray['sy'];
        }
        $_SESSION['init'] = true;
    }
}
