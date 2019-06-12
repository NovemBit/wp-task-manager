<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Checks the reasons not to run tasks
 *
 * Class BTM_Run_Restrictor
 */
final class BTM_Run_Restrictor {
	// region Singleton

	/**
	 * @var BTM_Run_Restrictor
	 */
	private static $instance = null;

	/**
	 * @return BTM_Run_Restrictor
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->timer = new BTM_Timer();
		$this->timer->start();
	}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * @var BTM_Timer
	 */
	private $timer;

	/**
	 * Checks all the restrictions
	 *
	 * @return string|true
	 *      string - restriction message
	 *      true - restriction passed
	 */
	public function check_all_restrictions(){
		$restriction_message = $this->check_execution_time_restriction();

		if( true !== $restriction_message ){
			return $restriction_message;
		}

		return true;
	}

	/**
	 * Checks the execution time restriction
	 *
	 * @return string|true
	 *      string - restriction message
	 *      true - restriction passed
	 */
	public function check_execution_time_restriction(){
		$allowed_duration = BTM_Plugin_Options::get_instance()->get_total_execution_allowed_duration_in_seconds();
		if( $allowed_duration <= $this->timer->get_time_elapsed_in_seconds() ){
			$this->timer->stop();
			return __( 'Allowed total execution time is exceeded', 'background_task_manager' );
		}

		return true;
	}
}