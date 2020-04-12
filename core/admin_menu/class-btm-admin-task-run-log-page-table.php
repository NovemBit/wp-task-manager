<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

final class BTM_Admin_Task_Run_Log_Page_Table extends BTM_Admin_Page_Table{
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
		return 'btm-task-run-logs';
	}

	protected function add_submenu_page(){
		$page_hook = add_submenu_page(
			$this->get_page_parent_slug(),
			__( 'Task Run Logs', 'background_task_manager' ),
			__( 'Task Run Logs', 'background_task_manager' ),
			'manage_options',
			self::get_page_slug(),
			array(
				$this,
				'on_hook_page_render_task_run_logs'
			)
		);

		if( $page_hook ){
			add_action( 'load-' . $page_hook, array( $this, 'on_hook_page_load_process_bulk' ) );
		}else{
			if( current_user_can('administrator') ) {
				add_action( 'admin_notices', function () {
					$class   = 'notice notice-error';
					$message = __( 'Could not create admin page to show background task run logs.',
						'background_task_manager' );

					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
				} );
			}
		}
	}

	/**
	 * Show Task Run Logs submenu page
	 *
	 * Callback for admin page render
	 */
	public function on_hook_page_render_task_run_logs(){
		$this->prepare_items();

		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>
			<?php $this->views(); ?>
			<form id="task-bulk-arguments-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $this->search_box('Search', 'search_id'); ?>
				<?php $this->display(); ?>
			</form>
		</div>
		<?php
	}

	public function on_hook_page_load_process_bulk(){
		if ( isset( $_GET['action'] ) && static::BULK_ACTION_DELETE === $_GET['action'] ) {
			// todo: bulk actions should be done with POST request and nonce should be checked
			$to_delete = $_GET[ static::BULK_ACTION_DELETE ];
			if( ! is_array( $to_delete ) ){
				$to_delete = array( $to_delete );
			}

			$deleted = BTM_Task_Run_Log_Dao::get_instance()->delete_many_by_ids( $to_delete );
			// todo: check $deleted, show admin notice success or error

			if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
				wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', static::BULK_ACTION_DELETE, ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
				exit;
			}
		} else {
			if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
				wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
				exit;
			}
		}
	}

	// endregion

	// region Table

	/**
	 * @var BTM_Task_Run_Log_View_Filter
	 */
	protected $filter;

	/**
	 * @return string
	 */
	protected function get_entity_singular_name(){
		return __( 'task run log', 'background_task_manager' );
	}
	/**
	 * @return string
	 */
	protected function get_entity_plural_name(){
		return __( 'task run logs', 'background_task_manager' );
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
		$this->items = array();
		$this->prepare_filter();

		$task_run_log_view_dao = BTM_Task_Run_Log_View_Dao::get_instance();
		$this->items = $task_run_log_view_dao->get_task_run_logs( $this->filter );
		$total_items = $task_run_log_view_dao->get_task_run_logs_count( $this->filter );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				// todo: make it customizable (per user?)
				'per_page'    => $this->filter->get_items_per_page()
			)
		);

		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
	}

	protected function prepare_filter(){
		$filter = new BTM_Task_Run_Log_View_Filter();
		if( ! empty( $_GET[ 'task_id' ] ) ){
			$filter->set_task_id( (int)$_GET[ 'task_id' ] );
		}
		if( ! empty( $_GET[ 'orderby' ] ) ){
			$filter->set_order_by( $_GET[ 'orderby' ] );
		}else{
			$filter->set_order_by( 'date_started' );
		}

		if( ! empty( $_GET[ 'order' ] ) ){
			$filter->set_order( $_GET[ 'order' ] );
		}else{
			$filter->set_order( 'DESC' );
		}

		if( ! empty( $_GET[ 's' ] ) ){
			$filter->set_search( $_GET[ 's' ] );
		}

		if( ! empty( $_GET[ 'status' ] ) && 'all' !== $_GET[ 'status' ] ){
			$filter->set_status( $_GET[ 'status' ] );
		}

		$filter->set_items_per_page( 20 );
		$filter->set_current_page( $this->get_pagenum() );

		$this->filter = $filter;
	}

	protected function get_views() {
		$task_run_log_count_by_statuses = BTM_Task_Run_Log_View_Dao::get_instance()->get_task_run_log_count_by_statuses();
		$total = 0;
		foreach ( $task_run_log_count_by_statuses as $status => $status_data ){
			$total += $status_data[ 'count' ];
		}

		$views = array();

		$all_status_url = remove_query_arg( 'status' );
		$all_status_class = ( $this->filter->has_status() ? '' : 'class="current"' );
		$views['all'] = '<a href="' . $all_status_url . '" ' . $all_status_class . '>'
		                . __( 'All', 'background_task_manager' ) . "({$total})"
		                . '</a>';

		foreach ( $task_run_log_count_by_statuses as $status => $status_data ){
			$status_url = add_query_arg( 'status', $status );

			if( $this->filter->has_status() && $this->filter->get_status() === $status ){
				$status_class = 'class="current"';
			}else{
				$status_class = '';
			}

			$views[ $status ] = "<a href='{$status_url}' {$status_class}>"
			                    . $status_data[ 'display_name' ] . "(" . $status_data[ 'count' ] . ")"
			                    . "</a>";
		}

		return $views;
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
			'task_id'                   => __( 'Task ID', 'background_task_manager' ),
			'callback_action'           => __( 'Callback Action', 'background_task_manager' ),
			'logs'                      => __( 'Logs', 'background_task_manager' ),
			'status'                    => __( 'Status', 'background_task_manager' ),
			'date_started'              => __( 'Date Started', 'background_task_manager' ),
			'date_finished'             => __( 'Date Finished', 'background_task_manager' )
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

	/**
	 * Get a list of sortable columns. The format is:
	 * 'internal-name' => 'orderby'
	 * or
	 * 'internal-name' => array( 'orderby', true )
	 *
	 * The second format will make the initial sorting order be descending
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'task_id'                   => array( 'task_id', true ),
			'callback_action'           => array( 'callback_action', false ),
			'status'                    => array( 'status', false ),
			'date_started'              => array( 'date_started', true ),
			'date_finished'             => array( 'date_finished', true ),
		);
		return $sortable_columns;
	}

	// endregion

	// region Column

	/**
	 * Show table checkboxes
	 *
	 * @param object $item
	 */
	public function column_cb($item) {
		echo sprintf('<input type="checkbox" name="' . static::BULK_ACTION_DELETE . '[]" value="%s" />', $item->get_id() );
	}

	/**
	 * Show task id
	 *
	 * @param BTM_Task_Run_Log_View $item
	 */
	public function column_task_id( BTM_Task_Run_Log_View $item ) {
		echo $item->get_task_id();
	}

	/**
	 * Show callback_action column
	 *
	 * @param BTM_Task_Run_Log_View $item
	 */
	public function column_callback_action( BTM_Task_Run_Log_View $item ) {
		echo $item->get_callback_action();
	}

	/**
	 * Show logs column
	 *
	 * @param BTM_Task_Run_Log_View $item
	 */
	public function column_logs( BTM_Task_Run_Log_View $item ) {
		$args = $item->get_logs();
		foreach ( $args as $key => $arg){
			if( is_array( $arg ) ){
				$arg = 'Array';
			}
			if( is_object( $arg ) ){
				$arg = 'Object';
			}
			echo '<p>'. $key .' => ' . $arg .'</p>';
		}
	}

	/**
	 * Show status column
	 *
	 * @param BTM_Task_Run_Log_View $item
	 */
	public function column_status( BTM_Task_Run_Log_View $item ) {
		echo $item->get_status();
	}

	/**
	 * Show date_created column
	 *
	 * @param BTM_Task_Run_Log_View $item
	 */
	public function column_date_started( BTM_Task_Run_Log_View $item ) {
		// todo: should be formatted by WP settings and applied user's time zone?
		$iso_date = date( 'Y-m-d H:i:s', $item->get_date_started_timestamp() );
		echo $iso_date;
	}

	/**
	 * Show date_created column
	 *
	 * @param BTM_Task_Run_Log_View $item
	 */
	public function column_date_finished( BTM_Task_Run_Log_View $item ) {
		// todo: should be formatted by WP settings and applied user's time zone?
		$iso_date = date( 'Y-m-d H:i:s', $item->get_date_finished_timestamp() );
		echo $iso_date;
	}

	// endregion

	// endregion
}