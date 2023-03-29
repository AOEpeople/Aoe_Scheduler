<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$tableName = $this->getTable('cron/schedule');

/**
 * First we drop a column that already exists in Default Magento Installation.
 */
$this->getConnection()->dropColumn(
    $tableName,
    'parameters'
);

$columns = ['parameters'       => "TEXT NULL COMMENT 'Serialized Parameters' AFTER finished_at", 'eta'              => "timestamp NULL DEFAULT NULL COMMENT 'Estimated Time of Arrival'", 'host'             => "varchar(255) NULL COMMENT 'Host running this job'", 'pid'              => "varchar(255) NULL COMMENT 'Process id of this job'", 'progress_message' => "TEXT NULL COMMENT 'Progress message'", 'last_seen'        => "timestamp NULL DEFAULT NULL COMMENT 'Last seen'", 'kill_request'     => "timestamp NULL DEFAULT NULL COMMENT 'Kill Request'", 'scheduled_by' => ['type'     => Varien_Db_Ddl_Table::TYPE_INTEGER, 'unsigned' => true, 'nullable' => true, 'default'  => null, 'comment'  => 'Scheduled by'], 'scheduled_reason' => ['type'     => Varien_Db_Ddl_Table::TYPE_TEXT, 'length'   => 256, 'nullable' => true, 'default'  => null, 'comment'  => 'Scheduled Reason']];

foreach ($columns as $columnName => $definition) {
    if ($this->getConnection()->tableColumnExists($tableName, $columnName)) {
        continue;
    }

    $this->getConnection()->addColumn($tableName, $columnName, $definition);
}

$this->endSetup();
