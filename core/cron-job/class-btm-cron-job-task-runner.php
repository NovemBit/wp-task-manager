<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Manages the plugin's cron job
 *
 * Class BTM_Cron_Job_Task_Runner
 */
final class BTM_Cron_Job_Task_Runner extends BTM_Cron_Job {
	// region Singleton

	/**
	 * @var BTM_Cron_Job_Task_Runner
	 */
	protected static $instance = null;

	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * @return string
	 */
	public function get_name(){
		return 'btm_run_background_tasks';
	}

	/**
	 * @return int
	 */
	public function get_interval_in_seconds(){
		return BTM_Plugin_Options::get_instance()->get_cron_job_interval_in_minutes() * 60;
	}

	/**
	 * @return string
	 */
	public function get_recurrence(){
		return BTM_Plugin_Options::get_instance()->get_cron_job_interval_in_minutes() . 'min';
	}

	/**
	 * The callback job, should not be called directly
	 * Runs the tasks
	 */
	public function on_cron_job_runs(){
		BTM_Task_Manager::get_instance()->run_the_tasks();
	}
}