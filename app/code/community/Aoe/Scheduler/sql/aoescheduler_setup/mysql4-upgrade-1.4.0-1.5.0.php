<?php

$installer = $this; /* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('cron/schedule'), 'memory_usage', array(
    'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
    'length'   => '12,4',
    'unsigned' => true,
    'nullable' => true,
    'default'  => null,
    'comment'  => 'Memory Used in MB',
));

$installer->endSetup();
