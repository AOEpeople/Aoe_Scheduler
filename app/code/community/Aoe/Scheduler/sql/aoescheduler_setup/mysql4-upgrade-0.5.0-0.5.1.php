<?php

$installer = $this; /* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('cron/schedule'), 'scheduled_by', ['type' => Varien_Db_Ddl_Table::TYPE_INTEGER, 'unsigned' => true, 'nullable' => true, 'default' => null, 'comment' => 'Scheduled by']);

$installer->getConnection()->addColumn($installer->getTable('cron/schedule'), 'scheduled_reason', ['type' => Varien_Db_Ddl_Table::TYPE_TEXT, 'length' => 256, 'nullable' => true, 'default' => null, 'comment' => 'Scheduled Reason']);

$installer->endSetup();
