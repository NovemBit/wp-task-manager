<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Run_Log_View_Dao
 */
class BTM_Task_Run_Log_View_Dao{
	// region Singleton

	/**
	 * @var BTM_Task_Run_Log_View_Dao
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Run_Log_View_Dao
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
	 * Method to get all task run logs from db filtered
	 *
	 * @param BTM_Task_Run_Log_View_Filter $filter
	 *
	 * @return BTM_Task_Run_Log_View[]
	 */
	public function get_task_run_logs( BTM_Task_Run_Log_View_Filter $filter ){
		global $wpdb;

		$where = $this->generate_where_statement( $filter, 'run_logs', 'tasks' );

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
				`run_logs`.`id`, 
				`run_logs`.`task_id`,
				`run_logs`.`logs`,
				`run_logs`.`status`,
				`run_logs`.`date_started`,
				`run_logs`.`date_finished`,
				`tasks`.`callback_action`
			FROM `' . BTM_Task_Run_Log_Dao::get_instance()->get_table_name() . '` AS `run_logs`
			JOIN `' . BTM_Task_Dao::get_instance()->get_table_name() . '` AS `tasks`
				ON `run_logs`.`task_id` = `tasks`.`id`
			WHERE 1=1
				' . $where . '
			' . $order . '
			' . $limit . '
		';

		$task_run_log_view_objs = $wpdb->get_results( $query, OBJECT );
		if( empty( $task_run_log_view_objs ) ){
			return array();
		}

		$task_run_log_views = array();
		foreach ( $task_run_log_view_objs as $task_run_log_view_obj ){
			$task_run_log_views[] = $this->create_task_run_log_from_db_obj( $task_run_log_view_obj );
		}
		return $task_run_log_views;
	}

	/**
	 * @param BTM_Task_Run_Log_View_Filter $filter
	 *
	 * @return int
	 */
	public function get_task_run_logs_count( BTM_Task_Run_Log_View_Filter $filter ){
		global $wpdb;

		$where = $this->generate_where_statement( $filter, 'run_logs', 'tasks' );

		$query = '
			SELECT COUNT( * )
			FROM `' . BTM_Task_Run_Log_Dao::get_instance()->get_table_name() . '` AS `run_logs`
			JOIN `' . BTM_Task_Dao::get_instance()->get_table_name() . '` AS `tasks`
				ON `run_logs`.`task_id` = `tasks`.`id`
			WHERE 1=1
				' . $where . '
		';

		$total_count = $wpdb->get_var( $query );
		if( ! $total_count ){
			return 0;
		}

		return (int) $total_count;
	}

	/**
	 * @param BTM_Task_Run_Log_View_Filter $filter
	 * @param string $task_run_log_table_alias
	 * @param string $task_table_alias
	 *
	 * @return string
	 */
	protected function generate_where_statement( BTM_Task_Run_Log_View_Filter $filter, $task_run_log_table_alias, $task_table_alias ){
		global $wpdb;

		$where = '';

		if( $filter->has_task_id() ){
			$where .= $wpdb->prepare( ' 
				AND `' . $task_run_log_table_alias . '`.`task_id` = '. $filter->get_task_id() .'
			' );
		}

		if( $filter->has_search() ){
			$search = '%' . $wpdb->esc_like( $filter->get_search() ) . '%';
			$where .= $wpdb->prepare( '
				AND (
					`' . $task_run_log_table_alias . '`.`id` LIKE %s
					OR `' . $task_run_log_table_alias . '`.`task_id` LIKE %s
					OR `' . $task_run_log_table_alias . '`.`logs` LIKE %s
					OR `' . $task_run_log_table_alias . '`.`date_started` LIKE %s
					OR `' . $task_run_log_table_alias . '`.`date_finished` LIKE %s
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
				AND `' . $task_run_log_table_alias . '`.`status` = %s
			',
				$filter->get_status()
			);
		}

		return $where;
	}

	/**
	 * @return array<status_slug, <count, display_name>>
	 */
	public function get_task_run_log_count_by_statuses(){
		global $wpdb;

		$query = '
			SELECT `status`, COUNT( `id` ) AS `count`
			FROM `' . BTM_Task_Run_Log_Dao::get_instance()->get_table_name() . '`
			GROUP BY `status`
		';

		$status_count = $wpdb->get_results( $query, OBJECT_K );
		if( ! $status_count ){
			$status_count  = array();
		}

		if( empty( $status_count[ BTM_Task_Run_Status::STATUS_SUCCEEDED ] ) ){
			$succeeded = 0;
		}else{
			$succeeded = (int) $status_count[ BTM_Task_Run_Status::STATUS_SUCCEEDED ]->count;
		}
		if( empty( $status_count[ BTM_Task_Run_Status::STATUS_FAILED ] ) ){
			$failed = 0;
		}else{
			$failed = (int) $status_count[ BTM_Task_Run_Status::STATUS_FAILED ]->count;
		}

		$task_run_statuses = array();
		$task_run_statuses[ BTM_Task_Run_Status::STATUS_SUCCEEDED ] = array(
			'count'         => $succeeded,
			'display_name'  => ( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_SUCCEEDED ) )->get_display_name()
		);
		$task_run_statuses[ BTM_Task_Run_Status::STATUS_FAILED ] = array(
			'count'         => $failed,
			'display_name'  => ( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_FAILED ) )->get_display_name()
		);

		return $task_run_statuses;
	}

	// endregion

	/**
	 * @param stdClass $task_run_log_view_obj
	 *
	 * @return BTM_Task_Run_Log_View
	 */
	protected function create_task_run_log_from_db_obj( stdClass $task_run_log_view_obj ){
		return BTM_Task_Run_Log_View::create_from_db_obj( $task_run_log_view_obj );
	}
}