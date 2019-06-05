<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

final class BTM_Task_Manager{
	// region Singleton

	/**
	 * @var BTM_Task_Manager
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Manager
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		try{
			$this->task_runner = BTM_Task_Runner::get_the_instance_once();
		}catch ( Exception $e ){
			// log error
		}
	}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * @var BTM_Task_Runner
	 */
	private $task_runner = null;

	public function run_the_tasks(){
		// @todo: log started running the tasks

		while( true ){
			$restriction_message = BTM_Run_Restrictor::get_instance()->check_all_restrictions();
			if( true !== $restriction_message ){
				$task_to_run = $this->get_next_task();

				if( false === $task_to_run ){
					// @todo: log there is no task to run
					break;
				}else{
					$this->task_runner->run_task( $task_to_run );
				}
			}else{
				// @todo: log the $restriction_message why we should not run the tasks
				break;
			}
		}

		// @todo: log finished running the tasks
	}

	/**
	 * @return BTM_Task|false
	 *      false - in the case there is no more tasks to run
	 */
	public function get_next_task(){
		return false;
	}

	/**
	 * @return string
	 */
	public function check_reasons_not_to_run_tasks(){
		return true;
	}

	/**
	 * @param BTM_Task $task
	 */
	public function register_task( BTM_Task $task ){

	}
}