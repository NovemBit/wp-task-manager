<?php


if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Notification_Manager
 */
class BTM_Notification_Manager {

	/**
	 * @var null
	 */
	private $range = null;

	/**
	 * BTM_Notification_Manager constructor.
	 *
	 * @param string $range
	 */
	public function __construct( $range ) {
		$this->range = $range;
		if( $range == "hourly" ){
			add_filter(
				BTM_Plugin_Options::get_instance()->get_task_filter_name_prefix() . $this->get_hourly_callback_action(),
				array( $this, 'on_btm_task_notification_report' ),
				10, 1
			);
		}
		if( $range == "daily" ){
			add_filter(
				BTM_Plugin_Options::get_instance()->get_task_filter_name_prefix() . $this->get_daily_callback_action(),
				array( $this, 'on_btm_task_notification_report' ),
				10, 1
			);
		}
	}

	/**
	 * @return string
	 */
	public function get_daily_callback_action() {
		return 'notification_daily_report';
	}

	/**
	 * @return string
	 */
	public function get_hourly_callback_action() {
		return 'notification_hourly_report';
	}

	/**
	 * @return string
	 */
	public function get_report_range() {
		return $this->range;
	}

	/**
	 * @return BTM_Task_Notify
	 */
	public function create_task() {
		if( $this->range == "hourly" ){
			return new BTM_Task_Notify( $this->get_hourly_callback_action(), array(), 1 );
		}
		if( $this->range == "daily" ){
			return new BTM_Task_Notify( $this->get_daily_callback_action(), array(), 1 );
		}
	}

	/**
	 * @param BTM_Task_Run_Filter_Log $task_run_filter_log the logs that callback functions should return
	 *
	 * @return BTM_Task_Run_Filter_Log
	 */
	public function on_btm_task_notification_report( BTM_Task_Run_Filter_Log $task_run_filter_log ) {

		$notification = new BTM_Notification_Runner();
		$reported     = $notification->report( $this->get_report_range() );
		if ( $reported === true ) {
			$task_run_filter_log->add_log( __( 'The ' . $this->get_report_range() . ' report is done', 'background_task_manager' ) );
			$task_run_filter_log->set_failed( false );
		} else {
			$task_run_filter_log->add_log( __( 'Nothing to report', 'background_task_manager' ) );
			$task_run_filter_log->set_failed( true );
		}

		return $task_run_filter_log;
	}

}