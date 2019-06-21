<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Bulk_Argument_Dao
 */
class BTM_Task_Bulk_Argument_Dao{
	// region Singleton

	/**
	 * @var BTM_Task_Bulk_Argument_Dao
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Bulk_Argument_Dao
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
		return BTM_Plugin_Options::get_instance()->get_db_table_prefix() . 'task_bulk_arguments';
	}

	// region CREATE

	/**
	 * @param BTM_Task_Bulk_Argument $task_bulk_argument
	 *
	 * @return bool
	 */
	public function create( BTM_Task_Bulk_Argument $task_bulk_argument ){
		global $wpdb;

		if( empty( $task_bulk_argument->get_status() ) ){
			$status = new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_REGISTERED );
			$task_bulk_argument->set_status( $status );
		}
		if( empty( $task_bulk_argument->get_date_created_timestamp() ) ){
			$task_bulk_argument->set_date_created_timestamp( time() );
		}

		$data = array(
			'task_id' => $task_bulk_argument->get_task_id(),
			'callback_arguments' => serialize( $task_bulk_argument->get_callback_arguments() ),
			'priority' => $task_bulk_argument->get_priority(),
			'status' => $task_bulk_argument->get_status()->get_value(),
			'date_created' => date( 'Y-m-d H:i:s', $task_bulk_argument->get_date_created_timestamp() )
		);
		$format = array( '%d', '%s', '%d', '%s', '%s' );

		if( 0 < $task_bulk_argument->get_id() ){
			$data['id'] = $task_bulk_argument->get_id();
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

		if( empty( $task_bulk_argument->get_id() ) ){
			$task_bulk_argument->set_id( $wpdb->insert_id );
		}

		return true;
	}

	// endregion

	// region READ

	/**
	 * Function to get all bulk tasks from db
	 *
	 * @param string $orderby to order by column
	 * @param string $order to order by ASC or DESC
	 * @param string $search to search in table some value
	 * @param string $status to get table data by status
	 *
	 * @return array|bool
	 */
	public function get_bulk_tasks( $orderby = '', $order = '', $search = '', $status = '' ){
		global $wpdb;

		$query = '
			SELECT * 
			FROM `' . $this->get_table_name() . '`
		';
		if( $status !== '' && $search == '' ){
			$query.= ' WHERE status = "'. $status .'" ';
		}

		if( $search !== '' ){
			$query.= ' WHERE
					id LIKE "%'. $search .'%" OR
					task_id LIKE "%'. $search .'%" OR
					callback_arguments LIKE "%'. $search .'%" OR
					priority LIKE "%'. $search .'%" OR
					status LIKE "%'. $search .'%" OR
					date_created LIKE "%'. $search .'%"
			';
			if( $status !== '' ){
				$query.= ' AND status = "'. $status .'" ';
			}
		}

		if( $orderby !== '' ){
			$query.= 'ORDER BY '. $orderby;
			if( $order !== '' ){
				$query.= ' '.$order;
			}
		}
		$tasks = $wpdb->get_results( $query, 'OBJECT' );

		if( empty( $tasks ) ){
			return false;
		}

		$tasks_arr = [];
		foreach ( $tasks as $task){
			if( !empty( $task ) ){
				$tasks_arr[] = $this->create_task_from_db_obj( $task );
			}
		}
		return $tasks_arr;
	}

