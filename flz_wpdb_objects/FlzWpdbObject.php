<?php

namespace flz_wpdb_objects;

use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;


class FlzWpdbObject {

	public int|null $id;

	public function __construct( int|null $id = null ) {
		$this->id = $id;
	}

	/**
	 * Löscht die Tabelle des konkreten Modells.
	 *
	 * Berechtigungen und Nonces müssen vom aufrufenden Admin- oder
	 * Aktivierungscode geprüft werden; diese Modellklasse kennt keinen Request.
	 */
	public static function delete_table(): void {
		global $wpdb;
		static::beforeDelete();
		$table_name = static::validated_identifier( static::table_name() );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Tabellenname wurde mit validated_identifier() geprüft.
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
		static::afterDelete();
	}

	/**
	 * Leert die Tabelle und stellt eine vorübergehend deaktivierte
	 * Fremdschlüsselprüfung zuverlässig wieder her.
	 */
	public static function truncate_table( bool $disable_fk_check = false ): void {
		global $wpdb;
		static::beforeTruncate();
		if ( $disable_fk_check ) {
			$wpdb->query( "SET FOREIGN_KEY_CHECKS = 0; " );
		}
		try {
			$table_name = static::validated_identifier( static::table_name() );
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Tabellenname wurde mit validated_identifier() geprüft.
			$wpdb->query( "TRUNCATE $table_name" );
		} finally {
			if ( $disable_fk_check ) {
				$wpdb->query( "SET FOREIGN_KEY_CHECKS = 1; " );
			}
		}
		static::afterTruncate();
	}


	public static function get_by_id( int|null $id ): null|object {
		if ( $id === null ) {
			return null;
		}

		return static::get_by_fields( [ 'id' => $id ] );
	}

	/**
	 * Liefert das erste Objekt, das alle angegebenen Feldwerte erfüllt.
	 *
	 * Nullwerte werden als IS NULL und Arraywerte als IN-Bedingung behandelt.
	 * Spaltennamen werden validiert; sämtliche Nutzwerte gehen als Platzhalter
	 * an $wpdb->prepare().
	 */
	public static function get_by_fields( array $fields ): null|object {
		if ( empty( $fields ) ) {
			return null;
		}

		$objects = static::get_all_by( $fields, 'id', 'ASC', 1 );

		return $objects[0] ?? null;
	}

	/**
	 * Liefert Objekte anhand sicher aufgebauter Gleichheitsbedingungen.
	 *
	 * Mehrere Felder werden mit AND verknüpft. Die Sortierrichtung wird auf ASC
	 * oder DESC begrenzt. SQL-Operatoren können nicht von Aufrufern übergeben
	 * werden.
	 */
	public static function get_all_by(
		array $fields = [],
		string $order_by = 'id',
		string $order = 'ASC',
		int|null $limit = null
	): array {
		global $wpdb;

		$table    = static::validated_identifier( static::table_name() );
		$order_by = static::validated_identifier( $order_by );
		$order    = strtoupper( $order ) === 'DESC' ? 'DESC' : 'ASC';
		[ $where_sql, $values ] = static::build_where_clause( $fields );

		// Alle dynamischen SQL-Fragmente wurden zuvor intern validiert/erzeugt.
		$query = "SELECT * FROM $table$where_sql ORDER BY $order_by $order";
		if ( $limit !== null ) {
			$query .= ' LIMIT %d';
			$values[] = max( 0, $limit );
		}
		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query enthält nur validierte Fragmente und Platzhalter.
			$query = $wpdb->prepare( $query, $values );
		}

		$objects = [];
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query enthält keine ungeprüften SQL-Fragmente.
		foreach ( $wpdb->get_results( $query ) as $result ) {
			$objects[] = static::createObjectFromResult( $result );
		}

