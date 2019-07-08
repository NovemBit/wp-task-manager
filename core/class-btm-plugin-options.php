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
		$this->init_url();
		$this->init_path();
		$this->init_dir_name();
		$this->init_total_execution_allowed_duration_in_seconds();
		$this->init_allowed_insert_bulk_size();
		$this->init_mode_debug();
		$this->init_request_debug();
		$this->init_cron_job_interval_in_minutes();
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
	 * @var string
	 */
	private $url;
	/**
	 * Returns absolute URL to the plugin directory
	 *
	 * @return string
	 */
	public function get_url(){
		return $this->url;
	}
	/**
	 * @param string $url
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $url is not a string or is empty
	 */
	private function set_url( $url ){
		if( ! is_string( $url ) || empty( $url ) ){
			throw new InvalidArgumentException(
				'Method set_url only accepts not empty strings. Input was: ' . $url
			);
		}

		$this->url = $url;
	}
	/**
	 * Initializes the absolute URL to the plugin directory
	 */
	private function init_url(){
		$this->set_url( plugins_url( basename( dirname( __DIR__ ) ) ) );
	}

	/**
	 * @var string
	 */
	private $path;
	/**
	 * Returns absolute path to the plugin directory
	 *      or false on failure
	 *
	 * @return bool|string
	 *
	 * @see realpath
	 */
	public function get_path(){
		return $this->path;
	}
	/**
	 * @param string $path
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $path is not a string or is empty
	 */
	private function set_path( $path ){
		if( ! is_string( $path ) || empty( $path ) ){
			throw new InvalidArgumentException(
				'Method set_path only accepts not empty strings. Input was: ' . $path
			);
		}

		$this->path = $path;
	}
	/**
	 * Initializes the absolute path to the plugin directory
	 */
	private function init_path(){
		$this->set_path( realpath( plugin_dir_path( dirname( __FILE__ ) ) ) );
	}

	/**
	 * @var string
	 */
	private $dir_name;
	/**
	 * Returns plugin root directory name
	 *
	 * @return string
	 */
	public function get_dir_name(){
		return basename( dirname( __DIR__ ) );
	}
	/**
	 * @param string $dir_name
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $dir_name is not a string or is empty
	 */
	private function set_dir_name( $dir_name ){
		if( ! is_string( $dir_name ) || empty( $dir_name ) ){
			throw new InvalidArgumentException(
				'Method set_dir_name only accepts not empty strings. Input was: ' . $dir_name
			);
		}

		$this->dir_name = $dir_name;
	}
	/**
	 * Initializes the plugin directory name
	 */
	private function init_dir_name(){
		$this->set_dir_name( basename( dirname( __DIR__ ) ) );
	}

	/**
	 * @var int
	 */
	private $total_execution_allowed_duration_in_seconds;
	/**
	 * @return int
	 */
	public function get_total_execution_allowed_duration_in_seconds(){
		return  $this->total_execution_allowed_duration_in_seconds;
	}
	/**
	 * @param int $total_execution_allowed_duration_in_seconds
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $total_execution_allowed_duration_in_seconds is not a positive int
	 */
	private function set_total_execution_allowed_duration_in_seconds( $total_execution_allowed_duration_in_seconds ){
		if( ! is_int( $total_execution_allowed_duration_in_seconds ) || 0 >= $total_execution_allowed_duration_in_seconds ){
			throw new InvalidArgumentException(
				'Method set_total_execution_allowed_duration_in_seconds only accepts int greater than 0. Input was: '
					. $total_execution_allowed_duration_in_seconds
			);
		}

		$this->total_execution_allowed_duration_in_seconds = $total_execution_allowed_duration_in_seconds;
	}
	/**
	 * Initializes total execution allowed duration in seconds
	 */
	private function init_total_execution_allowed_duration_in_seconds(){
		// @todo: make it configurable, read from DB
		$this->set_total_execution_allowed_duration_in_seconds( 60 * 1 );
	}

	/**
	 * @return string
	 */
	public function get_db_table_prefix(){
		return 'btm_';
	}

	/**
	 * @var int
	 */
	private $allowed_insert_bulk_size;
	/**
	 * @return int
	 */
	public function get_allowed_insert_bulk_size(){
		return $this->allowed_insert_bulk_size;
	}
	/**
	 * @param int $allowed_insert_bulk_size
	 */
	private function set_allowed_insert_bulk_size( $allowed_insert_bulk_size ){
		if( ! is_int( $allowed_insert_bulk_size ) || 0 >= $allowed_insert_bulk_size || 200 < $allowed_insert_bulk_size ){
			throw new InvalidArgumentException(
				'Argument $allowed_insert_bulk_size should be int between 1 and 200. Input was: ' . $allowed_insert_bulk_size
			);
		}

		$this->allowed_insert_bulk_size = $allowed_insert_bulk_size;
	}
	/**
	 * Initializes the allowed insert bulk size
	 */
	private function init_allowed_insert_bulk_size(){
		$this->set_allowed_insert_bulk_size( 100 );
	}

	/**
	 * @return int
	 */
	public function get_max_priority(){
		return -1000;
	}
	/**
	 * @return int
	 */
	public function get_min_priority(){
		return 1000;
	}

	/**
	 * @return string
	 */
	public function get_task_filter_name_prefix(){
		return 'btm_';
	}

	/**
	 * @var bool
	 */
	private $is_mode_debug;
	/**
	 * Is plugin in debug mode
	 *
	 * @return bool
	 */
	public function is_mode_debug(){
		return $this->is_mode_debug;
	}
	/**
	 * @param bool $is_mode_debug
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $is_mode_debug is not a bool
	 */
	private function set_mode_debug( $is_mode_debug ){
		if( ! is_bool( $is_mode_debug ) ){
			throw new InvalidArgumentException(
				'Method set_mode_debug only accepts bool values. Input was: ' . $is_mode_debug
			);
		}

		$this->is_mode_debug = $is_mode_debug;
	}
	/**
	 * Initializes whether the current mode is debug
	 */
	private function init_mode_debug(){
		if( defined('BTM_DEBUG') && true === BTM_DEBUG ){
			$is_mode_debug = true;
		}else{
			$is_mode_debug = false;
		}

		$this->set_mode_debug( $is_mode_debug );
	}

	/**
	 * @var bool
	 */
	private $is_request_debug;
	/**
	 * Is the current request made to debug the plugin
	 *
	 * @return bool
	 */
	public function is_request_debug(){
		return $this->is_request_debug;
	}
	/**
	 * @param bool $is_request_debug
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $is_request_debug is not a bool
	 */
	private function set_request_debug( $is_request_debug ){
		if( ! is_bool( $is_request_debug ) ){
			throw new InvalidArgumentException(
				'Method set_request_debug only accepts bool values. Input was: ' . $is_request_debug
			);
		}

		$this->is_request_debug = $is_request_debug;
	}
	/**
	 * Initializes whether the current request made to debug the plugin
	 */
	private function init_request_debug(){
		if( false !== strpos( $_SERVER['REQUEST_URI'], 'debug-btm.php' ) ){
			$is_request_debug = true;
		}else{
			$is_request_debug = false;
		}

		$this->set_request_debug( $is_request_debug );
	}

	/**
	 * @var int
	 */
	private $interval_in_minutes;
	/**
	 * @return int
	 */
	public function get_cron_job_interval_in_minutes(){
		return $this->interval_in_minutes;
	}
	/**
	 * @param int $interval
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $interval is not an int or is not greater than 0
	 */
	private function set_cron_job_interval_in_minutes( $interval ){
		if( ! is_int( $interval ) || $interval <= 0 ){
			throw new InvalidArgumentException(
				'Method set_cron_job_interval_in_minutes only accepts int greater than 0. Input was: ' . $interval
			);
		}

		$this->interval_in_minutes = $interval;
	}
	/**
	 * Initializes the cron job recurrence interval in minutes
	 */
	private function init_cron_job_interval_in_minutes(){
		// @todo: make it configurable, read from DB
		$this->set_cron_job_interval_in_minutes( 5 );
	}
}