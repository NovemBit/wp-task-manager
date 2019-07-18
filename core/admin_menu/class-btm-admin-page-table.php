<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

// WP_List_Table may not be loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

abstract class BTM_Admin_Page_Table extends WP_List_Table{
	const BULK_ACTION_DELETE = 'bulk-delete';

	// region Singleton

	/**
	 * Should be rewritten by children
	 *
	 * @var bool
	 */
	protected static $created = false;

	public function __construct( $page_parent_slug ) {
		if( false !== static::$created ){
			throw new LogicException('BTM_Admin_Page_Table each child instance should be created only once.');
		}
		static::$created = true;

		parent::__construct( array(
				'singular'  => $this->get_entity_singular_name(),  //singular name of the listed records
				'plural'    => $this->get_entity_plural_name(),    //plural name of the listed records
				'ajax'      => false                               //does this table support ajax?
			)
		);

		$this->set_page_parent_slug( $page_parent_slug );
		$this->add_submenu_page();
	}
	protected function __clone() {}
	protected function __wakeup() {}

	// endregion

	// region Page

	/**
	 * @var string
	 */
	protected $page_parent_slug;
	/**
	 * @return string
	 */
	public function get_page_parent_slug(){
		return $this->page_parent_slug;
	}
	/**
	 * @param string $page_parent_slug
	 */
	protected function set_page_parent_slug( $page_parent_slug ){
		if( ! is_string( $page_parent_slug ) || false !== strpos( $page_parent_slug, ' ' ) ){
			throw new InvalidArgumentException(
				__( 'Argument $page_parent_slug should be valid slug(string)', 'background_task_manager' )
			);
		}

		$this->page_parent_slug = $page_parent_slug;
	}

	abstract protected function add_submenu_page();

	// endregion

	// region Table

	/**
	 * @return string
	 *
	 * @throws Exception
	 */
	abstract protected function get_entity_singular_name();
	/**
	 * @return string
	 */
	abstract protected function get_entity_plural_name();

	// endregion
}