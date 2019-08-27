<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

final class BTM_Admin_Notifications_Manager {
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
			throw new LogicException('BTM_Admin_Notifications_Manager should only run once inside this plugin');
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

		// Admin Notification submenu page init
		add_submenu_page(
			$menu_slug,
			__( 'Notifications', 'background_task_manager' ),
			__( 'Notifications', 'background_task_manager' ),
			'manage_options',
			'btm-notifications',
			array(
				$this,
				'btm_admin_notifications_sub_page'
			)
		);
	}

	/**
	 * Show Settings submenu page
	 */
	public function btm_admin_notifications_sub_page(){

		if( ! empty( $_POST[ "btm-discord-webhook" ] ) &&
            ! empty( $_POST[ "callback_action" ] ) &&
                ( ! empty( $_POST[ "hourly" ] ) ||
                  ! empty( $_POST[ "daily" ] ) ||
                  ! empty( $_POST[ "failed" ] )
                )
        ){
			$report = [];
			if( ! empty( $_POST[ "hourly" ] ) ){
				$report[] = $_POST[ "hourly" ];
			}
			if( ! empty( $_POST[ "daily" ] ) ){
				$report[] = $_POST[ "daily" ];
			}
			if( ! empty( $_POST[ "failed" ] ) ){
				$report[] = $_POST[ "failed" ];
			}
			$callback_actions = maybe_serialize( $_POST[ "callback_action" ] );
		    $webhook = wp_http_validate_url( $_POST[ "btm-discord-webhook" ] );
			$report = serialize( $report );
			BTM_Notification_Dao::get_instance()->create( $callback_actions, $webhook, $report );
        }

		$callback_actions = BTM_Task_View_Dao::get_instance()->get_callback_actions();
		$rule = BTM_Notification_Dao::get_instance()->get_notification_rules();
		?>

		<div class="wrap">
			<h1><?php esc_html_e( 'Background Task Manager Notifications','background_task_manager' ); ?></h1>
			<form method="post">
				<table class="form-table">
					<tbody>
                    <tr>
                        <th scope="row"><label for="callback"><?php esc_html_e( 'Send Notifications On','background_task_manager' ); ?></label></th>
                        <td>
							<?php //region Selects ?>
                            <select name="callback_action[]" id="btm-notification-callback" multiple="multiple">
								<?php foreach ( $callback_actions as $callback_action ) {
									?>
                                    <option value="<?php echo $callback_action->callback_action; ?>" ><?php echo $callback_action->callback_action; ?></option>
									<?php
								} ?>
                            </select>
                            <fieldset>
                                <label for="btm-checkbox-callback">
                                    <input type="checkbox" id="btm-checkbox-callback" >
			                        <?php esc_html_e( 'Select All','background_task_manager' ); ?>
                                </label>
                            </fieldset>
                            <p class="description" id="duration" ><?php esc_html_e( 'Select callback action to trigger a notification in Discord','background_task_manager' ); ?></p>
							<?php //endregion   ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="discord"><?php esc_html_e( 'Discord WebHook','background_task_manager' ); ?></label></th>
                        <td>
                            <input name="btm-discord-webhook" id="discord" type="text" class="regular-text" value="" >
                            <p class="description" id="discord" ><?php esc_html_e( 'Enter Discord bot webhook url to send notification to this channel','background_task_manager' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label><?php esc_html_e( 'Report','background_task_manager' ); ?></label></th>
                        <td>
                            <fieldset>
                                <label for="hourly">
                                    <input type="checkbox" class="btm-hourly" id="hourly" name="hourly" value="hourly" />
                                    <?php esc_html_e( 'Hourly','background_task_manager' ); ?>
                                </label><br>
                                <label for="daily">
                                    <input type="checkbox" class="btm-daily" id="daily" name="daily" value="daily" />
                                    <?php esc_html_e( 'Daily','background_task_manager' ); ?>
                                </label>
                                <p class="description"><?php esc_html_e( 'Hourly and daily report types should report all tasks in the current Discord webhook.', 'background_task_manager' ); ?></p>
                                <br>
                                <label for="failed">
                                    <input type="checkbox" class="btm-daily" id="failed" name="failed" value="failed" />
                                    <?php esc_html_e( 'On failed status','background_task_manager' ); ?>
                                </label>
                                <p class="description"><?php esc_html_e( 'Report if current callback actions go to failed status.', 'background_task_manager' ); ?></p>
                            </fieldset>
                        </td>
                    </tr>

					</tbody>
				</table>
				<?php submit_button( 'Add Rule', 'primary', 'submit', true, array() ); ?>
			</form>
			<?php if( $rule ){ ?>
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
							<th class="btm-th-td"><?php esc_html_e( 'Callback Actions','background_task_manager' ); ?></th>
                            <th class="btm-th-td"><?php esc_html_e( 'Discord WebHook','background_task_manager' ); ?></th>
                            <th class="btm-th-td"><?php esc_html_e( 'Report Types','background_task_manager' ); ?></th>
						</tr>
						<?php
						foreach ( $rule as $values ){
							?>
							<tr class="btm-tr">
								<td class="btm-th-td"><input type="checkbox" class="btm-delete" name="delete" value="<?php echo $values->id; ?>" /></td>
								<td class="btm-th-td" >
                                    <?php
                                        $callback_actions = maybe_unserialize( $values->callback_action );
                                        foreach ( $callback_actions as $callback_action ){
                                            ?><span class="btm-item"><?php echo $callback_action ?></span><br><?php
                                        }
                                    ?>
                                </td>
                                <td class="btm-th-td" ><span class="btm-item"><?php echo $values->webhook; ?></span></td>
                                <td class="btm-th-td" >
                                    <?php
	                                $report_types = maybe_unserialize( $values->report_type );
	                                foreach ( $report_types as $report_type ){
		                                ?><span class="btm-item"><?php echo $report_type ?></span><br><?php
	                                }
	                                ?></td>
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