<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_View_Filter
 */
class BTM_Task_View_Filter {
	/**
	 * @var string
	 */
	protected $order_by = '';
	/**
	 * @return string
	 */
	public function get_order_by() {
		return $this->order_by;
	}
	/**
	 * @param string $order_by
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $order_by is not a valid column name(string) and is not empty
	 */
	public function set_order_by( $order_by ) {
		if( ! is_string( $order_by ) ){
			throw new InvalidArgumentException('Argument $order_by should be valid column name(string) or empty. Input was: ' . $order_by );
		}

		$this->order_by = $order_by;
	}

	/**
	 * @var string
	 */
	protected $order = '';
	/**
	 * @return string
	 */
	public function get_order() {
		return $this->order;
	}
	/**
	 * @param string $order
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $order is not "ASC" or "DESC"
	 */
	public function set_order( $order ) {
		if( ! is_string( $order ) || ('ASC' !== strtoupper( $order ) && 'DESC' !== strtoupper( $order ) ) ){
			throw new InvalidArgumentException('Argument $order should be "ASC" or "DESC". Input was: ' . $order );
		}

		$this->order = $order;
	}

	/**
	 * @return bool
	 */
	public function has_order_params(){
		if( 0 < strlen( $this->order_by ) && 0 < strlen( $this->order ) ){
			return true;
		}

		return false;
	}

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

	/**
	 * @var string
	 */
	protected $callback = '';
	/**
	 * @return string
	 */
	public function get_callback(){
		return $this->callback;
	}
	/**
	 * @param string $callback
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $callback is not a string
	 */
	public function set_callback( $callback ){
		if( ! is_string( $callback ) ){
			throw new InvalidArgumentException('Argument $callback should be string. Input was: ' . $callback );
		}

		$this->callback = $callback;
	}
	/**
	 * @return bool
	 */
	public function has_callback(){
		if( 0 < strlen( $this->callback ) ){
			return true;
		}

		return false;
	}

	/**
	 * @var string
	 */
	protected $date_start = '';
	/**
	 * @return string
	 */
	public function get_date_start(){
		return $this->date_start;
	}
	/**
	 * @return string
	 */
	public function get_date_start_short(){
		if( ! $this->has_date_start() ){
			return '';
		}else{
			return explode( ' ', $this->date_start )[0];
		}
	}
	/**
	 * @param string $date_start should be Y-m-d or Y-m-d H:i:s
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $date_start is not a valid date(string)
	 */
	public function set_date_start( $date_start ){
		if( false === strpos( $date_start, ' ' ) ){
			$date_start .= ' 00:00:00';
		}
		if( '' !== $date_start && ! $this->is_valid_date( $date_start ) ){
			throw new InvalidArgumentException('Argument $date_start should be valid date. Input was: ' . $date_start );
		}

		$this->date_start = $date_start;
	}
	/**
	 * @return bool
	 */
	public function has_date_start(){
		if( 0 < strlen( $this->date_start ) ){
			return true;
		}

		return false;
	}

	/**
	 * @var string
	 */
	protected $date_end = '';
	/**
	 * @return string
	 */
	public function get_date_end(){
		return $this->date_end;
	}
	/**
	 * @return string
	 */
	public function get_date_end_short(){
		if( ! $this->has_date_end() ){
			return '';
		}else{
			return explode( ' ', $this->date_end )[0];
		}
	}
	/**
	 * @param string $date_end should be Y-m-d or Y-m-d H:i:s
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $date_end is not a valid date(string)
	 */
	public function set_date_end( $date_end ){
		if( false === strpos( $date_end, ' ' ) ){
			$date_end .= ' 00:00:00';
		}
		if( '' !== $date_end &&  ! $this->is_valid_date( $date_end ) ){
			throw new InvalidArgumentException('Argument $date_end should be valid date. Input was: ' . $date_end );
		}

		$this->date_end = $date_end;
	}
	/**
	 * @return bool
	 */
	public function has_date_end(){
		if( 0 < strlen( $this->date_end ) ){
			return true;
		}

		return false;
	}

	/**
	 * @param string $date_string
	 *
	 * @return bool
	 */
	protected function is_valid_date( $date_string ){

		$format = 'Y-m-d H:i:s';
		$date = DateTime::createFromFormat($format, $date_string);
		return $date && $date->format($format) == $date_string;
	}

	/**
	 * @var bool
	 */
	protected $show_system = false;
	/**
	 * @return bool
	 */
	public function show_system(){
		return $this->show_system;
	}
	/**
	 * @param bool $show_system
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $show_system is not a bool
	 */
	public function set_show_system( $show_system ){
		if( ! is_bool( $show_system ) ){
			throw new InvalidArgumentException('Argument $show_system should be valid bool. Input was: ' . $show_system);
		}

		$this->show_system = $show_system;
	}

	/**
	 * @var int
	 */
	protected $items_per_page;
	/**
	 * @return int
	 */
	public function get_items_per_page(){
		return $this->items_per_page;
	}
	/**
	 * @param int $items_per_page
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $items_per_page is not a positive int
	 */
	public function set_items_per_page( $items_per_page ){
		if( ! is_int( $items_per_page ) && 0 >= $items_per_page ){
			throw new  InvalidArgumentException( 'Argument $items_per_page should be positive int. Input was: ' . $items_per_page );
		}

		$this->items_per_page = $items_per_page;
	}

	/**
	 * @var int
	 */
	protected $current_page = 1;
	/**
	 * @return int
	 */
	public function get_current_page(){
		return $this->current_page;
	}
	/**
	 * @param int $current_page
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $current_page is not a positive int
	 */
	public function set_current_page( $current_page ){
		if( ! is_int( $current_page ) && 0 >= $current_page ){
			throw new  InvalidArgumentException( 'Argument $current_page should be positive int. Input was: ' . $current_page );
		}

		$this->current_page = $current_page;
	}

	/**
	 * @return string
	 */
	public function get_hash(){
		$hash = '';

		if( $this->has_search() ){
			$hash .= $this->get_search();
		}else{
			$hash .= '#';
		}

		if( $this->has_status() ){
			$hash .= $this->get_status();
		}else{
			$hash .= '#';
		}

		if( $this->has_callback() ){
			$hash .= $this->get_callback();
		}else{
			$hash .= '#';
		}

		if( $this->has_date_start() ){
			$hash .= $this->get_date_start();
		}else{
			$hash .= '#';
		}

		if( $this->has_date_end() ){
			$hash .= $this->get_date_end();
		}else{
			$hash .= '#';
		}

		if( $this->show_system() ){
			$hash .= '1';
		}else{
			$hash .= '#';
		}

		return md5( $hash );
	}
}