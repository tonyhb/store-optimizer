<?php

require 'Base.php';

$write = Mage::getSingleton('core/resource')->getConnection("core/write");
$write->query("TRUNCATE TABLE `abtest_conversion`; TRUNCATE TABLE `abtest_hit`; TRUNCATE TABLE `abtest_test`; TRUNCATE TABLE `abtest_variation`;");
