<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Single_View_Dao
 */
class BTM_Task_Single_View_Dao{
	// region Singleton

	/**
	 * @var BTM_Task_Single_View_Dao
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Single_View_Dao
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

	public function get_task( $task_id ){
		global $wpdb;

		$query = '
			SELECT *
			FROM `btm_tasks`
			WHERE `id` = '. $task_id .'
			';

		$task_data = $wpdb->get_row( $query, OBJECT );

		if( empty( $task_data ) ){
			return false;
		}

		return $this->create_task_from_db_obj( $task_data );

	}

	// endregion

	// region UPDATE

	/**
	 * @param int $task_id
	 * @param int $priority
	 *
	 * @return bool
	 */
	public function update_task_priority( $task_id, $priority ){
		global $wpdb;

		$updated = $wpdb->update(
			'btm_tasks',
			array(
				'priority' => $priority,
			),
			array(
				'id' => $task_id
			),
			array( '%d' ),
			array( '%d' )
		);

		if( false === $updated || 0 === $updated ){
			return false;
		}

		return true;
	}

	/**
	 * @param int $task_id
	 * @param int $task_bulk_size
	 *
	 * @return bool
	 */
	public function update_task_bulk_size( $task_id, $task_bulk_size ){
		global $wpdb;

		$updated = $wpdb->update(
			'btm_tasks',
			array(
				'bulk_size' => $task_bulk_size,
			),
			array(
				'id' => $task_id
			),
			array( '%d' ),
			array( '%d' )
		);

		if( false === $updated || 0 === $updated ){
			return false;
		}

		return true;
	}

	//endregion

	/**
	 * @param stdClass $task_obj
	 *
	 * @return BTM_Task_Single_View
	 */
	protected function create_task_from_db_obj( stdClass $task_obj ){
		return BTM_Task_Single_View::create_from_db_obj( $task_obj );
	}
}