<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Run_Filter_Log
 */
class BTM_Task_Run_Filter_Log{
	/**
	 * @var string[]
	 */
	protected $logs = array();
	/**
	 * @return string[]
	 */
	public function get_logs(){
		return $this->logs;
	}
	/**
	 * @param string $message
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $message is not a string
	 */
	public function add_log( $message ){
		if( ! is_string( $message ) ){
			throw new InvalidArgumentException( 'The method add_log only accepts strings. Input was: ' . $message );
		}

		$this->logs[] = $message;
	}
	/**
	 * @param string[] $messages
	 */
	public function add_logs( array $messages ){
		foreach( $messages as $message ){
			$this->add_log( $message );
		}
	}

	/**
	 * @var bool
	 */
	protected $is_failed = true;
	/**
	 * @return bool
	 */
	public function is_failed(){
		return $this->is_failed;
	}
	/**
	 * @param bool $is_failed
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $is_failed is not bool
	 */
	public function set_failed( $is_failed ){
		if( ! is_bool( $is_failed ) ){
			throw new InvalidArgumentException( 'The method add_log only accepts bool. Input was: ' . $is_failed );
		}

		$this->is_failed = $is_failed;
	}

	/**
	 * @var array<int,BTM_Task_Bulk_Argument>
	 */
	protected $bulk_fails = array();
	/**
	 * @return array<int,BTM_Task_Bulk_Argument>    with the id as a key
	 */
	public function get_bulk_fails(){
		return $this->bulk_fails;
	}
	/**
	 * @param BTM_Task_Bulk_Argument $task_bulk_argument
	 */
	public function set_bulk_fail( BTM_Task_Bulk_Argument $task_bulk_argument ){
		$this->bulk_fails[ $task_bulk_argument->get_id() ] = $task_bulk_argument;
	}
}