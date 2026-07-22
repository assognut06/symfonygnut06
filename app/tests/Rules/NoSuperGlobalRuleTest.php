<?php
namespace App\Tests\Rules;

use App\Rules\NoSuperGlobalRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoSuperGlobalRule>
 */
class NoSuperGlobalRuleTest extends RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		// getRule() method needs to return an instance of the tested rule
		return new NoSuperGlobalRule();
	}

	public function testRule(): void
	{
		// first argument: path to the example file that contains some errors that should be reported by MyRule
		// second argument: an array of expected errors,
		// each error consists of the asserted error message, and the asserted error file line
		$this->analyse([__DIR__ . '/data/SuperGlobalCall.php'], [
			[
				'Superglobal $_GET usage is not allowed.',
				17
			],
			[
				'Superglobal $_POST usage is not allowed.',
				18
			],
			[
				'Superglobal $_SERVER usage is not allowed.',
				19
			],
			[
				'Superglobal $_COOKIE usage is not allowed.',
				20
			],
			[
				'Superglobal $_FILES usage is not allowed.',
				21
			],
			[
				'Superglobal $_ENV usage is not allowed.',
				22
			],
			[
				'Superglobal $_REQUEST usage is not allowed.',
				23
			],
		]);

		// the test fails, if the expected error does not occur,
		// or if there are other errors reported beside the expected one
	}

}
