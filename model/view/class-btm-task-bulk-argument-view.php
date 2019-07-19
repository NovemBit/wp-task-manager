<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Bulk_Argument_View
 */
class BTM_Task_Bulk_Argument_View {
	/**
	 * @param stdClass $task_bulk_argument_view_obj
	 *
	 * @return BTM_Task_Bulk_Argument_View
	 */
	public static function create_from_db_obj( stdClass $task_bulk_argument_view_obj ){
		$task_view = new static();

		$task_view->set_id( (int) $task_bulk_argument_view_obj->id );
		$task_view->set_task_id( (int) $task_bulk_argument_view_obj->task_id );
		$task_view->set_callback_action( $task_bulk_argument_view_obj->callback_action );
		$task_view->set_callback_arguments( unserialize( $task_bulk_argument_view_obj->callback_arguments ) );
		$task_view->set_priority( (int) $task_bulk_argument_view_obj->priority );
		$task_view->set_status( new BTM_Task_Run_Status( $task_bulk_argument_view_obj->status ) );
		$task_view->set_date_created_timestamp( strtotime( $task_bulk_argument_view_obj->date_created ) );

		return $task_view;
	}

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
	 */
	protected function set_id( $id ){
		if( ! is_int( $id ) || 0 >= $id ){
			throw new InvalidArgumentException( 'Argument $id should be positive int. Input was: ' . $id );
		}

		$this->id = $id;
	}

	/**
	 * @var int
	 */
	protected $task_id;
	/**
	 * @return int
	 */
	public function get_task_id(){
		return $this->task_id;
	}
	/**
	 * @param int $task_id
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $task_id is not a positive int
	 */
	protected function set_task_id( $task_id ){
		if( ! is_int( $task_id ) || 0 >= $task_id ){
			throw new InvalidArgumentException( 'Argument $task_id should be positive int. Input was: ' . $task_id );
		}

		$this->task_id = $task_id;
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