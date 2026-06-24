<?php

use flz_wpdb_objects\FlzWpdbObject;
use flz_wpdb_objects\FlzWpdbObjectsException;

// Exception-Texte sind interne Logdaten; HTML-Escaping erfolgt erst an der UI-Grenze.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

class FlzZeitraum extends FlzWpdbObject {

	public int|null $beginn;
	public int|null $ende;

	public function __construct( array $data = [] ) {
		parent::__construct( id: $data['id'] ?? null );
		$this->beginn = static::nullable_int( $data, 'beginn' );
		$this->ende   = static::nullable_int( $data, 'ende' );
	}

	/**
	 * Liefert die Dauer des vollständigen Zeitraums in mehreren Einheiten.
	 *
	 * @throws FlzWpdbObjectsException Wenn Beginn oder Ende ungültig sind.
	 */
	public function dauer(): array {
		if ( $this->beginn === null || $this->ende === null ) {
			throw FlzWpdbObjectsException::invalid_model_state(
				static::class,
				'Beginn und Ende müssen vor der Dauerberechnung gesetzt sein.'
			);
		}
		if ( $this->ende < $this->beginn ) {
			throw FlzWpdbObjectsException::invalid_model_state(
				static::class,
				'Das Ende darf nicht vor dem Beginn liegen.'
			);
		}

		$total_seconds = $this->ende - $this->beginn;
		$durations = $this->calculateDurations( $total_seconds );

		return [
			'Dauer in Sekunden' => $total_seconds,
			'Sekunden'          => $durations['seconds'],
			'Minuten'           => $durations['minutes'],
			'Stunden'           => $durations['hours'],
			'Tage'              => $durations['days'],
		];
	}

	/**
	 * Zerlegt eine nichtnegative Sekundenzahl in Tage, Stunden, Minuten und Sekunden.
	 *
	 * @throws InvalidArgumentException Bei einer negativen Dauer.
	 */
	public function calculateDurations( int $totalSeconds ): array {
		if ( $totalSeconds < 0 ) {
			throw new InvalidArgumentException( 'Die Dauer darf nicht negativ sein.' );
		}

		$seconds = $totalSeconds % 60;
		$totalMinutes = intdiv( $totalSeconds, 60 );
		$minutes = $totalMinutes % 60;
		$totalHours = intdiv( $totalMinutes, 60 );
		$hours = $totalHours % 24;
		$days = intdiv( $totalHours, 24 );

		return [
			'seconds' => $seconds,
			'minutes' => $minutes,
			'hours'   => $hours,
			'days'    => $days,
		];
	}

	private static function nullable_int( array $data, string $key ): int|null {
		if ( ! isset( $data[ $key ] ) || $data[ $key ] === '' ) {
			return null;
		}
		if ( filter_var( $data[ $key ], FILTER_VALIDATE_INT ) === false ) {
			throw new InvalidArgumentException(
				'Das Feld "' . $key . '" für ' . static::class . ' muss eine Ganzzahl sein.'
			);
		}

		return (int) $data[ $key ];
	}
}

// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
