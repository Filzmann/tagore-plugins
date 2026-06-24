<?php
/*
Plugin Name: flz_wpdb_objects
Plugin URI: Deine Plugin-URI
Description: Stellt WordPress-Datenbankfunktionen als einfache CRUD-Modelle zur Verfügung
Version: 1.1.0
Author: Filzmann
Author URI: Deine Autor-URI
License: GPLv2 or later
*/

defined( 'ABSPATH' ) || exit;

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
 * @throws InvalidArgumentException Bei einem ungültigen Dateinamen oder Objekt.
 * @throws RuntimeException         Wenn das Uploadverzeichnis nicht nutzbar ist.
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

	$upload_dir = wp_upload_dir();
	if ( ! empty( $upload_dir['error'] ) ) {
		throw new RuntimeException( 'Das WordPress-Uploadverzeichnis ist nicht verfügbar.' );
	}

	$csv_file = trailingslashit( $upload_dir['basedir'] ) . $filename;
	$csv_url = trailingslashit( $upload_dir['baseurl'] ) . rawurlencode( $filename );
	$file_handle = fopen( $csv_file, 'wb' );
	if ( $file_handle === false ) {
		throw new RuntimeException( 'Die CSV-Datei konnte nicht geöffnet werden.' );
	}

	try {
		if ( $csv_head !== '' && fwrite( $file_handle, $csv_head ) === false ) {
			throw new RuntimeException( 'Der CSV-Kopf konnte nicht geschrieben werden.' );
		}
		foreach ( $objects as $object ) {
			if ( ! is_object( $object ) || ! method_exists( $object, 'getCsvLine' ) ) {
				throw new InvalidArgumentException( 'Jedes CSV-Objekt muss getCsvLine() bereitstellen.' );
			}
			if ( fwrite( $file_handle, $object->getCsvLine() . "\n" ) === false ) {
				throw new RuntimeException( 'Eine CSV-Zeile konnte nicht geschrieben werden.' );
			}
		}
	} finally {
		fclose( $file_handle );
	}

	return $csv_url;
}
