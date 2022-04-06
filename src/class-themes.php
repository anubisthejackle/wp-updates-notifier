<?php
/**
 * Class for the handling notifications around themes.
 *
 * @package wp-updates-notifier
 */

namespace Notifier;

class Themes {

	public static function boot(): void {
		$themes = new self();

		add_filter( 'sc_wpun_themes_need_update', [ $themes, 'check_themes_against_notified' ] );
	}

	/**
	 * Filter for removing themes from update list if already been notified about
	 *
	 * @param array $themes_need_update Array of themes that need an update.
	 *
	 * @return array $themes_need_update
	 */
	public function check_themes_against_notified( $themes_need_update ) {
		$settings = $this->get_set_options( self::OPT_FIELD ); // get settings
		foreach ( $themes_need_update as $key => $data ) { // loop through themes that need update
			if ( isset( $settings['notified']['theme'][ $key ] ) ) { // has this theme been notified before?
				if ( $data['new_version'] === $settings['notified']['theme'][ $key ] ) { // does this theme version match that of the one that's been notified?
					unset( $themes_need_update[ $key ] ); // don't notify this theme as has already been notified
				}
			}
		}
		return $themes_need_update;
	}

	/**
	 * Check to see if any theme updates.
	 *
	 * @return bool
	 */
	private function themes_update_check() {
		$settings = $this->get_set_options( self::OPT_FIELD ); // get settings
		do_action( 'wp_update_themes' ); // force WP to check for theme updates
		$update_themes = get_site_transient( 'update_themes' ); // get information of updates
		$theme_updates = []; // array to store all the theme updates
		if ( ! empty( $update_themes->response ) ) { // any theme updates available?
			$themes_need_update = $update_themes->response; // themes that need updating
			$active_theme       = [ ( (string) get_option( 'template' ) ) => [] ]; // find current theme that is active
			$themes_need_update = array_intersect_key( $themes_need_update, $active_theme ); // only keep theme that is active
			$themes_need_update = apply_filters( 'sc_wpun_themes_need_update', $themes_need_update ); // additional filtering of themes need update
			if ( count( $themes_need_update ) >= 1 ) { // any themes need updating after all the filtering gone on above?
				foreach ( $themes_need_update as $key => $data ) { // loop through the themes that need updating
					$theme_info      = wp_get_theme( $key ); // get theme info
					$theme_updates[] = [
						'name'        => $theme_info['Name'],
						'old_version' => $theme_info['Name'],
						'new_version' => $data['new_version'],
					];

					$settings['notified']['theme'][ $key ] = $data['new_version']; // set theme version we are notifying about
				}
				$this->get_set_options( self::OPT_FIELD, $settings ); // save settings
				return $theme_updates; // we have theme updates return the array of updates
			}
		} else {
			if ( 0 !== count( $settings['notified']['theme'] ) ) { // is there any theme notifications?
				$settings['notified']['theme'] = []; // set theme notifications to empty as all themes up-to-date
				$this->get_set_options( self::OPT_FIELD, $settings ); // save settings
			}
		}
		return false; // No theme updates so return false
	}

}
