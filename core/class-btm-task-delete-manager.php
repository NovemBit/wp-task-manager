<?php


if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Delete_Manager
 */
class BTM_Task_Delete_Manager {
	// region Singleton

	/**
	 * @var BTM_Task_Delete_Manager
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Delete_Manager
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct(){
		add_filter( 'btm_' . $this->get_callback_action(), array( $this, 'on_btm_task_delete' ), 10, 1 );
	}
	private function __clone(){}
	private function __wakeup(){}

	// endregion

	public function get_callback_action(){
		return 'delete_task_bulk_arguments_logs';
	}

	/**
	 *  Create task to delete logs
	 *
	 * @return bool|BTM_Task_Delete
	 */
	public function create_task(){
		return new BTM_Task_Delete( $this->get_callback_action(), array(), 200 );
	}

	/**
	 * @param BTM_Task_Run_Filter_Log $task_run_filter_log      the logs that callback functions should return
	 *
	 * @return BTM_Task_Run_Filter_Log
	 */
	public function on_btm_task_delete( BTM_Task_Run_Filter_Log $task_run_filter_log ){
			$plugin_options = BTM_Plugin_Options::get_instance();
			$interval = $plugin_options->get_delete_old_tasks_logs_bulk_arguments_interval();

			$deleted_tasks = BTM_Task_Dao::get_instance()->delete_by_date_interval( $interval );

			if( $deleted_tasks ){
				$task_run_filter_log->add_log( $interval . ' days expired tasks deleted successfully' );
				$task_run_filter_log->set_failed( false );
			}else{
				$task_run_filter_log->add_log( 'Could not delete '. $interval .' days expired tasks' );
				$task_run_filter_log->set_failed( true );
			}

			$deleted_bulk_arguments = BTM_Task_Bulk_Argument_Dao::get_instance()->delete_by_date_interval( $interval );

			if( $deleted_bulk_arguments ){
				$task_run_filter_log->add_log( $interval . ' days expired bulk arguments deleted successfully' );
				$task_run_filter_log->set_failed( false );
			}else{
				$task_run_filter_log->add_log( 'Could not delete '. $interval .' days expired bulk arguments' );
				$task_run_filter_log->set_failed( true );
			}

			$deleted_logs = BTM_Task_Run_Log_Dao::get_instance()->delete_by_date_interval( $interval );

			if( $deleted_logs ){
				$task_run_filter_log->add_log( $interval . ' days expired logs deleted successfully' );
				$task_run_filter_log->set_failed( false );
			}else{
				$task_run_filter_log->add_log( 'Could not delete '. $interval .' days expired  logs' );
				$task_run_filter_log->set_failed( true );
			}

		return $task_run_filter_log;
	}
}