<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

class BTM_Timer{
	const STATE_INITIAL = 'initial';
	const STATE_STARTED = 'started';
	const STATE_STOPPED = 'stopped';

	/**
	 * The timer initial state
	 *
	 * @var string
	 */
	protected $state = self::STATE_INITIAL;
	/**
	 * Return the state of timer
	 *
	 * @return string
	 */
	public function get_state(){
		return $this->state;
	}

	/**
	 * Timer start timestamp
	 *
	 * @var int
	 */
	public $started;
	/**
	 * Timer stop timestamp
	 *
	 * @var int
	 */
	public $stopped;

	/**
	 * BTM_Timer constructor.
	 */
	public function __construct() {}

	/**
	 * Timer start
	 *
	 * @throws Exception
	 *      in the case the timer is already started
	 */
	public function start(){
		if( $this->get_state() === BTM_Timer::STATE_STARTED ){
			throw new Exception("Timer is already started.");
		}else{
			$this->started = time();
			$this->state = self::STATE_STARTED;
		}
	}
	/**
	 * Timer stop
	 *
	 * @throws Exception
	 *      in the case the timer is not started
	 */
	public function stop(){
		if( $this->get_state() === BTM_Timer::STATE_STARTED ){
			$this->stopped = time();
			$this->state = self::STATE_STOPPED;
		}else{
			throw new Exception("Timer is not started.");
		}
	}

	/**
	 * Get the time elapsed after the timer is started
	 *
	 * @return int
	 */
	public function get_time_elapsed_in_seconds(){
		if( $this->get_state() === BTM_Timer::STATE_STOPPED ){
			return $this->stopped - $this->started;
		}else{
			return time() - $this->started;
		}
	}
}