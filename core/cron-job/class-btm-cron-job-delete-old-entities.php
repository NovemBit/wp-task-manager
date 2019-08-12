<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Manages the plugin's cron job
 *
 * Class BTM_Cron_Job_Delete_Old_Entities
 */
final class BTM_Cron_Job_Delete_Old_Entities extends BTM_Cron_Job {
	// region Singleton

	/**
	 * @var BTM_Cron_Job_Delete_Old_Entities
	 */
	protected static $instance = null;

	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * @return string
	 */
	public function get_name(){
		return 'btm_run_background_delete_old_entities';
	}

	/**
	 * @return int
	 */
	public function get_interval_in_seconds(){
		return BTM_Plugin_Options::get_instance()->get_delete_old_entities_cron_job_interval_in_days() * 24*60*60;
	}

	/**
	 * @return string
	 */
	public function get_recurrence(){
		return BTM_Plugin_Options::get_instance()->get_delete_old_entities_cron_job_interval_in_days() . 'day';
	}

	/**
	 * The callback job, should not be called directly
	 * Deletes the old entities
	 */
	public function on_cron_job_runs(){
		BTM_Task_Delete_Old_Entities_Manager::get_instance();

		$delete_old_entities = BTM_Task_Delete_Old_Entities_Manager::get_instance()->create_task();
		BTM_Task_Manager::get_instance()->register_simple_task( $delete_old_entities );
	}
}