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
 *  starts migrations
 *  starts running the tasks as a cron job
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

		$model_path = $plugin_path . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR;
		require_once( $model_path . 'class-btm-timer.php' );

		require_once( $model_path . 'enum' . DIRECTORY_SEPARATOR . 'class-btm-task-management-status.php' );
		require_once( $model_path . 'enum' . DIRECTORY_SEPARATOR . 'class-btm-task-run-status.php' );

		require_once( $model_path . 'task' . DIRECTORY_SEPARATOR . 'interface-btm-task.php' );
		require_once( $model_path . 'task' . DIRECTORY_SEPARATOR . 'class-btm-task.php' );
		require_once( $model_path . 'task' . DIRECTORY_SEPARATOR . 'class-btm-task-simple.php' );
		require_once( $model_path . 'task' . DIRECTORY_SEPARATOR . 'class-btm-task-bulk-argument-normalizer.php' );
		require_once( $model_path . 'task' . DIRECTORY_SEPARATOR . 'class-btm-task-bulk-argument.php' );

		require_once( $model_path . 'log' . DIRECTORY_SEPARATOR . 'class-btm-task-run-log.php' );
		require_once( $model_path . 'log' . DIRECTORY_SEPARATOR . 'class-btm-task-manager-log.php' );
		require_once( $model_path . 'log' . DIRECTORY_SEPARATOR . 'class-btm-task-run-filter-log.php' );

		require_once( $model_path . 'admin-table' . DIRECTORY_SEPARATOR . 'class-btm-admin-table-tasks.php' );
		require_once( $model_path . 'admin-table' . DIRECTORY_SEPARATOR . 'class-btm-admin-table-logs.php' );
		require_once( $model_path . 'admin-table' . DIRECTORY_SEPARATOR . 'class-btm-admin-table-bulk-tasks.php' );

		$data_provider_path = $plugin_path . DIRECTORY_SEPARATOR . 'data-provider' . DIRECTORY_SEPARATOR;
		require_once( $data_provider_path . 'class-btm-task-type-service.php' );
		require_once( $data_provider_path . 'class-btm-db-transaction.php' );
		require_once( $data_provider_path . 'class-btm-task-dao.php' );
		require_once( $data_provider_path . 'class-btm-task-bulk-argument-dao.php' );
		require_once( $data_provider_path . 'class-btm-task-run-log-dao.php' );
		require_once( $data_provider_path . 'class-btm-task-manager-log-dao.php' );

		$migration_path = $plugin_path . DIRECTORY_SEPARATOR . 'migration' . DIRECTORY_SEPARATOR;
		require_once( $migration_path . 'interface-btm-migration-manager.php' );
		require_once( $migration_path . 'class-btm-migration-manager.php' );

		$core_path = $plugin_path . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR;
		require_once( $core_path . 'class-btm-task-bulk-argument-manager.php' );
		require_once( $core_path . 'class-btm-cron-job-manager.php' );
		require_once( $core_path . 'class-btm-run-restrictor.php' );
		require_once( $core_path . 'class-btm-task-runner.php' );

		$app_path = $plugin_path . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR;
		require_once( $app_path . 'class-btm-task-manager.php' );
		require_once( $app_path . 'class-btm-admin-manager.php' );
	}

	/**
	 * Applies for plugin activation, deactivation, removal and other hooks
	 */
	private function hooking_up(){
		register_activation_hook( __FILE__, array( $this, 'on_plugin_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'on_plugin_deactivation' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'on_plugin_uninstall' ) );

		add_action( BTM_Plugin_Options::get_instance()->get_cron_job_name(), array( $this, 'on_cron_job_run_tasks' ) );
		add_action( 'after_setup_theme', array( $this, 'on_after_setup_theme' ) );
		add_action( 'init', array( $this, 'on_late_init' ), PHP_INT_MAX - 10 );
	}

	public function on_cron_job_run_tasks(){
		BTM_Task_Bulk_Argument_Manager::get_instance();
		BTM_Task_Manager::get_instance()->run_the_tasks();
	}

	/**
	 * Callback for after_setup_theme, should not be called directly
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/after_setup_theme
	 */
	public function on_after_setup_theme(){
		// init cron job manager
		BTM_Cron_Job_Manager::get_instance();

		// init options
		BTM_Plugin_Options::get_instance();
	}

	/**
	 * Callback for init, should not be called directly
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/init
	 */
	public function on_late_init(){
		$plugin_options = BTM_Plugin_Options::get_instance();
		if( is_admin() ){
			BTM_Admin_Manager::run();
		}else if( $plugin_options->is_mode_debug() && $plugin_options->is_request_debug() && current_user_can('administrator') ){
			$this->on_cron_job_run_tasks();
			exit;
		}
	}

	/**
	 * Callback for plugin activation, should not be called directly
	 * @see register_activation_hook
	 */
	public function on_plugin_activation(){
		BTM_Migration_Manager::get_instance()->migrate_up();
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