<?php

declare(strict_types=1);

$test = (string) getenv('TAGORE_COVERAGE_TEST');
if ('' === $test || !is_file($test) || !str_ends_with($test, '-smoke.php')) {
	throw new RuntimeException('Der Coverage-Wrapper benötigt einen vorhandenen *-smoke.php-Test.');
}

(static function (string $test_file): void {
	require $test_file;
})($test);
