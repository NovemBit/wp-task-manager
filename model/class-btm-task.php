<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

class BTM_Task{
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
	 */
	public function set_callback_action( $callback_action ){
		$this->callback_action = $callback_action;
	}

	/**
	 * @var mixed[]
	 */
	protected $callback_arguments;
	/**
	 * @return mixed[]
	 */
	public function get_callback_arguments(){
		return $this->callback_arguments;
	}
	public function set_callback_arguments( array $callback_arguments ){
		// @todo: check arguments to be serializable,
		// log error otherwise?
		$this->callback_arguments = $callback_arguments;
	}

	public function get_priority(){
		throw new Exception('Not Implemented');
	}

	public function get_date_created(){
		throw new Exception('Not Implemented');
	}
}