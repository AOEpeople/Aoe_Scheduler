<?php
/* @var $this Mage_Core_Model_Resource_Setup */

// Migrate to new settings
$node = Mage::getConfig()->getNode('default/system/cron/disabled_crons');
if ($node) {
    $allowedCodes = Mage::getSingleton('aoe_scheduler/job')->getResource()->getJobCodes();
    $codes = array_intersect(array_unique(array_filter(array_map('trim', explode(',', trim($node))))), $allowedCodes);
    foreach ($codes as $code) {
        $this->getConnection()->insertOnDuplicate(
            $this->getTable('core/config_data'),
            array(
                'scope'    => 'default',
                'scope_id' => 0,
                'path'     => 'crontab/jobs/' . $code . '/is_active',
                'value'    => 0,
            )
        );
    }
}

// Remove old config setting
$this->getConnection()->delete(
    $this->getTable('core/config_data'),
    array(
        'scope = ?'    => 'default',
        'scope_id = ?' => 0,
        'path = ?'     => 'system/cron/disabled_crons'
    )
);
