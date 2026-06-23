<?php
/*
Plugin Name: Shortcode Redirect
Description: Redirects based on shortcode parameters and GET variables.
*/

function shortcode_redirect( $atts ) {
    $atts = shortcode_atts( array(
        'secret' => '',
        'redirect' => ''
    ), $atts );

    // Überprüfen, ob ein Benutzer angemeldet ist
    if ( is_user_logged_in() ) {
        // Benutzer ist angemeldet, keine Weiterleitung
        return;
    }

    // Überprüfen, ob ein $_GET['secret'] vorhanden ist und ob es mit dem Shortcode-Wert übereinstimmt
    if ( ! isset( $_GET['secret'] ) || $_GET['secret'] !== $atts['secret'] ) {
        // Weiterleitung auf die angegebene URL
        wp_redirect( $atts['redirect'] );
        exit;
    }

    // Wenn alles passt, kann hier beliebiger Inhalt ausgegeben werden

}
add_shortcode( 'redirect', 'shortcode_redirect' );
