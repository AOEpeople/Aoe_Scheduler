<?php

$installer = $this; /* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();
$installer->getConnection()->addColumn(
	$installer->getTable('cron_schedule'),
	'parameters',
	"TEXT NULL COMMENT 'Serialized Parameters'"
);
$installer->endSetup();
