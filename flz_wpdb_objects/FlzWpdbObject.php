<?php

namespace flz_wpdb_objects;

use ReflectionException;
use ReflectionProperty;


class FlzWpdbObject {

	public int|null $id;

	public function __construct( $id = null ) {
		$this->id = $id;
	}

	public static function delete_table(): void {
		global $wpdb;
		static::beforeDelete();
		$table_name = static::validated_identifier( static::table_name() );
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
		static::afterDelete();
	}

	public static function truncate_table( $disable_fk_check = false ): void {
		global $wpdb;
		static::beforeTruncate();
		if ( $disable_fk_check ) {
			$wpdb->query( "SET FOREIGN_KEY_CHECKS = 0; " );
		}
		$table_name = static::validated_identifier( static::table_name() );
		$wpdb->query( "TRUNCATE $table_name" );
		if ( $disable_fk_check ) {
			$wpdb->query( "SET FOREIGN_KEY_CHECKS = 1; " );
		}
		static::afterTruncate();
	}


	public static function get_by_id( int|null $id ): null|object {
		if ( $id == null ) {
			return null;
		}

		return static::get_by_fields( [ 'id' => $id ] );
	}

	/**
	 * Retrieves one object using equality comparisons joined with AND.
	 *
	 * Null values are translated to IS NULL. Array values are translated to
	 * IN clauses. Column names are restricted to SQL identifiers and all values
	 * are passed through $wpdb->prepare().
	 */
	public static function get_by_fields( array $fields ): null|object {
		if ( empty( $fields ) ) {
			return null;
		}

		$objects = static::get_all_by( $fields, 'id', 'ASC', 1 );

		return $objects[0] ?? null;
	}

	/**
	 * Safe list query for equality, NULL and IN conditions.
	 */
	public static function get_all_by(
		array $fields = [],
		string $order_by = 'id',
		string $order = 'ASC',
		int|null $limit = null
	): array {
		global $wpdb;

		$table = static::validated_identifier( static::table_name() );
		$order_by = static::validated_identifier( $order_by );
		$order = strtoupper( $order ) === 'DESC' ? 'DESC' : 'ASC';
		[ $where_sql, $values ] = static::build_where_clause( $fields );

		$query = "SELECT * FROM $table$where_sql ORDER BY $order_by $order";
		if ( $limit !== null ) {
			$query .= ' LIMIT %d';
			$values[] = max( 0, $limit );
		}
		if ( ! empty( $values ) ) {
			$query = $wpdb->prepare( $query, $values );
		}

		$objects = [];
		foreach ( $wpdb->get_results( $query ) as $result ) {
			$objects[] = static::createObjectFromResult( $result );
		}

		return $objects;
	}


	protected static function createObjectFromResult( $result ): object {

		$params         = get_object_vars( $result );
		$cleaned_params = array();
		foreach ( $params as $key => $val ) {
			$split_key = explode( "_", $key );
			if ( isset( $split_key[1] ) && $split_key[1] == "id" ) {
				try {
					// Use reflection to get the type of this property
					$reflectionProperty = new ReflectionProperty( static::class, $split_key[0] );
					$type               = $reflectionProperty->getType()->getName();
					// Ensure the class name is capitalized
					$class_name = ucfirst( $type );

					// Ensure that this class exists
					if ( class_exists( $class_name )) {
						$val = $class_name::get_by_id( $val );

					}
					$cleaned_params[ $split_key[0] ] = $val;
				} catch ( ReflectionException $e ) {
					// Handle the exception here
					// You can log it or throw it further
					write_log( $e );
					$val = null;
					//throw $e;
				}
			} else {
				$cleaned_params[ $key ] = $val;
			}

		}

		return new static( $cleaned_params );

	}

