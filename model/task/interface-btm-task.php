<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Interface I_BTM_Task
 */
interface I_BTM_Task{
	/**
	 * @param stdClass $task_obj
	 *
	 * @return I_BTM_Task
	 */
	public static function create_from_db_obj( stdClass $task_obj );

	/**
	 * @return int
	 */
	public function get_id();
	/**
	 * @param int $id
	 */
	public function set_id( $id );

	/**
	 * @return string
	 */
	public function get_callback_action();
	/**
	 * @param string $callback_action
	 */
	public function set_callback_action( $callback_action );

	/**
	 * @return mixed[]
	 */
	public function get_callback_arguments();
	/**
	 * @param mixed[] $callback_arguments
	 */
	public function set_callback_arguments( array $callback_arguments );

	/**
	 * @return int
	 */
	public function get_priority();
	/**
	 * @param int $priority
	 */
	public function set_priority( $priority );

	/**
	 * @return int
	 */
	public function get_bulk_size();
	/**
	 * @param $bulk_size
	 */
	public function set_bulk_size( $bulk_size );

	/**
	 * @return BTM_Task_Run_Status
	 */
	public function get_status();
	/**
	 * @param BTM_Task_Run_Status $status
	 */
	public function set_status( BTM_Task_Run_Status $status );

	/**
	 * @return int
	 */
	public function get_date_created_timestamp();
	/**
	 * @param int $date_created_timestamp
	 */
	public function set_date_created_timestamp( $date_created_timestamp );
}