<?php

class Aoe_Scheduler_Model_Resource_Job extends Mage_Core_Model_Resource_Db_Abstract
{
    /** @var bool */
    protected $loaded = false;

    /** @var Aoe_Scheduler_Model_Job[] */
    protected $jobs = array();

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('core/config_data', 'job_code');
    }

    public function getJobCodes()
    {
        $codes = array();

        $nodes = array('crontab/jobs', 'default/crontab/jobs');
        foreach ($nodes as $node) {
            $jobs = Mage::getConfig()->getNode($node);
            if ($jobs && $jobs->hasChildren()) {
                foreach ($jobs->children() as $code => $child) {
                    $codes[] = trim($code);
                }
            }
        }

        // Remove empties and de-dupe
        $codes = array_unique(array_filter($codes));

        // Sort
        sort($codes);

        return $codes;
    }

    /**
     * @param Aoe_Scheduler_Model_Job $object
     * @param mixed                   $value
     * @param null                    $field
     *
     * @return $this
     */
    public function load(Mage_Core_Model_Abstract $object, $value, $field = null)
    {
        if (!$object instanceof Aoe_Scheduler_Model_Job) {
            throw new InvalidArgumentException(sprintf("Expected object of type 'Aoe_Scheduler_Model_Job' got '%s'", get_class($object)));
        }

        /** @var Aoe_Scheduler_Model_Job $object */

        if (!empty($field)) {
            throw new InvalidArgumentException('Aoe_Scheduler_Model_Resource_Job cannot load by any field except the job code.');
        }

        if (empty($value)) {
            $this->setModelFromJobData($object, array());
            $object->setJobCode('');
            $object->setXmlJobData(array());
            $object->setDbJobData(array());
            return $this;
        }

        $xmlJobData = $this->getJobDataFromXml($value);
        $dbJobData = $this->getJobDataFromDb($value);
        $jobData = array_merge($xmlJobData, $this->getJobDataFromConfig($value, true), $dbJobData);

        $this->setModelFromJobData($object, $jobData);
        $object->setJobCode($value);
        $object->setXmlJobData($xmlJobData);
        $object->setDbJobData($dbJobData);

        $this->unserializeFields($object);
        $this->_afterLoad($object);

        return $this;
    }

    /**
     * @param Aoe_Scheduler_Model_Job $object
     *
     * @return $this
     */
    public function save(Mage_Core_Model_Abstract $object)
    {
        if ($object->isDeleted()) {
            return $this->delete($object);
        }

        if (!$object instanceof Aoe_Scheduler_Model_Job) {
            throw new InvalidArgumentException(sprintf("Expected object of type 'Aoe_Scheduler_Model_Job' got '%s'", get_class($object)));
        }

        if (!$object->getJobCode()) {
            Mage::throwException('Invalid data. Must have job code.');
        }

        $this->_serializeFields($object);
        $this->_beforeSave($object);

        $newValues = $this->getJobDataFromModel($object);
        $oldValues = $this->getJobDataFromDb($object->getJobCode());
        $defaultValues = $this->getJobDataFromXml($object->getJobCode());

        // Generate key/value lists for Update and Insert
        $updateValues = array_intersect_key($newValues, $oldValues);
        $insertValues = array_diff_key($newValues, $oldValues);

        // Remove Updates and Inserts that match defaults
        $updateValues = array_diff_assoc($updateValues, $defaultValues);
        $insertValues = array_diff_assoc($insertValues, $defaultValues);

        // Remove empty value inserts if this is a DB only job
        if (empty($defaultValues)) {
            foreach ($insertValues as $k => $v) {
                if ($v === '' || $v === null) {
                    unset($insertValues[$k]);
                }
            }
        }

        // Generate key/value lists for Delete (Old values, not being updated, that are identical to default values)
        $deleteValues = array_intersect_assoc(array_diff_key($oldValues, $updateValues), $defaultValues);

        $pathPrefix = $this->getJobPathPrefix($object->getJobCode()) . '/';

        $adapter = $this->_getWriteAdapter();
        foreach ($updateValues as $k => $v) {
            $adapter->update(
                $this->getMainTable(),
                array('value' => $v),
                array(
                    'scope = ?'    => 'default',
                    'scope_id = ?' => 0,
                    'path = ?'     => $pathPrefix . $k
                )
            );
        }
        foreach ($insertValues as $k => $v) {
            $adapter->insert(
                $this->getMainTable(),
                array(
                    'scope'    => 'default',
                    'scope_id' => 0,
                    'path'     => $pathPrefix . $k,
                    'value'    => $v
                )
            );
        }
        foreach ($deleteValues as $k => $v) {
            $adapter->delete(
                $this->getMainTable(),
                array(
                    'scope = ?'    => 'default',
                    'scope_id = ?' => 0,
                    'path = ?'     => $pathPrefix . $k
                )
            );
        }

        if (count($updateValues) || count($insertValues) || count($deleteValues)) {
            Mage::getConfig()->reinit();
        }

        $this->unserializeFields($object);
        $this->_afterSave($object);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function forsedSave(Mage_Core_Model_Abstract $object)
    {
        throw new RuntimeException('Method no longer exists');
    }

    /**
     * @param Aoe_Scheduler_Model_Job $object
     *
     * @return $this
     */
    public function delete(Mage_Core_Model_Abstract $object)
    {
        if (!$object instanceof Aoe_Scheduler_Model_Job) {
            throw new InvalidArgumentException(sprintf("Expected object of type 'Aoe_Scheduler_Model_Job' got '%s'", get_class($object)));
        }

        $this->_beforeDelete($object);

        if (!$object->getJobCode()) {
            Mage::throwException('Invalid data. Must have job code.');
        }

        $adapter = $this->_getWriteAdapter();
        $adapter->delete(
            $this->getMainTable(),
            array(
                'path LIKE ?' => $this->getJobSearchPath($object->getJobCode()),
                'scope = ?' => 'default',
                'scope_id = ?' => 0
            )
        );

        Mage::getConfig()->reinit();

        $this->_afterDelete($object);

        return $this;
    }

    protected function getJobPathPrefix($jobCode)
    {
        return 'crontab/jobs/' . $jobCode;
    }

    protected function getJobSearchPath($jobCode)
    {
        return str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $this->getJobPathPrefix($jobCode)) . '/%';
    }

    private function getJobDataFromConfig($jobCode, $useDefaultScope = false, $default = null)
    {
        $config = Mage::getConfig()->getNode(($useDefaultScope ? 'default/' : '') . $this->getJobPathPrefix($jobCode));
        if (!$config) {
            return array();
        }

        $config = $config->asArray();

        $values = array();

        if (isset($config['name'])) {
            $values['name'] = $config['name'];
        } elseif ($default !== null) {
            $values['name'] = $default;
        }
        if (isset($config['description'])) {
            $values['description'] = $config['description'];
        } elseif ($default !== null) {
            $values['description'] = $default;
        }
        if (isset($config['short_description'])) {
            $values['short_description'] = $config['short_description'];
        } elseif ($default !== null) {
            $values['short_description'] = $default;
        }
        if (isset($config['run']['model'])) {
            $values['run/model'] = $config['run']['model'];
        } elseif ($default !== null) {
            $values['run/model'] = $default;
        }
        if (isset($config['schedule']['config_path'])) {
            $values['schedule/config_path'] = $config['schedule']['config_path'];
        } elseif ($default !== null) {
            $values['schedule/config_path'] = $default;
        }
        if (isset($config['schedule']['cron_expr'])) {
            $values['schedule/cron_expr'] = $config['schedule']['cron_expr'];
        } elseif ($default !== null) {
            $values['schedule/cron_expr'] = $default;
        }
        if (isset($config['parameters'])) {
            $values['parameters'] = $config['parameters'];
        } elseif ($default !== null) {
            $values['parameters'] = $default;
        }
        if (isset($config['groups'])) {
            $values['groups'] = $config['groups'];
        } elseif ($default !== null) {
            $values['groups'] = $default;
        }
        if (isset($config['is_active'])) {
            $values['is_active'] = $config['is_active'];
        } elseif ($default !== null) {
            $values['is_active'] = $default;
        }

        // Clean up each entry to being a trimmed string
        $values = array_map('trim', $values);

        return $values;
    }

    public function getJobDataFromXml($jobCode)
    {
        return $this->getJobDataFromConfig($jobCode, false, null);
    }

    public function getJobDataFromDb($jobCode)
    {
        $adapter = $this->_getWriteAdapter();

        $select = $adapter->select()
            ->from($this->getMainTable(), array('path', 'value'))
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', '0')
            ->where('path LIKE ?', $this->getJobSearchPath($jobCode));

        $pathPrefix = $this->getJobPathPrefix($jobCode) . '/';
        $values = array();
        foreach ($adapter->query($select)->fetchAll() as $row) {
            if (strpos($row['path'], $pathPrefix) === 0) {
                $values[substr($row['path'], strlen($pathPrefix))] = $row['value'];
            }
        }

        // Clean up each entry to being a trimmed string
        $values = array_map('trim', $values);

        return $values;
    }

    public function getJobDataFromModel(Aoe_Scheduler_Model_Job $job)
    {
        $values = array(
            'name'                 => $job->getName(),
            'description'          => $job->getDescription(),
            'short_description'    => $job->getShortDescription(),
            'run/model'            => $job->getRunModel(),
            'schedule/config_path' => $job->getScheduleConfigPath(),
            'schedule/cron_expr'   => $job->getScheduleCronExpr(),
            'parameters'           => $job->getParameters(),
            'groups'               => $job->getGroups(),
            'is_active'            => ($job->getIsActive() ? '1' : '0'),
        );

        // Strip out the auto-generated name
        if ($values['name'] === $job->getJobCode()) {
            $values['name'] = '';
        }

        // Clean up each entry to being a trimmed string
        $values = array_map('trim', $values);

        return $values;
    }

    public function setModelFromJobData(Aoe_Scheduler_Model_Job $job, array $data)
    {
        $job->setName(isset($data['name']) ? $data['name'] : '');
        $job->setDescription(isset($data['description']) ? $data['description'] : '');
        $job->setShortDescription(isset($data['short_description']) ? $data['short_description'] : '');
        $job->setRunModel(isset($data['run/model']) ? $data['run/model'] : '');
        $job->setScheduleConfigPath(isset($data['schedule/config_path']) ? $data['schedule/config_path'] : '');
        $job->setScheduleCronExpr(isset($data['schedule/cron_expr']) ? $data['schedule/cron_expr'] : '');
        $job->setParameters(isset($data['parameters']) ? $data['parameters'] : '');
        $job->setGroups(isset($data['groups']) ? $data['groups'] : '');
        $job->setIsActive(isset($data['is_active']) ? $data['is_active'] : '');
        return $job;
    }
}
