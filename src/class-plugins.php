<?php
/**
 * Class for the handling notifications around plugins.
 *
 * @package wp-updates-notifier
 */

namespace Notifier;

/**
 * The Plugins class manages checking for Plugin updates.
 */
class Plugins {

	/**
	 * Initialize the Plugins update checker.
	 */
	public static function boot(): void {
		$plugins = new self();

		add_filter( 'sc_wpun_plugins_need_update', [ $plugins, 'check_plugins_against_notified' ], 10, 2 );
		add_filter( 'sc_wpun_plugins_need_update', [ $plugins, 'check_plugins_against_disabled' ] );
	}

	/**
	 * Filter for removing plugins from update list if already been notified about
	 *
	 * @param array $plugins_need_update Array of plugins that need an update.
	 *
	 * @return array $plugins_need_update
	 */
	public function check_plugins_against_notified( $plugins_need_update, $notified ) {
		foreach ( $plugins_need_update as $plugin => $data ) {
            if (! isset($notified['plugin'][ $plugin ])) {
                continue;
            }

			if ( $data->new_version === $notified['plugin'][ $plugin ] ) {
				unset( $plugins_need_update[ $plugin ] );
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
		$disabled_plugins = Settings::get_instance()->get( 'disabled_plugins' );

		foreach ( $plugins_need_update as $plugin => $data ) {
			if ( isset( $disabled_plugins[ $plugin ] ) ) {
				unset( $plugins_need_update[ $plugin ] );
			}
		}

		return $plugins_need_update;
	}

	/**
	 * Check to see if any plugin updates.
	 *
	 * @return false|array
	 */
	public function update_check() {

		$settings = Settings::get_instance();

		do_action( 'wp_update_plugins' );
		$update_plugins = get_site_transient( 'update_plugins' );
		$plugin_updates = [];
		$notified       = $settings->get( 'notified' );

		if ( empty( $update_plugins->response ) ) {
			$notified['plugin'] = [];
			$settings->set( 'notified', $notified );
			return false;
		}

		$plugins_need_update = $update_plugins->response;
		$active_plugins      = get_option( 'active_plugins' );

		if( ! is_array( $active_plugins ) ) {
			$active_plugins = [];
		}

		$active_plugins      = array_flip( $active_plugins );
		$plugins_need_update = array_intersect_key( $plugins_need_update, $active_plugins );

		$plugins_need_update = apply_filters( 'sc_wpun_plugins_need_update', $plugins_need_update, $notified );

        if( empty( $plugins_need_update ) ) {
            return false;
        }

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Required for plugin API.

		foreach ( $plugins_need_update as $plugin => $data ) {
			$plugin_info      = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$plugin_updates[] = [
				'name'          => $plugin_info['Name'],
				'old_version'   => $plugin_info['Version'],
				'new_version'   => $data->new_version,
				'changelog_url' => $data->url . 'changelog/',
			];

			$notified['plugin'][ $plugin ] = $data->new_version;
		}

		$settings->set( 'notified', $notified );
		return $plugin_updates;
	}
}
