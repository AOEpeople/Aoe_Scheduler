<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$tableName = $this->getTable('cron/schedule');

$this->getConnection()->dropColumn(
    $tableName,
    'parameters'
);

$this->getConnection()->addColumn(
    $tableName,
    'parameters',
    "TEXT NULL COMMENT 'Serialized Parameters'"
);

$this->getConnection()->addColumn(
    $tableName,
    'eta',
    "timestamp NULL DEFAULT NULL COMMENT 'Estimated Time of Arrival'"
);

$this->getConnection()->addColumn(
    $tableName,
    'host',
    "varchar(255) NULL COMMENT 'Host running this job'"
);

$this->getConnection()->addColumn(
    $tableName,
    'pid',
    "varchar(255) NULL COMMENT 'Process id of this job'"
);

$this->getConnection()->addColumn(
    $tableName,
    'progress_message',
    "TEXT NULL COMMENT 'Progress message'"
);

$this->getConnection()->addColumn(
    $tableName,
    'last_seen',
    "timestamp NULL DEFAULT NULL COMMENT 'Last seen'"
);

$this->getConnection()->addColumn(
    $tableName,
    'kill_request',
    "timestamp NULL DEFAULT NULL COMMENT 'Kill Request'"
);

$this->getConnection()->addColumn(
    $tableName,
    'scheduled_by',
    array(
        'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'unsigned' => true,
        'nullable' => true,
        'default'  => null,
        'comment'  => 'Scheduled by'
    )
);

$this->getConnection()->addColumn(
    $tableName,
    'scheduled_reason',
    array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'   => 256,
        'nullable' => true,
        'default'  => null,
        'comment'  => 'Scheduled Reason'
    )
);

$this->endSetup();
