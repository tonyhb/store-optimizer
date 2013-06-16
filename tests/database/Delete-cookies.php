<?php

require 'Base.php';

@unlink(dirname(__DIR__)."/cookies.txt");

# We delete the cookies whilst running CasperJS test suites. CasperJS doesn't 
# remove the cookies from the running PhantomJS instance, so we need to clear 
# Magento's session storage, too.
# As well as removing our cookies

$write = Mage::getSingleton('core/resource')->getConnection("core/write");
$write->query("TRUNCATE TABLE `core_session`;");

# And, if we're using file based sessions, remove them too.

$session_directory = dirname(dirname(dirname(__DIR__)))."/{$version}/var/session/";

# Iterate through each file in the session directory
$files = glob($session_directory."*");
foreach ($files as $file)
{
    if (is_file($file))
        unlink($file);
}
