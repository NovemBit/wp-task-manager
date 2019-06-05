<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

final class BTM_Task_Runner{
	// region Singleton

	private static $created = false;
	/**
	 * @return BTM_Task_Runner
	 *
	 * @throws Exception
	 *      in the case this method called more than once
	 */
	public static function get_the_instance_once(){
		if( null === self::$created ){
			return new self();
		}else{
			throw new Exception('The instance should only be created once and used from the class BTM_Task_Manager');
		}
	}
	private function __construct() {}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * Runs the given task, returns the
	 *
	 * @param BTM_Task $task
	 *
	 * @return string[]
	 */
	public function run_task( BTM_Task $task ){
		$timer = new BTM_Timer();
		$timer->start();

		try{
			apply_filters( $task->get_callback_action(), $task->get_callback_arguments() );
		}catch( Exception $e ){

		}

		$timer->stop();

		// @todo: log task info, and run-status, $timer->get_time_elapsed()
	}
}