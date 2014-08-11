<?php

$installer = $this; /* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$table = $installer->getConnection()
    ->newTable($installer->getTable('aoe_scheduler/job'))
    ->addColumn('job_code', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, array(
        'nullable'  => false,
        'primary'   => true,
    ), 'Job Code')
    ->addColumn('schedule_cron_expr', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Schedule cron expression')
    ->addColumn('schedule_config_path', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
    ), 'Schedule cron config path')
    ->addColumn('run_model', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        'nullable'  => false,
    ), 'Run model')
    ->addColumn('is_active', Varien_Db_Ddl_Table::TYPE_TINYINT, 1, array(
        'nullable' => false,
        'default' => 1
    ), 'Is active')
    ->addColumn('parameter', Varien_Db_Ddl_Table::TYPE_TEXT, '64K', array(
    ), 'Is active')
    ->addIndex($installer->getIdxName('aoe_scheduler/job', array('job_code')),
        array('job_code'))
    ->setComment('Cron Job Definition');
$installer->getConnection()->createTable($table);

$installer->endSetup();