	/**
	 * @param int $id
	 *
	 * @return BTM_Task_Bulk_Argument|false
	 */
	public function get_by_id( $id ){
		global $wpdb;

		$query = $wpdb->prepare('
			SELECT * 
			FROM `' . $this->get_table_name() . '`
			WHERE `id` = %d
		', $id );

		$task_bulk_argument_obj = $wpdb->get_row( $query, OBJECT );
		if( null === $task_bulk_argument_obj ){
			return false;
		}

		return $this->create_task_from_db_obj( $task_bulk_argument_obj );
	}

	/**
	 * @param int $task_id
	 * @param int $offset
	 * @param int $limit
	 *
	 * @return BTM_Task_Bulk_Argument[]
	 */
	public function get_by_task_id( $task_id, $offset = 0, $limit = 100 ){
		global $wpdb;

		$query = $wpdb->prepare('
			SELECT * 
			FROM `' . $this->get_table_name() . '`
			WHERE `task_id` = %d
			LIMIT %d, %d
		',
			$task_id,
			$offset,
			$limit
		);

		$task_bulk_argument_objs = $wpdb->get_results( $query, OBJECT );
		if( ! $task_bulk_argument_objs ){
			$task_bulk_argument_objs = array();
		}

		$task_bulk_arguments = array();
		foreach( $task_bulk_argument_objs as $task_bulk_argument_obj ){
			$task_bulk_arguments[] = $this->create_task_from_db_obj( $task_bulk_argument_obj );
		}

		return $task_bulk_arguments;
	}

	// endregion

	// region UPDATE

	/**
	 * @param BTM_Task_Bulk_Argument $task_bulk_argument
	 *
	 * @return bool
	 */
	public function update( BTM_Task_Bulk_Argument $task_bulk_argument ){
		global $wpdb;

		$updated = $wpdb->update(
			$this->get_table_name(),
			array(
				'task_id' => $task_bulk_argument->get_task_id(),
				'callback_arguments' => serialize( $task_bulk_argument->get_callback_arguments() ),
				'priority' => $task_bulk_argument->get_priority(),
				'status' => $task_bulk_argument->get_status()->get_value(),
				'date_created' => date( 'Y-m-d H:i:s' , $task_bulk_argument->get_date_created_timestamp() )
			),
			array(
				'id' => $task_bulk_argument->get_id()
			),
			array( '%d', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		if( false === $updated || 0 === $updated ){
			return false;
		}

		return true;
	}

	/**
	 * @param BTM_Task_Bulk_Argument $task_bulk_argument
	 *
	 * @return bool
	 */
	public function mark_as_running( BTM_Task_Bulk_Argument $task_bulk_argument ){
		$task_bulk_argument->set_status( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_RUNNING ) );
		return $this->update( $task_bulk_argument );
	}
	/**
	 * @param BTM_Task_Bulk_Argument $task_bulk_argument
	 *
	 * @return bool
	 */
	public function mark_as_succeeded( BTM_Task_Bulk_Argument $task_bulk_argument ){
		$task_bulk_argument->set_status( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_SUCCEEDED ) );
		return $this->update( $task_bulk_argument );
	}
	/**
	 * @param BTM_Task_Bulk_Argument $task_bulk_argument
	 *
	 * @return bool
	 */
	public function mark_as_failed( BTM_Task_Bulk_Argument $task_bulk_argument ){
		$task_bulk_argument->set_status( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_FAILED ) );
		return $this->update( $task_bulk_argument );
	}

	/**
	 * @param BTM_Task_Bulk_Argument[] $task_bulk_arguments
	 * @param BTM_Task_Run_Status $task_run_status
	 *
	 * @return bool
	 */
	public function mark_many_as( array $task_bulk_arguments, BTM_Task_Run_Status $task_run_status ){
		$db_transaction = BTM_DB_Transaction::get_instance();

		$db_transaction->start();
		foreach ( $task_bulk_arguments as $task_bulk_argument ){
			if( ! is_a( $task_bulk_argument, 'BTM_Task_Bulk_Argument' ) ){
				$db_transaction->rollback();
				throw new InvalidArgumentException(
					'Argument $task_bulk_arguments should only contain BTM_Task_Bulk_Argument instances. Input was: ' . $task_bulk_argument
				);
			}

			$task_bulk_argument->set_status( $task_run_status );
			$updated = $this->update( $task_bulk_argument );
			if( true !== $updated ){
				$db_transaction->rollback();
				return false;
			}
		}

		$db_transaction->commit();
		return true;
	}
	/**
	 * @param BTM_Task_Bulk_Argument[] $task_bulk_arguments
	 *
	 * @return bool
	 */
	public function mark_many_as_running( array $task_bulk_arguments ){
		return $this->mark_many_as(
			$task_bulk_arguments,
			new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_RUNNING )
		);
	}
	/**
	 * @param BTM_Task_Bulk_Argument[] $task_bulk_arguments
	 *
	 * @return bool
	 */
	public function mark_many_as_succeeded( array $task_bulk_arguments ){
		return $this->mark_many_as(
			$task_bulk_arguments,
			new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_SUCCEEDED )
		);
	}
	/**
	 * @param BTM_Task_Bulk_Argument[] $task_bulk_arguments
	 *
	 * @return bool
	 */
	public function mark_many_as_failed( array $task_bulk_arguments ){
		return $this->mark_many_as(
			$task_bulk_arguments,
			new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_FAILED )
		);
	}

	// endregion

	// region DELETE

	/**
	 * @param BTM_Task_Bulk_Argument $task_bulk_argument
	 *
	 * @return bool
	 */
	public function delete( BTM_Task_Bulk_Argument $task_bulk_argument ){
		return $this->delete_by_id( $task_bulk_argument->get_id() );
	}

	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	public function delete_by_id( $id ){
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
	 * @param int $task_id  task id
	 *
	 * @return bool         true if operation was successful, false otherwise
	 */
	public function delete_by_task_id( $task_id ){
		global $wpdb;

		$deleted = $wpdb->delete(
			$this->get_table_name(),
			array( 'task_id' => $task_id ),
			array( '%d' )
		);

		if( false === $deleted ){
			return false;
		}

		return true;
	}

	// endregion

	/**
	 * @param stdClass $task_bulk_argument_obj
	 *
	 * @return BTM_Task_Bulk_Argument
	 */
	protected function create_task_from_db_obj( stdClass $task_bulk_argument_obj ){
		return BTM_Task_Bulk_Argument::create_from_db_obj( $task_bulk_argument_obj );
	}
}