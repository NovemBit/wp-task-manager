<?php


if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Cron_Job_Notification_Hourly
 */
final class BTM_Cron_Job_Notification_Hourly extends BTM_Cron_Job {
	// region Singleton

	/**
	 * @var BTM_Cron_Job_Notification_Hourly
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
		return 'btm_run_notification_hourly';
	}

	/**
	 * @return int
	 */
	public function get_interval_in_seconds() {
		return 60*60;
	}

	/**
	 * @return string
	 */
	public function get_recurrence() {
		return 'hourly';
	}

	/**
	 * The callback job, should not be called directly
	 * Report hourly
	 */
	public function on_cron_job_runs() {
		$manager = new BTM_Notification_Manager( $this->get_recurrence() );
		$to_report = $manager->create_task();
		BTM_Task_Manager::get_instance()->register_simple_task( $to_report );
	}
}