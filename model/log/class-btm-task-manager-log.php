<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Manager_Log
 */
class BTM_Task_Manager_Log{
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
	 * @var string
	 */
	protected $log;
	/**
	 * @return string
	 */
	public function get_log(){
		return $this->log;
	}
	/**
	 * @param string $log
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $log is not a string
	 */
	public function set_log( $log ){
		if( ! is_string( $log ) ){
			throw new InvalidArgumentException( 'Argument $log should be string. Input was: ' . $log );
		}

		$this->log = $log;
	}

	/**
	 * BTM_Task_Manager_Log constructor.
	 *
	 * @param int $session_id
	 * @param string $log
	 */
	public function __construct(
		$session_id,
		$log = ''
	){
		$this->set_session_id( $session_id );
		$this->set_log( $log );
	}
}