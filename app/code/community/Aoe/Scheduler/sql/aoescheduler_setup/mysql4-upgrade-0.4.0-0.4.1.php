<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$tableName = $installer->getTable('cron_schedule');

try {
    $installer->getConnection()->dropColumn($tableName, 'parameters');
} catch (Exception) {
    // ignored intentionally
}

$installer->getConnection()->addColumn(
    $tableName,
    'parameters',
    "TEXT NULL COMMENT 'Serialized Parameters'"
);

$installer->getConnection()->addColumn(
    $tableName,
    'eta',
    "timestamp NULL DEFAULT NULL COMMENT 'Estimated Time of Arrival'"
);

$installer->getConnection()->addColumn(
    $tableName,
    'host',
    "varchar(255) NULL COMMENT 'Host running this job'"
);

$installer->getConnection()->addColumn(
    $tableName,
    'pid',
    "varchar(255) NULL COMMENT 'Process id of this job'"
);

$installer->getConnection()->addColumn(
    $tableName,
    'progress_message',
    "TEXT NULL COMMENT 'Progress message'"
);

$installer->endSetup();
