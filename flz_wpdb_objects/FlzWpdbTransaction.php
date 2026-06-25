<?php

namespace flz_wpdb_objects;

require_once __DIR__ . '/FlzWpdbObjectsException.php';

use Throwable;

// Exception-Texte sind interne Logdaten; HTML-Escaping erfolgt erst an der UI-Grenze.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

/**
 * Führt mehrere Modelloperationen atomar in einer Datenbanktransaktion aus.
 *
 * Die Klasse enthält absichtlich keine Fachlogik. Aufrufer liefern einen
 * sprechenden Vorgangstext, der in jeder weitergereichten Fehlermeldung steht.
 */
final class FlzWpdbTransaction {

	/**
	 * @return mixed Rückgabewert des Callbacks.
	 * @throws FlzWpdbObjectsException Bei Start-, Callback-, Commit- oder Rollbackfehlern.
	 */
	public static function run( callable $callback, string $operation ) {
		self::execute_command( 'START TRANSACTION', 'Starten der Transaktion für ' . $operation );

		try {
			$result = $callback();
			self::execute_command( 'COMMIT', 'Abschließen der Transaktion für ' . $operation );

			return $result;
		} catch ( Throwable $error ) {
			try {
				self::execute_command( 'ROLLBACK', 'Zurückrollen der Transaktion für ' . $operation );
			} catch ( Throwable $rollback_error ) {
				throw new FlzWpdbObjectsException(
					sprintf(
						'Der Vorgang "%s" ist fehlgeschlagen (%s). Zusätzlich ist das Rollback fehlgeschlagen (%s).',
						$operation,
						$error->getMessage(),
						$rollback_error->getMessage()
					),
					0,
					$error
				);
			}

			throw FlzWpdbObjectsException::operation( $operation, 'Datenbanktransaktion', $error );
		}
	}

	/**
	 * Prüft ein festes Transaktionskommando auf Exceptions und false.
	 */
	private static function execute_command( string $sql, string $operation ): void {
		global $wpdb;

		try {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Festes internes Transaktionskommando ohne Nutzdaten.
			$result = $wpdb->query( $sql );
		} catch ( Throwable $error ) {
			throw FlzWpdbObjectsException::operation( $operation, 'WordPress-Datenbank', $error );
		}

		if ( $result === false ) {
			throw FlzWpdbObjectsException::database(
				$operation,
				'WordPress-Datenbank',
				(string) $wpdb->last_error
			);
		}
	}
}