		return $objects;
	}


	/**
	 * Wandelt eine Datenbankzeile in das konkrete Modell um.
	 *
	 * Eine Spalte wie teacher_id wird nur dann als Beziehung aufgelöst, wenn
	 * das Modell eine passend typisierte Property $teacher besitzt und deren
	 * Klasse von FlzWpdbObject erbt. Unbekannte *_id-Spalten bleiben Rohwerte;
	 * dadurch führen Schemaerweiterungen nicht zu Reflection-Fatal-Errors.
	 */
	protected static function createObjectFromResult( object $result ): object {
		$cleaned_params = [];
		foreach ( get_object_vars( $result ) as $key => $value ) {
			$property_name = str_ends_with( $key, '_id' ) ? substr( $key, 0, -3 ) : null;
			$relation_class = $property_name ? static::relation_class_for_property( $property_name ) : null;

			if ( $relation_class === null ) {
				$cleaned_params[ $key ] = $value;
				continue;
			}

			$cleaned_params[ $property_name ] = $value === null
				? null
				: $relation_class::get_by_id( (int) $value );
		}

		return new static( $cleaned_params );
	}

	/**
	 * Ermittelt die Modellklasse einer typisierten Beziehungs-Property.
	 */
	private static function relation_class_for_property( string $property_name ): string|null {
		try {
			$property = new ReflectionProperty( static::class, $property_name );
		} catch ( ReflectionException ) {
			return null;
		}

		$type       = $property->getType();
		$type_names = [];
		if ( $type instanceof ReflectionNamedType ) {
			$type_names[] = $type;
		} elseif ( $type instanceof ReflectionUnionType ) {
			$type_names = $type->getTypes();
		}

		$relation_class = null;
		foreach ( $type_names as $named_type ) {
			if ( $named_type->isBuiltin() || $named_type->getName() === 'null' ) {
				continue;
			}
			if ( $relation_class !== null ) {
				// Mehrdeutige Union-Typen werden nicht automatisch aufgelöst.
				return null;
			}
			$relation_class = $named_type->getName();
		}

		if ( $relation_class === null || ! is_subclass_of( $relation_class, self::class ) ) {
			return null;
		}

		return $relation_class;
	}

	protected static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . strtolower( get_called_class() ) . 's';
	}

	private static function validated_identifier( string $identifier ): string {
		if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier ) ) {
			throw new \InvalidArgumentException( 'Ungültiger SQL-Bezeichner.' );
		}

		return $identifier;
	}

	private static function build_where_clause( array $fields ): array {
		if ( empty( $fields ) ) {
			return [ '', [] ];
		}

		$conditions = [];
		$values = [];
		foreach ( $fields as $column => $value ) {
			$column = static::validated_identifier( (string) $column );
			if ( $value === null ) {
				$conditions[] = "$column IS NULL";
				continue;
			}
			if ( is_array( $value ) ) {
				if ( empty( $value ) ) {
					$conditions[] = '1 = 0';
					continue;
				}
				$placeholders = [];
				$contains_null = false;
				foreach ( $value as $item ) {
					if ( $item === null ) {
						$contains_null = true;
						continue;
					}
					$placeholders[] = static::placeholder_for( $item );
					$values[] = $item;
				}
				if ( empty( $placeholders ) ) {
					$conditions[] = "$column IS NULL";
					continue;
				}
				$in_condition = "$column IN (" . implode( ', ', $placeholders ) . ')';
				$conditions[] = $contains_null ? "($in_condition OR $column IS NULL)" : $in_condition;
				continue;
			}

			$conditions[] = "$column = " . static::placeholder_for( $value );
			$values[] = $value;
		}

		return [ ' WHERE ' . implode( ' AND ', $conditions ), $values ];
	}

	private static function placeholder_for( $value ): string {
		if ( ! is_scalar( $value ) ) {
			throw new \InvalidArgumentException( 'Ungültiger SQL-Vergleichswert.' );
		}
		if ( is_bool( $value ) || is_int( $value ) ) {
			return '%d';
		}
		if ( is_float( $value ) ) {
			return '%f';
		}

		return '%s';
	}

	public static function create_table(): void {
		global $wpdb;
		static::beforeCreate();
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = static::validated_identifier( static::table_name() );
		$table_schema    = static::get_table_schema();

		if ( ! empty( $table_schema ) ) {
			// Das Schema stammt ausschließlich aus der konkreten Modellklasse.
			$sql = "CREATE TABLE $table_name $table_schema $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
		static::afterCreate();
	}

	protected static function get_table_schema(): string {
		return '';
	}

	public function save(): int|false {

		global $wpdb;
		$table_name = static::validated_identifier( static::table_name() );
		static::beforeInsert();
		$data = $this->prepareDataForSaving();
		if ( isset( $this->id ) && $this->id > 0 ) {
			$result = $wpdb->update( $table_name, $data, [ 'id' => $this->id ] );
		} else {

			$result = $wpdb->insert( $table_name, $data );
			if ( $result !== false ) {
				$this->id = $wpdb->insert_id;
				static::afterInsert();
			}
		}
		return $result;
	}

	protected function prepareDataForSaving(): array {
		// Unterklassen liefern hier ausschließlich bekannte Tabellenspalten.
		return [];
	}


	public function delete(): int|false {
		global $wpdb;

		static::beforeDelete();
		$result = $wpdb->delete(
			static::validated_identifier( static::table_name() ),
			array( 'id' => $this->id ),
			array( '%d' )
		);
		static::afterDelete();

		return $result;
	}

	public static function count_by( array $fields = [] ): int {
		global $wpdb;

		$table = static::validated_identifier( static::table_name() );
		[ $where_sql, $values ] = static::build_where_clause( $fields );
		// Alle dynamischen SQL-Fragmente wurden zuvor intern validiert/erzeugt.
		$query = "SELECT COUNT(*) FROM $table$where_sql";
		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query enthält nur validierte Fragmente und Platzhalter.
			$query = $wpdb->prepare( $query, $values );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query enthält keine ungeprüften SQL-Fragmente.
		return (int) $wpdb->get_var( $query );
	}

	protected static function beforeCreate(): void {
		// Optionaler Hook vor dem Anlegen einer Tabelle.
	}

	protected static function afterCreate(): void {
		// Optionaler Hook nach dem Anlegen einer Tabelle.
	}

	protected static function beforeInsert(): void {
		// Historischer Hook vor INSERT und UPDATE.
	}

	protected static function afterInsert(): void {
		// Optionaler Hook nach einem erfolgreichen INSERT.
	}

	protected static function beforeTruncate(): void {
		// Optionaler Hook vor dem Leeren einer Tabelle.
	}

	protected static function afterTruncate(): void {
		// Optionaler Hook nach dem Leeren einer Tabelle.
	}

	protected static function beforeDelete(): void {
		// Historischer Hook vor dem Löschen einer Zeile oder Tabelle.
	}

	protected static function afterDelete(): void {
		// Historischer Hook nach dem Löschen einer Zeile oder Tabelle.
	}

	public function getCsvLine(): string {
		$values    = [];
		$classVars = get_object_vars( $this );

		foreach ( $classVars as $value ) {
			if ( is_object( $value ) && method_exists( $value, 'getCsvLine' ) ) {
				$value = $value->getCsvLine();
			}

			$values[] = $value;
		}

		return implode( ';', $values );
	}

	/**
	 * Übernimmt skalare Formulardaten in öffentliche Modell-Properties.
	 *
	 * Die Datenbank-ID wird grundsätzlich nicht übernommen. Aufrufer sollten
	 * zusätzlich eine explizite Feldliste übergeben, damit interne Felder
	 * wie Status oder Bestätigungstoken nicht per Mass Assignment änderbar sind.
	 */
	public function assignPostData( array $data, array $allowed_fields ): void {
		foreach ( $data as $key => $value ) {
			if ( ! is_string( $key ) || $key === 'id' || ! property_exists( $this, $key ) ) {
				continue;
			}
			if ( ! in_array( $key, $allowed_fields, true ) ) {
				continue;
			}
			$property = new ReflectionProperty( $this, $key );
			if ( ! $property->isPublic() || $property->isStatic() || is_array( $value ) || is_object( $value ) ) {
				continue;
			}
		$type = $property->getType();
		if ( $type instanceof ReflectionNamedType && ! $type->isBuiltin() ) {
				continue;
			}
		if ( $type instanceof ReflectionUnionType ) {
				$contains_object_type = false;
				foreach ( $type->getTypes() as $named_type ) {
					if ( $named_type->getName() !== 'null' && ! $named_type->isBuiltin() ) {
						$contains_object_type = true;
						break;
					}
				}
				if ( $contains_object_type ) {
					continue;
				}
			}
			if ( $value === '' || $value === null ) {
				continue;
			}
			$this->$key = static::sanitize_property_value( $property, $key, $value );
		}
	}

	private static function sanitize_property_value( ReflectionProperty $property, string $key, $value ): mixed {
		$value = wp_unslash( $value );
		if ( $key === 'email' ) {
			return sanitize_email( $value );
		}

		$type      = $property->getType();
		$type_name = $type instanceof ReflectionNamedType ? $type->getName() : '';
		return match ( $type_name ) {
			'int' => (int) $value,
			'float' => (float) $value,
			'bool' => filter_var( $value, FILTER_VALIDATE_BOOLEAN ),
			default => sanitize_text_field( $value ),
		};
	}
}
