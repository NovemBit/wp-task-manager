<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Run_Status
 */
final class BTM_Task_Run_Status{
	const STATUS_REGISTERED = 'registered';
	const STATUS_FAILED = 'failed';
	const STATUS_RUNNING = 'running';
	const STATUS_SUCCEEDED = 'succeeded';
	const STATUS_IN_PROGRESS = 'in_progress';

	/**
	 * @return array<string,string>  status value and display name
	 */
	public static function get_statuses(){
		$statuses = array();
		$reflectionClass = new ReflectionClass( __CLASS__ );
		foreach ( $reflectionClass->getConstants() as $constant_name => $constant_val ) {
			if ( 0 === strpos( $constant_name, 'STATUS_' ) ) {
				$statuses[ $constant_val ] = self::get_status_display_name( $constant_val );
			}
		}

		return $statuses;
	}

	/**
	 * @param string $status
	 *
	 * @return string
	 */
	public static function get_status_display_name( $status ){
		switch ( $status ){
			case self::STATUS_REGISTERED :
				return __( 'Registered', 'background_task_manager' );
			case self::STATUS_FAILED :
				return __( 'Failed', 'background_task_manager' );
			case self::STATUS_RUNNING :
				return __( 'Running', 'background_task_manager' );
			case self::STATUS_SUCCEEDED :
				return __( 'Succeeded', 'background_task_manager' );
			case self::STATUS_IN_PROGRESS :
				return __( 'In progress', 'background_task_manager' );
		}
		return '';
	}

	/**
	 * @var string
	 */
	private $status;
	/**
	 * @return string
	 */
	public function get_display_name(){
		return self::get_status_display_name( $this->status );
	}
	/**
	 * @return string
	 */
	public function get_value(){
		return $this->status;
	}

	/**
	 * @param string $status
	 *
	 * @return bool
	 */
	public function is_valid_status( $status ){
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
		return $this->get_display_name();
	}
}