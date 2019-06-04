<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Cron_Job_Manager
 *  manages the cron job of the plugins
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
	 * Unschedules the cron job of the plugin
	 */
	public function remove_cron_job(){
		 wp_clear_scheduled_hook( BTM_Plugin_Options::get_instance()->get_cron_job_name() );
	}
}