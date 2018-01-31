<?php
/*
Plugin Name: Solid Splash
Plugin URI: http://cameronkloot.com/plugins/solid-splash
Description: A solid splash page implementation
Version: 0.2.0
Author: Cameron Kloot
Author URI: http://cameronkloot.com
*/

if( !defined( 'ABSPATH' ) ) exit;

define( 'SOLID_PATH', plugin_dir_path( __FILE__ ) );
define( 'SOLID_URL', plugin_dir_url( __FILE__ ) );

require_once SOLID_PATH .  'admin/options.php';
require_once SOLID_PATH .  'admin/admin.php';
require_once SOLID_PATH .  'lib/solid-splash.php';
