<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

final class BTM_Admin_Task_Page_Table extends BTM_Admin_Page_Table{
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
		throw new LogicException( 'uses parent page slug' );
	}

	protected function add_submenu_page(){
		$page_hook = add_submenu_page(
			$this->get_page_parent_slug(),
			__( 'Tasks', 'background_task_manager' ),
			__( 'Tasks', 'background_task_manager' ),
			'manage_options',
			$this->get_page_parent_slug(),
			array(
				$this,
				'on_hook_page_render_tasks'
			)
		);

		if( $page_hook ){
			add_action( 'load-' . $page_hook, array( $this, 'on_hook_page_load_process_bulk' ) );
		}else{
			add_action( 'admin_notices', function(){
				$class = 'notice notice-error';
				$message = __( 'Could not create admin page to show background tasks.', 'background_task_manager' );

				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			} );
		}
	}

	/**
	 * Show Task submenu page
	 *
	 * Callback for admin page render
	 */
	public function on_hook_page_render_tasks(){
		$this->prepare_items();

		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>
			<?php $this->views(); ?>
			<form id="tasks-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
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

			$deleted = BTM_Task_Dao::get_instance()->delete_many_by_ids( $to_delete );
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
	 * @var BTM_Task_View_Filter
	 */
	protected $filter;

	/**
	 * @return string
	 */
	protected function get_entity_singular_name(){
		return __( 'task', 'background_task_manager' );
	}
	/**
	 * @return string
	 */
	protected function get_entity_plural_name(){
		return __( 'tasks', 'background_task_manager' );
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
		$this->prepare_filter();

		$task_view_dao = BTM_Task_View_Dao::get_instance();
		$this->items = $task_view_dao->get_tasks( $this->filter );
		$total_items = $task_view_dao->get_tasks_count( $this->filter );

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
		$filter = new BTM_Task_View_Filter();
		if( ! empty( $_GET[ 'orderby' ] ) ){
			$filter->set_order_by( $_GET[ 'orderby' ] );
		}else{
			$filter->set_order_by( 'date_created' );
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

		if( ! empty( $_GET[ 'callback' ] ) ){
			$filter->set_callback( $_GET[ 'callback' ] );
		}

		if( ! empty( $_GET[ 'date_start' ] ) ){
			$filter->set_date_start( $_GET[ 'date_start' ] );
		}

		if( ! empty( $_GET[ 'date_end' ] ) ){
			$filter->set_date_end( $_GET[ 'date_end' ] );
		}

		if( ! empty( $_GET[ 'system' ] ) ){
			$filter->set_show_system( true );
		}else{
			$filter->set_show_system( false );
		}

		$filter->set_items_per_page( 20 );
		$filter->set_current_page( $this->get_pagenum() );

		$this->filter = $filter;
	}

	/**
	 * Extra controls to be displayed status filter
	 *
	 * @param string $which
	 */
	protected function extra_tablenav($which) {
		if ( $which == "top" ) {
			?>
			<label for="system"><?php _e( 'Show system tasks', 'background_task_manager' ); ?></label>
			<input type="checkbox" id="system" name="system" value="1" <?php checked( $this->filter->show_system() , 1 ); ?> />
			<select name="callback" id="callback-filter">
				<?php $callback_actions = BTM_Task_View_Dao::get_instance()->get_callback_actions(); ?>
				<option value=""><?php _e( 'Callback Actions', 'background_task_manager' ); ?></option>
				<?php foreach ( $callback_actions as $callback_action ) { ?>
					<option     value="<?php echo $callback_action->callback_action; ?>"
								<?php selected( $this->filter->get_callback(), $callback_action->callback_action ); ?> >
						<?php echo $callback_action->callback_action; ?>
					</option>
				<?php } ?>
			</select>
			<input type="date" id="jquery-datepicker-start" name="date_start" value="<?php echo $this->filter->get_date_start_short(); ?>" />
			<input type="date" id="jquery-datepicker-end" name="date_end" value="<?php echo $this->filter->get_date_end_short(); ?>" />
			<span class="trash" ><a id="btm-reset" href="#" ><?php _e( 'Reset', 'background_task_manager' ); ?></a></span>
			<?php
				submit_button( __( 'Apply', 'background_task_manager' ), 'action', 'btm-submit', false );
		}
	}

	protected function get_views() {
		$task_count_by_statuses = BTM_Task_View_Dao::get_instance()->get_task_count_by_statuses();
		$total = 0;
		foreach ( $task_count_by_statuses as $status => $status_data ){
			$total += $status_data[ 'count' ];
		}

		$views = array();

		$all_status_url = remove_query_arg( 'status' );
		$all_status_class = ( $this->filter->has_status() ? '' : 'class="current"' );
		$views['all'] = '<a href="' . $all_status_url . '" ' . $all_status_class . '>'
		                . __( 'All', 'background_task_manager' ) . "({$total})"
						. '</a>';

		foreach ( $task_count_by_statuses as $status => $status_data ){
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
			'callback_action'           => __( 'Callback Action', 'background_task_manager' ),
			'callback_arguments'        => __( 'Callback Arguments', 'background_task_manager' ),
			'priority'                  => __( 'Priority', 'background_task_manager' ),
			'bulk_size'                 => __( 'Bulk Size', 'background_task_manager' ),
			'status'                    => __( 'Status', 'background_task_manager' ),
			'date_created'              => __( 'Date Created', 'background_task_manager' )
		);
		return $columns;
	}

	/**
	 * Get a list of hidden columns.
	 *
	 * @param string|WP_Screen $screen The screen you want the hidden columns for
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
			'callback_action'           => array( 'callback_action', false ),
			'priority'                  => array( 'priority', false ),
			'bulk_size'                 => array( 'bulk_size', true ),
			'status'                    => array( 'status', true ),
			'date_created'              => array( 'date_created', true ),
		);
		return $sortable_columns;
	}

	// endregion

	// region Column

	/**
	 * Show table checkboxes
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		echo sprintf('<input type="checkbox" name="' . static::BULK_ACTION_DELETE . '[]" value="%s" />', $item->get_id() );
	}

	/**
	 * Show callback_action column
	 *
	 * @param BTM_Task_View $item
	 *
	 * @return string
	 */
	public function column_callback_action( BTM_Task_View $item ) {
		$actions = array();

		$url = admin_url() . 'admin.php';

		$item_id = $item->get_id();
		$actions[ 'edit' ] = sprintf(
			'<a href="?page=%s-task-view&action=%s&task_id=%d">' . __( 'Edit', 'background_task_manager' ) . '</a>',
			$_REQUEST['page'],
			'edit',
			$item_id
		);

		if ( $item->get_status() != BTM_Task_Run_Status::STATUS_REGISTERED ){
			$logs_url = add_query_arg('page', BTM_Admin_Task_Run_Log_Page_Table::get_page_slug(), $url );
			$actions['view_logs'] =
			'<a href="' . $logs_url . '">'
				. __( 'Logs', 'background_task_manager' ) .
			'</a>';
		}

		return sprintf(
			'%1$s %2$s',
			$item->get_callback_action(),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Show callback_arguments column
	 *
	 * @param BTM_Task_View $item
	 */
	public function column_callback_arguments( BTM_Task_View $item ) {
		$args = $item->get_callback_arguments();
		foreach ( $args as $key => $arg){
			if( is_array( $arg ) ){
				$arg = 'Array';
			}
			if( is_object( $arg ) ){
				$arg = 'Object';
			}
			echo '<p>'. $key .' => ' . $arg .'</p>';
		}
		?>
		<a id="btm-arg-data" href="#arg-data">View more</a>

		<div style="display:none">
			<div id="arg-data" data-selectable="true">
				<?php highlight_string("<?php\n\$args =\n" . var_export($args, true) . ";\n?>"); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Show priority column
	 *
	 * @param BTM_Task_View $item
	 */
	public function column_priority( BTM_Task_View $item ) {
		echo $item->get_priority();
	}

	/**
	 * Show bulk_size column
	 *
	 * @param BTM_Task_View $item
	 */
	public function column_bulk_size( BTM_Task_View $item ) {
		echo $item->get_bulk_size();
	}

	/**
	 * Show status column
	 *
	 * @param BTM_Task_View $item
	 */
	public function column_status( BTM_Task_View $item ) {
		if( 0 < $item->get_bulk_size() ){
			echo $item->get_status() . " ({$item->get_done_bulk_arguments()}/{$item->get_total_bulk_arguments()})";
		}else{
			echo $item->get_status();
		}
	}

	/**
	 * Show date_created column
	 *
	 * @param BTM_Task_View $item
	 */
	public function column_date_created( BTM_Task_View $item ) {
		// todo: should be formatted by WP settings and applied user's time zone?
		$iso_date = date( 'Y-m-d H:i:s', $item->get_date_created_timestamp() );
		echo $iso_date;
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

	// endregion
}