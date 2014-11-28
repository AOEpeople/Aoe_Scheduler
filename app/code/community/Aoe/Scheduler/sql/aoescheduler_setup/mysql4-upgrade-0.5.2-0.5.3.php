<?php

$installer = $this; /* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('aoe_scheduler/job'), 'name', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length' => 256,
    'nullable' => true,
    'default' => null,
    'comment' => 'Name'
));

$installer->getConnection()->addColumn($installer->getTable('aoe_scheduler/job'), 'short_description', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length' => '64K',
    'comment' => 'Short Description'
));

$installer->getConnection()->addColumn($installer->getTable('aoe_scheduler/job'), 'description', array(
    'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
    'length' => '64K',
    'comment' => 'Description'
));

$installer->endSetup();
