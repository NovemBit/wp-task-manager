<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Plugin_Options
 *  provides plugin-wide options
 *  provides environmental options
 */
final class BTM_Plugin_Options{
	// region Singleton

	/**
	 * @var BTM_Plugin_Options
	 */
	private static $instance = null;
	/**
	 * @return BTM_Plugin_Options
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->init_cron_job_interval_in_minutes();
		$this->init_is_request_debug();
	}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * @return string
	 */
	public function get_cron_job_name(){
		return 'btm_run_background_tasks';
	}

	/**
	 * Returns absolute URL to the plugin directory
	 *
	 * @return string
	 */
	public function get_url(){
		return plugins_url( basename( dirname( __DIR__ ) ) );
	}
	/**
	 * Returns absolute path to the plugin directory
	 *      or false on failure
	 *
	 * @return bool|string
	 *
	 * @see realpath
	 */
	public function get_path(){
		return realpath( plugin_dir_path( dirname( __FILE__ ) ) );
	}
	/**
	 * Returns plugin root directory name
	 *
	 * @return string
	 */
	public function get_dir_name(){
		return basename( dirname( __DIR__ ) );
	}

	/**
	 * Is plugin in debug mode
	 *
	 * @return bool
	 */
	public function is_mode_debug(){
		if( defined('BTM_DEBUG') && true === BTM_DEBUG ){
			return true;
		}

		return false;
	}

	/**
	 * @var bool
	 */
	private $is_request_debug = false;
	/**
	 * Is the current request made to debug the plugin
	 *
	 * @return bool
	 */
	public function is_request_debug(){
		return $this->is_request_debug;
	}
	/**
	 * Initiates is the current request made to debug the plugin
	 */
	private function init_is_request_debug(){
		if( false !== strpos( $_SERVER['REQUEST_URI'], 'debug-btm.php' ) ){
			$this->is_request_debug = true;
		}
	}

	/**
	 * @var int
	 */
	private $interval;
	/**
	 * @return int
	 */
	public function get_cron_job_interval_in_minutes(){
		return $this->interval;
	}
	/**
	 * @param int $interval
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $interval is not an integer of is not greater than 0
	 */
	public function set_cron_job_interval_in_minutes( $interval ){
		if( ! is_int( $interval ) || $interval <= 0 ){
			throw new InvalidArgumentException(
				'Method set_cron_job_interval_in_minutes only accepts integers greater than 0. Input was: ' . $interval
			);
		}
		// @todo: save configuration in DB, make it persistent
		$this->interval = $interval;
	}
	/**
	 * Initiates the cron job recurrence interval in minutes
	 *
	 * @return int
	 */
	private function init_cron_job_interval_in_minutes(){
		// @todo: make it configurable, read from DB
		return $this->interval = 5;
	}
}