<?php


if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Notification_Hourly_Manager
 */
class BTM_Notification_Hourly_Manager {
	/**
	 * @var BTM_Notification_Hourly_Manager
	 */
	private static $instance = null;
	/**
	 * @return BTM_Notification_Hourly_Manager
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * BTM_Notification_Hourly_Manager constructor.
	 */
	public function __construct(){
		add_filter(
			BTM_Plugin_Options::get_instance()->get_task_filter_name_prefix() . $this->get_callback_action(),
			array( $this, 'on_btm_task_notification_hourly_report' ),
			10, 1
		);
	}

	/**
	 * @return string
	 */
	public function get_callback_action(){
		return 'notification_hourly_report';
	}

	/**
	 * @return string
	 */
	public function get_report_range(){
		return 'hourly';
	}

	/**
	 *  Create task to notify hourly
	 *
	 * @return BTM_Task_Notify
	 */
	public function create_task(){
		return new BTM_Task_Notify( $this->get_callback_action(), array(), 200 );
	}

	/**
	 * @param BTM_Task_Run_Filter_Log $task_run_filter_log      the logs that callback functions should return
	 *
	 * @return BTM_Task_Run_Filter_Log
	 */
	public function on_btm_task_notification_hourly_report( BTM_Task_Run_Filter_Log $task_run_filter_log ){

		$notification = new BTM_Notification_Runner();
		$reported = $notification->report( $this->get_report_range() );
		if( $reported === true ){
			$task_run_filter_log->add_log( __( 'The hourly report is done', 'background_task_manager' ) );
			$task_run_filter_log->set_failed( false );
		}else{
			$task_run_filter_log->add_log( __( 'Nothing to report', 'background_task_manager' ) );
			$task_run_filter_log->set_failed( true );
		}

		return $task_run_filter_log;
	}
}