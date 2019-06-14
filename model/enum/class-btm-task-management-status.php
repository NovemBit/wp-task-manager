<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Management_Status
 */
final class BTM_Task_Management_Status{
	const STATUS_ = 'status_';

	/**
	 * @var string
	 */
	private $status = self::STATUS_;
	/**
	 * @return string
	 */
	private function get_status_display_name(){
		switch ( $this->status ){
			case self::STATUS_ :
				return __( 'status_', 'background_task_manager' );
		}
		return '';
	}

	/**
	 * @param string $status
	 *
	 * @return bool
	 */
	private function is_valid_status( $status ){
		$reflectionClass = new ReflectionClass( __CLASS__ );
		foreach ( $reflectionClass->getConstants() as $constant_name => $constant_val ) {
			if ( 0 === strpos( $constant_name, 'STATUS_' ) && $constant_val === $status ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * BTM_Task_Run_Status constructor.
	 *
	 * @param string $status
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument status is not valid
	 */
	public function __construct( $status ) {
		if( ! $this->is_valid_status( $status ) ){
			throw new InvalidArgumentException(
				'BTM_Task_Run_Status constructor accepts only one of its STATUS_* constants. Input was: ' . $status
			);
		}

		$this->status = $status;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->get_status_display_name();
	}
}