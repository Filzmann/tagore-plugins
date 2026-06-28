<?php
/*
Plugin Name: FLZ Shortcode Redirect
Description: Redirects based on shortcode parameters and GET variables.
Version: 1.1.0
*/

defined( 'ABSPATH' ) || exit;

/**
 * Protokolliert Redirect-Probleme mit Shared-Logging, falls verfügbar.
 */
function flz_shortcode_redirect_log( string $message ): void {
	$error = new RuntimeException( $message );

	if ( class_exists( 'flz_wpdb_objects\\FlzWpdbObjectsException' ) ) {
		flz_wpdb_objects\FlzWpdbObjectsException::log_error(
			$error,
			'flz_shortcode_redirect',
			'Ausführen des Redirect-Shortcodes'
		);
		return;
	}

	error_log( '[flz_shortcode_redirect] ' . $message );
}

/**
 * Leitet nicht angemeldete Besucher ohne passendes Secret sicher weiter.
 *
 * Der historische Shortcode-Name [redirect] bleibt aus Kompatibilitätsgründen
 * bestehen; nur die PHP-Funktion folgt jetzt der flz_-Namenskonvention.
 */
function flz_shortcode_redirect( $atts ): string {
	$atts = shortcode_atts(
		array(
			'secret' => '',
			'redirect' => '',
		),
		(array) $atts,
		'redirect'
	);

	if ( is_user_logged_in() ) {
		return '';
	}

	$expected_secret = (string) $atts['secret'];
	$provided_secret = isset( $_GET['secret'] )
		? sanitize_text_field( wp_unslash( $_GET['secret'] ) )
		: '';
	if ( $expected_secret !== '' && hash_equals( $expected_secret, $provided_secret ) ) {
		return '';
	}

	$redirect_url = esc_url_raw( (string) $atts['redirect'] );
	if ( $redirect_url === '' ) {
		flz_shortcode_redirect_log( 'Weiterleitung abgebrochen: Im Shortcode fehlt eine gültige Ziel-URL.' );
		return '<p>' . esc_html__( 'Die Weiterleitung ist nicht konfiguriert.', 'flz-shortcode-redirect' ) . '</p>';
	}

	if ( headers_sent( $source_file, $source_line ) ) {
		flz_shortcode_redirect_log(
			'Weiterleitung nicht mehr möglich: HTTP-Header wurden bereits in '
			. (string) $source_file . ':' . (string) $source_line . ' gesendet.'
		);
		return '<p><a href="' . esc_url( $redirect_url ) . '">'
			. esc_html__( 'Zur Zielseite', 'flz-shortcode-redirect' )
			. '</a></p>';
	}

	if ( ! wp_safe_redirect( $redirect_url ) ) {
		flz_shortcode_redirect_log( 'wp_safe_redirect() hat die Ziel-URL abgelehnt.' );
		return '<p>' . esc_html__( 'Die Weiterleitung konnte nicht ausgeführt werden.', 'flz-shortcode-redirect' ) . '</p>';
	}
	exit;
}

add_shortcode( 'redirect', 'flz_shortcode_redirect' );
