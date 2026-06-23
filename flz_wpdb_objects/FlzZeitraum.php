<?php

use flz_wpdb_objects\FlzWpdbObject;


class FlzZeitraum extends FlzWpdbObject {

	public int|null $beginn;
	public int|null $ende;

	public function __construct( array $data ) {
		parent::__construct( id: $data['id'] ?? null );
		$this->beginn      = (isset($data['beginn']) && $data['beginn']!='')  ?$data['beginn']: null;
		$this->ende = (isset($data['ende'])  && $data['ende']!='')?$data['ende']: null;
	}

	public function dauer(): array {
		$durations = $this->calculateDurations($this->ende - $this->beginn);

		return [
			"Dauer in Sekunden" => $this->ende - $this->beginn,
			"Sekunden" => $durations["seconds"],
			"Minuten" => $durations["minutes"],
			"Stunden" => $durations["hours"],
			"Tage" => $durations["days"]
		];
	}

	public function calculateDurations(int $totalSeconds): array {
		$seconds = $totalSeconds % 60;
		$totalMinutes = ($totalSeconds - $seconds) / 60;
		$minutes = $totalMinutes % 60;
		$totalHours = ($totalMinutes - $minutes) / 60;
		$hours = $totalHours % 24;
		$days = ($totalHours - $hours) / 24;

		return [
			"seconds" => $seconds,
			"minutes" => $minutes,
			"hours" => $hours,
			"days" => $days
		];
	}
}
