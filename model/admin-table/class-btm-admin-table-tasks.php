<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class BTM_Admin_Table_Tasks
 */
class BTM_Admin_Table_Tasks extends WP_List_Table{

	/**
	 * BTM_Admin_Table_Tasks constructor.
	 */
	public function __construct() {
		parent::__construct( array(
								'singular'  => 'task',     //singular name of the listed records
								'plural'    => 'tasks',    //plural name of the listed records
								'ajax'      => false        //does this table support ajax?
							)
				);
	}

	/**
	 * Get table data from db
	 *
	 * @return array|bool
	 */
	public function get_table_data_from_db() {
		$orderby = '';
		$order = '';
		$search = '';
		$status = '';
		$callback = '';
		$date_start = '';
		$date_end = '';
		if( isset( $_GET[ 'orderby' ] ) ){
			$orderby = $_GET[ 'orderby' ];
		}
		if( isset( $_GET[ 'order' ] ) ){
			$order = $_GET['order'];
		}
		if( isset( $_GET[ 's' ] ) ){
			$search = $_GET[ 's' ];
		}
		if( isset( $_GET[ 'status' ] ) ){
			$status = $_GET[ 'status' ];
		}
		if( isset( $_GET[ 'callback' ] ) ){
			$callback = $_GET[ 'callback' ];
		}
		if( isset( $_GET[ 'entry_date' ] ) ){
			$date_start = $_GET[ 'entry_date' ];
		}
		if( isset( $_GET[ 'end_date' ] ) ){
			$date_end = $_GET[ 'end_date' ];
		}
		$dao = BTM_Task_Dao::get_instance();
		$tasks = $dao->get_tasks(   $orderby,
									$order,
									$search,
									$status,
									$callback,
									$date_start,
									$date_end
								);
		if( $tasks === false ){
			return array();
		}

		return $tasks;
	}

	public function get_callback_actions(){

		$dao = BTM_Task_Dao::get_instance();
		$callback_actions = $dao->get_callback_actions();

		return $callback_actions;

	}

	/**
	 * Table data sliced for pagination
	 * @var array
	 */
	protected $found_data = [];

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @uses WP_List_Table::set_pagination_args()
	 *
	 */
	public function prepare_items() {
		$db_data = $this->get_table_data_from_db();
		$per_page = 20;
		$current_page = $this->get_pagenum();
		$total_items = count( $db_data );
		$this->found_data = array_slice( $db_data, ( ( $current_page-1 ) * $per_page ), $per_page );
		$this->set_pagination_args(
			array(
			'total_items' => $total_items,
			'per_page'    => $per_page
			)
		);
		$this->items = $this->found_data;
		$this->process_bulk_action();
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'                        => '<input type="checkbox" />',
			'id'                        => 'ID',
			'callback_action'           => 'Callback Action',
			'callback_arguments'        => 'Callback Arguments',
			'priority'                  => 'Priority',
			'bulk_size'                 => 'Bulk Size',
			'status'                    => 'Status',
			'date_created'              => 'Date Created',
			'view_logs'                 => 'Logs'
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
		return array();
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
			'id'                        => array( 'id', false ),
			'priority'                  => array( 'priority', false ),
			'bulk_size'                 => array( 'bulk_size', false ),
			'status'                    => array( 'status', false ),
			'date_created'              => array( 'date_created', false ),
			'callback_action'           => array( 'callback_action', false )
		);
		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 * * @return array */
	public function get_bulk_actions() {
		$actions = ['bulk-delete' => 'Delete'];
		return $actions;
	}

