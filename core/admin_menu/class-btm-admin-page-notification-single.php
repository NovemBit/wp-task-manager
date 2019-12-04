<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Admin_Task_Single_Page
 */
final class BTM_Admin_Notification_Single_Page {

	/**
	 * BTM_Admin_Task_Single_Page constructor.
	 */
	public function __construct() {
		$this->add_submenu_page();
	}

	// region Page

	protected function add_submenu_page() {
		$page_hook = add_submenu_page(
			'admin.php',
			__( 'Notification Rule', 'background_task_manager' ),
			__( 'Notification Rule', 'background_task_manager' ),
			'manage_options',
			'btm-notification-rule',
			array(
				$this,
				'on_hook_page_render_notification_rule'
			)
		);

		if ( ! $page_hook ) {
			if( is_admin() ){
				add_action( 'admin_notices', function () {
					$class   = 'notice notice-error';
					$message = __( 'Could not create admin page to show notification rule page.', 'background_task_manager' );

					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
				} );
			}
		}else{
			add_action( 'load-' . $page_hook, array( $this, 'on_hook_page_load_process_add_new' ) );
        }
	}

	public function on_hook_page_load_process_add_new(){
		if ( ! empty( $_POST["btm-discord-webhook"] ) &&
		     ( ! empty( $_POST["callback_action"] ) || ! empty( $_POST['all-actions'] ) ) &&
		     ( ! empty( $_POST["hourly"] ) ||
		       ! empty( $_POST["daily"] ) ||
		       ! empty( $_POST["failed"] )
		     )
		) {
			$report = [];
			if ( ! empty( $_POST["hourly"] ) ) {
				$report[] = $_POST["hourly"];
			}
			if ( ! empty( $_POST["daily"] ) ) {
				$report[] = $_POST["daily"];
			}
			if ( ! empty( $_POST["failed"] ) ) {
				$report[] = $_POST["failed"];
			}
			$callback_actions = [];
			if ( ! empty( $_POST['all-actions'] ) ) {
				$callback_actions = maybe_serialize( array( $_POST['all-actions'] ) );
			}
			if ( ! empty( $_POST['callback_action'] ) ) {
				$callback_actions = maybe_serialize( $_POST['callback_action'] );
			}

			$webhook = wp_http_validate_url( $_POST["btm-discord-webhook"] );
			$report  = serialize( $report );
			if ( ! empty( $_POST['submit'] ) && $_POST['submit'] === 'Update Rule' && ! empty( $_GET['id'] ) ) {
				$id = (int) $_GET['id'];
				BTM_Notification_Dao::get_instance()->update( $id, $callback_actions, $webhook, $report );
			} else {
				$inserted_id = BTM_Notification_Dao::get_instance()->create( $callback_actions, $webhook, $report );
				if( $inserted_id ){
					wp_safe_redirect( admin_url( '?page=btm-notification-rule&id='.$inserted_id ) );
				}else{
					if( is_admin() ) {
						add_action( 'admin_notices', function () {
							$class   = 'notice notice-error';
							$message = __( 'Could not create notification rule.', 'background_task_manager' );

							printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
						} );
					}
				}
			}

		}
    }
	/**
	 * Show Notification Rule page
	 */
	public function on_hook_page_render_notification_rule() {
		$callback_actions      = BTM_Task_Dao::get_instance()->get_callback_actions_not_system();
		$rule_webhook          = '';
		$rule_callback_actions = array();
		$rule_report_types     = array();
		if ( ! empty( $_GET['id'] ) ) {
			$rule = BTM_Notification_Dao::get_instance()->get_notification_rule_by_id( (int) $_GET['id'] );
			foreach ( $rule as $rule_obj ) {
				$rule_webhook          = $rule_obj->webhook;
				$rule_report_types     = maybe_unserialize( $rule_obj->report_type );
				$rule_callback_actions = maybe_unserialize( $rule_obj->callback_action );
			}
		}
		?>
        <div class="wrap">
        <h1><?php esc_html_e( 'Background Task Manager Notifications', 'background_task_manager' ); ?></h1>
        <form class="btm-task-edit" method="post">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label
                                for="discord"><?php esc_html_e( 'Discord WebHook', 'background_task_manager' ); ?></label>
                    </th>
                    <td>
                        <input name="btm-discord-webhook" id="discord" type="text" class="regular-text"
                               value="<?php if ( ! empty( $rule_webhook ) ) {
							       echo $rule_webhook;
						       } ?>">
                        <p class="description"
                           id="discord"><?php esc_html_e( 'Enter Discord bot webhook url to send notification to this channel', 'background_task_manager' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e( 'Report Frequence', 'background_task_manager' ); ?></label></th>
                    <td>
                        <fieldset>
                            <label for="hourly">
                                <input type="checkbox" class="btm-hourly" id="hourly" name="hourly"
                                       value="hourly" <?php if ( in_array( 'hourly', $rule_report_types ) ) {
									echo 'checked';
								} ?> />
								<?php esc_html_e( 'Hourly', 'background_task_manager' ); ?>
                            </label><br>
                            <label for="daily">
                                <input type="checkbox" class="btm-daily" id="daily" name="daily"
                                       value="daily" <?php if ( in_array( 'daily', $rule_report_types ) ) {
									echo 'checked';
								} ?> />
								<?php esc_html_e( 'Daily', 'background_task_manager' ); ?>
                            </label>
                            <br>
                            <label for="btm-failed">
                                <input type="checkbox" class="btm-failed" id="btm-failed" name="failed"
                                       value="failed" <?php if ( in_array( 'failed', $rule_report_types ) ) {
									echo 'checked';
								} ?> />
								<?php esc_html_e( ' On failure', 'background_task_manager' ); ?>
                            </label>
                        </fieldset>
                        <br>
                        <div class="on-fail">
                            <fieldset>
                                <label for="btm-checkbox-callback">
                                    <input type="checkbox" id="btm-checkbox-callback" name="all-actions"
                                           value="all" <?php if ( isset( $rule_callback_actions[0] ) && $rule_callback_actions[0] === 'all' ) {
										echo 'checked';
									} ?> >
									<?php esc_html_e( 'All callback actions', 'background_task_manager' ); ?>
                                    <p class="description"><?php esc_html_e( 'If checked All callback actions, you get notifications about all actions before or after adding this rule', 'background_task_manager' ); ?></p>
                                </label>
                            </fieldset>
                            <div class="select-callbacks">
                                <div class="notify-fail">
                                    <label for="btm-notification-callback">
                                        <strong><?php esc_html_e( 'Select callback actions to get notifications on failure', 'background_task_manager' ); ?></strong>
                                    </label>
                                </div>
                                <select name="callback_action[]" id="btm-notification-callback" multiple="multiple">
									<?php foreach ( $callback_actions as $callback_action ) {
										?>
                                        <option <?php if ( in_array( $callback_action->callback_action, $rule_callback_actions ) ) {
											echo 'selected';
										} ?> value="<?php echo $callback_action->callback_action; ?>"><?php echo $callback_action->callback_action; ?></option>
										<?php
									} ?>
                                </select><br>
                                <a class="edit" id="btm-add-all" href="javascript:void(0)"><?php esc_html_e( 'Select all', 'background_task_manager' ); ?></a>
                            </div>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
			<?php
			if ( ! empty( $_GET['id'] ) ){
				?>
                <div class="btm-submit-wrap"><?php
				submit_button( 'Update Rule', 'primary', 'submit', true, array() );
				?><a class="btm-cansle-edit"href="<?php echo admin_url( 'admin.php?page=btm-notifications' ); ?>"><?php esc_html_e( 'Cancel', 'background_task_manager' ); ?></a>
                </div><?php
			} else {
			?>
            <div class="btm-submit-wrap"><?php
				submit_button( 'Add Rule', 'primary', 'submit', true, array() );
	            ?><a class="btm-cansle-edit"href="<?php echo admin_url( 'admin.php?page=btm-notifications' ); ?>"><?php esc_html_e( 'Cancel', 'background_task_manager' ); ?></a><?php
				} ?>
            </div>
        </form>
        </div><?php
	}


}
