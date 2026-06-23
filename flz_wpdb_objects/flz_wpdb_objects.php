<?php
/*
Plugin Name: flz_wpdb_objects
Plugin URI: Deine Plugin-URI
Description: Stellt die wordpress Datenbankfunktionen als einfache CRUD-Modelle zur Verfügung
Version: 1.0
Author: Filzmann
Author URI: Deine Autor-URI
License: GPLv2 or later
*/


// Aktivierungshook, um die Tabellen zu erstellen
register_activation_hook( __FILE__, 'flz_wpdb_objects_activate' );
// Deaktivierungshook, um die Daten zu löschen
register_deactivation_hook( __FILE__, 'flz_wpdb_objects_deactivate' );

// Aktivierung/Deaktivierung-Modul einbinden
function flz_wpdb_objects_activate(): void {}

// Funktion zum Löschen der Tabellen beim Deaktivieren des Plugins
function flz_wpdb_objects_deactivate(): void {}


function debug($var, $text=''): void {
	if(is_array($var) or is_object($var))
		echo "<pre> ".$text .print_r($var,true)."</pre>";
	else echo $text."= ".$var;

}
if (!function_exists('write_log')) {

	function write_log($log): void {
		if (true === WP_DEBUG) {
			if (is_array($log) || is_object($log)) {
				error_log(print_r($log, true));
			} else {
				error_log($log);
			}
		}
	}

}


//Todo Move to FlzWpdbObject.php
function createCsv( array $appointments, $filename='default.csv', $csvHead='' ): string {
	$upload_dir = wp_upload_dir();
	$csvFile = $upload_dir['basedir'] . '/'.$filename;
	$csvUrl = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $csvFile);

	$fileHandle = fopen($csvFile, 'w');

	fwrite($fileHandle, $csvHead);
	foreach($appointments as $appointment) {
		$csvLine = $appointment->getCsvLine()."\n";
		fwrite($fileHandle, $csvLine);
	}

	fclose($fileHandle);

	return $csvUrl;

}

