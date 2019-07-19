<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_View_Dao
 */
class BTM_Task_Bulk_Argument_View_Dao{
	// region Singleton

	/**
	 * @var BTM_Task_Bulk_Argument_View_Dao
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Bulk_Argument_View_Dao
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	// region READ

	/**
	 * Method to get all task bulk arguments from db filtered
	 *
	 * @param BTM_Task_Bulk_Argument_View_Filter $filter
	 *
	 * @return array
	 */
	public function get_task_bulk_arguments( BTM_Task_Bulk_Argument_View_Filter $filter ){
		global $wpdb;

		$where = $this->generate_where_statement( $filter, 'bulk_args', 'tasks' );

		if( $filter->has_order_params() ){
			$order = ' ORDER BY `'. $filter->get_order_by() . '` ' . $filter->get_order();
		}else{
			$order = '';
		}

		$offset = $filter->get_items_per_page() * ( $filter->get_current_page() - 1 );
		$limit = $wpdb->prepare('
			LIMIT %d, %d
		',
			$offset,
			$filter->get_items_per_page()
		);

		$query = '
			SELECT 
				`bulk_args`.`id`, 
				`bulk_args`.`task_id`, 
				`bulk_args`.`callback_arguments`,
				`bulk_args`.`priority`,
				`bulk_args`.`status`,
				`bulk_args`.`date_created`,
				`tasks`.`callback_action`
			FROM `' . BTM_Task_Bulk_Argument_Dao::get_instance()->get_table_name() . '` AS `bulk_args`
			JOIN `' . BTM_Task_Dao::get_instance()->get_table_name() . '` AS `tasks`
				ON `bulk_args`.`task_id` = `tasks`.`id`
			WHERE 1=1
				' . $where . '
			' . $order . '
			' . $limit . '
		';

		$task_bulk_argument_view_objs = $wpdb->get_results( $query, OBJECT );
		if( empty( $task_bulk_argument_view_objs ) ){
			return array();
		}

		$task_bulk_argument_views = array();
		foreach ( $task_bulk_argument_view_objs as $task_bulk_argument_view_obj ){
			$task_bulk_argument_views[] = $this->create_task_bulk_argument_from_db_obj( $task_bulk_argument_view_obj );
		}
		return $task_bulk_argument_views;
	}

	/**
	 * @param BTM_Task_Bulk_Argument_View_Filter $filter
	 *
	 * @return int
	 */
	public function get_task_bulk_arguments_count( BTM_Task_Bulk_Argument_View_Filter $filter ){
		global $wpdb;

		$where = $this->generate_where_statement( $filter, 'bulk_args', 'tasks' );

		$query = '
			SELECT COUNT( * )
			FROM `' . BTM_Task_Bulk_Argument_Dao::get_instance()->get_table_name() . '` AS `bulk_args`
			JOIN `' . BTM_Task_Dao::get_instance()->get_table_name() . '` AS `tasks`
				ON `bulk_args`.`task_id` = `tasks`.`id`
			WHERE 1=1
				' . $where . '
		';

		$total_count = $wpdb->get_var( $query );
		if( ! $total_count ){
			return 0;
		}

		return (int) $total_count;
	}

	protected function generate_where_statement( BTM_Task_Bulk_Argument_View_Filter $filter, $task_bulk_args_table_alias, $task_table_alias ){
		global $wpdb;

		$where = '';

		if( $filter->has_search() ){
			$search = '%' . $wpdb->esc_like( $filter->get_search() ) . '%';
			$where .= $wpdb->prepare( '
				AND (
					`' . $task_bulk_args_table_alias . '`.`id` LIKE %s
					OR `' . $task_bulk_args_table_alias . '`.`task_id` LIKE %s
					OR `' . $task_bulk_args_table_alias . '`.`callback_arguments` LIKE %s
					OR `' . $task_bulk_args_table_alias . '`.`priority` LIKE %s
					OR `' . $task_bulk_args_table_alias . '`.`date_created` LIKE %s
					OR `' . $task_table_alias . '`.`callback_action` LIKE %s
				)
			',
				$search,
				$search,
				$search,
				$search,
				$search,
				$search
			);
		}

		if( $filter->has_status() ){
			$where .= $wpdb->prepare('
				AND `' . $task_bulk_args_table_alias . '`.`status` = %s
			',
				$filter->get_status()
			);
		}

		return $where;
	}

	/**
	 * @return array<status_slug, <count, display_name>>
	 */
	public function get_task_bulk_argument_count_by_statuses(){
		global $wpdb;

		$query = '
			SELECT `status`, COUNT( `id` ) AS `count`
			FROM `' . BTM_Task_Bulk_Argument_Dao::get_instance()->get_table_name() . '`
			GROUP BY `status`
		';

		$status_count = $wpdb->get_results( $query, OBJECT_K );
		if( ! $status_count ){
			$status_count  = array();
		}

		$task_run_statuses = BTM_Task_Run_Status::get_statuses();
		unset( $task_run_statuses[ BTM_Task_Run_Status::STATUS_IN_PROGRESS ] );
		unset( $task_run_statuses[ BTM_Task_Run_Status::STATUS_PAUSED ] );

		foreach ( $task_run_statuses as $status => $display_name ){
			$count = 0;
			if( isset( $status_count[ $status ] ) ){
				$count = $status_count[ $status ]->count;
			}

			$task_run_statuses[ $status ] = array( 'count' => $count, 'display_name' => $display_name );
		}

		return $task_run_statuses;
	}

	// endregion

	/**
	 * @param stdClass $task_bulk_argument_view_obj
	 *
	 * @return BTM_Task_Bulk_Argument_View
	 */
	protected function create_task_bulk_argument_from_db_obj( stdClass $task_bulk_argument_view_obj ){
		return BTM_Task_Bulk_Argument_View::create_from_db_obj( $task_bulk_argument_view_obj );
	}
}