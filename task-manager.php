<?php
/**
 * Plugin Name: Background Task Manager
 * Description: Manages Background Tasks.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BTM_PLUGIN_ACTIVE', true );

/**
 * Plugin main class
 *  requires the files
 *  handles plugin activation, deactivation, removal
 */
final class BTM_Plugin {
	// region Singleton

	/**
	 * @var BTM_Plugin
	 */
	private static $instance = null;
	/**
	 * @return BTM_Plugin
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->bootstrap();

		$this->hooking_up();
	}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * Requires plugin files
	 */
	private function bootstrap(){
		require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'core'. DIRECTORY_SEPARATOR . 'class-btm-plugin-options.php' );
		BTM_Plugin_Options::get_instance();


		$plugin_path = BTM_Plugin_Options::get_instance()->get_path();

		$core_path = $plugin_path . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR;
		require_once( $core_path . 'class-btm-cron-job-manager.php' );

		$model_path = $plugin_path . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR;
		require_once( $model_path . 'class-btm-task-management-status.php' );
		require_once( $model_path . 'class-btm-task-run-status.php' );
		require_once( $model_path . 'class-btm-timer.php' );
		require_once( $model_path . 'class-btm-task.php' );
		require_once( $model_path . 'class-btm-task-log.php' );
		require_once( $model_path . 'class-btm-task-manager-log.php' );

		$dao_path = $plugin_path . DIRECTORY_SEPARATOR . 'dao' . DIRECTORY_SEPARATOR;
		require_once( $dao_path . 'class-btm-task-dao.php' );
		require_once( $dao_path . 'class-btm-task-log-dao.php' );
		require_once( $dao_path . 'class-btm-task-manager-log-dao.php' );

		$controller_path = $plugin_path . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR;
		require_once( $controller_path . 'class-btm-task-runner.php' );
		require_once( $controller_path . 'class-btm-task-manager.php' );
	}

	/**
	 * Applies for plugin activation, deactivation, removal and other hooks
	 */
	private function hooking_up(){
		register_activation_hook( __FILE__, array( $this, 'on_plugin_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'on_plugin_deactivation' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'on_plugin_uninstall' ) );

		add_action( BTM_Plugin_Options::get_instance()->get_cron_job_name(), array( $this, 'on_cron_job_run_tasks' ) );
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
	}

	public function on_cron_job_run_tasks(){
		BTM_Task_Manager::get_instance()->run_the_tasks();
	}

	/**
	 * Callback for plugins_loaded, should not be called directly
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/plugins_loaded
	 */
	public function on_plugins_loaded(){
		// init cron job manager
		BTM_Cron_Job_Manager::get_instance();

		$plugin_options = BTM_Plugin_Options::get_instance();
		if( is_admin() ){
			// create admin page(s) to show report(s)
			// handle ajax requests
		}else if( $plugin_options->is_mode_debug() && $plugin_options->is_request_debug() ){
			$this->on_cron_job_run_tasks();
			exit;
		}
	}

	/**
	 * Callback for plugin activation, should not be called directly
	 * @see register_activation_hook
	 */
	public function on_plugin_activation(){
		//  run DB migrations up if any
		BTM_Cron_Job_Manager::get_instance()->activate_cron_job();
	}

	/**
	 * Callback for plugin deactivation, should not be called directly
	 * @see register_deactivation_hook
	 */
	public function on_plugin_deactivation(){
		BTM_Cron_Job_Manager::get_instance()->remove_cron_job();
	}

	/**
	 * Callback for plugin removal, should not be called directly
	 * @see register_uninstall_hook
	 */
	public static function on_plugin_uninstall(){
		//  run DB migrations down if any
	}
}

// start the plugin
BTM_Plugin::get_instance();