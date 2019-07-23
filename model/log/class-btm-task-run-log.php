<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Run_Log
 */
class BTM_Task_Run_Log{
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
	 * @throws LogicException
	 *      in the case the id is already evaluated
	 */
	public function set_id( $id ){
		if( ! is_int( $id ) || 0 >= $id ){
			throw new InvalidArgumentException( 'Argument $id should be positive int. Input was: ' . $id );
		}
		if( ! empty( $this->id ) ){
			throw new LogicException( 'Id is already evaluated.' );
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
	 * @throws LogicException
	 *      in the case the task_id is already evaluated
	 */
	public function set_task_id( $task_id ){
		if( ! is_int( $task_id ) || 0 >= $task_id ){
			throw new InvalidArgumentException( 'Argument $task_id should be positive int. Input was: ' . $task_id );
		}
		if( ! empty( $this->task_id ) ){
			throw new LogicException( 'Task id is already evaluated.' );
		}

		$this->task_id = $task_id;
	}

	/**
	 * @var int
	 */
	protected $session_id;
	/**
	 * @return int
	 */
	public function get_session_id(){
		return $this->session_id;
	}
	/**
	 * @param int $session_id
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $session_id is not an int
	 * @throws LogicException
	 *      in the case the session_id is already evaluated
	 */
	public function set_session_id( $session_id ){
		if( ! is_int( $session_id ) ){
			throw new InvalidArgumentException( 'Argument $session_id should be int. Input was: ' . $session_id );
		}
		if( ! empty( $this->session_id ) ){
			throw new LogicException( 'Session id is already evaluated.' );
		}

		$this->session_id = $session_id;
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
	public function set_logs( array $logs ){
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
	public function set_status( BTM_Task_Run_Status $status ){
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
	 *      in the case the argument $date_started_timestamp in not an int
	 */
	public function set_date_started_timestamp( $date_started_timestamp ){
		if( ! is_int( $date_started_timestamp ) ){
			throw new InvalidArgumentException( 'Argument $date_started_timestamp should be int. Input was: ' . $date_started_timestamp );
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
	public function set_date_finished_timestamp( $date_finished_timestamp ){
		if( ! is_int( $date_finished_timestamp ) ){
			throw new InvalidArgumentException( 'Argument $date_finished_timestamp should be int. Input was: ' . $date_finished_timestamp );
		}
		$this->date_finished_timestamp = $date_finished_timestamp;
	}

	/**
	 * BTM_Task_Run_Log constructor.
	 *
	 * @param int $task_id
	 * @param int $session_id
	 * @param string[] $logs
	 * @param BTM_Task_Run_Status $task_run_status
	 * @param int|null $date_started_timestamp
	 * @param int|null $date_finished_timestamp
	 */
	public function __construct(
		$task_id,
		$session_id,
		array $logs,
		BTM_Task_Run_Status $task_run_status,
		$date_started_timestamp = null,
		$date_finished_timestamp = null
	){
		$this->set_task_id( $task_id );
		$this->set_session_id( $session_id );
		$this->set_logs( $logs );
		$this->set_status( $task_run_status );

		if( null !== $date_started_timestamp ){
			$this->set_date_started_timestamp( $date_started_timestamp );
		}
		if( null !== $date_finished_timestamp ){
			$this->set_date_finished_timestamp( $date_finished_timestamp );
		}
	}
}