<?php

/**
 *
 * Enter description here ...
 *
 * @author Fabrizio
 *
 * @method string getModel()
 * @method string getStatus()
 * @method string getCronExpr()
 */
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
		if ($global && $global->schedule && $global->schedule->config_path) {
			$cronExpr = Mage::getStoreConfig((string)$global->schedule->config_path);
		}
		if (empty($cronExpr) && $global && $global->schedule && $global->schedule->cron_expr) {
			$cronExpr = (string)$global->schedule->cron_expr;
		}
		if ($cronExpr) {
			$this->setCronExpr($cronExpr);
		}
		if ($global && $global->run && $global->run->model) {
			$this->setModel((string)$global->run->model);
		}

		$configurable = $this->getConfigurableCrontabJobXmlConfig();
		if ($configurable) {
			if (is_object($configurable->schedule)) {
				if ($configurable && $configurable->schedule && $configurable->schedule->cron_expr) {
					$this->setCronExpr((string)$configurable->schedule->cron_expr);
				}
			}
			if (is_object($configurable->run)) {
				if ($configurable && $configurable->run && $configurable->run->model) {
					$this->setModel((string)$configurable->run->model);
				}
			}
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

}