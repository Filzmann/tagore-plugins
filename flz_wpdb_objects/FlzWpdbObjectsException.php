<?php

namespace flz_wpdb_objects;

use RuntimeException;
use Throwable;

/**
 * Einheitliche Laufzeitexception für das Shared-Plugin.
 *
 * Die Factory-Methoden ergänzen jede technische Ursache um den betroffenen
 * Vorgang und Kontext. Eine ursprüngliche Exception bleibt über getPrevious()
 * erhalten und kann von aufrufendem Code aussagekräftig protokolliert werden.
 */
class FlzWpdbObjectsException extends RuntimeException {

	public static function database(
		string $operation,
		string $table,
		string $database_error = ''
	): self {
		$database_error = self::normalize_message( $database_error );
		if ( $database_error === '' ) {
			$database_error = 'Die Datenbank lieferte keine Detailmeldung.';
		}

		return new self(
			sprintf(
				'Datenbankfehler beim Vorgang "%s" für Tabelle "%s": %s',
				$operation,
				$table,
				$database_error
			)
		);
	}

	public static function operation(
		string $operation,
		string $context,
		Throwable $previous
	): self {
		$previous_message = self::normalize_message( $previous->getMessage() );
		if ( $previous_message === '' ) {
			$previous_message = 'Die ursprüngliche Exception vom Typ ' . get_class( $previous ) . ' enthält keine Meldung.';
		}

		return new self(
			sprintf(
				'Fehler beim Vorgang "%s" (%s): %s',
				$operation,
				$context,
				$previous_message
			),
			0,
			$previous
		);
	}

	public static function relation_not_found(
		string $model_class,
		string $property_name,
		string $relation_class,
		int $relation_id
	): self {
		return new self(
			sprintf(
				'Die Beziehung "%s" des Modells "%s" konnte nicht geladen werden: '
				. 'In "%s" existiert kein Datensatz mit ID %d.',
				$property_name,
				$model_class,
				$relation_class,
				$relation_id
			)
		);
	}

	public static function invalid_model_state(
		string $model_class,
		string $details
	): self {
		return new self(
			sprintf(
				'Ungültiger Zustand des Modells "%s": %s',
				$model_class,
				self::normalize_message( $details )
			)
		);
	}

	public static function file_system(
		string $operation,
		string $path,
		string $details = '',
		Throwable|null $previous = null
	): self {
		$details = self::normalize_message( $details );
		if ( $details === '' ) {
			$details = 'Das Dateisystem lieferte keine Detailmeldung.';
		}

		return new self(
			sprintf(
				'Dateisystemfehler beim Vorgang "%s" für "%s": %s',
				$operation,
				$path,
				$details
			),
			0,
			$previous
		);
	}

	/**
	 * Beschreibt eine Exception samt Ursachekette in einer einzeiligen Logform.
	 *
	 * UI-Code darf diese Meldung nicht direkt ausgeben: Sie enthält bewusst
	 * technische Details. Für Besucher und Redakteure bleibt die App zuständig
	 * und gibt dort sichere, fachliche Meldungen aus.
	 */
	public static function describe_chain( Throwable $error ): string {
		$messages = array();
		$current  = $error;

		do {
			$message = self::normalize_message( $current->getMessage() );
			if ( $message === '' ) {
				$message = 'keine Detailmeldung';
			}

			$messages[] = get_class( $current ) . ': ' . $message;
			$current    = $current->getPrevious();
		} while ( $current instanceof Throwable );

		return implode( ' <- ', $messages );
	}

	/**
	 * Protokolliert eine technische Ursache mit Plugin- und Vorgangskontext.
	 *
	 * Die Funktion zentralisiert nur das technische Logformat. Die sichtbare
	 * Fehlerbehandlung bleibt in den Fachplugins, damit jede App passende und
	 * sichere Hinweise an Admins oder Besucher geben kann.
	 */
	public static function log_error( Throwable $error, string $plugin_slug, string $context ): void {
		$plugin_slug = trim( preg_replace( '/[^a-z0-9_\\-]/i', '', $plugin_slug ) ?? $plugin_slug );
		if ( $plugin_slug === '' ) {
			$plugin_slug = 'flz';
		}

		$context = self::normalize_message( $context );
		if ( $context === '' ) {
			$context = 'Unbenannter Vorgang';
		}

		error_log( '[' . $plugin_slug . '] ' . $context . ' | ' . self::describe_chain( $error ) );
	}

	private static function normalize_message( string $message ): string {
		return trim( preg_replace( '/\s+/', ' ', $message ) ?? $message );
	}
}
