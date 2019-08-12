<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Cron Job Prototype
 *
 * Class BTM_Cron_Job
 */
abstract class BTM_Cron_Job{
	// region Singleton

	/**
	 * @var BTM_Cron_Job
	 */
	protected static $instance = null;
	/**
	 * @return BTM_Cron_Job
	 */
	public static function get_instance(){
		if( null === static::$instance ){
			self::$instance = new static();
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
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * @return int
	 */
	abstract public function get_interval_in_seconds();

	/**
	 * @return string
	 */
	abstract public function get_recurrence();

	/**
	 * Schedule
	 */
	public function activate(){
		if ( ! wp_next_scheduled ( $this->get_name() ) ) {
			wp_schedule_event( time(), $this->get_recurrence(), $this->get_name() );
		}
	}

	/**
	 * Callback for cron_schedules to fix intervals
	 *      should not be called directly
	 *
	 * @param array<string, array> $schedules
	 *
	 * @return array<string, array>
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Filter_Reference/cron_schedules
	 */
	public function on_cron_schedules_fix_interval( $schedules ) {
		if ( ! isset( $schedules[ $this->get_recurrence() ] ) ) {
			$schedules[ $this->get_recurrence() ] = array(
				'interval' => $this->get_interval_in_seconds(),
				'display'  => sprintf( __( 'Once every %s', 'background_task_manager' ), $this->get_recurrence() )
			);
		}
		return $schedules;
	}

	/**
	 * Unschedule
	 */
	public function remove(){
		wp_clear_scheduled_hook( $this->get_name() );
	}

	/**
	 * Applies for the cron job hook
	 */
	public function hook_up(){
		add_action( $this->get_name(), array( $this, 'on_cron_job_runs' ) );
	}

	/**
	 * The callback job, should not be called directly
	 */
	abstract public function on_cron_job_runs();
}