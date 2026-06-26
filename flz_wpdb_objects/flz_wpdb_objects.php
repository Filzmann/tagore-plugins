<?php
/*
Plugin Name: flz_wpdb_objects
Plugin URI: Deine Plugin-URI
Description: Stellt WordPress-Datenbankfunktionen als einfache CRUD-Modelle zur Verfügung
Version: 1.3.0
Author: Filzmann
Author URI: Deine Autor-URI
License: GPLv2 or later
*/

defined( 'ABSPATH' ) || exit;

// Exception-Texte sind interne Logdaten; HTML-Escaping erfolgt erst an der UI-Grenze.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

require_once __DIR__ . '/FlzWpdbObject.php';
require_once __DIR__ . '/FlzPerson.php';
require_once __DIR__ . '/FlzZeitraum.php';

// Die konkreten Fachplugins verwalten ihre eigenen Tabellen.
register_activation_hook( __FILE__, 'flz_wpdb_objects_activate' );
register_deactivation_hook( __FILE__, 'flz_wpdb_objects_deactivate' );

function flz_wpdb_objects_activate(): void {}

// Bei der Deaktivierung werden absichtlich keine Daten gelöscht.
function flz_wpdb_objects_deactivate(): void {}

/**
 * Verhindert, dass Tabellenkalkulationen CSV-Zellen als Formel ausführen.
 *
 * @param mixed $value Zellwert.
 */
function flz_wpdb_objects_csv_safe_cell($value): string {
	$value = (string) $value;

	return preg_match( '/^[=+\-@]/', $value ) ? "'" . $value : $value;
}

/**
 * Baut eine CSV-Datei im Speicher.
 *
 * @param array<int,mixed> $header Kopfzeile.
 * @param array<int,array<int,mixed>> $rows Datenzeilen.
 *
 * @throws flz_wpdb_objects\FlzWpdbObjectsException Bei Stream-/Schreibfehlern.
 */
function flz_wpdb_objects_build_csv_string(
	array $header,
	array $rows,
	string $delimiter = ';',
	bool $with_bom = true
): string {
	$out = fopen( 'php://temp', 'w+b' );
	if ( $out === false ) {
		throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
			'Öffnen des temporären CSV-Speichers',
			'php://temp'
		);
	}

	$pending_error = null;
	try {
		if ( $with_bom && fwrite( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) ) !== 3 ) {
			throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
				'Schreiben der CSV-Kodierungsmarkierung',
				'php://temp'
			);
		}

		if ( ! empty( $header ) && fputcsv( $out, array_map( 'flz_wpdb_objects_csv_safe_cell', $header ), $delimiter ) === false ) {
			throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
				'Schreiben der CSV-Kopfzeile',
				'php://temp'
			);
		}

		foreach ( $rows as $index => $row ) {
			if ( ! is_array( $row ) ) {
				throw new InvalidArgumentException(
					'Die CSV-Zeile mit Index "' . (string) $index . '" muss ein Array sein.'
				);
			}
			if ( fputcsv( $out, array_map( 'flz_wpdb_objects_csv_safe_cell', $row ), $delimiter ) === false ) {
				throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
					'Schreiben der CSV-Datenzeile mit Index ' . (string) $index,
					'php://temp'
				);
			}
		}

		if ( ! rewind( $out ) ) {
			throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
				'Zurücksetzen des CSV-Datenstroms',
				'php://temp'
			);
		}

		$csv = stream_get_contents( $out );
		if ( $csv === false ) {
			throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
				'Lesen des fertigen CSV-Datenstroms',
				'php://temp'
			);
		}
	} catch ( Throwable $error ) {
		$pending_error = $error;
	}

	if ( ! fclose( $out ) && $pending_error === null ) {
		throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
			'Schließen des temporären CSV-Speichers',
			'php://temp'
		);
	}

	if ( $pending_error !== null ) {
		throw $pending_error;
	}

	return $csv;
}

