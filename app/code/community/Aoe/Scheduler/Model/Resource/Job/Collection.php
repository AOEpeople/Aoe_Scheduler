<?php

class Aoe_Scheduler_Model_Resource_Job_Collection extends Varien_Data_Collection
{
    protected $model;
    protected $resourceModel;
    protected $whiteList = array();
    protected $blackList = array();
    protected $activeOnly = false;
    protected $dbOnly = false;

    public function __construct()
    {
        $this->model = 'aoe_scheduler/job';
        $this->resourceModel = 'aoe_scheduler/job';
    }

    /**
     * @return Aoe_Scheduler_Model_Resource_Job
     */
    public function getResource()
    {
        $resource = Mage::getResourceSingleton($this->resourceModel);
        if (!$resource instanceof Aoe_Scheduler_Model_Resource_Job) {
            Mage::throwException(
                sprintf(
                    'Invalid resource class. Expected "%s" and received "%s".',
                    'Aoe_Scheduler_Model_Resource_Job',
                    (is_object($resource) ? get_class($resource) : 'UNKNOWN')
                )
            );
        }
        return $resource;
    }

    /**
     * Retrieve collection empty item
     *
     * @return Aoe_Scheduler_Model_Job
     */
    public function getNewEmptyItem()
    {
        $model = Mage::getModel($this->model);

        if (!$model instanceof Aoe_Scheduler_Model_Job) {
            Mage::throwException(
                sprintf(
                    'Invalid model class. Expected "%s" and received "%s".',
                    'Aoe_Scheduler_Model_Job',
                    (is_object($model) ? get_class($model) : 'UNKNOWN')
                )
            );
        }

        return $model;
    }

    /**
     * Load data
     *
     * @return $this
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        $this->clear();

        foreach ($this->getResource()->getJobCodes() as $jobCode) {
            if (!empty($this->whiteList) && !in_array($jobCode, $this->whiteList)) {
                continue;
            }
            if (!empty($this->blackList) && in_array($jobCode, $this->blackList)) {
                continue;
            }

            $job = $this->getNewEmptyItem()->load($jobCode);
            if ($this->activeOnly && !$job->getIsActive()) {
                continue;
            }
            if ($this->dbOnly && !$job->isDbOnly()) {
                continue;
            }

            $this->addItem($job);
        }

        ksort($this->_items);

        $this->_setIsLoaded(true);

        return $this;
    }

    /**
     * @return string[]
     */
    public function getWhiteList()
    {
        return $this->whiteList;
    }

    /**
     * @param string[] $list
     *
     * @return $this
     */
    public function setWhiteList(array $list)
    {
        $list = array_unique(array_filter(array_map('trim', array_values($list))));
        sort($list);
        if ($this->whiteList !== $list) {
            $this->clear();
            $this->whiteList = $list;
        }
        return $this;
    }

    /**
     * @return string[]
     */
    public function getBlackList()
    {
        return $this->blackList;
    }

    /**
     * @param string[] $list
     *
     * @return $this
     */
    public function setBlackList(array $list)
    {
        $list = array_unique(array_filter(array_map('trim', array_values($list))));
        sort($list);
        if ($this->blackList !== $list) {
            $this->clear();
            $this->blackList = $list;
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function getActiveOnly()
    {
        return $this->activeOnly;
    }

    /**
     * @param bool $flag
     *
     * @return $this
     */
    public function setActiveOnly($flag = true)
    {
        $flag = (bool)$flag;
        if ($this->activeOnly !== $flag) {
            $this->clear();
            $this->activeOnly = $flag;
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function getDbOnly()
    {
        return $this->dbOnly;
    }

    /**
     * @param bool $flag
     *
     * @return $this
     */
    public function setDbOnly($flag = true)
    {
        $flag = (bool)$flag;
        if ($this->dbOnly !== $flag) {
            $this->clear();
            $this->dbOnly = $flag;
        }
        return $this;
    }

    /**
     * Adding item to item array
     *
     * @param   Aoe_Scheduler_Model_Job $item
     *
     * @return  $this
     */
    public function addItem(Varien_Object $item)
    {
        if (!$item instanceof Aoe_Scheduler_Model_Job) {
            Mage::throwException(
                sprintf(
                    'Invalid model class. Expected "%s" and received "%s".',
                    'Aoe_Scheduler_Model_Job',
                    get_class($item)
                )
            );
        }

        $jobCode = $item->getJobCode();
        if (!$jobCode) {
            Mage::throwException('Jobs must have a job code');
        }

        if (isset($this->_items[$jobCode])) {
            Mage::throwException('Job with the same job code "' . $item->getJobCode() . '" already exist');
        }

        $this->_items[$jobCode] = $item;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getAllIds()
    {
        return array_keys($this->_items);
    }

    /**
     * Retrieve field values from all items
     *
     * @param   string $column
     *
     * @return  array
     */
    public function getColumnValues($column)
    {
        $values = array();
        foreach ($this as $item) {
            /** @var Aoe_Scheduler_Model_Job $item */
            $values[] = $item->getDataUsingMethod($column);
        }
        return $values;
    }

    /**
     * Search all items by field value
     *
     * @param   string $column
     * @param   mixed  $value
     *
     * @return  Aoe_Scheduler_Model_Job[]
     */
    public function getItemsByColumnValue($column, $value)
    {
        $items = array();
        foreach ($this as $item) {
            /** @var Aoe_Scheduler_Model_Job $item */
            if ($item->getDataUsingMethod($column) == $value) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Search first item by field value
     *
     * @param   string $column
     * @param   mixed  $value
     *
     * @return  Aoe_Scheduler_Model_Job|null
     */
    public function getItemByColumnValue($column, $value)
    {
        foreach ($this as $item) {
            /** @var Aoe_Scheduler_Model_Job $item */
            if ($item->getDataUsingMethod($column) == $value) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Setting data for all collection items
     *
     * @param   mixed $key
     * @param   mixed $value
     *
     * @return  $this
     */
    public function setDataToAll($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->setDataToAll($k, $v);
            }
            return $this;
        }

        foreach ($this->getItems() as $item) {
            /** @var Aoe_Scheduler_Model_Job $item */
            $item->setDataUsingMethod($key, $value);
        }

        return $this;
    }

    public function toOptionArray($valueField = 'job_code', $labelField = 'name', array $additional = array())
    {
        return $this->_toOptionArray($valueField, $labelField, $additional);
    }

    public function toOptionHash($valueField = 'job_code', $labelField = 'name')
    {
        return $this->_toOptionHash($valueField, $labelField);
    }


    /**
     * Convert items array to array for select options
     *
     * @param   string $valueField
     * @param   string $labelField
     *
     * @return  array
     */
    protected function _toOptionArray($valueField = 'job_code', $labelField = 'name', $additional = array())
    {
        $options = array();

        $additional['value'] = $valueField;
        $additional['label'] = $labelField;

        foreach ($this as $item) {
            /** @var Aoe_Scheduler_Model_Job $item */
            $data = array();
            foreach ($additional as $code => $field) {
                $data[$code] = $item->getDataUsingMethod($field);
            }
            $options[] = $data;
        }
        return $options;
    }


    /**
     * Convert items array to hash for select options
     *
     * @param   string $valueField
     * @param   string $labelField
     *
     * @return  array
     */
    protected function _toOptionHash($valueField = 'job_code', $labelField = 'name')
    {
        $res = array();
        foreach ($this as $item) {
            /** @var Aoe_Scheduler_Model_Job $item */
            $res[$item->getDataUsingMethod($valueField)] = $item->getDataUsingMethod($labelField);
        }
        return $res;
    }
}
