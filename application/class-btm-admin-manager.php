<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

final class BTM_Admin_Manager {
	// region Singleton

	/**
	 * @var bool
	 */
	private static $created = false;
	/**
	 * @return BTM_Admin_Manager
	 *
	 * @throws Exception
	 *      in the case this method called more than once
	 */
	public static function run(){
		if( false === self::$created ){
			return new self();
		}else{
			throw new Exception('The instance should only be created once and used from the class BTM_Admin_Manager');
		}
	}

	private function __construct() {
		add_action( 'load-toplevel_page_btm', array($this, 'on_load_toplevel_page_btm') );
		add_action( 'load-bg-task-manager_page_btm-bulk-tasks', array($this, 'on_load_bg_task_manager_page_btm_bulk_tasks') );
		add_action( 'load-bg-task-manager_page_btm-logs', array($this, 'on_load_bg_task_manager_page_btm_logs') );
		add_action( 'admin_menu', array( $this, 'btm_plugin_setup_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'on_hook_admin_scripts' ) );
	}

	private function __clone() {}
	private function __wakeup() {}

	function on_hook_admin_scripts() {
		wp_enqueue_script( 'wp-jquery-date-picker', plugins_url( 'wp-task-manager/assets/js/admin.js' ), array( 'jquery','jquery-ui-datepicker' ), '1.0.0' , true );
	}

	// endregion

	/**
	 * @var $table_task BTM_Admin_Table_Tasks object
	 */
	private $table_task;

	/**
	 * Callback function toplevel_page_btm
	 */
	public function on_load_toplevel_page_btm(){
		$this->table_task = new BTM_Admin_Table_Tasks();
		$this->table_task->process_bulk_action();
	}

	/**
	 * @var $table_task BTM_Admin_Table_Bulk_Tasks object
	 */
	private $table_bulk_tasks;

	/**
	 * Callback function bg-task-manager-page_btm_bulk_tasks
	 */
	public function on_load_bg_task_manager_page_btm_bulk_tasks(){
		$this->table_bulk_tasks = new BTM_Admin_Table_Bulk_Tasks();
		$this->table_bulk_tasks->process_bulk_action();
	}

	/**
	 * @var $table_task BTM_Admin_Table_Logs object
	 */
	private $table_logs;

	/**
	 * Callback function bg-task-manager-page_btm_logs
	 */
	public function on_load_bg_task_manager_page_btm_logs(){
		$this->table_logs = new BTM_Admin_Table_Logs();
		$this->table_logs->process_bulk_action();
	}

	/**
	 * admin_menu action callback function
	 */
	public function btm_plugin_setup_menu(){
		// Admin menu Background Task Manager page init
		add_menu_page(
			'Background Task Manager',
			'BG Task Manager',
			'manage_options',
			'btm'
		);

		// Admin Tasks submenu page init
		add_submenu_page(
			'btm',
			'Tasks',
			'Tasks',
			'manage_options',
			'btm',
			array(
				$this,
				'btm_admin_task_sub_page'
			)
		);

		// Admin Bulk Tasks submenu page init
		add_submenu_page(
			'btm',
			'Bulk Tasks',
			'Bulk Tasks',
			'manage_options',
			'btm-bulk-tasks',
			array(
				$this,
				'btm_admin_bulk_task_sub_page'
			)
		);

		// Admin Logs submenu page init
		add_submenu_page(
			'btm',
			'Logs',
			'Logs',
			'manage_options',
			'btm-logs',
			array(
				$this,
				'btm_admin_logs_sub_page'
			)
		);
	}

	/**
	 * Show Task submenu page
	 */
	public function btm_admin_task_sub_page(){
		$this->table_task->prepare_items();
		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>
			<form id="tasks-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $this->table_task->search_box('Search', 'search_id'); ?>
				<?php $this->table_task->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Show Bulk Task submenu page
	 */
	public function btm_admin_bulk_task_sub_page(){
		$this->table_bulk_tasks->prepare_items();

		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>
			<form id="bulk-tasks-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $this->table_bulk_tasks->search_box('Search', 'search_id'); ?>
				<?php $this->table_bulk_tasks->display(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Show Logs submenu page
	 */
	public function btm_admin_logs_sub_page(){
		$this->table_logs->prepare_items();
		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>
			<form id="logs-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $this->table_logs->search_box('Search', 'search_id'); ?>
				<?php $this->table_logs->display(); ?>
			</form>
		</div>
		<?php
	}

}