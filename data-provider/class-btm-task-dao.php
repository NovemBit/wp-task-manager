<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Dao
 */
class BTM_Task_Dao{
	// region Singleton

	/**
	 * @var BTM_Task_Dao
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Dao
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

	/**
	 * @return string
	 */
	public function get_table_name(){
		return BTM_Plugin_Options::get_instance()->get_db_table_prefix() . 'tasks';
	}

	// region CREATE

	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function create( I_BTM_Task $task ){
		global $wpdb;

		if( empty( $task->get_status() ) ){
			$status = new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_REGISTERED );
			$task->set_status( $status );
		}
		if( empty( $task->get_date_created_timestamp() ) ){
			$task->set_date_created_timestamp( time() );
		}

		$data = array(
			'callback_action' => $task->get_callback_action(),
			'callback_arguments' => serialize( $task->get_callback_arguments() ),
			'priority' => $task->get_priority(),
			'bulk_size' => $task->get_bulk_size(),
			'status' => $task->get_status()->get_value(),
			'date_created' => date( 'Y-m-d H:i:s', $task->get_date_created_timestamp() ),
			'type' => BTM_Task_Type_Service::get_instance()->get_type_from_task( $task )
		);
		$format = array( '%s', '%s', '%d', '%d', '%s', '%s', '%s' );

		if( 0 < $task->get_id() ){
			$data['id'] = $task->get_id();
			$format[] = '%d';
		}

		$inserted = $wpdb->insert(
			$this->get_table_name(),
			$data,
			$format
		);

		if( false === $inserted ){
			return false;
		}

		if( empty( $task->get_id() ) ){
			$task->set_id( $wpdb->insert_id );
		}

		return true;
	}

	// endregion

	// region READ

	/**
	 * @param int $id
	 *
	 * @return I_BTM_Task|false
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $id is not a positive int
	 */
	public function get_by_id( $id ){
		global $wpdb;

		if( ! is_int( $id ) || 0 >= $id ){
			throw new InvalidArgumentException( 'Argument $id should be positive int. Input was: ' . $id );
		}

		$query = $wpdb->prepare('
			SELECT * 
			FROM `' . $this->get_table_name() . '`
			WHERE `id` = %d
		', $id);

		$task_obj = $wpdb->get_row( $query, OBJECT );
		if( null === $task_obj ){
			return false;
		}

		return $this->create_task_from_db_obj( $task_obj );
	}

	/**
	 * @param I_BTM_Task $task
	 *
	 * @return I_BTM_Task|false
	 */
	public function get_existing_task( I_BTM_Task $task ){
		global $wpdb;

		$query = $wpdb->prepare('
			SELECT *
			FROM `' . $this->get_table_name() . '`
			WHERE `callback_action` = %s
					AND `callback_arguments` = %s
					AND `priority` = %d
					AND `type` = %s
					AND ( `status` = %s OR `status` = %s OR `status` = %s )
		',
			$task->get_callback_action(),
			serialize( $task->get_callback_arguments() ),
			$task->get_priority(),
			BTM_Task_Type_Service::get_instance()->get_type_from_task( $task ),
			BTM_Task_Run_Status::STATUS_IN_PROGRESS,
			BTM_Task_Run_Status::STATUS_REGISTERED,
			BTM_Task_Run_Status::STATUS_PAUSED
		);

		$task_obj = $wpdb->get_row( $query, OBJECT );
		if( null === $task_obj ){
			return false;
		}

		return $this->create_task_from_db_obj( $task_obj );
	}

	/**
	 * @return I_BTM_Task|false
	 */
	public function get_next_task_to_run(){
		global $wpdb;

		$where = ' 1=1 ';
		$where .= $wpdb->prepare('
			AND ( `status` = %s OR `status` = %s )
		',
			BTM_Task_Run_Status::STATUS_REGISTERED,
			BTM_Task_Run_Status::STATUS_IN_PROGRESS
		);

		$query = '
			SELECT *
			FROM `' . $this->get_table_name() . '`
			WHERE ' . $where . '
			ORDER BY `priority` ASC, `date_created` ASC
		';

		$task_obj = $wpdb->get_row( $query, OBJECT );
		if( null === $task_obj ){
			return false;
		}

		return $this->create_task_from_db_obj( $task_obj );
	}

	// endregion

	// region UPDATE

	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function update( I_BTM_Task $task ){
		global $wpdb;

		$updated = $wpdb->update(
			$this->get_table_name(),
			array(
				'callback_action' => $task->get_callback_action(),
				'callback_arguments' => serialize( $task->get_callback_arguments() ),
				'priority' => $task->get_priority(),
				'bulk_size' => $task->get_bulk_size(),
				'status' => $task->get_status()->get_value(),
				'date_created' => date( 'Y-m-d H:i:s' , $task->get_date_created_timestamp() ),
				'type' => BTM_Task_Type_Service::get_instance()->get_type_from_task( $task )
			),
			array(
				'id' => $task->get_id()
			),
			array( '%s', '%s', '%d', '%d', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if( false === $updated || 0 === $updated ){
			return false;
		}

		return true;
	}

	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function mark_as_running( I_BTM_Task $task ){
		$task->set_status( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_RUNNING ) );
		return $this->update( $task );
	}
	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function mark_as_succeeded( I_BTM_Task $task ){
		$task->set_status( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_SUCCEEDED ) );
		return $this->update( $task );
	}
	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function mark_as_failed( I_BTM_Task $task ){
		$task->set_status( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_FAILED ) );
		return $this->update( $task );
	}
	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function mark_as_in_progress( I_BTM_Task $task ){
		$task->set_status( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_IN_PROGRESS ) );
		return $this->update( $task );
	}

	// endregion

	// region DELETE

	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function delete( I_BTM_Task $task ){
		if( 0 < $task->get_bulk_size() ){
			return $this->delete_bulk_by_id( $task->get_id() );
		}else{
			return $this->delete_simple_by_id( $task->get_id() );
		}
	}

	/**
	 * @param int $id   task id
	 *
	 * @return bool
	 */
	public function delete_simple_by_id( $id ){
		global $wpdb;

		$deleted = $wpdb->delete(
			$this->get_table_name(),
			array( 'id' => $id ),
			array( '%d' )
		);

		if( false === $deleted || 0 === $deleted ){
			return false;
		}

		return true;
	}

	/**
	 * @param int $id   task id
	 *
	 * @return bool
	 */
	public function delete_bulk_by_id( $id ){
		global $wpdb;
		$db_transaction = BTM_DB_Transaction::get_instance();

		$db_transaction->start();

		$deleted = $wpdb->delete(
			$this->get_table_name(),
			array( 'id' => $id ),
			array( '%d' )
		);

		if( false === $deleted || 0 === $deleted ){
			$db_transaction->rollback();
			return false;
		}

		$deleted = BTM_Task_Bulk_Argument_Dao::get_instance()->delete_by_task_id( $id );
		if( true !== $deleted ){
			$db_transaction->rollback();
			return false;
		}

		$db_transaction->commit();
		return true;
	}

	// endregion

	/**
	 * @param stdClass $task_obj
	 *
	 * @return I_BTM_Task
	 */
	protected function create_task_from_db_obj( stdClass $task_obj ){
		$class_name = BTM_Task_Type_Service::get_instance()->get_class_from_type( $task_obj->type );

		/** @var I_BTM_Task $class_name */
		return $class_name::create_from_db_obj( $task_obj );
	}
}