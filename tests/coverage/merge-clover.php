<?php

declare(strict_types=1);

if ($argc !== 3 && $argc !== 4) {
	fwrite(STDERR, "Aufruf: php merge-clover.php <Slug> <Clover-Verzeichnis> [Mindest-Coverage]\n");
	exit(2);
}

$slug      = $argv[1];
$directory = $argv[2];
$minimum   = null;

if ($argc === 4) {
	if (!is_numeric($argv[3]) || (float) $argv[3] < 0.0 || (float) $argv[3] > 100.0) {
		fwrite(STDERR, "Ungültige Mindest-Coverage: {$argv[3]}\n");
		exit(2);
	}
	$minimum = (float) $argv[3];
}

$reports = glob(rtrim($directory, '/') . '/*.xml') ?: array();
if (array() === $reports) {
	throw new RuntimeException("Keine Clover-Berichte für {$slug} gefunden.");
}

/** @var array<string, array<int, int>> $lines */
$lines = array();
foreach ($reports as $report) {
	$xml = simplexml_load_file($report);
	if (false === $xml) {
		throw new RuntimeException("Ungültiger Clover-Bericht: {$report}");
	}
	$files = $xml->xpath('/coverage/project/file | /coverage/project/package/file') ?: array();
	foreach ($files as $file) {
		$path = (string) $file['name'];
		foreach ($file->line as $line) {
			if ('stmt' !== (string) $line['type']) {
				continue;
			}
			$number                = (int) $line['num'];
			$count                 = (int) $line['count'];
			$lines[$path][$number] = max($lines[$path][$number] ?? 0, $count);
		}
	}
}

$executable = 0;
$covered    = 0;
foreach ($lines as $file_lines) {
	$executable += count($file_lines);
	foreach ($file_lines as $count) {
		if ($count > 0) {
			++$covered;
		}
	}
}

$percent = 0 === $executable ? 0.0 : ($covered / $executable) * 100;
printf("%s\t%d\t%d\t%.2f\n", $slug, $executable, $covered, $percent);

if (null !== $minimum && round($percent, 2) < $minimum) {
	fwrite(
		STDERR,
		sprintf("%s: %.2f %% liegt unter %.2f %%.\n", $slug, $percent, $minimum)
	);
	exit(1);
}
