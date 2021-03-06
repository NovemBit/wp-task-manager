<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_View_Dao
 */
class BTM_Task_View_Dao{
	// region Singleton

	/**
	 * @var BTM_Task_View_Dao
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_View_Dao
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
	 * Method to get all tasks from db  filtered
	 *
	 * @param BTM_Task_View_Filter $filter
	 *
	 * @return BTM_Task_View[]
	 */
	public function get_tasks( BTM_Task_View_Filter $filter ){
		global $wpdb;

		$where = $this->generate_where_statement( $filter, 'tasks' );

		if( $filter->has_order_params() ){
			$order = ' ORDER BY `tasks`.`'. $filter->get_order_by() . '` ' . $filter->get_order();
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
			SELECT `matched_tasks_totals`.*, COUNT(`bulk_args_done`.`id`) AS `done`
			FROM (
				SELECT `matched_tasks`.*, COUNT(`bulk_args_total`.`id`) AS `total`
				FROM (
					SELECT
					  `tasks`.`id`,
					  `tasks`.`callback_action`,
					  `tasks`.`callback_arguments`,
					  `tasks`.`priority`,
					  `tasks`.`bulk_size`,
					  `tasks`.`status`,
					  `tasks`.`date_created`
					FROM `' . BTM_Task_Dao::get_instance()->get_table_name() . '` AS `tasks`
					WHERE 1=1
						' . $where . '
					' . $order . '
					' . $limit . '
				) AS `matched_tasks`
				LEFT JOIN `' . BTM_Task_Bulk_Argument_Dao::get_instance()->get_table_name() . '` AS `bulk_args_total`
					ON `bulk_args_total`.`task_id` = `matched_tasks`.`id`
				GROUP BY `matched_tasks`.`id`
			) AS `matched_tasks_totals`
			LEFT JOIN `' . BTM_Task_Bulk_Argument_Dao::get_instance()->get_table_name() . '` AS `bulk_args_done`
				ON `bulk_args_done`.`task_id` = `matched_tasks_totals`.`id`
				AND `bulk_args_done`.`status` != "' . BTM_Task_Run_Status::STATUS_REGISTERED . '"
			GROUP BY `matched_tasks_totals`.`id` DESC
		';

		$task_view_objs = $wpdb->get_results( $query, OBJECT );
		if( empty( $task_view_objs ) ){
			return array();
		}

		$task_views = array();
		foreach ( $task_view_objs as $task_view_obj ){
			$task_views[] = $this->create_task_from_db_obj( $task_view_obj );
		}
		return $task_views;
	}

	/**
	 * @param BTM_Task_View_Filter $filter
	 *
	 * @return int
	 */
	public function get_tasks_count( BTM_Task_View_Filter $filter ){
		global $wpdb;

		$where = $this->generate_where_statement( $filter, 'tasks' );

		$query = '
			SELECT COUNT( * )
			FROM `' . BTM_Task_Dao::get_instance()->get_table_name() . '` AS `tasks`
			WHERE 1=1 '
			. $where;

		$total_count = $wpdb->get_var( $query );
		if( ! $total_count ){
			return 0;
		}

		return (int) $total_count;
	}

	/**
	 * @param BTM_Task_View_Filter $filter
	 * @param string $task_table_alias
	 *
	 * @return string
	 */
	protected function generate_where_statement( BTM_Task_View_Filter $filter, $task_table_alias ){
		global $wpdb;

		$where = '';

		if( $filter->has_search() ){
			$search = '%' . $wpdb->esc_like( $filter->get_search() ) . '%';
			$where .= $wpdb->prepare( '
				AND (
					`' . $task_table_alias . '`.`id` LIKE %s
					OR `' . $task_table_alias . '`.`callback_arguments` LIKE %s
					OR `' . $task_table_alias . '`.`priority` LIKE %s
					OR `' . $task_table_alias . '`.`bulk_size` LIKE %s
				)
			',
				$search,
				$search,
				$search,
				$search
			);
		}

		if( $filter->has_status() ){
			$where .= $wpdb->prepare( '
				AND `' . $task_table_alias . '`.`status` = %s
			',
				$filter->get_status()
			);
		}

		if( $filter->has_callback() ){
			$where .= $wpdb->prepare( '
				AND `' . $task_table_alias . '`.`callback_action` = %s
			',
				$filter->get_callback()
			);
		}

		if( $filter->has_date_start() ){
			$where .= $wpdb->prepare( '
				AND `' . $task_table_alias . '`.`date_created` >= %s
			',
				$filter->get_date_start()
			);
		}

		if( $filter->has_date_end() ){
			$where .= $wpdb->prepare( '
				AND `' . $task_table_alias . '`.`date_created` <= %s
			',
				$filter->get_date_end()
			);
		}

		$where .= $wpdb->prepare( '
				AND `' . $task_table_alias . '`.`is_system` = %d
			',
			$filter->show_system()
		);

		return $where;
	}

	/**
	 * @return array<status_slug, <count, display_name>>
	 */
	public function get_task_count_by_statuses(){
		global $wpdb;

		$show_system = BTM_Plugin_Options::get_instance()->get_show_system();
		$where = '';
		if( $show_system === 'off' ){
			$where = 'WHERE is_system = 0';
		}

		$query = '
			SELECT `status`, COUNT( `id` ) AS `count`
			FROM `' . BTM_Task_Dao::get_instance()->get_table_name() . '`
			'. $where .'
			GROUP BY `status`
		';

		$status_count = $wpdb->get_results( $query, OBJECT_K );
		if( ! $status_count ){
			$status_count  = array();
		}

		$task_run_statuses = BTM_Task_Run_Status::get_statuses();

		foreach ( $task_run_statuses as $status => $display_name ){
			$count = 0;
			if( isset( $status_count[ $status ] ) ){
				$count = $status_count[ $status ]->count;
			}

			$task_run_statuses[ $status ] = array( 'count' => $count, 'display_name' => $display_name );
		}

		return $task_run_statuses;
	}

	/**
	 * Return Distinct callback actions
	 *
	 * @return array
	 */
	public function get_callback_actions(){
		global $wpdb;

		$query = '
			SELECT DISTINCT `callback_action`
			FROM `' . BTM_Task_Dao::get_instance()->get_table_name() . '`
		';

		$callback_actions = $wpdb->get_results( $query, OBJECT );

		if( empty( $callback_actions ) ){
			return array();
		}

		return $callback_actions;
	}

	// endregion

	/**
	 * @param stdClass $task_obj
	 *
	 * @return BTM_Task_View
	 */
	protected function create_task_from_db_obj( stdClass $task_obj ){
		return BTM_Task_View::create_from_db_obj( $task_obj );
	}
}