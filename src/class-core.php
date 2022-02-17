<?php
/**
 * Class for the handling notifications around themes.
 */

namespace Notifier;

class Core {

    public static function boot(): void {
		$themes = new self();

    }

	/**
	 * Checks to see if any WP core updates
	 *
	 * @return array Array of core updates.
	 */
	private function core_update_check() {
		global $wp_version;
		$settings = $this->get_set_options( self::OPT_FIELD ); // get settings
		do_action( 'wp_version_check' ); // force WP to check its core for updates
		$update_core = get_site_transient( 'update_core' ); // get information of updates
		if ( 'upgrade' === $update_core->updates[0]->response ) { // is WP core update available?
			if ( $update_core->updates[0]->current !== $settings['notified']['core'] ) { // have we already notified about this version?
				require_once ABSPATH . WPINC . '/version.php'; // Including this because some plugins can mess with the real version stored in the DB.
				$new_core_ver                 = $update_core->updates[0]->current; // The new WP core version
				$old_core_ver                 = $wp_version; // the old WP core version
				$core_updates                 = array(
					'old_version' => $old_core_ver,
					'new_version' => $new_core_ver,
				);
				$settings['notified']['core'] = $new_core_ver; // set core version we are notifying about
				$this->get_set_options( self::OPT_FIELD, $settings ); // update settings
				return $core_updates; // we have updates so return the array of updates
			} else {
				return false; // There are updates but we have already notified in the past.
			}
		}
		$settings['notified']['core'] = ''; // no updates lets set this nothing
		$this->get_set_options( self::OPT_FIELD, $settings ); // update settings
		return false; // no updates return false
	}
}
