<?php

$installer = $this; /* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$tableName = $installer->getTable('cron_schedule');

$installer->getConnection()->addColumn(
    $tableName,
    'kill_request',
    "timestamp NULL DEFAULT NULL COMMENT 'Kill Request'"
);

$installer->endSetup();
