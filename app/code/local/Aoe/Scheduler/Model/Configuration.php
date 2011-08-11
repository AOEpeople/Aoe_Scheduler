<?php

class Aoe_Scheduler_Model_Configuration extends Mage_Core_Model_Abstract {

	const STATUS_DISABLED = 'disabled';
	const STATUS_ENABLED = 'enabled';

	/**
	 * Override method.
	 *
	 * @return false
	 */
	protected function _getResource() {
		return false;
	}



	/**
	 * Get id field name
	 *
	 * @return string
	 */
	public function getIdFieldName() {
		return 'id';
	}



	/**
	 * Load configuration object by code
	 *
	 * @param string $code
	 */
	public function loadByCode($code) {
		$this->setId($code);
		$this->setName($code);

		$global = $this->getGlobalCrontabJobXmlConfig();
		$cronExpr = null;
		if (isset($global->schedule) && $global->schedule->config_path) {
			$cronExpr = Mage::getStoreConfig((string)$global->schedule->config_path);
		}
		if (empty($cronExpr) && isset($global->schedule) && $global->schedule->cron_expr) {
			$cronExpr = (string)$global->schedule->cron_expr;
		}
		if ($cronExpr) {
			$this->setCronExpr($cronExpr);
		}
		if (isset($global->run)) {
			$model = (string)$global->run->model;
			if ($model) {
				$this->setModel($model);
			}
		}
		$globalArray = $global?$global->asArray():array();
		unset($globalArray['schedule']); 
		unset($globalArray['run']);
		$this->setOptions($globalArray);
		
		$configurable = $this->getConfigurableCrontabJobXmlConfig();
		if ($configurable) {
			if (is_object($configurable->schedule)) {
				$cronExpr = (string)$configurable->schedule->cron_expr;
				if ($cronExpr) {
					$this->setCronExpr($cronExpr);
				}
			}
			if (is_object($configurable->run)) {
				$model = (string)$configurable->run->model;
				if ($model) {
					$this->setModel($model);
				}
			}
			$configArray = $configurable->asArray();
			unset($configArray['schedule']); 
			unset($configArray['run']);
			$this->setOptions(array_merge($globalArray,$configArray));			
		}

		
		if (!$this->getModel()) {
			Mage::throwException(sprintf('No configuration found for code "%s"', $code));
		}

		$disabledCrons = Mage::helper('aoe_scheduler')->trimExplode(',', Mage::getStoreConfig('system/cron/disabled_crons'), true);
		$this->setStatus(in_array($this->getId(), $disabledCrons) ? self::STATUS_DISABLED : self::STATUS_ENABLED);

		return $this;
	}



	/**
	 * Get global crontab job xml configuration
	 *
	 * @return Mage_Core_Model_Config_Element|false
	 */
	protected function getGlobalCrontabJobXmlConfig() {
		return $this->getJobXmlConfig('crontab/jobs');
	}



	/**
	 * Get configurable crontab job xml configuration
	 *
	 * @return Mage_Core_Model_Config_Element|false
	 */
	protected function getConfigurableCrontabJobXmlConfig() {
		return $this->getJobXmlConfig('default/crontab/jobs');
	}


	/**
	 * Get job xml configuration
	 *
	 * @param string $path path to configuration
	 * @return Mage_Core_Model_Config_Element|false
	 */
	protected function getJobXmlConfig($path) {
		$xmlConfig = false;
		$config = Mage::getConfig()->getNode($path);
		if ($config instanceof Mage_Core_Model_Config_Element) {
			$xmlConfig = $config->{$this->getId()};
		}
		return $xmlConfig;
	}

	/**
	 * If a model is not set then it is not ready to create.
	 *
	 * @return boolean
	 */
	public function isCompleteToCreate()
	{
		return (bool)($this->getModel());
	}
	
	/**
	 * Find global configurations by model.
	 *
	 * @param string $model
	 * 
	 * @return array
	 */
	public function findByModel($model) {
    	$models = array();
		$collection = Mage::getModel('aoe_scheduler/collection_globalcrons');
		foreach ($collection as $item) {
			if ($item->getModel() == $model) {
				$models[$item->getModel()] = $item; 
			}
		}
		return $models;
	}
	
}