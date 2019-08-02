<?php


if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Delete_Manager
 */
class BTM_Task_Delete_Old_Entities_Manager {
	// region Singleton

	/**
	 * @var BTM_Task_Delete_Old_Entities_Manager
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Delete_Old_Entities_Manager
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct(){
		add_filter( BTM_Plugin_Options::get_instance()->get_task_filter_name_prefix() . $this->get_callback_action(), array( $this, 'on_btm_task_delete_old_entities' ), 10, 1 );
	}
	private function __clone(){}
	private function __wakeup(){}

	// endregion

	public function get_callback_action(){
		return 'delete_old_entities';
	}

	/**
	 *  Create task to delete logs
	 *
	 * @return BTM_Task_Delete_Old_Entities
	 */
	public function create_task(){
		return new BTM_Task_Delete_Old_Entities( $this->get_callback_action(), array(), 200 );
	}

	/**
	 * @param BTM_Task_Run_Filter_Log $task_run_filter_log      the logs that callback functions should return
	 *
	 * @return BTM_Task_Run_Filter_Log
	 */
	public function on_btm_task_delete_old_entities( BTM_Task_Run_Filter_Log $task_run_filter_log ){
		$plugin_options = BTM_Plugin_Options::get_instance();
		$interval = $plugin_options->get_entities_become_old_interval_in_days();

		$deleted_tasks = BTM_Task_Dao::get_instance()->delete_by_date_interval( $interval );

		if( $deleted_tasks ){
			$task_run_filter_log->add_log( $interval . __( ' days expired tasks deleted successfully', 'background_task_manager' ) );
			$task_run_filter_log->set_failed( false );
		}else{
			$task_run_filter_log->add_log( __( 'Could not delete ', 'background_task_manager' ) . $interval . __( ' days expired tasks', 'background_task_manager' ) );
			$task_run_filter_log->set_failed( true );
		}

		$deleted_bulk_arguments = BTM_Task_Bulk_Argument_Dao::get_instance()->delete_by_date_interval( $interval );

		if( $deleted_bulk_arguments ){
			$task_run_filter_log->add_log( $interval . __( ' days expired bulk arguments deleted successfully', 'background_task_manager' ) );
			$task_run_filter_log->set_failed( false );
		}else{
			$task_run_filter_log->add_log( __( 'Could not delete ', 'background_task_manager' ) . $interval . __( ' days expired bulk arguments', 'background_task_manager' ) );
			$task_run_filter_log->set_failed( true );
		}

		$deleted_logs = BTM_Task_Run_Log_Dao::get_instance()->delete_by_date_interval( $interval );

		if( $deleted_logs ){
			$task_run_filter_log->add_log( $interval . __( ' days expired logs deleted successfully', 'background_task_manager' ) );
			$task_run_filter_log->set_failed( false );
		}else{
			$task_run_filter_log->add_log( __( 'Could not delete ', 'background_task_manager' ) . $interval . __( ' days expired logs', 'background_task_manager' ) );
			$task_run_filter_log->set_failed( true );
		}

		return $task_run_filter_log;
	}
}