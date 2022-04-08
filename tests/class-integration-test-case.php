<?php

namespace Notifier\Tests;

use Mantle\Testing\Framework_Test_Case;
use function Mantle\Testing\tests_add_filter;

abstract class Integration_Test_Case extends Framework_Test_Case {

	private static bool $installed = false;

	public static function setUpBeforeClass(): void {
		if( self::$installed ) {
			return;
		}

		\Mantle\Testing\install(
			function() {
				tests_add_filter(
					'muplugins_loaded',
					function() {
						require __DIR__ . '/../wp-updates-notifier.php';
					}
				);
			}
		);

		self::$installed = true;
	}

}