/**
 * Liest ein hochgeladenes CSV-Feld sicher in Zeilen ein.
 *
 * Nonce- und Berechtigungsprüfung bleiben bewusst Aufgabe des aufrufenden
 * Workflows. Diese Funktion kümmert sich nur um Dateiart, Öffnen/Schließen und
 * saubere Fehlerkontexte.
 *
 * @return array<int,array<int,string|null>>
 *
 * @throws RuntimeException|InvalidArgumentException|flz_wpdb_objects\FlzWpdbObjectsException
 */
function flz_wpdb_objects_read_uploaded_csv(
	string $file_field,
	string $operation,
	bool $skip_header = true,
	string $delimiter = ';'
): array {
	if ( '' === trim( $file_field ) ) {
		throw new InvalidArgumentException( 'Das Upload-Feld für den CSV-Import darf nicht leer sein.' );
	}

	// Nonce- und Berechtigungsprüfung erfolgen im aufrufenden Admin-Workflow,
	// damit dieser generische Dateihelper keine fachlichen Formularaktionen kennen muss.
	// phpcs:disable WordPress.Security.NonceVerification.Missing
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Dateiupload wird feldbezogen geprüft und der Name anschließend bereinigt.
	if ( ! isset( $_FILES[ $file_field ]['tmp_name'], $_FILES[ $file_field ]['name'] ) ) {
		throw new RuntimeException( 'Keine CSV-Datei hochgeladen.' );
	}

	$file_original_name = sanitize_file_name( wp_unslash( $_FILES[ $file_field ]['name'] ) );
	$file_path          = sanitize_text_field( wp_unslash( $_FILES[ $file_field ]['tmp_name'] ) );
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	if ( strtolower( pathinfo( $file_original_name, PATHINFO_EXTENSION ) ) !== 'csv' ) {
		throw new InvalidArgumentException( 'Das ist keine CSV-Datei.' );
	}

	error_clear_last();
	$file_handle = @fopen( $file_path, 'rb' );
	if ( $file_handle === false ) {
		$php_error = error_get_last();
		throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
			'Öffnen der CSV-Datei zum Lesen',
			$file_original_name,
			(string) ( $php_error['message'] ?? '' )
		);
	}

	$rows          = array();
	$pending_error = null;
	try {
		if ( $skip_header ) {
			fgetcsv( $file_handle, 0, $delimiter );
		}

		while ( ( $line = fgetcsv( $file_handle, 0, $delimiter ) ) !== false ) {
			$rows[] = $line;
		}
	} catch ( Throwable $error ) {
		$pending_error = flz_wpdb_objects\FlzWpdbObjectsException::operation(
			$operation,
			'CSV-Upload ' . $file_original_name,
			$error
		);
	}

	error_clear_last();
	if ( ! @fclose( $file_handle ) && $pending_error === null ) {
		$php_error = error_get_last();
		throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
			'Schließen der CSV-Datei',
			$file_original_name,
			(string) ( $php_error['message'] ?? '' )
		);
	}

	if ( $pending_error !== null ) {
		throw $pending_error;
	}

	return $rows;
}

/**
 * Schreibt Modellobjekte als CSV-Datei in das WordPress-Uploadverzeichnis.
 *
 * Der Dateiname wird auf einen einfachen CSV-Basisnamen begrenzt. Die Funktion
 * erzeugt bewusst nur die Datei; Zugriffsschutz und Nonce-Prüfung gehören in
 * den aufrufenden Download-Workflow.
 *
 * @throws InvalidArgumentException                          Bei einem ungültigen Dateinamen oder Objekt.
 * @throws flz_wpdb_objects\FlzWpdbObjectsException Bei Datei- oder Modellfehlern.
 */
