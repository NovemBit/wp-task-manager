<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Manages the plugin's cron jobs
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
		$this->jobs[] = BTM_Cron_Job_Delete_Old_Entities::get_instance();
		$this->jobs[] = BTM_Cron_Job_Task_Runner::get_instance();
	}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * @var BTM_Cron_Job[]
	 */
	private $jobs = array();

	public function activate_jobs(){
		foreach ( $this->jobs as $job ){
			$job->activate();
		}
	}

	public function remove_jobs(){
		foreach ( $this->jobs as $job ){
			$job->remove();
		}
	}

	public function hook_up_jobs(){
		foreach ( $this->jobs as $job ){
			$job->hook_up();
		}
	}
}