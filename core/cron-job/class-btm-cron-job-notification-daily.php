<?php


if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Cron_Job_Notification_Daily
 */
final class BTM_Cron_Job_Notification_Daily extends BTM_Cron_Job {
	// region Singleton

	/**
	 * @var BTM_Cron_Job_Notification_Daily
	 */
	protected static $instance = null;

	private function __clone() {
	}

	private function __wakeup() {
	}

	// endregion

	/**
	 * @return string
	 */
	public function get_name() {
		return 'btm_run_notification_daily';
	}

	/**
	 * @return int
	 */
	public function get_interval_in_seconds() {
		return 24*60*60;
	}

	/**
	 * @return string
	 */
	public function get_recurrence() {
		return 'daily';
	}

	/**
	 * The callback job, should not be called directly
	 * Report daily
	 */
	public function on_cron_job_runs() {

		BTM_Notification_Daily_Manager::get_instance();

		$to_report = BTM_Notification_Daily_Manager::get_instance()->create_task();

		BTM_Task_Manager::get_instance()->register_simple_task( $to_report );
	}
}