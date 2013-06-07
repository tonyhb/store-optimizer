<?php

# Only allow internal requests
if ($_SERVER['REMOTE_ADDR'] != "127.0.0.1" AND $_SERVER["REMOTE_ADDR"] != "10.0.2.2") {
    die();
}

# Only allow CasperJS requests
if (strpos($_SERVER["HTTP_USER_AGENT"], "CasperJS") === FALSE) {
    #die();
}

# Despite the above serucity checks:
#
# We definitely don't want to just hit the $_GET['version'] in the require path 
# because of the terrible impact on security. We're going to just typecast to 
# an integer, typecast to a string and intersect each character with an 
# undesrscore.
#
# EG: "1_4_2_0" => 1420 => "1_4_2_0

$get_version = $_GET["version"];
$get_version = (string) preg_replace("/[^0-9]/", "", $get_version); # Only allow numerics, which removes any path manipulation

foreach (str_split($get_version) as $character) {
    if ( ! isset($version)) {
        $version = $character;
    } else {
        $version .= "_".$character;
    }
}

# Now, no matter what was passed into $_GET (such as ../../../app/config/etc.xml), the $version variable will be secure

# So we can require the magento file safely, now.
require '../../../' . $version . '/app/Mage.php';

Mage::app('admin')->setUseSessionInUrl(false);
Mage::getConfig()->init();

ini_set("display_errors", 1);
Mage::setIsDeveloperMode(true);

