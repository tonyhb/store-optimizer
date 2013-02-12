<?php

# This is just Magento convention - it could all be done through $this
$installer = $this;
$installer->startSetup();

$installer->run("
    CREATE TABLE `{$installer->getTable('abtest/test')}` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `start_date` date NOT NULL,
        `end_date` date DEFAULT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Allows early ending of test',
        `description` text,
        `type` varchar(50) DEFAULT NULL,
        `observer_target` varchar(255) NOT NULL DEFAULT '',
        `observer_conversion` varchar(255) NOT NULL DEFAULT '',
        `has_winner` tinyint(1) unsigned NOT NULL DEFAULT '0',
        `significance` float(2,2) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `end_date_active` (`end_date`,`is_active`)
    ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

    CREATE TABLE `{$installer->getTable('abtest/variation')}` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `test_id` int(11) unsigned NOT NULL,
        `is_default_template` tinyint(1) NOT NULL,
        `layout_update` mediumtext,
        `split_percentage` tinyint(3) unsigned DEFAULT NULL,
        `visitors` int(11) unsigned DEFAULT '0',
        `views` int(11) unsigned DEFAULT '0',
        `conversions` int(11) unsigned DEFAULT '0' COMMENT 'Denormalisation... We keep a running total here',
        `total_value` decimal(12,4) DEFAULT '0.0000',
        `is_winner` tinyint(1) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `abtest_id` (`test_id`),
        CONSTRAINT `abtest_id` FOREIGN KEY (`test_id`) REFERENCES `abtest` (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

    CREATE TABLE `{$installer->getTable('abtest/conversion')}` (
        `test_id` int(11) unsigned NOT NULL,
        `variation_id` int(11) unsigned NOT NULL,
        `order_id` int(10) DEFAULT NULL,
        `value` decimal(12,4) DEFAULT NULL,
        KEY `variant_id` (`variation_id`),
        KEY `test_id` (`test_id`),
        CONSTRAINT `test_id` FOREIGN KEY (`test_id`) REFERENCES `abtest` (`id`),
        CONSTRAINT `variant_id` FOREIGN KEY (`variation_id`) REFERENCES `abtest_variation` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
