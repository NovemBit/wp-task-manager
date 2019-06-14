<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Should only be used from the BTM_Task_Manager
 * Runs the given task
 * Changes the task running status during the run
 * Logs information that the task callbacks returns
 *
 * Class BTM_Task_Runner
 */
final class BTM_Task_Runner{
	// region Singleton

	/**
	 * @var bool
	 */
	private static $created = false;
	/**
	 * @return BTM_Task_Runner
	 *
	 * @throws Exception
	 *      in the case this method called more than once
	 */
	public static function get_the_instance_once(){
		if( false === self::$created ){
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
	 * Changes the task running status during the run
	 * Logs information that the task callbacks returns
	 *
	 * @param I_BTM_Task $task
	 */
	public function run_task( I_BTM_Task $task ){
		$task_dao = BTM_Task_Dao::get_instance();
		$task_dao::get_instance()->mark_task_running( $task );

		$start = time();
		try{

			$logs = array();
			$callback_args = $task->get_callback_arguments();
			/**
			 * Runs the background tasks, gathers their logs.
			 *
			 * There is a prefix added to the filter tag,
			 * @see BTM_Plugin_Options::get_task_filter_name_prefix()
			 *
			 * @param string[] $logs            the logs that callback functions should return
			 * @param mixed[] $callback_args    the callback arguments
			 */
			$logs = apply_filters(
				BTM_Plugin_Options::get_instance()->get_task_filter_name_prefix() . $task->get_callback_action(),
				$logs,
				$callback_args
			);

			$run_success = true;
		}catch( Exception $e ){
			$logs = array( $e->getMessage() );
			$run_success = false;
		}
		$end = time();

		if( true === $run_success ){
			$task_dao::get_instance()->mark_task_succeeded( $task );
		}else{
			$task_dao::get_instance()->mark_task_failed( $task );
		}

		BTM_Task_Run_Log_Dao::get_instance()->create(
			new BTM_Task_Run_Log(
				$task->get_id(),
				BTM_Task_Manager_Log_Dao::get_instance()->get_session_id(),
				$logs,
				$start,
				$end
			)
		);
	}
}