<?php

# This is just Magento convention - it could all be done through $this
$installer = $this;
$installer->startSetup();

$installer->run("
    CREATE TABLE `{$installer->getTable('abtest/test')}` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) DEFAULT NULL,
        `start_date` date NOT NULL,
        `end_date` date DEFAULT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT '1',
        `observer_target` varchar(255) NOT NULL DEFAULT '',
        `observer_conversion` varchar(255) NOT NULL DEFAULT '',
        `only_test_new_visitors` tinyint(1) NOT NULL DEFAULT '0',
        `has_winner` tinyint(1) unsigned NOT NULL DEFAULT '0',
        `significance` float(2,2) DEFAULT '0.00',
        `visitors` int(11) unsigned DEFAULT '0',
        `views` int(11) unsigned DEFAULT '0',
        `conversions` int(11) unsigned DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `end_date_active` (`end_date`,`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE `{$installer->getTable('abtest/variation')}` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `test_id` int(11) unsigned NOT NULL,
        `is_control` tinyint(1) NOT NULL,
        `name` varchar(50) DEFAULT NULL,
        `layout_update` mediumtext,
        `package` varchar(255) DEFAULT NULL,
        `package_exceptions` text,
        `templates` varchar(255) DEFAULT NULL,
        `templates_exceptions` text,
        `skin` varchar(255) DEFAULT NULL,
        `skin_exceptions` text,
        `layout` varchar(255) DEFAULT NULL,
        `layout_exceptions` text,
        `default` varchar(255) DEFAULT NULL,
        `default_exceptions` text,
        `split_percentage` tinyint(3) unsigned DEFAULT NULL,
        `visitors` int(11) unsigned DEFAULT '0',
        `views` int(11) unsigned DEFAULT '0',
        `conversions` int(11) unsigned DEFAULT '0' COMMENT 'Denormalisation... We keep a running total here',
        `conversion_rate` decimal(12,4) DEFAULT '0.0000',
        `total_value` decimal(12,4) DEFAULT '0.0000',
        `is_winner` tinyint(1) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `abtest_id` (`test_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    create table `{$installer->gettable('abtest/conversion')}` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `test_id` int(11) unsigned NOT NULL,
        `variation_id` int(11) unsigned NOT NULL,
        `order_id` int(10) DEFAULT NULL,
        `value` decimal(12,4) DEFAULT NULL,
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        KEY `variant_id` (`variation_id`),
        KEY `test_id` (`test_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    CREATE TABLE `{$installer->gettable('abtest/hit')}` (
        `test_id` int(11) unsigned NOT NULL,
        `variation_id` int(11) unsigned NOT NULL,
        `date` date NOT NULL,
        `visitors` int(11) unsigned NOT NULL DEFAULT '1',
        `views` int(10) unsigned NOT NULL DEFAULT '1',
        UNIQUE KEY `test_id_2` (`test_id`,`variation_id`,`date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
