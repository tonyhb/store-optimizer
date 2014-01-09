<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    ALTER TABLE `{$installer->getTable('abtest/test')}` CHANGE COLUMN `is_active` `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '0 - Not active, 1 - Active, 2 - Manually stopped'
");
