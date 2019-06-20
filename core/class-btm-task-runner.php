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
		if( 0 < $task->get_bulk_size() ){
			$this->run_bulk_task( $task );
		}else{
			$this->run_simple_task( $task );
		}
	}

	/**
	 * Changes the task running status during the run
	 * Logs information that the task callbacks returns
	 *
	 * @param I_BTM_Task $task
	 */
	public function run_bulk_task( I_BTM_Task $task ){
		$task_dao = BTM_Task_Dao::get_instance();
		$task_bulk_argument_dao = BTM_Task_Bulk_Argument_Dao::get_instance();

		$task_dao->mark_as_running( $task );

		$task_bulk_arguments = $task_bulk_argument_dao->get_next_arguments_to_run(
			$task->get_id(),
			$task->get_bulk_size() + 1
		);

		if( count( $task_bulk_arguments ) > $task->get_bulk_size() ){
			array_pop( $task_bulk_arguments );
			$is_in_progress = true;
		}else{
			$is_in_progress = false;
		}

		$task_bulk_argument_dao->mark_many_as_running( $task_bulk_arguments );

		$start = time();
		try{
			$task_run_filter_log = new BTM_Task_Run_Filter_Log();
			$callback_args = $task->get_callback_arguments();
			/**
			 * Runs the background tasks, gathers their logs.
			 *
			 * There is a prefix added to the filter tag,
			 * @see BTM_Plugin_Options::get_task_filter_name_prefix()
			 *
			 * @param BTM_Task_Run_Filter_Log $task_run_filter_log      the logs that callback functions should return
			 * @param mixed[] $callback_args                            the callback arguments
			 * @param BTM_Task_Bulk_Argument[] $task_bulk_arguments     the callback arguments
			 */
			$task_run_filter_log = apply_filters(
				BTM_Plugin_Options::get_instance()->get_task_filter_name_prefix() . $task->get_callback_action(),
				$task_run_filter_log,
				$callback_args,
				$task_bulk_arguments
			);
		}catch( Exception $e ){
			$task_run_filter_log->add_log( $e->getMessage() );
		}finally{
			$end = time();

			if( $task_run_filter_log->is_failed() ){
				$task_dao::get_instance()->mark_as_failed( $task );
			}else if( $is_in_progress ){
				$task_dao::get_instance()->mark_as_in_progress( $task );
			}else{
				$task_dao::get_instance()->mark_as_succeeded( $task );
			}

			// marking bulk arguments failed or succeeded
			$task_bulk_arguments_succeeded = array();
			foreach( $task_bulk_arguments as $task_bulk_argument ){
				if( ! isset( $task_run_filter_log->get_bulk_fails()[ $task_bulk_argument->get_id() ] ) ){
					$task_bulk_arguments_succeeded[] = $task_bulk_argument;
				}
			}
			$task_bulk_argument_dao->mark_many_as_succeeded( $task_bulk_arguments_succeeded );
			$task_bulk_argument_dao->mark_many_as_failed( array_values( $task_run_filter_log->get_bulk_fails() ) );

			BTM_Task_Run_Log_Dao::get_instance()->create(
				new BTM_Task_Run_Log(
					$task->get_id(),
					BTM_Task_Manager_Log_Dao::get_instance()->get_session_id(),
					$task_run_filter_log->get_logs(),
					$start,
					$end
				)
			);
		}
	}

	/**
	 * Changes the task running status during the run
	 * Logs information that the task callbacks returns
	 *
	 * @param I_BTM_Task $task
	 */
	public function run_simple_task( I_BTM_Task $task ){
		$task_dao = BTM_Task_Dao::get_instance();
		$task_dao::get_instance()->mark_as_running( $task );

		$start = time();
		try{
			$task_run_filter_log = new BTM_Task_Run_Filter_Log();
			$callback_args = $task->get_callback_arguments();
			$task_bulk_arguments = array();
			/**
			 * Runs the background tasks, gathers their logs.
			 *
			 * There is a prefix added to the filter tag,
			 * @see BTM_Plugin_Options::get_task_filter_name_prefix()
			 *
			 * @param BTM_Task_Run_Filter_Log $task_run_filter_log      the logs that callback functions should return
			 * @param mixed[] $callback_args                            the callback arguments
			 * @param BTM_Task_Bulk_Argument[] $task_bulk_arguments     the callback arguments
			 */
			$task_run_filter_log = apply_filters(
				BTM_Plugin_Options::get_instance()->get_task_filter_name_prefix() . $task->get_callback_action(),
				$task_run_filter_log,
				$callback_args,
				$task_bulk_arguments
			);
		}catch( Exception $e ){
			$task_run_filter_log->add_log( $e->getMessage() );
			$task_run_filter_log->set_failed( true );
		}finally{
			$end = time();

			if( $task_run_filter_log->is_failed() ){
				$task_dao::get_instance()->mark_as_failed( $task );
			}else{
				$task_dao::get_instance()->mark_as_succeeded( $task );
			}

			BTM_Task_Run_Log_Dao::get_instance()->create(
				new BTM_Task_Run_Log(
					$task->get_id(),
					BTM_Task_Manager_Log_Dao::get_instance()->get_session_id(),
					$task_run_filter_log->get_logs(),
					$start,
					$end
				)
			);
		}
	}
}