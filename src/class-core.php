<?php
/**
 * Class for the handling notifications around themes.
 *
 * @package wp-updates-notifier
 */

namespace Notifier;

/**
 * The Core class manages checking for WordPress Core updates.
 */
class Core {

	/**
	 * Initialize the WordPress core update checker.
	 */
	public static function boot(): void {

	}

	/**
	 * Checks to see if any WP core updates
	 *
	 * @return false|array Array of core updates, false if no updates are found.
	 */
	public function update_check() {
		global $wp_version;

		$settings = Settings::get_instance();

		if ( ! (bool) $settings->get( 'notify_automatic' ) ) {
			return false;
		}

		do_action( 'wp_version_check' );

		$update_core = get_site_transient( 'update_core' );
		$notified    = $settings->get( 'notified' );

		if ( 'upgrade' !== $update_core->updates[0]->response ) {
			$notified['core'] = '';
			$settings->set( 'notified', $notified );
			return false;
		}

		if ( $update_core->updates[0]->current === $notified['core'] ) {
			return false;
		}

		require_once ABSPATH . WPINC . '/version.php';
		$new_core_ver     = $update_core->updates[0]->current;
		$old_core_ver     = $wp_version;
		$notified['core'] = $new_core_ver;

		$settings->set( 'notified', $notified );

		return [
			'old_version' => $old_core_ver,
			'new_version' => $new_core_ver,
		];
	}
}
