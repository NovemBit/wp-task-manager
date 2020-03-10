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
			'callback_action'       => $task->get_callback_action(),
			'callback_arguments'    => serialize( $task->get_callback_arguments() ),
			'priority'              => $task->get_priority(),
			'bulk_size'             => $task->get_bulk_size(),
			'status'                => $task->get_status()->get_value(),
			'date_created'          => date( 'Y-m-d H:i:s', $task->get_date_created_timestamp() ),
			'type'                  => BTM_Task_Type_Service::get_instance()->get_type_from_task( $task ),
			'is_system'             => $task->is_system()
		);
		$format = array( '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d' );
		$data['argument_hash'] = md5( $data['callback_arguments'] );
		$format[] = '%s';

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
	 * @param string $callback_action
	 *
	 * @return I_BTM_Task|false
	 */
	public function get_by_callback_action( $callback_action ){
		global $wpdb;

		$query = $wpdb->prepare('
			SELECT * 
			FROM `' . $this->get_table_name() . '`
			WHERE `callback_action` = %d
		', $callback_action);

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
					AND `argument_hash` = %s
					AND `priority` = %d
					AND `type` = %s
					AND ( `status` = %s OR `status` = %s OR `status` = %s )
		',
			$task->get_callback_action(),
			md5( serialize( $task->get_callback_arguments() ) ),
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

	/**
	 * @return array|bool
	 */
	public function get_running_tasks(){
		global $wpdb;

		$where = ' 1=1 ';
		$where .= $wpdb->prepare('
			AND `status` = %s AND `is_system` = 0
		',
			BTM_Task_Run_Status::STATUS_RUNNING
		);

		$query = '
			SELECT *
			FROM `' . $this->get_table_name() . '`
			WHERE ' . $where . '
		';

		$tasks_obj = $wpdb->get_results( $query, OBJECT );
		if( null === $tasks_obj ){
			return false;
		}

		$tasks = array();
		foreach ( $tasks_obj as $task_obj ){
			$tasks[] = $this->create_task_from_db_obj( $task_obj );
		}

		return $tasks;
	}

	/**
	 * @param int $hour
	 *
	 * @return array|bool
	 */
	public function get_last_tasks_by_hours( $hour ){
		global $wpdb;

		$query = $wpdb->get_results('
			SELECT * 
			FROM `' . $this->get_table_name() . '`
			WHERE `date_created` > DATE_SUB( "'.date( 'Y-m-d H:i:s').'", INTERVAL '. $hour .' HOUR) AND `is_system` = 0 
		', OBJECT );

		if( null === $query ){
			return false;
		}

		$tasks = array();
		foreach ( $query as $task_obj ){
			$tasks[] = $this->create_task_from_db_obj( $task_obj );
		}

		return $tasks;
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
			FROM `' . $this->get_table_name() . '`
		';

		$callback_actions = $wpdb->get_results( $query, OBJECT );

		if( empty( $callback_actions ) ){
			return array();
		}

		return $callback_actions;
	}

	/**
	 * Return Distinct callback actions
	 *
	 * @return array
	 */
	public function get_callback_actions_not_system(){
		global $wpdb;

		$query = '
			SELECT DISTINCT `callback_action`
			FROM `' . BTM_Task_Dao::get_instance()->get_table_name() . '`
			WHERE is_system = 0
		';

		$callback_actions = $wpdb->get_results( $query, OBJECT );

		if( empty( $callback_actions ) ){
			return array();
		}

		return $callback_actions;
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

		$callback_arguments = serialize( $task->get_callback_arguments() );
		$updated = $wpdb->update(
			$this->get_table_name(),
			array(
				'callback_action'       => $task->get_callback_action(),
				'callback_arguments'    => $callback_arguments,
				'priority'              => $task->get_priority(),
				'bulk_size'             => $task->get_bulk_size(),
				'status'                => $task->get_status()->get_value(),
				'date_created'          => date( 'Y-m-d H:i:s' , $task->get_date_created_timestamp() ),
				'last_modified'         => date( 'Y-m-d H:i:s' , time() ),
				'type'                  => BTM_Task_Type_Service::get_instance()->get_type_from_task( $task ),
				'argument_hash'         => md5( $callback_arguments ),
				'is_system'             => $task->is_system()
			),
			array(
				'id' => $task->get_id()
			),
			array( '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d' ),
			array( '%d' )
		);

		if( false === $updated ){
			return false;
		}

		return true;
	}

	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function change_last_modified( I_BTM_Task $task ){
		global $wpdb;

		$updated = $wpdb->update(
			$this->get_table_name(),
			array(
				'last_modified'          => date( 'Y-m-d H:i:s' , time() )
			),
			array(
				'id' => $task->get_id()
			),
			array( '%s' ),
			array( '%d' )
		);

		if( false === $updated ){
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

	/**
	 * This function should be update only registered and in progress tasks to paused tasks
	 *
	 * @param array $ids
	 *
	 * @return bool
	 */
	public function pause_tasks( array $ids ){
		global $wpdb;

		if( 0 === count( $ids ) ){
			return true;
		}

		$ids_in = '';
		foreach ( $ids as $id ){
			$ids_in .= $wpdb->prepare(', %d', $id);
		}

		$ids_in = ltrim( $ids_in, ', ' );

		$updated = $wpdb->query( '
			UPDATE `' . $this->get_table_name() . '`
			SET `status` =  "'.BTM_Task_Run_Status::STATUS_PAUSED.'"
			WHERE `id` IN ( ' . $ids_in . ' )
			AND ( 
					`status` = "'.BTM_Task_Run_Status::STATUS_REGISTERED.'" 
				OR 
					`status` = "'.BTM_Task_Run_Status::STATUS_IN_PROGRESS.'" 
				)
		' );

		if( $updated ){
			return true;
		}

		return false;
	}

	/**
	 * This function should be update only paused tasks to registered tasks
	 *
	 * @param array $ids
	 *
	 * @return bool
	 */
	public function resume_tasks( array $ids ){
		global $wpdb;

		if( 0 === count( $ids ) ){
			return true;
		}

		$ids_in = '';
		foreach ( $ids as $id ){
			$ids_in .= $wpdb->prepare(', %d', $id);
		}

		$ids_in = ltrim( $ids_in, ', ' );

		$updated = $wpdb->query( '
			UPDATE `' . $this->get_table_name() . '`
			SET `status` =  "'.BTM_Task_Run_Status::STATUS_REGISTERED.'"
			WHERE `id` IN ( ' . $ids_in . ' ) 
			AND `status` = "'.BTM_Task_Run_Status::STATUS_PAUSED.'"
		' );

		if( $updated ){
			return true;
		}

		return false;
	}

	// endregion

	// region DELETE

	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function delete( I_BTM_Task $task ){
		return $this->delete_by_id( $task->get_id() );
	}

	/**
	 * @param int $id   task id
	 *
	 * @return bool
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $id is not a positive int
	 */
	public function delete_by_id( $id ){
		global $wpdb;

		if( ! is_int( $id ) || 0 >= $id ){
			throw new InvalidArgumentException( 'Argument $id should be positive int. Input was: ' . $id );
		}

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
	 * @param array $ids
	 *
	 * @return bool
	 */
	public function delete_many_by_ids( array $ids ){
		global $wpdb;

		if( 0 === count( $ids ) ){
			return true;
		}

		$ids_in = '';
		foreach ( $ids as $id ){
			$ids_in .= $wpdb->prepare(', %d', $id);
		}

		$ids_in = ltrim( $ids_in, ', ' );

		$deleted = $wpdb->query( '
			DELETE FROM `' . $this->get_table_name() . '` 
			WHERE `id` IN ( ' . $ids_in . ' ) AND `status` != "'. BTM_Task_Run_Status::STATUS_RUNNING .'"
		' );

		return false !== $deleted;
	}

	/**
	 * @param int $interval_in_days
	 *
	 * @return bool
	 */
	public function delete_by_date_interval( $interval_in_days ){
		global $wpdb;

		if( ! is_int( $interval_in_days ) || 0 >= $interval_in_days ){
			throw new InvalidArgumentException( 'Argument $interval should be positive int. Input was: ' . $interval_in_days );
		}

		$interval = date( 'Y-m-d H:i:s', time() - $interval_in_days * 24 * 60 * 60 );

		$query = $wpdb->prepare('
			DELETE FROM `' . $this->get_table_name() . '`
			WHERE `date_created` < %s
		', $interval);

		$deleted = $wpdb->query( $query );

		if( false === $deleted ){
			return false;
		}

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