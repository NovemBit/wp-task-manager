<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Manager_Log_Dao
 */
class BTM_Task_Manager_Log_Dao{
	// region Singleton

	/**
	 * @var BTM_Task_Manager_Log_Dao
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Manager_Log_Dao
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->init_session_id();
	}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * @return string
	 */
	public function get_table_name(){
		return BTM_Plugin_Options::get_instance()->get_db_table_prefix() . 'task_manager_logs';
	}

	// region CREATE

	/**
	 * @param BTM_Task_Manager_Log $task_manager_log
	 *
	 * @return bool
	 */
	public function create( BTM_Task_Manager_Log $task_manager_log ){
		global $wpdb;

		$data = array(
			'session_id' => date( 'Y-m-d H:i:s', $task_manager_log->get_session_id() ),
			'log' => $task_manager_log->get_log()
		);
		$format = array( '%s', '%s' );

		if( 0 < $task_manager_log->get_id() ){
			$data['id'] = $task_manager_log->get_id();
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

		if( empty( $task_manager_log->get_id() ) ){
			$task_manager_log->set_id( $wpdb->insert_id );
		}

		return true;
	}

	/**
	 * @param string $log
	 *
	 * @return bool
	 */
	public function log( $log ){
		return $this->create( new BTM_Task_Manager_Log(
			$this->get_session_id(),
			$log
		) );
	}

	// endregion

	// region READ

	/**
	 * @param int $id
	 *
	 * @return BTM_Task_Manager_Log|false
	 */
	public function get_by_id( $id ){
		global $wpdb;

		$query = $wpdb->prepare('
			SELECT * 
			FROM `' . $this->get_table_name() . '`
			WHERE `id` = %d
		', $id);

		$task_manager_log_obj = $wpdb->get_row( $query, OBJECT );
		if( null === $task_manager_log_obj ){
			return false;
		}

		return $this->create_task_manager_log_from_db_obj( $task_manager_log_obj );
	}

	// endregion

	// region UPDATE

	/**
	 * @param BTM_Task_Manager_Log $task_manager_log
	 *
	 * @return bool
	 */
	public function update( BTM_Task_Manager_Log $task_manager_log ){
		global $wpdb;

		$updated = $wpdb->update(
			$this->get_table_name(),
			array(
				'log' => $task_manager_log->get_log()
			),
			array(
				'id' => $task_manager_log->get_id()
			),
			array( '%s' ),
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
	 * @param BTM_Task_Manager_Log $task_manager_log
	 *
	 * @return bool
	 */
	public function delete( BTM_Task_Manager_Log $task_manager_log ){
		return $this->delete_by_id( $task_manager_log->get_id() );
	}

	/**
	 * @param int $id   task manager log id
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

	// endregion

	/**
	 * @param stdClass $task_manager_log_obj
	 *
	 * @return BTM_Task_Manager_Log
	 */
	protected function create_task_manager_log_from_db_obj( stdClass $task_manager_log_obj ){
		$task_manager_log = new BTM_Task_Manager_Log(
			strtotime( $task_manager_log_obj->session_id )
		);

		$task_manager_log->set_id( (int) $task_manager_log_obj->id );
		if( ! empty( $task_manager_log_obj->log ) ){
			$task_manager_log->set_log( $task_manager_log_obj->log );
		}

		return $task_manager_log;
	}

	/**
	 * @var int
	 */
	protected $session_id = null;
	/**
	 * @return int
	 */
	public function get_session_id(){
		return $this->session_id;
	}
	/**
	 * Initializes the session id
	 */
	protected function init_session_id(){
		$this->session_id = time();
	}
}