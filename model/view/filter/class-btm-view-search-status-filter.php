<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_View_Filter
 */
class BTM_View_Search_Status_Filter extends BTM_View_Filter {
	/**
	 * @var string
	 */
	protected $search = '';
	/**
	 * @return string
	 */
	public function get_search() {
		return $this->search;
	}
	/**
	 * @param string $search
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $search is not a string
	 */
	public function set_search( $search ) {
		if( ! is_string( $search ) ){
			throw new InvalidArgumentException('Argument $search should be string. Input was: ' . $search );
		}

		$this->search = $search;
	}
	/**
	 * @return bool
	 */
	public function has_search(){
		if( 0 < strlen( $this->search ) ){
			return true;
		}

		return false;
	}

	/**
	 * @var string
	 */
	protected $status = '';
	/**
	 * @return string
	 */
	public function get_status(){
		return $this->status;
	}
	/**
	 * @param string $status
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $status is not a valid BTM_Task_Run_Status::STATUS_* status and is not an empty string
	 */
	public function set_status( $status ){
		if( '' !== $status && ! BTM_Task_Run_Status::is_valid_status( $status ) ){
			throw new InvalidArgumentException('
				Argument $status should be one of BTM_Task_Run_Status::STATUS_* constants or empty string. Input was: ' . $status
			);
		}

		$this->status = $status;
	}
	/**
	 * @return bool
	 */
	public function has_status(){
		if( 0 < strlen( $this->status ) ){
			return true;
		}

		return false;
	}
}