	protected static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . strtolower( get_called_class() ) . 's';
	}

	private static function validated_identifier( string $identifier ): string {
		if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier ) ) {
			throw new \InvalidArgumentException( 'Invalid SQL identifier.' );
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
				foreach ( $value as $item ) {
					$placeholders[] = static::placeholder_for( $item );
					$values[] = $item;
				}
				$conditions[] = "$column IN (" . implode( ', ', $placeholders ) . ')';
				continue;
			}

			$conditions[] = "$column = " . static::placeholder_for( $value );
			$values[] = $value;
		}

		return [ ' WHERE ' . implode( ' AND ', $conditions ), $values ];
	}

	private static function placeholder_for( $value ): string {
		if ( ! is_scalar( $value ) && $value !== null ) {
			throw new \InvalidArgumentException( 'Invalid SQL comparison value.' );
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
		//write_log("in create table");
		global $wpdb;
		static::beforeCreate();
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = static::validated_identifier( static::table_name() );
		$table_schema    = static::get_table_schema();

		if ( ! empty( $table_schema ) ) {
			$sql = "CREATE TABLE $table_name $table_schema $charset_collate;";
			//write_log( $sql);
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
				$this->afterInsert();
			}
		}
		return $result;
	}

	protected function prepareDataForSaving(): array {
		// Implement this method in each subclass to prepare the data for saving.
		return [];
	}


	public function delete(): int|false {
		global $wpdb;

		$this->beforeDelete();
		$result = $wpdb->delete(
			static::validated_identifier( static::table_name() ),
			array( 'id' => $this->id ),
			array( '%d' )
		);
		$this->afterDelete();

		return $result;
	}

	public static function count_by( array $fields = [] ): int {
		global $wpdb;

		$table = static::validated_identifier( static::table_name() );
		[ $where_sql, $values ] = static::build_where_clause( $fields );
		$query = "SELECT COUNT(*) FROM $table$where_sql";
		if ( ! empty( $values ) ) {
			$query = $wpdb->prepare( $query, $values );
		}

		return (int) $wpdb->get_var( $query );
	}

	protected static function beforeCreate() {
		// Implement this method in each subclass for actions to perform before Creation.
	}

	protected static function afterCreate() {
		// Implement this method in each subclass for actions to perform after creation.
	}

	protected static function beforeInsert() {
		// Implement this method in each subclass for actions to perform before insertion.
	}

	protected static function afterInsert() {
		// Implement this method in each subclass for actions to perform after insertion.
	}

	protected static function beforeTruncate() {
		// Implement this method in each subclass for actions to perform before insertion.
	}

	protected static function afterTruncate() {
		// Implement this method in each subclass for actions to perform after insertion.
	}

	protected static function beforeDelete() {
		// Implement this method in each subclass for actions to perform before deletion.
	}

	protected static function afterDelete() {
		// Implement this method in each subclass for actions to perform after deletion.
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

	public function assignPostData(array $data, array|null $allowed_fields = null): void
	{
		foreach ($data as $key => $value) {
			if ( ! is_string( $key ) || ! property_exists( $this, $key ) ) {
				continue;
			}
			if ( $allowed_fields !== null && ! in_array( $key, $allowed_fields, true ) ) {
				continue;
			}
			$property = new ReflectionProperty( $this, $key );
			if ( ! $property->isPublic() || $property->isStatic() || is_array( $value ) || is_object( $value ) ) {
				continue;
			}
			$type = $property->getType();
			if ( $type instanceof \ReflectionNamedType && ! $type->isBuiltin() ) {
				continue;
			}
			if ( $type instanceof \ReflectionUnionType ) {
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

	private static function sanitize_property_value( ReflectionProperty $property, string $key, $value ) {
		if ( $key === 'email' ) {
			return sanitize_email( $value );
		}

		$type = $property->getType();
		$type_name = $type instanceof \ReflectionNamedType ? $type->getName() : '';
		return match ( $type_name ) {
			'int' => intval( $value ),
			'float' => floatval( $value ),
			'bool' => filter_var( $value, FILTER_VALIDATE_BOOLEAN ),
			default => sanitize_text_field( $value ),
		};
	}
}
