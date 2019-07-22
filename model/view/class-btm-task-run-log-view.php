<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Run_Log_View
 */
class BTM_Task_Run_Log_View {
	/**
	 * @param stdClass $task_run_log_view_obj
	 *
	 * @return BTM_Task_Run_Log_View
	 */
	public static function create_from_db_obj( stdClass $task_run_log_view_obj ){
		$task_view = new static();

		$task_view->set_id( (int) $task_run_log_view_obj->id );
		$task_view->set_task_id( (int) $task_run_log_view_obj->task_id );
		$task_view->set_callback_action( $task_run_log_view_obj->callback_action );
		$task_view->set_logs( unserialize( $task_run_log_view_obj->logs ) );
		$task_view->set_status( new BTM_Task_Run_Status( $task_run_log_view_obj->status ) );
		$task_view->set_date_started_timestamp( strtotime( $task_run_log_view_obj->date_started ) );
		$task_view->set_date_finished_timestamp( strtotime( $task_run_log_view_obj->date_finished ) );

		return $task_view;
	}

	/**
	 * @var int
	 */
	protected $id;
	/**
	 * @return int
	 */
	public function get_id(){
		return $this->id;
	}
	/**
	 * @param int $id
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $id is not a positive int
	 */
	protected function set_id( $id ){
		if( ! is_int( $id ) || 0 >= $id ){
			throw new InvalidArgumentException( 'Argument $id should be positive int. Input was: ' . $id );
		}

		$this->id = $id;
	}

	/**
	 * @var int
	 */
	protected $task_id;
	/**
	 * @return int
	 */
	public function get_task_id(){
		return $this->task_id;
	}
	/**
	 * @param int $task_id
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $task_id is not a positive int
	 */
	protected function set_task_id( $task_id ){
		if( ! is_int( $task_id ) || 0 >= $task_id ){
			throw new InvalidArgumentException( 'Argument $task_id should be positive int. Input was: ' . $task_id );
		}

		$this->task_id = $task_id;
	}

	/**
	 * @var string
	 */
	protected $callback_action;
	/**
	 * @return string
	 */
	public function get_callback_action(){
		return $this->callback_action;
	}
	/**
	 * @param string $callback_action
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $callback_action is not a string or is empty
	 */
	protected function set_callback_action( $callback_action ){
		if( ! is_string( $callback_action ) || empty( $callback_action ) ){
			throw new InvalidArgumentException( 'Argument $callback_action should be non empty string. Input was: ' . $callback_action );
		}

		$this->callback_action = $callback_action;
	}

	/**
	 * @var string[]
	 */
	protected $logs;
	/**
	 * @return string[]
	 */
	public function get_logs(){
		return $this->logs;
	}
	/**
	 * @param string[] $logs
	 */
	protected function set_logs( array $logs ){
		// @todo: validate $logs to be string array, or allow other values, simple and complex
		$this->logs = $logs;
	}

	/**
	 * @var BTM_Task_Run_Status
	 */
	protected $status;
	/**
	 * @return BTM_Task_Run_Status
	 */
	public function get_status(){
		return $this->status;
	}
	/**
	 * @param BTM_Task_Run_Status $status
	 */
	protected function set_status( BTM_Task_Run_Status $status ){
		$this->status = $status;
	}

	/**
	 * @var int
	 */
	protected $date_started_timestamp;
	/**
	 * @return int
	 */
	public function get_date_started_timestamp(){
		return $this->date_started_timestamp;
	}
	/**
	 * @param int $date_started_timestamp
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $date_started_timestamp is not an int
	 */
	protected function set_date_started_timestamp( $date_started_timestamp ){
		if( ! is_int( $date_started_timestamp ) ){
			throw new InvalidArgumentException( 'Argument $date_started should be int. Input was: ' . $date_started_timestamp );
		}

		$this->date_started_timestamp = $date_started_timestamp;
	}

	/**
	 * @var int
	 */
	protected $date_finished_timestamp;
	/**
	 * @return int
	 */
	public function get_date_finished_timestamp(){
		return $this->date_finished_timestamp;
	}
	/**
	 * @param int $date_finished_timestamp
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $date_finished_timestamp is not an int
	 */
	protected function set_date_finished_timestamp( $date_finished_timestamp ){
		if( ! is_int( $date_finished_timestamp ) ){
			throw new InvalidArgumentException( 'Argument $date_finished should be int. Input was: ' . $date_finished_timestamp );
		}

		$this->date_finished_timestamp = $date_finished_timestamp;
	}
}