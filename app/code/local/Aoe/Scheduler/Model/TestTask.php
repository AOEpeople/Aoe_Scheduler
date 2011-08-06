<?php

class Aoe_Scheduler_Model_TestTask {

	public function run() {
		sleep(rand(60,180));
		throw new Exception('This is a dummy exception');
	}

}