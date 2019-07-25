<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Manages the plugin's cron job
 *
 * Class BTM_Cron_Job_Manager
 */
final class BTM_Cron_Job_Manager{
	// region Singleton

	/**
	 * @var BTM_Cron_Job_Manager
	 */
	private static $instance = null;
	/**
	 * @return BTM_Cron_Job_Manager
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_filter( 'cron_schedules', array( $this, 'on_cron_schedules_fix_interval' ) );
		add_filter( 'cron_schedules', array( $this, 'on_cron_schedules_fix_interval_for_delete_old_tasks_logs_bulk_arguments' ) );
	}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * Schedule the cron job of the plugin
	 */
	public function activate_cron_job(){
		$plugin_options = BTM_Plugin_Options::get_instance();
		if ( ! wp_next_scheduled ( $plugin_options->get_cron_job_name() ) ) {
			$interval = $plugin_options->get_cron_job_interval_in_minutes();

			wp_schedule_event( time(), $interval . 'min', $plugin_options->get_cron_job_name() );
		}
	}

	/**
	 * Schedule the cron job of the plugin logs delete
	 */
	public function activate_delete_old_tasks_logs_bulk_arguments_cron_job(){
		$plugin_options = BTM_Plugin_Options::get_instance();
		if ( ! wp_next_scheduled ( $plugin_options->get_delete_old_tasks_logs_bulk_arguments_cron_job_name() ) ) {
			$interval = $plugin_options->get_delete_old_tasks_logs_bulk_arguments_cron_job_interval_in_days();
			wp_schedule_event( time(), $interval . 'day', $plugin_options->get_delete_old_tasks_logs_bulk_arguments_cron_job_name() );
		}
	}

	/**
	 * Callback for cron_schedules to fix minute intervals
	 *
	 * @param array<string, array> $schedules
	 *
	 * @return array<string, array>
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/cron_schedules
	 */
	public function on_cron_schedules_fix_interval( $schedules ) {
		$interval = BTM_Plugin_Options::get_instance()->get_cron_job_interval_in_minutes();
		if ( ! isset( $schedules[ $interval . 'min' ] ) ) {
			$schedules[ $interval . 'min' ] = array(
				'interval' => $interval * 60,
				'display'  => sprintf( __( 'Once every %d minutes', 'background_task_manager' ), $interval )
			);
		}
		return $schedules;
	}

	/**
	 * Callback for cron_schedules to fix days intervals
	 *
	 * @param array<string, array> $schedules
	 *
	 * @return array<string, array>
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/cron_schedules
	 */
	public function on_cron_schedules_fix_interval_for_delete_old_tasks_logs_bulk_arguments( $schedules ) {
		$interval = BTM_Plugin_Options::get_instance()->get_delete_old_tasks_logs_bulk_arguments_cron_job_interval_in_days();
		if ( ! isset( $schedules[ $interval . 'day' ] ) ) {
			$schedules[ $interval . 'day' ] = array(
				'interval' => $interval * 86400,
				'display'  => sprintf( __( 'Once every %d days', 'background_task_manager' ), $interval )
			);
		}
		return $schedules;
	}

	/**
	 * Unschedules the cron job of the plugin
	 */
	public function remove_cron_job(){
		 wp_clear_scheduled_hook( BTM_Plugin_Options::get_instance()->get_cron_job_name() );
	}

	/**
	 * Unschedules the cron job of the plugin log delete
	 */
	public function remove_delete_old_tasks_logs_bulk_arguments_cron_job(){
		wp_clear_scheduled_hook( BTM_Plugin_Options::get_instance()->get_delete_old_tasks_logs_bulk_arguments_cron_job_name() );
	}
}