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
	 * @throws LogicException
	 *      in the case this method called more than once
	 */
	public static function run(){
		if( false === self::$created ){
			new self();
		}else{
			throw new LogicException('BTM_Admin_Manager should only run once inside this plugin');
		}
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'on_hook_admin_menu_setup' ) );

		add_action( 'load-bg-task-manager_page_btm-logs', array($this, 'on_load_bg_task_manager_page_btm_logs') );

		add_action( 'wp_ajax_btm_ajax', array( $this, 'btm_ajax_handler') );
		add_action( 'wp_ajax_btm_bulk_delete_ajax', array( $this, 'on_hook_wp_ajax_btm_bulk_delete_ajax') );

		add_action( 'admin_enqueue_scripts', array( $this, 'on_hook_admin_enqueue_scripts' ) );
	}

	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * Should only be called from hook admin_enqueue_scripts
	 * @see https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
	 */
	function on_hook_admin_enqueue_scripts() {
		$asset_version = BTM_Plugin_Options::get_instance()->get_asset_version();

		wp_enqueue_script( 'select2-script', plugin_dir_url( __DIR__ ) . 'assets/js/select2/dist/js/select2.js', array( 'jquery' ), $asset_version , true );
		wp_enqueue_style( 'select2-style', plugin_dir_url( __DIR__ ) . 'assets/js/select2/dist/css/select2.css', array(), $asset_version );

		wp_enqueue_script( 'fancybox-script', plugin_dir_url( __DIR__ ) . 'assets/js/fancybox/fancybox/jquery.fancybox-1.3.4.pack.js', array( 'jquery' ), $asset_version , true );
		wp_enqueue_style( 'fancybox-style', plugin_dir_url( __DIR__ ) . 'assets/js/fancybox/fancybox/jquery.fancybox-1.3.4.css', array(), $asset_version );

		wp_enqueue_script( 'btm-admin-scripts', plugin_dir_url( __DIR__ ) . 'assets/js/admin.js' , array( 'jquery' , 'fancybox-script' ), $asset_version , true );
		wp_localize_script( 'btm-admin-scripts', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_style( 'btm-admin-style', plugin_dir_url( __DIR__ ) . 'assets/css/style.css', array(), $asset_version );
	}

	// region Admin Menu

	/**
	 * Setup plugin admin menus
	 *
	 * Should only be called from hook admin_menu
	 * @see https://developer.wordpress.org/reference/hooks/admin_menu/
	 */
	public function on_hook_admin_menu_setup(){
		$menu_slug = BTM_Plugin_Options::get_instance()->get_admin_menu_slug();

		// Admin menu Background Task Manager page init
		add_menu_page(
			__( 'Background Task Manager', 'background_task_manager' ),
			__( 'BG Task Manager', 'background_task_manager' ),
			'manage_options',
			$menu_slug
		);

		new BTM_Admin_Task_Page_Table( $menu_slug );

		new BTM_Admin_Task_Bulk_Argument_Page_Table( $menu_slug );

//		// Admin Logs submenu page init
//		add_submenu_page(
//			'btm',
//			__( 'Logs', 'background_task_manager' ),
//			__( 'Logs', 'background_task_manager' ),
//			'manage_options',
//			'btm-logs',
//			array(
//				$this,
//				'btm_admin_logs_sub_page'
//			)
//		);
//
//		// Admin Settings submenu page init
//		add_submenu_page(
//			'btm',
//			__( 'Settings', 'background_task_manager' ),
//			__( 'Settings', 'background_task_manager' ),
//			'manage_options',
//			'btm-settings',
//			array(
//				$this,
//				'btm_admin_settings_sub_page'
//			)
//		);
	}

	/**
	 * Show Settings submenu page
	 */
	public function btm_admin_settings_sub_page(){
		$notification = BTM_Notification_Dao::get_instance();
		if( isset( $_POST[ "btm-cron-interval" ] ) ){
			$interval = $_POST[ "btm-cron-interval" ];
			if( ctype_digit( $interval ) ){
				update_option( 'btm_cron_interval', (int)$interval, 'no' );
			}
		}
		if( isset( $_POST[ "btm-cron-duration" ] ) ){
			$duration = $_POST[ "btm-cron-duration" ];
			if( ctype_digit( $duration ) ){
				update_option( 'btm_cron_duration', (int)$duration, 'no' );
			}
		}
		if( isset( $_POST[ "callback_action" ] ) && isset( $_POST[ "status" ] ) && isset( $_POST[ "users" ] ) ){
			$callback[ "callback_action" ] = $_POST[ "callback_action" ];
			$callback[ "status" ] = $_POST[ "status" ];
			$users = $_POST[ "users" ];

			$last_insert_id = $notification->create_callback( $callback );
			if( $last_insert_id !== false )
				foreach ( $users as $user_id ){
					$notification->create_users( $user_id, $last_insert_id );
				}
		}
		$tasks = new BTM_Admin_Table_Tasks();
		$callback_actions = $tasks->get_callback_actions();
		$task_run_statuses = BTM_Task_Run_Status::get_statuses();
		$users = get_users( [ 'role__in' => [ 'administrator' ] ] );
		$callbacks_and_statuses = $notification->get_callback_actions_and_statuses();
		$users_data = $notification->get_users();
		?>

		<div class="wrap">
			<h1>Background Task Manager Settings</h1>
			<form method="post">
				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row"><label for="cron-job">Cron Job Interval</label></th>
						<td>
							<input name="btm-cron-interval" id="cron-job" type="number" min="1" class="regular-text" value="<?php echo get_option( 'btm_cron_interval' ); ?>" >
							<p class="description" id="cron-job" >The cron job recurrence interval in minutes</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="duration">Total Execution Duration </label></th>
						<td>
							<input name="btm-cron-duration" id="duration" type="number" min="1" class="regular-text" value="<?php echo get_option( 'btm_cron_duration' ); ?>" >
							<p class="description" id="duration" >Total execution allowed duration in seconds</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="callback">Email Notifications</label></th>
						<td>
							<?php //region Selects ?>
							<label for="callback">If callback action</label>
							<select name="callback_action" id="callback" class="btm-callback-action-settings" >
								<option>Callback actions</option>
								<?php foreach ( $callback_actions as $callback_action ) {
									?>
									<option value="<?php echo $callback_action->callback_action; ?>" ><?php echo $callback_action->callback_action; ?></option>
									<?php
								} ?>
							</select>
							<label for="status">on status</label>
							<select name="status" id="status" class="btm-status-settings">
								<option>Status</option>
								<?php foreach ( $task_run_statuses as $status => $display_name ) {
									?><option value="<?php echo $status; ?>"><?php echo $display_name; ?></option><?php
								} ?>
							</select>
							<label for="users">notify</label>
							<select name="users[]" is="users" class="btm-users-settings" multiple="multiple">
								<?php foreach ( $users as $user ) {
									?><option value="<?php echo $user->data->ID; ?>"><?php echo $user->data->display_name; ?></option><?php
								} ?>
							</select>
							<p class="description" id="duration" >Select callback action to trigger a notification for the selected users</p>
							<?php //endregion   ?>
						</td>
					</tr>

					</tbody>
				</table>
				<?php submit_button( 'Save Changes', 'primary', 'submit', true, array() ); ?>
			</form>
			<?php if( $callbacks_and_statuses ){ ?>
				<div class="btm-container" >
					<div class="btm-bulk-action">
						<select class="btm-bulk-select">
							<option>Bulk actions</option>
							<option value="delete">Delete</option>
						</select>
						<button class="button btm-bulk-delete-button">Apply</button>
					</div>
					<table class="btm-notify-table">
						<tr class="btm-tr">
							<th class="btm-th-td"><input type="checkbox" class="btm-bulk-delete" name="bulk-delete" value="all" /></th>
							<th class="btm-th-td">Callback Action</th>
							<th class="btm-th-td">Status</th>
							<th class="btm-th-td">Users</th>
						</tr>
						<?php
						foreach ( $callbacks_and_statuses as $value ){
							?>
							<tr class="btm-tr">
								<td class="btm-th-td"><input type="checkbox" class="btm-delete" name="delete" value="<?php echo $value->id; ?>" /></td>
								<td class="btm-th-td" ><?php echo $value->callback_action; ?></td>
								<td class="btm-th-td"><?php echo $value->status; ?></td>
								<td class="btm-th-td">
									<?php
									foreach ( $users_data as $user_data ){
										if( $value->id === $user_data->notification_callback_id ){
											$user = get_userdata( $user_data->user_id );

											?><span class="btm-user" >
											<span class="btm-user-remove" data_user_id="<?php echo $user_data->user_id; ?>" data_notification_callback_id="<?php echo $user_data->notification_callback_id; ?>">×</span>
											<?php echo $user->display_name; ?>

											</span><?php
										}
									}
									?>
								</td>
							</tr>
							<?php
						}
						?>
					</table>
				</div>
			<?php } ?>
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

	// endregion

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

	public function btm_ajax_handler(){
		$notification = BTM_Notification_Dao::get_instance();
		$callback_id = (int)$_POST['notification_callback_id'];
		$user_id = (int)$_POST['user_id'];
		$response = $notification->delete_user( $callback_id, $user_id );

		if( $response === true ){
			wp_send_json_success( true );
		}else{
			wp_send_json_error( false );
		}
	}

	public function on_hook_wp_ajax_btm_bulk_delete_ajax(){
		$notification = BTM_Notification_Dao::get_instance();
		$notification_ids = $_POST['callback_action_ids'];
			foreach ( $notification_ids as $notification_id){
				$responses[] = $notification->delete_notification_rule( $notification_id );
			}
	}
}