<?php

class Aoe_Scheduler_Model_Resource_Job extends Mage_Core_Model_Resource_Db_Abstract
{
    /** @var bool */
    protected $loaded = false;

    /** @var Aoe_Scheduler_Model_Job[] */
    protected $jobs = [];

    /**
     * Resource initialization
     */
    protected function _construct()
    {
        $this->_init('core/config_data', 'job_code');
    }

    public function getJobCodes()
    {
        $codes = [];

        $nodes = ['crontab/jobs', 'default/crontab/jobs'];
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
     * @param null                    $field
     * @return $this
     */
    public function load(Mage_Core_Model_Abstract $object, mixed $value, $field = null)
    {
        if (!$object instanceof Aoe_Scheduler_Model_Job) {
            throw new InvalidArgumentException(sprintf("Expected object of type 'Aoe_Scheduler_Model_Job' got '%s'", $object::class));
        }

        /** @var Aoe_Scheduler_Model_Job $object */

        if (!empty($field)) {
            throw new InvalidArgumentException('Aoe_Scheduler_Model_Resource_Job cannot load by any field except the job code.');
        }

        if (empty($value)) {
            $this->setModelFromJobData($object, []);
            $object->setJobCode('');
            $object->setXmlJobData([]);
            $object->setDbJobData([]);
            return $this;
        }

        $xmlJobData = $this->getJobDataFromXml($value);
        $jobData = array_merge($xmlJobData, $this->getJobDataFromConfig($value, true));

        $this->setModelFromJobData($object, $jobData);
        $object->setJobCode($value);
        $object->setXmlJobData($xmlJobData);

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
            throw new InvalidArgumentException(sprintf("Expected object of type 'Aoe_Scheduler_Model_Job' got '%s'", $object::class));
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
                ['value' => $v],
                ['scope = ?'    => 'default', 'scope_id = ?' => 0, 'path = ?'     => $pathPrefix . $k]
            );
        }
        foreach ($insertValues as $k => $v) {
            $adapter->insert(
                $this->getMainTable(),
                ['scope'    => 'default', 'scope_id' => 0, 'path'     => $pathPrefix . $k, 'value'    => $v]
            );
        }
        foreach ($deleteValues as $k => $v) {
            $adapter->delete(
                $this->getMainTable(),
                ['scope = ?'    => 'default', 'scope_id = ?' => 0, 'path = ?'     => $pathPrefix . $k]
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
            throw new InvalidArgumentException(sprintf("Expected object of type 'Aoe_Scheduler_Model_Job' got '%s'", $object::class));
        }

        $this->_beforeDelete($object);

        if (!$object->getJobCode()) {
            Mage::throwException('Invalid data. Must have job code.');
        }

        $adapter = $this->_getWriteAdapter();
        $adapter->delete(
            $this->getMainTable(),
            ['path LIKE ?' => $this->getJobSearchPath($object->getJobCode()), 'scope = ?' => 'default', 'scope_id = ?' => 0]
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
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->getJobPathPrefix($jobCode)) . '/%';
    }

    private function getJobDataFromConfig($jobCode, $useDefaultScope = false, $default = null)
    {
        $config = Mage::getConfig()->getNode(($useDefaultScope ? 'default/' : '') . $this->getJobPathPrefix($jobCode));
        if (!$config) {
            return [];
        }

        $config = $config->asArray();

        $values = [];

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
        if (isset($config['on_success'])) {
            $values['on_success'] = $config['on_success'];
        } elseif ($default !== null) {
            $values['on_success'] = $default;
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
            ->from($this->getMainTable(), ['path', 'value'])
            ->where('scope = ?', 'default')
            ->where('scope_id = ?', '0')
            ->where('path LIKE ?', $this->getJobSearchPath($jobCode));

        $pathPrefix = $this->getJobPathPrefix($jobCode) . '/';
        $values = [];
        foreach ($adapter->query($select)->fetchAll() as $row) {
            if (str_starts_with($row['path'], $pathPrefix)) {
                $values[substr($row['path'], strlen($pathPrefix))] = $row['value'];
            }
        }

        // Clean up each entry to being a trimmed string
        $values = array_map('trim', $values);

        return $values;
    }

    public function getJobDataFromModel(Aoe_Scheduler_Model_Job $job)
    {
        $values = ['name'                 => $job->getName(), 'description'          => $job->getDescription(), 'short_description'    => $job->getShortDescription(), 'run/model'            => $job->getRunModel(), 'schedule/config_path' => $job->getScheduleConfigPath(), 'schedule/cron_expr'   => $job->getScheduleCronExpr(), 'parameters'           => $job->getParameters(), 'groups'               => $job->getGroups(), 'is_active'            => ($job->getIsActive() ? '1' : '0'), 'on_success'           => $job->getOnSuccess()];

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
        $job->setName($data['name'] ?? '');
        $job->setDescription($data['description'] ?? '');
        $job->setShortDescription($data['short_description'] ?? '');
        $job->setRunModel($data['run/model'] ?? '');
        $job->setScheduleConfigPath($data['schedule/config_path'] ?? '');
        $job->setScheduleCronExpr($data['schedule/cron_expr'] ?? '');
        $job->setParameters($data['parameters'] ?? '');
        $job->setGroups($data['groups'] ?? '');
        $job->setIsActive($data['is_active'] ?? '');
        $job->setOnSuccess($data['on_success'] ?? '');
        return $job;
    }
}
