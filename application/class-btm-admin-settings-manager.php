<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

final class BTM_Admin_Settings_Manager {
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
			throw new LogicException('BTM_Admin_Settings_Manager should only run once inside this plugin');
		}
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'on_hook_admin_menu_setup' ) );
		add_action( 'wp_ajax_btm_bulk_delete_ajax', array( $this, 'on_hook_wp_ajax_btm_bulk_delete_ajax') );
	}

	private function __clone() {}
	private function __wakeup() {}

	// endregion

	// region Admin Menu

	/**
	 * Setup plugin admin settings submenu
	 *
	 * Should only be called from hook admin_menu
	 * @see https://developer.wordpress.org/reference/hooks/admin_menu/
	 */
	public function on_hook_admin_menu_setup(){
		$menu_slug = BTM_Plugin_Options::get_instance()->get_admin_menu_slug();

		// Admin Settings submenu page init
		add_submenu_page(
			$menu_slug,
			__( 'Settings', 'background_task_manager' ),
			__( 'Settings', 'background_task_manager' ),
			'manage_options',
			'btm-settings',
			array(
				$this,
				'btm_admin_settings_sub_page'
			)
		);
	}

	/**
	 * Show Settings submenu page
	 */
	public function btm_admin_settings_sub_page(){
		$notification = BTM_Notification_Dao::get_instance();
		if( isset( $_POST[ "btm-cron-interval" ] ) ){
			$interval = $_POST[ "btm-cron-interval" ];
			if( ctype_digit( $interval ) ){
				$updated = BTM_Plugin_Options::get_instance()->update_cron_job_interval_in_minutes( (int)$interval );
				if( $updated ){
					BTM_Cron_Job_Task_Runner::get_instance()->remove();
					BTM_Cron_Job_Task_Runner::get_instance()->activate();
				}
			}
		}
		if( isset( $_POST[ "btm-cron-duration" ] ) ){
			$duration = $_POST[ "btm-cron-duration" ];
			if( ctype_digit( $duration ) ){
				BTM_Plugin_Options::get_instance()->update_total_execution_allowed_duration_in_seconds( (int)$duration );
			}
		}
		if( isset( $_POST[ "btm-delete-old-entities-interval" ] ) ){
			$delete_old_tasks_logs_bulk_arguments_interval = $_POST[ "btm-delete-old-entities-interval" ];
			if( ctype_digit( $delete_old_tasks_logs_bulk_arguments_interval ) ){
				BTM_Plugin_Options::get_instance()->update_entities_become_old_interval( (int)$delete_old_tasks_logs_bulk_arguments_interval );
			}
		}
		if( isset( $_POST[ "btm-delete-old-entities-cron-job-interval" ] ) ){
			$delete_old_tasks_logs_bulk_arguments_cron_job_interval = $_POST[ "btm-delete-old-entities-cron-job-interval" ];
			if( ctype_digit( $delete_old_tasks_logs_bulk_arguments_cron_job_interval ) ){
				$updated = BTM_Plugin_Options::get_instance()->update_delete_old_entities_cron_job_interval_in_days( (int)$delete_old_tasks_logs_bulk_arguments_cron_job_interval );
				if( $updated ){
					BTM_Cron_Job_Delete_Old_Entities::get_instance()->remove();
					BTM_Cron_Job_Delete_Old_Entities::get_instance()->activate();
				}
			}
		}
		if( isset( $_POST[ "btm-discord-webhook" ] ) ){
		    $webhook = wp_http_validate_url( $_POST[ "btm-discord-webhook" ] );
		    $updated = BTM_Plugin_Options::get_instance()->update_discord_webook( $webhook );
		    if( $updated ){
		        //todo show admin notice success or errors
            }
        }
		if( isset( $_POST[ "callback_action" ] ) && isset( $_POST[ "status" ] ) ){
			$callback[ "callback_action" ] = $_POST[ "callback_action" ];
			$callback[ "status" ] = $_POST[ "status" ];

			$insert_id = $notification->create_callback( $callback );
		}

		$callback_actions = BTM_Task_View_Dao::get_instance()->get_callback_actions();
		$task_run_statuses = BTM_Task_Run_Status::get_statuses();
		$callbacks_and_statuses = $notification->get_callback_actions_and_statuses();
		?>

		<div class="wrap">
			<h1><?php esc_html_e( 'Background Task Manager Settings','background_task_manager' ); ?></h1>
			<form method="post">
				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row"><label for="cron-job"><?php esc_html_e( 'Cron Job Interval','background_task_manager' ); ?></label></th>
						<td>
							<input name="btm-cron-interval" id="cron-job" type="number" min="1" class="regular-text" value="<?php echo get_option( 'btm_cron_interval', 5 ); ?>" >
							<p class="description" id="cron-job" ><?php esc_html_e( 'The cron job recurrence interval in minutes','background_task_manager' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="duration"><?php esc_html_e( 'Total Execution Duration','background_task_manager' ); ?></label></th>
						<td>
							<input name="btm-cron-duration" id="duration" type="number" min="1" class="regular-text" value="<?php echo get_option( 'btm_cron_duration', 240 ); ?>" >
							<p class="description" id="duration" ><?php esc_html_e( 'Total execution allowed duration in seconds','background_task_manager' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="delete-old-entities-cron-job"><?php esc_html_e( 'Delete Expired Tasks, Bulk arguments and Logs Cron Job Interval','background_task_manager' ); ?></label></th>
						<td>
							<input name="btm-delete-old-entities-cron-job-interval" id="delete-old-entities-cron-job" type="number" min="1" class="regular-text" value="<?php echo get_option( 'btm_delete_old_entities_cron_job_interval_in_days', 30 ); ?>" >
							<p class="description" id="delete-old-entities-cron-job" ><?php esc_html_e( 'The expired tasks, bulk arguments and logs cron job recurrence interval in days','background_task_manager' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="delete-old-entities"><?php esc_html_e( 'Tasks, Bulk arguments and Logs Expiration Time','background_task_manager' ); ?></label></th>
						<td>
							<input name="btm-delete-old-entities-interval" id="delete-old-entities" type="number" min="1" class="regular-text" value="<?php echo get_option( 'btm_entities_become_old_interval_in_days' , 30 ); ?>" >
							<p class="description" id="delete-old-entities" ><?php esc_html_e( 'The tasks, bulk arguments and logs expiration time in days','background_task_manager' ); ?></p>
						</td>
					</tr>
                    <tr>
                        <th scope="row"><label for="discord"><?php esc_html_e( 'Discord WebHook','background_task_manager' ); ?></label></th>
                        <td>
                            <input name="btm-discord-webhook" id="discord" type="text" class="regular-text" value="<?php echo get_option( 'btm_discord_webhook', '' ); ?>" >
                            <p class="description" id="discord" ><?php esc_html_e( 'Enter Discord bot webhook url to send notification to this channel','background_task_manager' ); ?></p>
                        </td>
                    </tr>
					<tr>
						<th scope="row"><label for="callback"><?php esc_html_e( 'Discord Notifications Condition','background_task_manager' ); ?></label></th>
						<td>
							<?php //region Selects ?>
							<select name="callback_action" id="callback" class="btm-callback-action-settings" >
								<option><?php esc_html_e( 'Callback actions','background_task_manager' ); ?></option>
								<?php foreach ( $callback_actions as $callback_action ) {
									?>
									<option value="<?php echo $callback_action->callback_action; ?>" ><?php echo $callback_action->callback_action; ?></option>
									<?php
								} ?>
							</select>
							<label for="status"><?php esc_html_e( 'on status','background_task_manager' ); ?></label>
							<select name="status" id="status" class="btm-status-settings">
								<option><?php esc_html_e( 'Status','background_task_manager' ); ?></option>
								<?php foreach ( $task_run_statuses as $status => $display_name ) {
									?><option value="<?php echo $status; ?>"><?php echo $display_name; ?></option><?php
								} ?>
							</select>
							<p class="description" id="duration" ><?php esc_html_e( 'Select callback action to trigger a notification for the selected status','background_task_manager' ); ?></p>
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
							<option><?php esc_html_e( 'Bulk actions','background_task_manager' ); ?></option>
							<option value="delete"><?php esc_html_e( 'Delete','background_task_manager' ); ?></option>
						</select>
						<button class="button btm-bulk-delete-button"><?php esc_html_e( 'Apply','background_task_manager' ); ?></button>
					</div>
					<table class="btm-notify-table">
						<tr class="btm-tr">
							<th class="btm-th-td"><input type="checkbox" class="btm-bulk-delete" name="bulk-delete" value="all" /></th>
							<th class="btm-th-td"><?php esc_html_e( 'Callback Action','background_task_manager' ); ?></th>
							<th class="btm-th-td"><?php esc_html_e( 'Status','background_task_manager' ); ?></th>
						</tr>
						<?php
						foreach ( $callbacks_and_statuses as $value ){
							?>
							<tr class="btm-tr">
								<td class="btm-th-td"><input type="checkbox" class="btm-delete" name="delete" value="<?php echo $value->id; ?>" /></td>
								<td class="btm-th-td" ><?php echo $value->callback_action; ?></td>
								<td class="btm-th-td"><?php echo $value->status; ?></td>
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

	// endregion
	public function on_hook_wp_ajax_btm_bulk_delete_ajax(){
		$notification = BTM_Notification_Dao::get_instance();
		$notification_ids = $_POST['callback_action_ids'];
		foreach ( $notification_ids as $notification_id){
			$responses[] = $notification->delete_notification_rule( $notification_id );
		}
	}
}