<?php
/*
Plugin Name: flz_wpdb_objects
Plugin URI: Deine Plugin-URI
Description: Stellt WordPress-Datenbankfunktionen als einfache CRUD-Modelle zur Verfügung
Version: 1.2.0
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
