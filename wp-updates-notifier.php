<?php
/**
 * Plugin Name: WP Updates Notifier
 * Plugin URI: https://github.com/alleyinteractive/wp-updates-notifier
 * Description: Sends email or Slack message to notify you if there are any updates for your WordPress site. Can notify about core, plugin and theme updates.
 * Contributors: l3rady, eherman24, alleyinteractive
 * Version: 2.0.0
 * Author: Alley Interactive
 * Author URI: https://alley.co/
 * Text Domain: wp-updates-notifier
 * Domain Path: /languages
 * License: GPL3+
 */

namespace Notifier;

require_once __DIR__ . '/vendor/autoload.php';

use Alley_Interactive\Autoloader\Autoloader;

Autoloader::generate(
	'Notifier\\',
	__DIR__ . '/src',
)->register();

/**
 * Boot the plugin once the rest of the plugins have been loaded.
 */
add_action(
	'plugins_loaded',
	function() {
		Loader::boot();
	}
);
