<?php

namespace Tests;

$loader = require_once dirname(__DIR__)."/vendor/autoload.php";

# The URL, admin username and admin password need to be supplied to run tests.
$required = array("url", "user", "pass");

$opts = getopt("", array("url:", "user:", "pass:"));
$opts = array_filter($opts);

if (array_keys($opts) != $required) {
    $opts["url"] = "http://127.0.0.1/1_4_2_0";
    $opts["user"] = "admin";
    $opts["pass"] = "password1";
    #die("Error: The URL, administration username, and password must be passed as arguments using --url, --user, --pass respectively\r\n");
}

ini_set("display_errors", 1);

# Run our unit tests
$tester = new \Tests\Loader();
$tester->run($opts["url"], $opts["user"], $opts["pass"]);
