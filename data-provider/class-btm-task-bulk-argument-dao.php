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

		$data[ 'argument_hash' ] = md5( $data[ 'callback_arguments' ] );
		$format[] = '%s';

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

	/**
	 * @param int $task_id
	 * @param BTM_Task_Bulk_Argument[] $task_bulk_arguments
	 *
	 * @return bool
	 *
	 * @throws InvalidArgumentException
	 */
	public function create_many( $task_id, array $task_bulk_arguments ){
		global $wpdb;

		if( ! is_int( $task_id ) || 0 >= $task_id ){
			throw new InvalidArgumentException('Argument $task_id should be positive int. Input was: ' . $task_id );
		}

		$base_query = '
			INSERT INTO `' . $this->get_table_name() . '`( `task_id`, `callback_arguments`, `priority`, `status`, `date_created`, `argument_hash` )
			VALUES
		';

		$allowed_insert_bulk_size = BTM_Plugin_Options::get_instance()->get_allowed_insert_bulk_size();
		$task_argument_chunks = array_chunk( $task_bulk_arguments, $allowed_insert_bulk_size );

		/** @var BTM_Task_Bulk_Argument[] $task_argument_chunk */
		foreach( $task_argument_chunks as $task_argument_chunk ){
			$query = $base_query;
			for( $i = 0; $i < count( $task_argument_chunk ) ; ++ $i ){
				if( 0 < $task_argument_chunk[ $i ]->get_task_id() ){
					if( $task_id !== $task_argument_chunk[ $i ]->get_task_id() ){
						throw new InvalidArgumentException(
							'Items in argument array $task_bulk_arguments should not have a task_id or it should be equal to the argument $task_id'
						);
					}
				}else{
					$task_argument_chunk[ $i ]->set_task_id( $task_id );
				}

				if( empty( $task_argument_chunk[ $i ]->get_status() ) ){
					$status = new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_REGISTERED );
					$task_argument_chunk[ $i ]->set_status( $status );
				}
				if( empty( $task_argument_chunk[ $i ]->get_date_created_timestamp() ) ){
					$task_argument_chunk[ $i ]->set_date_created_timestamp( time() );
				}

				$callback_arguments = serialize( $task_argument_chunk[ $i ]->get_callback_arguments() );
				$values = $wpdb->prepare('
					( %d, %s, %d, %s, %s, %s ),
				',
					$task_id,
					$callback_arguments,
					$task_argument_chunk[ $i ]->get_priority(),
					$task_argument_chunk[ $i ]->get_status()->get_value(),
					date( 'Y-m-d H:i:s', $task_argument_chunk[ $i ]->get_date_created_timestamp() ),
					md5( $callback_arguments )
				);

				$query .= $values;
			}

			$query = trim( $query, ", \t\n\r\0\x0B" );

			$inserted = $wpdb->query( $query );
			if( false === $inserted ){
				return false;
			}
		}

		return true;
	}

	/**
	 * @param int $task_id
	 * @param BTM_Task_Bulk_Argument $task_bulk_argument
	 *
	 * @return bool
	 *
	 * @throws InvalidArgumentException
	 */
	public function add_to_keep_higher_priority( $task_id, BTM_Task_Bulk_Argument $task_bulk_argument ){
		if( ! is_int( $task_id ) || 0 >= $task_id ){
			throw new InvalidArgumentException('Argument $task_id should be positive int. Input was: ' . $task_id );
		}
		if( 0 < $task_bulk_argument->get_task_id() ){
			if( $task_id !== $task_bulk_argument->get_task_id() ){
				throw new InvalidArgumentException(
					'Argument $task_bulk_argument should not have a task_id or it should be equal to the argument $task_id'
				);
			}
		}else{
			$task_bulk_argument->set_task_id( $task_id );
		}

		$created = $this->create( $task_bulk_argument );
		if( false === $created ){
			return false;
		}

		$deleted = $this->delete_low_priority_duplicates( $task_id );
		if( false === $deleted ){
			return false;
		}

		return true;
	}
	/**
	 * @param int $task_id
	 * @param BTM_Task_Bulk_Argument[] $task_bulk_arguments
	 *
	 * @return bool
	 */
	public function add_many_to_keep_higher_priority( $task_id, array $task_bulk_arguments ){
		$created = $this->create_many( $task_id, $task_bulk_arguments );
		if( false === $created ){
			return false;
		}

		$deleted = $this->delete_low_priority_duplicates( $task_id );
		if( false === $deleted ){
			return false;
		}

		return true;
	}
	/**
	 * @param int $task_id
	 *
	 * @return bool
	 */
	protected function delete_low_priority_duplicates( $task_id ){
		global $wpdb;

		$status_registered = BTM_Task_Run_Status::STATUS_REGISTERED;

		$query = $wpdb->prepare('
			SELECT `t1`.`id`
			FROM `' . $this->get_table_name() . '` AS `t1`
			JOIN (
				SELECT `t2`.`argument_hash`, MIN(`t2`.`priority`) AS `priority`, `t2`.`id`
				FROM `' . $this->get_table_name() . '` AS `t2`
				JOIN `' . $this->get_table_name() . '` AS `t3`
					ON `t2`.`id` != `t3`.`id`
					AND `t2`.`argument_hash` = `t3`.`argument_hash`
				WHERE `t2`.`task_id` = %d
					AND `t3`.`task_id` = %d
					AND `t2`.`status` = %s
					AND `t3`.`status` = %s
				GROUP BY `t2`.`argument_hash`
			) AS `max_p`
				ON `t1`.`argument_hash` = `max_p`.`argument_hash`
				AND `t1`.`id` != `max_p`.`id`
			WHERE `t1`.`task_id` = %d
				AND `t1`.`status` = %s
		',
			$task_id,
			$task_id,
			$status_registered,
			$status_registered,
			$task_id,
			$status_registered
		);

		$ids_to_remove = $wpdb->get_col( $query );
		if( empty( $ids_to_remove ) ){
			return true;
		}

		$deleted = $wpdb->query('
			DELETE FROM `' . $this->get_table_name() . '`
			WHERE id IN (' . implode( ',', $ids_to_remove ) . ')
		');

		if( false === $deleted ){
			return false;
		}

		return true;
	}

	/**
	 * @param int $task_id
	 * @param BTM_Task_Bulk_Argument $task_bulk_argument
	 *
	 * @return bool
	 *
	 * @throws InvalidArgumentException
	 */
	public function add_to_overwrite( $task_id, BTM_Task_Bulk_Argument $task_bulk_argument ){
		if( 0 < $task_bulk_argument->get_task_id() ){
			if( $task_id !== $task_bulk_argument->get_task_id() ){
				throw new InvalidArgumentException(
					'Argument $task_bulk_argument should not have a task_id or it should be equal to the argument $task_id'
				);
			}
		}else{
			$task_bulk_argument->set_task_id( $task_id );
		}

		$created = $this->create( $task_bulk_argument );
		if( false === $created ){
			return false;
		}

		$deleted = $this->delete_old_duplicates( $task_id );
		if( false === $deleted ){
			return false;
		}

		return true;
	}
	/**
	 * @param int $task_id
	 * @param BTM_Task_Bulk_Argument[] $task_bulk_arguments
	 *
	 * @return bool
	 */
	public function add_many_to_overwrite( $task_id, array $task_bulk_arguments ){
		$created = $this->create_many( $task_id, $task_bulk_arguments );
		if( false === $created ){
			return false;
		}

		$deleted = $this->delete_old_duplicates( $task_id );
		if( false === $deleted ){
			return false;
		}

		return true;
	}
	/**
	 * @param int $task_id
	 *
	 * @return bool
	 */
	protected function delete_old_duplicates( $task_id ){
		global $wpdb;

		$status_registered = BTM_Task_Run_Status::STATUS_REGISTERED;

		$query = $wpdb->prepare('
			SELECT `t1`.`id`
			FROM `' . $this->get_table_name() . '` AS `t1`
			JOIN (
				SELECT `t2`.`argument_hash`, MAX(`t2`.`id`) AS `id`
				FROM `' . $this->get_table_name() . '` AS `t2`
				JOIN `' . $this->get_table_name() . '` AS `t3`
					ON `t2`.`id` != `t3`.`id`
					AND `t2`.`argument_hash` = `t3`.`argument_hash`
				WHERE `t2`.`task_id` = %d
					AND `t3`.`task_id` = %d
					AND `t2`.`status` = %s
					AND `t3`.`status` = %s
				GROUP BY `t2`.`argument_hash`
			) AS max_p
				ON `t1`.`argument_hash` = max_p.`argument_hash`
				AND `t1`.`id` != max_p.`id`
			WHERE `t1`.`task_id` = %d
				AND `t1`.`status` = %s

		',
			$task_id,
			$task_id,
			$status_registered,
			$status_registered,
			$task_id,
			$status_registered
		);

		$ids_to_remove = $wpdb->get_col( $query );
		if( empty( $ids_to_remove ) ){
			return true;
		}

		$deleted = $wpdb->query('
			DELETE FROM `' . $this->get_table_name() . '`
			WHERE id IN (' . implode( ',', $ids_to_remove ) . ')
		');

		if( false === $deleted ){
			return false;
		}

		return true;
	}

	// endregion

	// region READ

	/**
	 * @param int $id
	 *
	 * @return BTM_Task_Bulk_Argument|false
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
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $task_id is not a positive int
	 */
	public function get_by_task_id( $task_id, $offset = 0, $limit = 100 ){
		global $wpdb;

		if( ! is_int( $task_id ) || 0 >= $task_id ){
			throw new InvalidArgumentException( 'Argument $task_id should be positive int. Input was: ' . $task_id );
		}

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

	/**
	 * @param int $task_id
	 * @param int $bulk_size
	 *
	 * @return BTM_Task_Bulk_Argument[]
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $task_id or $bulk_size is not a positive int
	 */
	public function get_next_arguments_to_run( $task_id, $bulk_size ){
		global $wpdb;

		if( ! is_int( $task_id ) || 0 >= $task_id ){
			throw new InvalidArgumentException( 'Argument $task_id should be positive int. Input was: ' . $task_id );
		}
		if( ! is_int( $bulk_size ) || 0 >= $bulk_size ){
			throw new InvalidArgumentException( 'Argument $bulk_size should be positive int. Input was: ' . $bulk_size );
		}

		$query = $wpdb->prepare('
			SELECT *
			FROM `' . $this->get_table_name() . '`
			WHERE task_id = %d
				AND `status` = %s
			ORDER BY `priority` ASC
			LIMIT 0, %d
		',
			$task_id,
			BTM_Task_Run_Status::STATUS_REGISTERED,
			$bulk_size
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

	/**
	 * @param int $task_id
	 *
	 * @return bool
	 */
	public function has_failed_arguments( $task_id ){
		global $wpdb;

		$query = $wpdb->prepare('
			SELECT `id`
			FROM `' . $this->get_table_name() . '`
			WHERE `task_id` = %d
				AND `status` = %s
			LIMIT 0, 1
		',
			$task_id,
			BTM_Task_Run_Status::STATUS_FAILED
		);

		$id = $wpdb->get_var( $query );

		if( 0 < $id ){
			return true;
		}else{
			return false;
		}
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

		$callback_arguments = serialize( $task_bulk_argument->get_callback_arguments() );

		$updated = $wpdb->update(
			$this->get_table_name(),
			array(
				'task_id' => $task_bulk_argument->get_task_id(),
				'callback_arguments' => $callback_arguments,
				'priority' => $task_bulk_argument->get_priority(),
				'status' => $task_bulk_argument->get_status()->get_value(),
				'date_created' => date( 'Y-m-d H:i:s' , $task_bulk_argument->get_date_created_timestamp() ),
				'argument_hash' => md5( $callback_arguments )
			),
			array(
				'id' => $task_bulk_argument->get_id()
			),
			array( '%d', '%s', '%d', '%s', '%s', '%s' ),
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
		// @todo: make a single query
		foreach ( $task_bulk_arguments as $task_bulk_argument ){
			if( ! is_a( $task_bulk_argument, 'BTM_Task_Bulk_Argument' ) ){
				throw new InvalidArgumentException(
					'Argument $task_bulk_arguments should only contain BTM_Task_Bulk_Argument instances. Input was: ' . $task_bulk_argument
				);
			}

			$task_bulk_argument->set_status( $task_run_status );
			$updated = $this->update( $task_bulk_argument );
			if( true !== $updated ){
				return false;
			}
		}

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

	/**
	 * @param array $ids
	 *
	 * @return bool
	 */
	public function delete_many_by_ids( array $ids ){
		global $wpdb;

		$query = $wpdb->prepare('
			DELETE FROM `' . $this->get_table_name() . '`
			WHERE `id` IN ( %s )
		', implode( ',', $ids ) );

		$deleted = $wpdb->query( $query );

		return false !== $deleted;
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