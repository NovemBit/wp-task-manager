<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

class BTM_Task_System_Simple extends BTM_Task_Simple{
	protected $is_system = true;
}