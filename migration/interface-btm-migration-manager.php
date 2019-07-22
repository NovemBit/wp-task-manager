<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Migration class specifications
 *
 * Interface I_BTM_Migration
 */
interface I_BTM_Migration{
	public function up();
	public function down();
}