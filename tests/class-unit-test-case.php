<?php

namespace Notifier\Tests;

use PHPUnit\Framework\TestCase;

abstract class Unit_Test_Case extends TestCase {
	/**
	 * We want to run our unit tests in isolation, allowing us to separate them
	 * from the WordPress installation cluttered global state.
	 */
	public function __construct(...$args){
		parent::__construct( ...$args );

		// Discard all of the WordPress global state.
		$this->setPreserveGlobalState(false);

		// Load a new process to allow us to redefine functions.
		$this->setRunClassInSeparateProcess(true);
	}
}
