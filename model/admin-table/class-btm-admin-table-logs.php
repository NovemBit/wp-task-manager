<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class BTM_Admin_Table_Logs
 */
class BTM_Admin_Table_Logs extends WP_List_Table{

	/**
	 * BTM_Admin_Table_Logs constructor.
	 */
	public function __construct() {
		parent::__construct( array(
								'singular'  => 'log',     //singular name of the listed records
								'plural'    => 'logs',    //plural name of the listed records
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
		if( isset( $_GET[ 'orderby' ] ) ){
			$orderby = $_GET[ 'orderby' ];
		}
		if( isset( $_GET[ 'order' ] ) ){
			$order = $_GET['order'];
		}
		if( isset( $_GET[ 's' ] ) ){
			$search = $_GET[ 's' ];
		}
		$dao = BTM_Task_Run_Log_Dao::get_instance();
		$logs = $dao->get_logs( $orderby, $order, $search );
		if( $logs === false ){
			return array();
		}

		return $logs;
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
			'cb' => '<input type="checkbox" />',
			'id'                => 'ID',
			'task_id'           => 'Task ID',
			'session_id'        => 'Session ID',
			'logs'              => 'Logs',
			'date_started'      => 'Date started',
			'date_finished'     => 'Date finished',
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
			'id'               => array( 'id', false ),
			'task_id'          => array( 'task_id', false ),
			'session_id'       => array( 'session_id', false ),
			'logs'             => array( 'logs', false ),
			'date_started'     => array( 'date_started', false ),
			'date_finished'    => array( 'date_finished', false )
		);
		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
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
	}

	/**
	 * Delete a log record.
	 *
	 * @param int $id  ID
	 */
	public static function delete_records($id) {
		global $wpdb;
		$wpdb->delete("btm_task_run_logs", ['id' => $id], ['%d']);
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
	 * @param BTM_Task_Run_Log $item
	 */
	public function column_id( BTM_Task_Run_Log $item ) {
		echo $item->get_id();
	}

	/**
	 * Show task_id column
	 *
	 * @param BTM_Task_Run_Log $item
	 */
	public function column_task_id( BTM_Task_Run_Log $item ) {
		echo $item->get_task_id();
	}

	/**
	 * Show session_id column
	 *
	 * @param BTM_Task_Run_Log $item
	 */
	public function column_session_id( BTM_Task_Run_Log $item ) {
		echo $item->get_session_id();
	}

	/**
	 * Show logs column
	 *
	 * @param BTM_Task_Run_Log $item
	 */
	public function column_logs( BTM_Task_Run_Log $item ) {
		$logs = $item->get_logs();

		if( count( $logs ) < 2 ){
			foreach ( $logs as $key => $value ){
				highlight_string( $value );
			}
		}else {
			?>
			<a id="btm-log-data" href="#log-data">View log</a>

			<div style="display:none">
				<div id="log-data" data-selectable="true">
					<?php highlight_string( "<?php\n\$log =\n" . var_export( $logs, true ) . ";\n?>" ); ?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Show date_started column
	 *
	 * @param BTM_Task_Run_Log $item
	 */
	public function column_date_started( BTM_Task_Run_Log $item ) {
		$iso_date = date( 'Y-m-d H:i:s', $item->get_date_started_timestamp() );
		echo $iso_date;
	}

	/**
	 * Show date_finished column
	 *
	 * @param BTM_Task_Run_Log $item
	 */
	public function column_date_finished( BTM_Task_Run_Log $item ) {
		$iso_date = date( 'Y-m-d H:i:s', $item->get_date_finished_timestamp() );
		echo $iso_date;
	}


	// endregion

}