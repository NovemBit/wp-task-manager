<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

class BTM_Timer{
	const STATE_INITIAL = 'initial';
	const STATE_STARTED = 'started';
	const STATE_STOPPED = 'stopped';

	/**
	 * @var string
	 */
	protected $state = self::STATE_INITIAL;

	public function get_state(){
		throw new Exception('not implemented');
	}

	public function __construct() {
		throw new Exception('not implemented');
	}

	public function start(){
		throw new Exception('not implemented');
	}

	public function stop(){
		throw new Exception('not implemented');
	}

	public function get_time_elapsed(){
		throw new Exception('not implemented');
	}
}