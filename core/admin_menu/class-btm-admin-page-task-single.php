<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Admin_Task_Single_Page
 */
final class BTM_Admin_Task_Single_Page {

	/**
	 * BTM_Admin_Task_Single_Page constructor.
	 */
	public function __construct() {
		$this->add_submenu_page();
	}

	// region Page

	protected function add_submenu_page(){
		$page_hook = add_submenu_page(
				'admin.php',
			__( 'Task Single', 'background_task_manager' ),
			__( 'Task Single', 'background_task_manager' ),
			'manage_options',
			'btm-task-view',
			array(
				$this,
				'on_hook_page_render_task_single'
			)
		);

		if( ! $page_hook ){
			if( is_admin() ) {
				add_action( 'admin_notices', function () {
					$class   = 'notice notice-error';
					$message = __( 'Could not create admin page to show task single page.', 'background_task_manager' );

					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
				} );
			}
		}
	}

	/**
	 * Show Task single page
	 */
	public function on_hook_page_render_task_single(){
		$task_id = $this->get_task_id();
		if( ! empty( $_POST[ 'priority' ] ) ){
			$this->update_task_priority( $_POST[ 'priority' ] );
		}
		if( ! empty( $_POST[ 'bulk_size' ] ) ){
			$task_bulk_size = (int)$_POST[ 'bulk_size' ];
			if( $task_bulk_size > 0 ){
				$this->update_task_bulk_size( $_POST[ 'bulk_size' ] );
			}
		}
		$dao = BTM_Task_Single_View_Dao::get_instance();
		$task = $dao->get_task( $task_id );
		if( $task === false ){
			throw new InvalidArgumentException( 'Wrong task id - ' . $task_id );
        }
		$task_status = $task->get_status()->get_value();
		if( $task_id === false ){
			throw new InvalidArgumentException( 'Argument $task_id should be set from $_GET. Input was: ' . $task_id );
		}
		?>
			<div class="wrap" >
				<h1>Task <?php echo $task_id; ?></h1>
				<form class="btm-task-edit" method="POST">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row"><label><?php echo __( 'Callback Action', 'background_task_manager' ); ?></label></th>
								<td><?php highlight_string( $task->get_callback_action() ); ?></td>
							</tr>
							<tr>
								<th scope="row"><label for="priority"><?php echo __( 'Priority', 'background_task_manager' ); ?></label></th>
								<td>
									<?php if( $task_status === 'registered' ){
										?><input type="number" id="priority" name="priority" value="<?php echo $task->get_priority(); ?>" /><?php
									}else{
										highlight_string( $task->get_priority() );
									} ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="bulk-size"><?php echo __( 'Bulk Size', 'background_task_manager' ); ?></label></th>
								<td>
									<?php if( $task_status === 'registered' && $task->get_bulk_size() > 0 ){
										?><input type="number" id="bulk-size" min="1" name="bulk_size" value="<?php echo $task->get_bulk_size(); ?>" /><?php
									}else{
										highlight_string( $task->get_bulk_size() );
									} ?>

								</td>
							</tr>
							<tr>
								<th scope="row"><label><?php echo __( 'Status', 'background_task_manager' ); ?></label></th>
								<td><?php highlight_string( $task->get_status() ); ?></td>
							</tr>
							<tr>
								<th scope="row"><label><?php echo __( 'Date Created', 'background_task_manager' ); ?></label></th>
								<td><?php highlight_string( date('Y/m/d H:i:s', $task->get_date_created_timestamp() ) ); ?></td>
							</tr>
							<tr>
								<th scope="row"><label><?php echo __( 'Callback Arguments', 'background_task_manager' ); ?></label></th>
								<td><?php highlight_string( "" . var_export( $task->get_callback_arguments(), true ) . "" ); ?></td>
							</tr>
						</tbody>
					</table>
					<?php submit_button( 'Save Changes', 'primary', 'submit', true, array() ); ?>
					<a class="btm-cansle-edit" href="<?php echo admin_url('admin.php?page=btm'); ?>">Cancel</a>
				</form>
			</div>
		<?php
	}

	/**
	 * @var string
	 */
	protected $task_id;

	/**
	 * @return string
	 */
	public function get_task_id(){
		if( isset( $_GET[ 'task_id' ] ) ){
			$this->task_id = $_GET[ 'task_id' ];

			return $this->task_id;
		}

		return false;
	}

	/**
	 * @param $task_priority
	 */
	protected function update_task_priority( $task_priority ){
		if( ! empty( $task_priority ) && ! empty( $this->task_id ) ){
			$task_priority = (int)$task_priority;
			$task_id = (int)$this->task_id;
			$dao = BTM_Task_Single_View_Dao::get_instance();
			$updated = $dao->update_task_priority( $task_id, $task_priority );

			if( $updated === false ){
				if( is_admin() ) {
					add_action( 'admin_notices', function () {
						$class   = 'notice notice-error';
						$message = __( 'Task priority is not updated.', 'background_task_manager' );

						printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
					} );
				}
			}
		}
	}

	/**
	 * @param $task_bulk_size
	 */
	protected function update_task_bulk_size( $task_bulk_size ){
		if( ! empty( $task_bulk_size ) && ! empty( $this->task_id ) ){
			$task_bulk_size = (int)$task_bulk_size;
			$task_id = (int)$this->task_id;
			$dao = BTM_Task_Single_View_Dao::get_instance();
			$updated = $dao->update_task_bulk_size( $task_id, $task_bulk_size );

			if( $updated === false ){
				if( is_admin() ) {
					add_action( 'admin_notices', function () {
						$class   = 'notice notice-error';
						$message = __( 'Task bulk size is not updated.', 'background_task_manager' );

						printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
					} );
				}
			}
		}
	}



	// endregion


}
