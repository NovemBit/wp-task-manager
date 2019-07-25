<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Run_Log_Dao
 */
class BTM_Task_Run_Log_Dao{
	// region Singleton

	/**
	 * @var BTM_Task_Run_Log_Dao
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Run_Log_Dao
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
		return BTM_Plugin_Options::get_instance()->get_db_table_prefix() . 'task_run_logs';
	}

	// region CREATE

	/**
	 * @param BTM_Task_Run_Log $task_run_log
	 *
	 * @return bool
	 */
	public function create( BTM_Task_Run_Log $task_run_log ){
		global $wpdb;

		if( empty( $task_run_log->get_date_started_timestamp() ) ){
			$task_run_log->set_date_started_timestamp( time() );
		}

		$data = array(
			'task_id'       => $task_run_log->get_task_id(),
			'session_id'    => date( 'Y-m-d H:i:s', $task_run_log->get_session_id() ),
			'logs'          => serialize( $task_run_log->get_logs() ),
			'status'        => $task_run_log->get_status()->get_value(),
			'date_started'  => date( 'Y-m-d H:i:s', $task_run_log->get_date_started_timestamp() )
		);
		$format = array( '%d', '%s', '%s', '%s', '%s' );

		if( 0 < $task_run_log->get_id() ){
			$data['id'] = $task_run_log->get_id();
			$format[] = '%d';
		}

		if( ! empty( $task_run_log->get_date_finished_timestamp() ) ){
			$data['date_finished'] = date( 'Y-m-d H:i:s', $task_run_log->get_date_finished_timestamp() );
			$format[] = '%s';
		}

		$inserted = $wpdb->insert(
			$this->get_table_name(),
			$data,
			$format
		);

		if( false === $inserted ){
			return false;
		}

		if( empty( $task_run_log->get_id() ) ){
			$task_run_log->set_id( $wpdb->insert_id );
		}

		return true;
	}

	// endregion

	// region READ

	/**
	 * @param int $id
	 *
	 * @return BTM_Task_Run_Log|false
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

		$task_run_log_obj = $wpdb->get_row( $query, OBJECT );
		if( null === $task_run_log_obj ){
			return false;
		}

		return $this->create_task_run_log_from_db_obj( $task_run_log_obj );
	}

	// endregion

	// region UPDATE

	/**
	 * @param BTM_Task_Run_Log $task_run_log
	 *
	 * @return bool
	 */
	public function update( BTM_Task_Run_Log $task_run_log ){
		global $wpdb;

		$updated = $wpdb->update(
			$this->get_table_name(),
			array(
				'logs'          => serialize( $task_run_log->get_logs() ),
				'date_started'  => date( 'Y-m-d H:i:s' , $task_run_log->get_date_started_timestamp() ),
				'date_finished' => date( 'Y-m-d H:i:s' , $task_run_log->get_date_finished_timestamp() ),
				'status'        => $task_run_log->get_status()->get_value()
			),
			array(
				'id' => $task_run_log->get_id()
			),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if( false === $updated || 0 === $updated ){
			return false;
		}

		return true;
	}

	// endregion

	// region DELETE

	/**
	 * @param BTM_Task_Run_Log $task_run_log
	 *
	 * @return bool
	 */
	public function delete( BTM_Task_Run_Log $task_run_log ){
		return $this->delete_by_id( $task_run_log->get_id() );
	}

	/**
	 * @param int $id   task run log id
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

		$query = $wpdb->prepare('
			DELETE FROM `' . $this->get_table_name() . '`
			WHERE `id` IN ( %s )
		', implode( ',', $ids ) );

		$deleted = $wpdb->query( $query );

		return false !== $deleted;
	}

	/**
	 * @param int $interval
	 *
	 * @return bool
	 */
	public function delete_by_date_interval( $interval ){
		global $wpdb;

		if( ! is_int( $interval ) || 0 >= $interval ){
			throw new InvalidArgumentException( 'Argument $interval should be positive int. Input was: ' . $interval );
		}

		$deleted = $wpdb->query(
			'DELETE FROM `' . $this->get_table_name() . '`
            		WHERE `date_finished` < (NOW() - INTERVAL '. $interval .' DAY)'
		);

		if( false === $deleted || 0 === $deleted ){
			return false;
		}

		return true;
	}

	// endregion

	/**
	 * @param stdClass $task_run_log_obj
	 *
	 * @return BTM_Task_Run_Log
	 */
	protected function create_task_run_log_from_db_obj( stdClass $task_run_log_obj ){
		$task_run_log = new BTM_Task_Run_Log(
			(int) $task_run_log_obj->task_id,
			strtotime( $task_run_log_obj->session_id ),
			unserialize( $task_run_log_obj->logs ),
			new BTM_Task_Run_Status( $task_run_log_obj->status ),
			strtotime( $task_run_log_obj->date_started )
		);

		$task_run_log->set_id( (int) $task_run_log_obj->id );
		if( ! empty( $task_run_log_obj->date_finished ) ){
			$task_run_log->set_date_finished_timestamp( strtotime( $task_run_log_obj->date_finished ) );
		}

		return $task_run_log;
	}
}