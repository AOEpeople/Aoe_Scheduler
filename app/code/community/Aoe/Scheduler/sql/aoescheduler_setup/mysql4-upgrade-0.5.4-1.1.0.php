<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();

$this->getConnection()->modifyColumn(
    $this->getTable('cron/schedule'),
    'status',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length'    => 30,
        'nullable'  => false,
        'default'   => 'pending',
        'comment'   => 'Status'
    )
);

$this->endSetup();
