<?php

require_once 'abstract.php';

class Aoe_Scheduler_Shell_Scheduler extends Mage_Shell_Abstract {
	
	/**
	 * Run script
	 * 
	 * @return void
	 */
	public function run() {
		$action = $this->getArg('action');
		if (empty($action)) {
			echo $this->usageHelp();
		} else {
			$actionMethodName = $action.'Action';
	        if (method_exists($this, $actionMethodName)) {
	        	$this->$actionMethodName();
	        } else {
	        	echo "Action $action not found!\n";
	        	echo $this->usageHelp();
	        	exit(1);
	        }
		}
	}
	
	
	
    /**
     * Retrieve Usage Help Message
     * 
     * @return string
     */
    public function usageHelp() {
    	$help = 'Available actions: ' . "\n";
    	$methods = get_class_methods($this);
		foreach ($methods as $method) {
			if (substr($method, -6) == 'Action') {
				$help .= '    -action ' . substr($method, 0, -6);
				$helpMethod = $method.'Help';
				if (method_exists($this, $helpMethod)) {
					$help .= $this->$helpMethod();
				}
				$help .= "\n";
			}
		}
    	return $help;
    }
    

    
    /**
     * List all availables codes / jobs
     * 
     * @return void
     */
    public function listAllCodesAction() {
    	$collection = Mage::getModel('aoe_scheduler/collection_crons');
    	foreach ($collection as $configuration) { /* @var $configuration Aoe_Scheduler_Model_Configuration */
    		echo sprintf("%-50s %-20s %s\n", $configuration->getId(), $configuration->getCronExpr(), $configuration->getStatus());  
    	}
    }
    
    
    
    /**
     * Schedule a job now
     * 
     * @return void
     */
    public function scheduleNowAction() {
    	$code = $this->getArg('code');
    	if (empty($code)) {
        	echo "\nNo code found!\n\n";
        	echo $this->usageHelp();
        	exit(1);
    	}
    	$schedule = Mage::getModel('cron/schedule'); /* @var $schedule Aoe_Scheduler_Model_Schedule */
    	$schedule->setJobCode($code);
    	$schedule->scheduleNow();
    	$schedule->save();
    }
    
    
    
    /**
     * Display extra help
     * 
     * @return string
     */
    public function scheduleNowActionHelp() {
    	return " -code <code>	Schedule a job to be executed as soon as possible";
    }
    
    
    
    /**
     * Run a job now
     * 
     * @return void
     */
    public function runNowAction() {
    	$code = $this->getArg('code');
    	if (empty($code)) {
        	echo "\nNo code found!\n\n";
        	echo $this->usageHelp();
        	exit(1);
    	}
    	$schedule = Mage::getModel('cron/schedule'); /* @var $schedule Aoe_Scheduler_Model_Schedule */
    	$schedule->setJobCode($code);
    	$schedule->runNow();
    	$schedule->save();
    }
    
    
    
    /**
     * Display extra help
     * 
     * @return string
     */
    public function runNowActionHelp() {
    	return " -code <code>	        Run a job directly";
    }
    
}

$shell = new Aoe_Scheduler_Shell_Scheduler();
$shell->run();