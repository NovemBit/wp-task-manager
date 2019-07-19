<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_View_Filter
 */
class BTM_View_Filter {
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
}