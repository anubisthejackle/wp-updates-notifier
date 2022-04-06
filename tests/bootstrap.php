<?php
/**
 * The bootstrap file for testing the plugin.
 *
 * @package wp-updates-notifier
 */

$wp_updates_notifier_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $wp_updates_notifier_tests_dir ) {
	$wp_updates_notifier_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $wp_updates_notifier_tests_dir . '/includes/functions.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable

/**
 * Manually load the plugin for tests.
 */
function wp_updates_notifier_test_loader() {
	require dirname( dirname( __FILE__ ) ) . '/class-sc-wp-updates-notifier.php';
}
tests_add_filter( 'muplugins_loaded', 'wp_updates_notifier_test_loader' );

require $wp_updates_notifier_tests_dir . '/includes/bootstrap.php'; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