	/**
	 * Process bulk actions
	 */
	public function process_bulk_action() {
		// If the delete bulk action is triggered
		if ( ( isset( $_GET['action'] ) && 'bulk-delete' === $_GET['action'] ) || ( isset( $_GET['action2'] ) && 'bulk-delete' === $_GET['action2'] )
		) {

			$delete_ids = esc_sql( $_GET['bulk-delete'] );
			// loop over the array of record IDs and delete them
			foreach ( $delete_ids as $id ) {
				self::delete_records( $id );
			}

			if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
				wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'action2', 'status-submit', 'bulk-delete', ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
				exit;
			}
		}
		else{
			if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
				wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action2', 'status-submit',  ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
				exit;
			}
		}
	}

	/**
	 * Delete a task record.
	 * * @param int $id  ID
	 */
	public static function delete_records($id) {
		global $wpdb;
		$wpdb->delete("btm_tasks", ['id' => $id], ['%d']);
	}

	/**
	 * Extra controls to be displayed status filter
	 *
	 * @param string $which
	 */
	protected function extra_tablenav($which) {
		if ( $which == "top" ) {
			?>
			<select name="callback" id="callback-filter">
				<?php $callback_actions = $this->get_callback_actions(); ?>
				<option value="">Callback Actions</option>
				<?php foreach ( $callback_actions as $callback_action ) {
					if( isset( $_GET[ 'callback' ] ) && $_GET[ 'callback' ] == $callback_action->callback_action ){
						?><option selected="selected" value="<?php echo $callback_action->callback_action; ?>"><?php echo $callback_action->callback_action; ?></option><?php
					}else {
						?><option value="<?php echo $callback_action->callback_action; ?>"><?php echo $callback_action->callback_action; ?></option><?php
					}
				} ?>
			</select>
				<?php
				$entry = '';
				$end = '';
				if( isset( $_GET[ 'entry_date' ] ) ) {
					$entry = $_GET[ 'entry_date' ];
					$end = $_GET[ 'end_date' ];
				}?>
					<input type="date" id="jquery-datepicker-entry" name="entry_date" value="<?php echo $entry; ?>" />
					<input type="date" id="jquery-datepicker-end" name="end_date" value="<?php echo $end; ?>" />
					<span class="trash" ><a id="unset-date" href="#" >Reset</a></span>
				<?php submit_button( 'Apply', 'action', 'date-submit', false ); ?>
			<?php
		}
	}

	protected function get_views() {
		$task_run_statuses = BTM_Task_Run_Status::get_statuses();
		$views = array();
		$current = ( !empty($_REQUEST['status']) ? $_REQUEST['status'] : 'all');

		//All link
		$class = ($current == 'all' ? ' class="current"' :'');
		$all_url = remove_query_arg('status');
		$views['all'] = "<a href='{$all_url }' {$class} >All</a>";

		foreach ( $task_run_statuses as $status => $display_name ){
			$foo_url = add_query_arg( 'status',$status );
			$class = ( $current == $status ? ' class="current"' :'' );
			$views[ $status ] = "<a href='{$foo_url}' {$class} >{$display_name}</a>";
		}

		return $views;
	}

	//region Columns

	/**
	 * Show table checkboxes
	 *
	 * @param object $item
	 *
	 * @return string|void
	 */
	public function column_cb($item) {
		return sprintf('<input type="checkbox" name="bulk-delete[]" value="%s" />', $item->get_id() );
	}

	/**
	 * Show id column
	 *
	 * @param I_BTM_Task $item
	 */
	public function column_id( I_BTM_Task $item ) {
		echo $item->get_id();
	}

	/**
	 * Show callback_action column
	 *
	 * @param I_BTM_Task $item
	 */
	public function column_callback_action( I_BTM_Task $item ) {
		echo $item->get_callback_action();
	}

	/**
	 * Show callback_arguments column
	 *
	 * @param I_BTM_Task $item
	 */
	public function column_callback_arguments( I_BTM_Task $item ) {
		$args = $item->get_callback_arguments();
		foreach ( $args as $key => $arg){
			echo '<p>'. $key .' => ' . $arg .'</p>';
		}
		?>
		<a id="btm-arg-data" href="#arg-data">View log</a>

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
	 * @param I_BTM_Task $item
	 */
	public function column_priority( I_BTM_Task $item ) {
		echo $item->get_priority();
	}

	/**
	 * Show bulk_size column
	 *
	 * @param I_BTM_Task $item
	 */
	public function column_bulk_size( I_BTM_Task $item ) {
		echo $item->get_bulk_size();
	}

	/**
	 * Show status column
	 *
	 * @param I_BTM_Task $item
	 */
	public function column_status( I_BTM_Task $item ) {
		echo $item->get_status();
	}

	/**
	 * Show date_created column
	 *
	 * @param I_BTM_Task $item
	 */
	public function column_date_created( I_BTM_Task $item ) {
		$iso_date = date( 'Y-m-d H:i:s', $item->get_date_created_timestamp() );
		echo $iso_date;
	}

	/**
	 * Show logs column
	 *
	 * @param I_BTM_Task $item
	 */
	public function column_view_logs( I_BTM_Task $item ) {
		$item->get_id();
		$dao = BTM_Task_Run_Log_Dao::get_instance();
		$log = $dao->get_by_id( $item->get_id() );
		?>
		<a id="btm-log-data" href="#log-data">View log</a>

		<div style="display:none">
			<div id="log-data" data-selectable="true">
				<?php highlight_string("<?php\n\$log =\n" . var_export($log, true) . ";\n?>"); ?>
			</div>
		</div>
	<?php
	}

	// endregion

}