<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

final class BTM_Admin_Manager {
	// region Singleton

	/**
	 * @var bool
	 */
	private static $created = false;
	/**
	 * @throws LogicException
	 *      in the case this method called more than once
	 */
	public static function run(){
		if( false === self::$created ){
			new self();
		}else{
			throw new LogicException('BTM_Admin_Manager should only run once inside this plugin');
		}
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'on_hook_admin_menu_setup' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'on_hook_admin_enqueue_scripts' ) );
	}

	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * Should only be called from hook admin_enqueue_scripts
	 * @see https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
	 */
	function on_hook_admin_enqueue_scripts() {
        global $plugin_page;
        if( strpos( $plugin_page, 'btm' ) !== false ){
            $asset_version = BTM_Plugin_Options::get_instance()->get_asset_version();

            wp_enqueue_script( 'select2-script', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/select2.min.js', array( 'jquery' ), $asset_version , true );
            wp_enqueue_style( 'select2-style', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/css/select2.min.css', array(), $asset_version );

            wp_enqueue_script( 'btm-admin-scripts', plugin_dir_url( __DIR__ ) . 'assets/js/admin.js' , array( 'jquery' ), $asset_version , true );
            wp_localize_script( 'btm-admin-scripts', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
            wp_enqueue_style( 'btm-admin-style', plugin_dir_url( __DIR__ ) . 'assets/css/style.css', array(), $asset_version );
        }
	}

	// region Admin Menu

	/**
	 * Setup plugin admin menus
	 *
	 * Should only be called from hook admin_menu
	 * @see https://developer.wordpress.org/reference/hooks/admin_menu/
	 */
	public function on_hook_admin_menu_setup(){
		$menu_slug = BTM_Plugin_Options::get_instance()->get_admin_menu_slug();

		// Admin menu Background Task Manager page init
		add_menu_page(
			__( 'Background Task Manager', 'background_task_manager' ),
			__( 'BG Task Manager', 'background_task_manager' ),
			'manage_options',
			$menu_slug
		);

		new BTM_Admin_Task_Page_Table( $menu_slug );
		new BTM_Admin_Task_Single_Page();

		new BTM_Admin_Task_Bulk_Argument_Page_Table( $menu_slug );

		new BTM_Admin_Task_Run_Log_Page_Table( $menu_slug );

		new BTM_Admin_Notification_Page_Table( $menu_slug );
		new BTM_Admin_Notification_Single_Page();
	}

	// endregion
}