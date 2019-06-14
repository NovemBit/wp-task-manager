<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/*
 * Registers background tasks
 * Checks the run restrictions and runs the tasks in a loop
 * Logs information about run restrictions and about the tasks it request to run
 */
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
		$this->task_runner = BTM_Task_Runner::get_the_instance_once();
	}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * @var BTM_Task_Runner
	 */
	private $task_runner = null;

	/**
	 * Checks run restrictions, runs the tasks, logs steps
	 */
	public function run_the_tasks(){
		$task_manager_log_dao = BTM_Task_Manager_Log_Dao::get_instance();
		$task_manager_log_dao->log( __( 'Started running the tasks', 'background_task_manager' ) );

		while( true ){
			$restriction_message = BTM_Run_Restrictor::get_instance()->check_all_restrictions();
			if( true === $restriction_message ){

				$task_to_run = BTM_Task_Dao::get_instance()->get_next_task_to_run();
				if( false === $task_to_run ){
					$task_manager_log_dao->log( __( 'There are no tasks to run', 'background_task_manager' ) );
					break;
				}else{
					$task_manager_log_dao->log(
						sprintf(
							__( 'Started running task %s: %s', 'background_task_manager' ),
							$task_to_run->get_callback_action(),
							BTM_Task_Type_Service::get_instance()->get_type_from_task( $task_to_run )
						)
					);
					$this->task_runner->run_task( $task_to_run );
				}
			}else{
				$task_manager_log_dao->log( $restriction_message );
				break;
			}
		}

		$task_manager_log_dao->log( __( 'Finished running the tasks', 'background_task_manager' ) );
	}

	/**
	 * Registers a task to run later in background
	 *
	 * @param I_BTM_Task $task
	 */
	public function register_task( I_BTM_Task $task ){
		BTM_Task_Dao::get_instance()->create( $task );
	}
}