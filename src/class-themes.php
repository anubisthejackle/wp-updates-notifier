<?php
/**
 * Class for the handling notifications around themes.
 *
 * @package wp-updates-notifier
 */

namespace Notifier;

/**
 * The Themes class manages checking for theme updates.
 */
class Themes {

	/**
	 * Initialize the Themes update checker.
	 */
	public static function boot(): void {
		$themes = new self();

		add_filter( 'sc_wpun_themes_need_update', [ $themes, 'check_themes_against_notified' ], 10, 2 );
	}

	/**
	 * Filter for removing themes from update list if already been notified about
	 *
	 * @param array $themes_need_update Array of themes that need an update.
	 * @param array $notified The array of themes that have already had notifications sent.
	 * @return array $themes_need_update
	 */
	public function check_themes_against_notified( $themes_need_update, $notified ) {
		foreach ( $themes_need_update as $theme => $data ) {
            if (! isset($notified['theme'][ $theme ])) {
                continue;
            }

			if ( $data['new_version'] === $notified['theme'][ $theme ] ) {
				unset( $themes_need_update[ $theme ] );
			}
		}
		return $themes_need_update;
	}

	/**
	 * Check to see if any theme updates.
	 *
	 * @return false|array
	 */
	public function update_check() {

		$settings = Settings::get_instance();

		do_action( 'wp_update_themes' ); // force WP to check for theme updates.

		$update_themes = get_site_transient( 'update_themes' );
		$theme_updates = [];
		$notified      = $settings->get( 'notified' );

		if ( empty( $update_themes->response ) ) {
			$notified['theme'] = [];
			$settings->set( 'notified', $notified );
			return false;
		}

		$themes_need_update = $update_themes->response;
		$active_theme       = [ ( (string) get_option( 'template' ) ) => [] ];
		$themes_need_update = array_intersect_key( $themes_need_update, $active_theme );
		$themes_need_update = apply_filters( 'sc_wpun_themes_need_update', $themes_need_update, $notified );

		if ( empty( $themes_need_update ) ) {
			return false;
		}

		foreach ( $themes_need_update as $theme => $data ) {
			$theme_info      = wp_get_theme( $theme );
			$theme_updates[] = [
				'name'        => $theme_info['Name'],
				'old_version' => $theme_info['Name'],
				'new_version' => $data['new_version'],
			];

			$notified['theme'][ $theme ] = $data['new_version'];
		}
		$settings->set( 'notified', $notified );
		return $theme_updates;

	}

}
