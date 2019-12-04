<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

final class BTM_Admin_Notification_Page_Table extends BTM_Admin_Page_Table{
	// region Singleton

	/**
	 * @var bool
	 */
	protected static $created = false;

	// endregion

	// region Page

	/**
	 * @return string
	 */
	public static function get_page_slug(){
		return 'btm-notifications';
	}

	protected function add_submenu_page(){

		$page_hook = add_submenu_page(
			$this->get_page_parent_slug(),
			__( 'Notifications', 'background_task_manager' ),
			__( 'Notifications', 'background_task_manager' ),
			'manage_options',
			self::get_page_slug(),
			array(
				$this,
				'on_hook_page_render_notifications'
			)
		);

		if( $page_hook ){
			add_action( 'load-' . $page_hook, array( $this, 'on_hook_page_load_process_bulk' ) );
		}else{
			if( is_admin() ) {
				add_action( 'admin_notices', function () {
					$class   = 'notice notice-error';
					$message = __( 'Could not create admin page to show background tasks.', 'background_task_manager' );

					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
				} );
			}
		}
	}

	/**
	 * Show Notifications submenu page
	 *
	 * Callback for admin page render
	 */
	public function on_hook_page_render_notifications(){
		$this->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo get_admin_page_title(); ?></h1>
            <a href="<?php echo admin_url( '?page=btm-notification-rule' ); ?>" class="page-title-action">Add New</a>
            <hr class="wp-header-end">
			<?php $this->views(); ?>
			<form id="tasks-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
				<?php $this->display(); ?>
			</form>
		</div>
		<?php
	}

	public function on_hook_page_load_process_bulk(){

        if( ! empty( $_GET[ 'action' ] ) ){
	        // Bulk Action Delete
	        if ( static::BULK_ACTION_DELETE === $_GET['action'] ) {
		            $this->delete_tasks();
	        }
        }

	}

	private function delete_tasks(){
		$notification = BTM_Notification_Dao::get_instance();
		$to_delete = $_GET[ 'record' ];
		if( ! is_array( $to_delete ) ){
			$to_delete = array( $to_delete );
		}
		foreach ( $to_delete as $rule_id ){
			$responses[] = $notification->delete_notification_rule( $rule_id );
		}

		if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
			wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', static::BULK_ACTION_DELETE, ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
			exit;
		}
    }

	// endregion

	// region Table

	/**
	 * @return string
	 */
	protected function get_entity_singular_name(){
		return __( 'notification', 'background_task_manager' );
	}
	/**
	 * @return string
	 */
	protected function get_entity_plural_name(){
		return __( 'notifications', 'background_task_manager' );
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array<string, string>
	 */
	public function get_bulk_actions() {
		$actions = array(
			static::BULK_ACTION_DELETE => __( 'Delete', 'background_task_manager' )
		);

		return $actions;
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @uses WP_List_Table::set_pagination_args()
	 *
	 */
	public function prepare_items() {
		$rules = BTM_Notification_Dao::get_instance()->get_notification_rules();
		if( $rules == false ){ $rules = array(); }
		$total_items = count( $rules );
		$this->items = $rules;

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => -1
			)
		);

		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$this->_column_headers = array($columns, $hidden);
	}

	// region Column Groups

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'                        => '<input type="checkbox" />',
			'id'                        => __( 'ID', 'background_task_manager' ),
			'callback_action'           => __( 'Callback Action', 'background_task_manager' ),
			'webhook'                   => __( 'Discord Webhook', 'background_task_manager' ),
			'report_type'               => __( 'Report types', 'background_task_manager' ),
		);
		return $columns;
	}

	/**
	 * Get a list of hidden columns.
	 *
	 * @return array
	 */
	public function get_hidden_columns(){
		return array( 'id' );
	}

	// endregion

	// region Column

	/**
	 * Show table checkboxes
	 *
	 * @param object $item
	 */
	public function column_cb( $item ) {
		echo sprintf('<input type="checkbox" name="record[]" value="%s" />', $item->id );
	}

	/**
	 * Show callback_action column
	 *
	 * @return string
	 */
	public function column_callback_action( $item ) {
		$url = admin_url() . 'admin.php';

		$item_id = $item->id;
		$actions[ 'edit' ] = sprintf(
			'<a href="?page=btm-notification-rule&id='. $item_id .'">' . __( 'Edit', 'background_task_manager' ) . '</a>',
			$_REQUEST['page'],
			'edit',
			$item_id
		);
		$callback_actions = maybe_unserialize( $item->callback_action );
		$to_show = '';
		foreach ( $callback_actions as $callback_action ){
			$to_show .= "\n".$callback_action;
        }

		return sprintf(
			'%1$s %2$s',
			$to_show,
			$this->row_actions( $actions )
		);
	}

	/**
	 * Show webhook column
	 */
	public function column_webhook( $item ) {
		$webhook = $item->webhook;
		echo $webhook;
	}

	/**
	 * Show priority column
	 */
	public function column_report_type( $item ) {
	    $report_types = $item->report_type;
		$report_types = maybe_unserialize( $report_types );
		$to_show = '';
		foreach ( $report_types as $report_type ){
			$to_show .= "<p>".$report_type."</p>";
		}

		echo $to_show;
	}

	protected function row_actions( $actions, $always_visible = false ) {
		if( ! is_array( $actions ) || 0 >= count( $actions ) ){
			return '';
		}
		$action_count = count( $actions );
		$i = 0;

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
		foreach ( $actions as $action => $link ) {
			++$i;
			( $i == $action_count ) ? $sep = '' : $sep = ' | ';
			$out .= "<span class='$action'>$link$sep</span>";
		}
		$out .= '</div>';

		return $out;
	}

	// endregion
}