function flz_wpdb_objects_create_csv(
	array $objects,
	string $filename = 'default.csv',
	string $csv_head = ''
): string {
	$filename = sanitize_file_name( wp_basename( $filename ) );
	if ( $filename === '' || strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) !== 'csv' ) {
		throw new InvalidArgumentException( 'Der Dateiname muss auf .csv enden.' );
	}

	try {
		$upload_dir = wp_upload_dir();
	} catch ( Throwable $error ) {
		throw flz_wpdb_objects\FlzWpdbObjectsException::operation(
			'Ermitteln des WordPress-Uploadverzeichnisses',
			'CSV-Export ' . $filename,
			$error
		);
	}
	if ( ! empty( $upload_dir['error'] ) ) {
		throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
			'Ermitteln des WordPress-Uploadverzeichnisses',
			$filename,
			(string) $upload_dir['error']
		);
	}
	if (
		! isset( $upload_dir['basedir'], $upload_dir['baseurl'] )
		|| ! is_string( $upload_dir['basedir'] )
		|| ! is_string( $upload_dir['baseurl'] )
	) {
		throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
			'Ermitteln des WordPress-Uploadverzeichnisses',
			$filename,
			'WordPress lieferte kein gültiges basedir/baseurl-Paar.'
		);
	}

	$csv_file = trailingslashit( $upload_dir['basedir'] ) . $filename;
	$csv_url = trailingslashit( $upload_dir['baseurl'] ) . rawurlencode( $filename );
	error_clear_last();
	$file_handle = @fopen( $csv_file, 'wb' );
	if ( $file_handle === false ) {
		$php_error = error_get_last();
		throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
			'Öffnen der CSV-Datei zum Schreiben',
			$csv_file,
			(string) ( $php_error['message'] ?? '' )
		);
	}

	$pending_error = null;
	try {
		if ( $csv_head !== '' ) {
			error_clear_last();
			if ( @fwrite( $file_handle, $csv_head ) === false ) {
				$php_error = error_get_last();
				throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
					'Schreiben des CSV-Kopfs',
					$csv_file,
					(string) ( $php_error['message'] ?? '' )
				);
			}
		}
		foreach ( $objects as $index => $object ) {
			if ( ! is_object( $object ) || ! method_exists( $object, 'getCsvLine' ) ) {
				throw new InvalidArgumentException(
					'Das CSV-Element mit Index "' . (string) $index . '" muss ein Objekt mit getCsvLine() sein.'
				);
			}
			try {
				$csv_line = $object->getCsvLine();
			} catch ( Throwable $error ) {
				throw flz_wpdb_objects\FlzWpdbObjectsException::operation(
					'Erzeugen der CSV-Zeile mit Index ' . (string) $index,
					get_class( $object ),
					$error
				);
			}
			error_clear_last();
			if ( @fwrite( $file_handle, $csv_line . "\n" ) === false ) {
				$php_error = error_get_last();
				throw flz_wpdb_objects\FlzWpdbObjectsException::file_system(
					'Schreiben der CSV-Zeile mit Index ' . (string) $index,
					$csv_file,
					(string) ( $php_error['message'] ?? '' )
				);
			}
		}
	} catch ( Throwable $error ) {
		$pending_error = $error;
	}

	error_clear_last();
	if ( ! @fclose( $file_handle ) ) {
		$php_error = error_get_last();
		$close_error = flz_wpdb_objects\FlzWpdbObjectsException::file_system(
			'Schließen der CSV-Datei',
			$csv_file,
			(string) ( $php_error['message'] ?? '' )
		);
		if ( $pending_error !== null ) {
			throw new flz_wpdb_objects\FlzWpdbObjectsException(
				$pending_error->getMessage() . ' Zusätzlich ist das Schließen der Datei fehlgeschlagen: '
				. $close_error->getMessage(),
				0,
				$pending_error
			);
		}
		throw $close_error;
	}
	if ( $pending_error !== null ) {
		throw $pending_error;
	}

	return $csv_url;
}

// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
