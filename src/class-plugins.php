<?php
/**
 * Class for the handling notifications around plugins.
 *
 * @package wp-updates-notifier
 */

namespace Notifier;

class Plugins {

	/**
	 * Initialize the Plugins update checker.
	 */
	public static function boot(): void {
		$plugins = new self();

		add_filter( 'sc_wpun_plugins_need_update', [ $plugins, 'check_plugins_against_notified' ] );
		add_filter( 'sc_wpun_plugins_need_update', [ $plugins, 'check_plugins_against_disabled' ] );
	}

	/**
	 * Filter for removing plugins from update list if already been notified about
	 *
	 * @param array $plugins_need_update Array of plugins that need an update.
	 *
	 * @return array $plugins_need_update
	 */
	public function check_plugins_against_notified( $plugins_need_update ) {
		$settings = $this->get_set_options( self::OPT_FIELD ); // get settings
		foreach ( $plugins_need_update as $key => $data ) { // loop through plugins that need update
			if ( isset( $settings['notified']['plugin'][ $key ] ) ) { // has this plugin been notified before?
				if ( $data->new_version === $settings['notified']['plugin'][ $key ] ) { // does this plugin version match that of the one that's been notified?
					unset( $plugins_need_update[ $key ] ); // don't notify this plugin as has already been notified
				}
			}
		}
		return $plugins_need_update;
	}

	/**
	 * Filter for removing plugins from update list if they are disabled
	 *
	 * @param array $plugins_need_update Array of plugins that need an update.
	 *
	 * @return array $plugins_need_update
	 */
	public function check_plugins_against_disabled( $plugins_need_update ) {
		$settings = $this->get_set_options( self::OPT_FIELD ); // get settings
		foreach ( $plugins_need_update as $key => $data ) { // loop through plugins that need update
			if ( isset( $settings['disabled_plugins'][ $key ] ) ) { // is this plugin's notifications disabled
				unset( $plugins_need_update[ $key ] ); // don't notify this plugin
			}
		}
		return $plugins_need_update;
	}

	/**
	 * Check to see if any plugin updates.
	 *
	 * @return bool
	 */
	private function plugins_update_check() {
		$settings = $this->get_set_options( self::OPT_FIELD ); // get settings
		do_action( 'wp_update_plugins' ); // force WP to check plugins for updates
		$update_plugins = get_site_transient( 'update_plugins' ); // get information of updates
		$plugin_updates = []; // array to store all of the plugin updates
		if ( ! empty( $update_plugins->response ) ) { // any plugin updates available?
			$plugins_need_update = $update_plugins->response; // plugins that need updating
			$active_plugins      = array_flip( get_option( 'active_plugins' ) ); // find which plugins are active
			$plugins_need_update = array_intersect_key( $plugins_need_update, $active_plugins ); // only keep plugins that are active
			$plugins_need_update = apply_filters( 'sc_wpun_plugins_need_update', $plugins_need_update ); // additional filtering of plugins need update
			if ( count( $plugins_need_update ) >= 1 ) { // any plugins need updating after all the filtering gone on above?
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Required for plugin API
				require_once ABSPATH . WPINC . '/version.php'; // Required for WP core version
				foreach ( $plugins_need_update as $key => $data ) { // loop through the plugins that need updating
					$plugin_info      = get_plugin_data( WP_PLUGIN_DIR . '/' . $key ); // get local plugin info
					$plugin_updates[] = [
						'name'          => $plugin_info['Name'],
						'old_version'   => $plugin_info['Version'],
						'new_version'   => $data->new_version,
						'changelog_url' => $data->url . 'changelog/',
					];

					$settings['notified']['plugin'][ $key ] = $data->new_version; // set plugin version we are notifying about
				}
				$this->get_set_options( self::OPT_FIELD, $settings ); // save settings
				return $plugin_updates; // we have plugin updates return the array
			}
		} else {
			if ( 0 !== count( $settings['notified']['plugin'] ) ) { // is there any plugin notifications?
				$settings['notified']['plugin'] = []; // set plugin notifications to empty as all plugins up-to-date
				$this->get_set_options( self::OPT_FIELD, $settings ); // save settings
			}
		}
		return false; // No plugin updates so return false
	}
}
