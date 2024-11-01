<?php
/*

Plugin Name: Wicked Block Builder
Plugin URI: https://wickedplugins.com/wicked-block-builder/
Description: Rapidy create custom blocks and patterns.
Version: 1.4.4
Author: Wicked Plugins
Author URI: https://wickedplugins.com/
Text Domain: wicked-block-builder
License: GPLv2 or later

Copyright 2021 Driven Development, LLC dba Wicked Plugins
(email : hello@wickedplugins.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

// Reminder: this file is removed during build in Wicked Block Builder Pro
register_activation_hook( __FILE__, array( 'Wicked_Block_Builder\Plugin', 'activate' ) );

// These classes shouldn't exist unless we're activating the core plugin with
// the pro plugin active.  Do a check just in case though to avoid an ugly PHP
// fatal error when trying to activate the core plugin in that scenario
if ( ! class_exists( 'Wicked_Block_Builder\Singleton' ) ) {
    require_once( dirname( __FILE__ ) . '/classes/class-singleton.php' );
}

if ( ! class_exists( 'Wicked_Block_Builder\Plugin' ) ) {
    require_once( dirname( __FILE__ ) . '/classes/class-plugin.php' );
}

use Wicked_Block_Builder\Plugin;

Plugin::get_instance();
