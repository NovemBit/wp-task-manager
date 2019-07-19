<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Single_View
 */
class BTM_Task_Single_View {

	public static function create_from_db_obj( stdClass $task_view_obj ){
		$task_view = new static();

		$task_view->set_callback_action( $task_view_obj->callback_action );
		$task_view->set_callback_arguments( unserialize( $task_view_obj->callback_arguments ) );
		$task_view->set_priority( (int) $task_view_obj->priority );
		$task_view->set_bulk_size( (int) $task_view_obj->bulk_size );
		$task_view->set_status( new BTM_Task_Run_Status( $task_view_obj->status ) );
		$task_view->set_date_created_timestamp( strtotime( $task_view_obj->date_created ) );

		return $task_view;
	}

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
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $callback_action is not a string or is empty
	 */
	protected function set_callback_action( $callback_action ){

		if( ! is_string( $callback_action ) || empty( $callback_action ) ){
			throw new InvalidArgumentException( 'Argument $callback_action should be non empty string. Input was: ' . $callback_action );
		}

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
	/**
	 * @param mixed[] $callback_arguments
	 */
	protected function set_callback_arguments( array $callback_arguments ){
		// @todo: check arguments to be serializable,
		// log error otherwise?
		ksort( $callback_arguments );
		$this->callback_arguments = $callback_arguments;
	}

	/**
	 * @var int
	 */
	protected $priority;
	/**
	 * @return int
	 */
	public function get_priority(){
		return $this->priority;
	}
	/**
	 * @param int $priority
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $priority is not an int
	 *      or is not between BTM_Plugin_Options::get_max_priority() and BTM_Plugin_Options::get_min_priority()
	 */
	protected function set_priority( $priority ){
		$plugin_options = BTM_Plugin_Options::get_instance();
		$max_priority = $plugin_options->get_max_priority();
		$min_priority = $plugin_options->get_min_priority();
		if( ! is_int( $priority ) || $max_priority > $priority || $min_priority < $priority ){
			throw new InvalidArgumentException(
				'Argument $priority should be int between ' . $max_priority . ' and ' . $min_priority . '. Input was: ' . $priority
			);
		}

		$this->priority = $priority;
	}

	/**
	 * @var int
	 */
	protected $bulk_size;
	/**
	 * @return int
	 */
	public function get_bulk_size(){
		return $this->bulk_size;
	}
	/**
	 * @param int $bulk_size
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $bulk_size is not an int or is less than 0
	 */
	protected function set_bulk_size( $bulk_size ){
		if( ! is_int( $bulk_size ) || 0 > $bulk_size ){
			throw new InvalidArgumentException( 'Argument $bulk_size should be non negative int. Input was: ' . $bulk_size );
		}

		$this->bulk_size = $bulk_size;
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
	protected function set_status( BTM_Task_Run_Status $status ){
		$this->status = $status;
	}

	/**
	 * @var int
	 */
	protected $date_created_timestamp;
	/**
	 * @return int
	 */
	public function get_date_created_timestamp(){
		return $this->date_created_timestamp;
	}
	/**
	 * @param int $date_created_timestamp
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $date_created_timestamp is not an int
	 */
	protected function set_date_created_timestamp( $date_created_timestamp ){
		if( ! is_int( $date_created_timestamp ) ){
			throw new InvalidArgumentException( 'Argument $date_created should be int. Input was: ' . $date_created_timestamp );
		}

		$this->date_created_timestamp = $date_created_timestamp;
	}

}