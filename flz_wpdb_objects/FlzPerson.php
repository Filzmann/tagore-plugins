<?php

use flz_wpdb_objects\FlzWpdbObject;


class FlzPerson extends FlzWpdbObject {

	public string|null $name;
	public string|null $firstName;
	public string|null $gender;
	public string|null $email;

	public function __construct( array $data ) {
		parent::__construct( id: $data['id'] ?? null );
		$this->name      = isset( $data['name'] ) && $data['name'] !== '' ? $data['name'] : null;
		$this->firstName = isset( $data['firstName'] ) && $data['firstName'] !== '' ? $data['firstName'] : null;
		$this->gender    = isset( $data['gender'] ) && $data['gender'] !== '' ? $data['gender'] : null;
		$this->email     = isset( $data['email'] ) && $data['email'] !== '' ? $data['email'] : null;
	}

	/**
	 * Sucht eine Person über eine normalisierte E-Mail-Adresse.
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
}
