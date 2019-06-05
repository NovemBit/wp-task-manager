<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

class BTM_Timer{
	const STATE_INITIAL = 'initial';
	const STATE_STARTED = 'started';
	const STATE_STOPPED = 'stopped';
	/**
	 * Timer start timestamp
	 *
	 * @var integer
	 */
	public $started;
	/**
	 * Timer stop timestamp
	 *
	 * @var integer
	 */
	public $stoped;
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
	 * BTM_Timer constructor.
	 */
	public function __construct() {
	}

	/**
	 * Timer start
	 *
	 * @throws Exception
	 */
	public function start(){
		if( $this->get_state() === BTM_Timer::STATE_INITIAL || $this->get_state() === BTM_Timer::STATE_STOPPED ){
			$this->started = time();
			$this->state = self::STATE_STARTED;
		}else{
			throw new Exception("Timer started Exception");
		}
	}
	/**
	 * Timer start
	 *
	 * @throws Exception
	 */
	public function stop(){
		if( $this->get_state() === BTM_Timer::STATE_STARTED ){
			$this->stoped = time();
			$this->state = self::STATE_STOPPED;
		}else{
			throw new Exception("Timer is not started Exception");
		}
	}

	/**
	 * Get elapdes time
	 *
	 * @throws Exception
	 */
	public function get_time_elapsed(){
		if( $this->get_state() === BTM_Timer::STATE_STOPPED ){
			$this->state = self::STATE_INITIAL;
		}else{
			throw new Exception("Timer is not stoped Exception");
		}
	}
}