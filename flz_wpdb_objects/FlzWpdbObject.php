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
		$wpdb->query( "DROP TABLE IF EXISTS " . static::table_name() );
		static::afterDelete();
	}

	public static function truncate_table( $disable_fk_check = false ): void {
		global $wpdb;
		static::beforeTruncate();
		if ( $disable_fk_check ) {
			$wpdb->query( "SET FOREIGN_KEY_CHECKS = 0; " );
		}
		$wpdb->query( "TRUNCATE " . static::table_name() );
		if ( $disable_fk_check ) {
			$wpdb->query( "SET FOREIGN_KEY_CHECKS = 1; " );
		}
		static::afterTruncate();
	}


	public static function get_by_id( int|null $id ): null|object {
		if ( $id == null ) {
			return null;
		}
		$where = " id = $id";
		return static::get_by_where( $where );
	}

	public static function get_by_where( $where ): null|object {

		global $wpdb;
		$table  = static::table_name();
		$query  = "SELECT * FROM $table ";
		$query  .= "WHERE ".$where;
		//echo $query;
		$result = $wpdb->get_row( $query );
		if(!$result) return null;
		//debug($result);

		return static::createObjectFromResult( $result );
	}

	public static function get_all( $where = '', $order_by = 'id' ): array {
		global $wpdb;
		$table = static::table_name();
		$query = "SELECT * FROM $table";
		if ( $where != '' ) {
			$query .= " WHERE $where";
		}
		$query .= " ORDER BY $order_by";
		$results = $wpdb->get_results( $query );
		$objects = [];
		foreach ( $results as $result ) {
			$object = static::createObjectFromResult( $result );


			$objects[] = $object;
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

	public static function create_table(): void {
		//write_log("in create table");
		global $wpdb;
		static::beforeCreate();
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = static::table_name();
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
		$table_name = static::table_name();
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
			static::table_name(),
			array( 'id' => $this->id ),
			array( '%d' )
		);
		$this->afterDelete();

		return $result;
	}

	public static function count($where=null): int {
		global $wpdb;
		$table = static::table_name();
		$query  = "SELECT * FROM $table ";
		$query  .= $where? "WHERE ".$where:'';
		$wpdb->get_results( $query  );
//return $query;
		return $wpdb->num_rows;
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

	public function assignPostData(array $data): void
	{
		foreach ($data as $key => $value) {

			if (empty($value) or $value=='')	continue;
			$this->$key =  match ( $key ) {
				'id' => intval( $value ),
				'email' => sanitize_email( $value ),

				default => sanitize_text_field( $value ),
			};
		}
	}
}