<?php


if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Delete_Log_Manager
 */
class BTM_Task_Delete_Log_Manager {
	// region Singleton

	/**
	 * @var BTM_Task_Delete_Log_Manager
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Delete_Log_Manager
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct(){
		add_filter( 'btm_' . $this->get_callback_action(), array( $this, 'on_btm_task_delete_log' ), 10, 1 );
	}
	private function __clone(){}
	private function __wakeup(){}

	// endregion

	public function get_callback_action(){
		return 'task_delete_log';
	}

	/**
	 *  Create task to delete logs
	 *
	 * @return bool|BTM_Task_Delete_Log
	 */
	public function create_task(){
		$callback_action_exists = BTM_Task_Dao::get_instance()->get_by_callback_action( $this->get_callback_action() );
		if( $callback_action_exists ){
			return false;
		}
		return new BTM_Task_Delete_Log( $this->get_callback_action(), array(), 200 );
	}

	/**
	 * @param BTM_Task_Run_Filter_Log $task_run_filter_log      the logs that callback functions should return
	 *
	 * @return BTM_Task_Run_Filter_Log
	 */
	public function on_btm_task_delete_log( BTM_Task_Run_Filter_Log $task_run_filter_log ){
			$plugin_options = BTM_Plugin_Options::get_instance();
			$interval = $plugin_options->get_delete_log_interval();
			update_option( 'asd', $interval );
			$deleted = BTM_Task_Run_Log_Dao::get_instance()->delete_by_date_interval( $interval );

			if( $deleted ){
				$task_run_filter_log->add_log( $interval . ' days old logs deleted successfully' );
				$task_run_filter_log->set_failed( false );
			}else{
				$task_run_filter_log->add_log( 'Could not delete '. $interval .' day old logs' );
				$task_run_filter_log->set_failed( true );
			}

		return $task_run_filter_log;
	}
}