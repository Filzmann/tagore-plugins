<?php

use flz_wpdb_objects\FlzWpdbObject;

// Exception-Texte sind interne Logdaten; HTML-Escaping erfolgt erst an der UI-Grenze.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

class FlzPerson extends FlzWpdbObject {

	public string|null $name;
	public string|null $firstName;
	public string|null $gender;
	public string|null $email;

	public function __construct( array $data ) {
		parent::__construct( id: $data['id'] ?? null );
		$this->name      = static::nullable_string( $data, 'name' );
		$this->firstName = static::nullable_string( $data, 'firstName' );
		$this->gender    = static::nullable_string( $data, 'gender' );
		$this->email     = static::nullable_string( $data, 'email' );
	}

	/**
	 * Sucht eine Person über eine normalisierte E-Mail-Adresse.
	 *
	 * @throws flz_wpdb_objects\FlzWpdbObjectsException Bei Datenbank- oder Hydrierungsfehlern.
	 */
	public static function get_by_email( string $email ): object|null {
		$email = sanitize_email( $email );
		if ( $email === '' ) {
			return null;
		}

		return static::get_by_fields( [ 'email' => $email ] );
	}
	public function get_gender_as_anrede(): string {
		return match ( $this->gender ) {
			'm' => 'Herr',
			'f' => 'Frau',
			default => '',
		};
	}

	/**
	 * Liest einen optionalen skalaren String aus Konstruktordaten.
	 */
	private static function nullable_string( array $data, string $key ): string|null {
		if ( ! isset( $data[ $key ] ) || $data[ $key ] === '' ) {
			return null;
		}
		if ( ! is_scalar( $data[ $key ] ) ) {
			throw new InvalidArgumentException(
				'Das Feld "' . $key . '" für ' . static::class . ' muss skalar sein; erhalten wurde '
				. get_debug_type( $data[ $key ] ) . '.'
			);
		}

		return (string) $data[ $key ];
	}
}

// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
