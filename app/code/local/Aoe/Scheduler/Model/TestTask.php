<?php

class Aoe_Scheduler_Model_TestTask {

	public function run() {
		sleep(rand(60,180));
		if (rand(0, 1) == 0) {
			throw new Exception('This is a dummy exception');
		}
	}

}