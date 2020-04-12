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
		if( isset( $_POST[ "btm-task-repeat" ] ) ){
			$repeat = $_POST[ "btm-task-repeat" ];
			if( ctype_digit( $repeat ) ){
				BTM_Plugin_Options::get_instance()->update_long_running_tasks_to_repeat_in_seconds( (int)$repeat );
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

		if( isset( $_POST[ 'btm-system-tasks' ] ) ){
            BTM_Plugin_Options::get_instance()->update_show_system( $_POST[ 'btm-system-tasks' ] );
        }elseif ( !empty( $_POST) ){
			BTM_Plugin_Options::get_instance()->update_show_system( 'off' );
        }
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
                        <th scope="row"><label for="repeat"><?php esc_html_e( 'Long running tasks to repeat','background_task_manager' ); ?></label></th>
                        <td>
                            <input name="btm-task-repeat" id="repeat" type="number" min="1" class="regular-text" value="<?php echo get_option( 'btm_task_repeat', get_option( 'btm_cron_interval', 5 ) * 2 * 60 ); ?>" >
                            <p class="description" id="repeat" ><?php esc_html_e( 'Duration in seconds, to keep alive tasks after disaster. Need to be greater than Cron Job Interval','background_task_manager' ); ?></p>
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
                        <th scope="row"><label for="system-tasks"><?php esc_html_e( 'System Tasks','background_task_manager' ); ?></label></th>
                        <td>
                            <?php $show_system = get_option( 'btm_show_system', 'off' ); ?>
                            <fieldset>
                                <label>
                                    <input name="btm-system-tasks" id="system-tasks" type="checkbox" class="regular-text" <?php if( $show_system === 'on' ){ echo 'checked'; } ?> >
	                                <?php esc_html_e( 'Show system tasks?', 'background_task_manager' ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
					</tbody>
				</table>
				<?php submit_button( 'Save Changes', 'primary', 'submit', true, array() ); ?>
		</div>
		<?php
	}
}