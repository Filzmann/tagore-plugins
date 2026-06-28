<?php

namespace flz_wpdb_objects;

require_once __DIR__ . '/FlzWpdbObjectsException.php';
require_once __DIR__ . '/FlzWpdbTransaction.php';

use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Throwable;

// Exception-Texte sind interne Logdaten; HTML-Escaping erfolgt erst an der UI-Grenze.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

class FlzWpdbObject {

	public int|null $id;

	public function __construct( $id = null ) {
		$this->id = static::normalize_id( $id, 'Konstruktor' );
	}

	/**
	 * Löscht die Tabelle des konkreten Modells.
	 *
	 * Berechtigungen und Nonces müssen vom aufrufenden Admin- oder
	 * Aktivierungscode geprüft werden; diese Modellklasse kennt keinen Request.
	 *
	 * @throws FlzWpdbObjectsException Bei Datenbank- oder Hook-Fehlern.
	 */
	public static function delete_table(): void {
		global $wpdb;
		$table_name = static::validated_identifier( static::table_name() );
		static::run_hook( 'beforeDelete', 'Vorbereitung vor dem Löschen der Tabelle' );
		static::execute_database_call(
			static function () use ( $wpdb, $table_name ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Tabellenname wurde mit validated_identifier() geprüft.
				return $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
			},
			'Löschen der Tabelle',
			$table_name
		);
		static::run_hook( 'afterDelete', 'Nachbereitung nach dem Löschen der Tabelle' );
	}

	/**
	 * Leert die Tabelle und stellt eine vorübergehend deaktivierte
	 * Fremdschlüsselprüfung zuverlässig wieder her.
	 *
	 * @throws FlzWpdbObjectsException Bei Datenbank- oder Hook-Fehlern.
	 */
	public static function truncate_table( bool $disable_fk_check = false ): void {
		global $wpdb;
		$table_name = static::validated_identifier( static::table_name() );
		static::run_hook( 'beforeTruncate', 'Vorbereitung vor dem Leeren der Tabelle' );

		if ( $disable_fk_check ) {
			static::execute_database_call(
				static fn() => $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' ),
				'Deaktivieren der Fremdschlüsselprüfung',
				$table_name
			);
		}

		$truncate_error = null;
		try {
			static::execute_database_call(
				static function () use ( $wpdb, $table_name ) {
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Tabellenname wurde mit validated_identifier() geprüft.
					return $wpdb->query( "TRUNCATE $table_name" );
				},
				'Leeren der Tabelle',
				$table_name
			);
		} catch ( Throwable $error ) {
			$truncate_error = $error;
		}

		$restore_error = null;
		if ( $disable_fk_check ) {
			try {
				static::execute_database_call(
					static fn() => $wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' ),
					'Wiederherstellen der Fremdschlüsselprüfung',
					$table_name
				);
			} catch ( Throwable $error ) {
				$restore_error = $error;
			}
		}

		if ( $truncate_error !== null && $restore_error !== null ) {
			throw new FlzWpdbObjectsException(
				sprintf(
					'Das Leeren der Tabelle "%s" ist fehlgeschlagen (%s). '
					. 'Zusätzlich konnte die Fremdschlüsselprüfung nicht wiederhergestellt werden (%s).',
					$table_name,
					$truncate_error->getMessage(),
					$restore_error->getMessage()
				),
				0,
				$truncate_error
			);
		}
		if ( $truncate_error !== null ) {
			throw $truncate_error;
		}
		if ( $restore_error !== null ) {
			throw $restore_error;
		}

		static::run_hook( 'afterTruncate', 'Nachbereitung nach dem Leeren der Tabelle' );
	}


	public static function get_by_id( $id ): null|object {
		$id = static::normalize_id( $id, 'get_by_id()' );
		if ( $id === null || $id <= 0 ) {
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
	 *
	 * @throws FlzWpdbObjectsException Bei Datenbank- oder Hydrierungsfehlern.
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
	 *
	 * @throws FlzWpdbObjectsException Bei Datenbank- oder Hydrierungsfehlern.
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
			$query = static::prepare_query( $query, $values, 'Vorbereiten der Datensatzabfrage', $table );
		}

		try {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query enthält keine ungeprüften SQL-Fragmente.
			$results = $wpdb->get_results( $query );
		} catch ( Throwable $error ) {
			throw FlzWpdbObjectsException::operation(
				'Laden von Datensätzen',
				'Tabelle ' . $table,
				$error
			);
		}
		if ( ! is_array( $results ) || static::has_database_error() ) {
			throw static::database_exception( 'Laden von Datensätzen', $table );
		}

		$objects = [];
		foreach ( $results as $result ) {
			if ( ! is_object( $result ) ) {
				throw FlzWpdbObjectsException::invalid_model_state(
					static::class,
					'Die Datenbankabfrage lieferte eine Zeile in einem unerwarteten Format.'
				);
			}
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

			if ( $value === null ) {
				$cleaned_params[ $property_name ] = null;
				continue;
			}

			$relation_id = (int) $value;
			try {
				$relation = $relation_class::get_by_id( $relation_id );
			} catch ( Throwable $error ) {
				throw FlzWpdbObjectsException::operation(
					'Auflösen der Beziehung ' . $property_name,
					static::class . ' -> ' . $relation_class . ' (ID ' . $relation_id . ')',
					$error
				);
			}
			if ( $relation === null ) {
				throw FlzWpdbObjectsException::relation_not_found(
					static::class,
					$property_name,
					$relation_class,
					$relation_id
				);
			}
			$cleaned_params[ $property_name ] = $relation;
		}

		try {
			return new static( $cleaned_params );
		} catch ( Throwable $error ) {
			throw FlzWpdbObjectsException::operation(
				'Hydrieren einer Datenbankzeile',
				static::class,
				$error
			);
		}
	}

	/**
	 * Ermittelt die Modellklasse einer typisierten Beziehungs-Property.
	 */
	private static function relation_class_for_property( string $property_name ): string|null {
		if ( ! property_exists( static::class, $property_name ) ) {
			return null;
		}

		try {
			$property = new ReflectionProperty( static::class, $property_name );
		} catch ( ReflectionException $error ) {
			throw FlzWpdbObjectsException::operation(
				'Lesen der Beziehungsmetadaten',
				static::class . '::$' . $property_name,
				$error
			);
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
				throw FlzWpdbObjectsException::invalid_model_state(
					static::class,
					'Die Beziehungs-Property $' . $property_name . ' besitzt einen mehrdeutigen Union-Typ.'
				);
			}
			$relation_class = $named_type->getName();
		}

		if ( $relation_class === null ) {
			return null;
		}
		if ( ! is_subclass_of( $relation_class, self::class ) ) {
			throw FlzWpdbObjectsException::invalid_model_state(
				static::class,
				'Die Beziehungs-Property $' . $property_name . ' verweist nicht auf ein FlzWpdbObject-Modell.'
			);
		}

		return $relation_class;
	}

	/**
	 * Führt eine fachlich aufgebaute, vorbereitete Leseabfrage aus.
	 *
	 * Diese Methode ist für die wenigen Fälle gedacht, in denen ein Modell mehr
	 * braucht als einfache Gleichheitsabfragen, z. B. kontrollierte JOINs oder
	 * fachliche Sortierungen. SQL-Fragmente dürfen weiterhin nur aus dem Modell
	 * stammen; Nutzwerte werden ausschließlich über Platzhalter übergeben.
	 *
	 * @throws FlzWpdbObjectsException Bei Datenbank- oder Vorbereitungsfehlern.
	 */
	protected static function query_rows(
		string $sql,
		array $values,
		string $operation,
		string $output = OBJECT
	): array {
		global $wpdb;

		$table = static::validated_identifier( static::table_name() );
		$query = empty( $values )
			? $sql
			: static::prepare_query( $sql, $values, 'Vorbereiten der Datenbankabfrage', $table );

		try {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query wurde fachlich kontrolliert aufgebaut und vorbereitet.
			$rows = $wpdb->get_results( $query, $output );
		} catch ( Throwable $error ) {
			throw FlzWpdbObjectsException::operation( $operation, 'Tabelle ' . $table, $error );
		}

		if ( ! is_array( $rows ) || static::has_database_error() ) {
			throw static::database_exception( $operation, $table );
		}

		return $rows;
	}

	/**
	 * Führt eine Custom-Leseabfrage aus und hydriert Ergebniszeilen als Modell.
	 *
	 * @throws FlzWpdbObjectsException Bei Datenbank- oder Hydrierungsfehlern.
	 */
	protected static function query_models( string $sql, array $values, string $operation ): array {
		$models = [];
		foreach ( static::query_rows( $sql, $values, $operation ) as $row ) {
			if ( ! is_object( $row ) ) {
				throw FlzWpdbObjectsException::invalid_model_state(
					static::class,
					'Eine Datenbankzeile besitzt nicht das erwartete Objektformat.'
				);
			}
			$models[] = static::createObjectFromResult( $row );
		}

		return $models;
	}

	protected static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . static::class_to_table_base( get_called_class() ) . 's';
	}

	private static function class_to_table_base( string $class_name ): string {
		$parts = explode( '\\', $class_name );
		$short_name = (string) end( $parts );
		$snake = preg_replace( '/(?<=[a-z0-9])(?=[A-Z])/', '_', $short_name );
		$snake = preg_replace( '/(?<=[A-Z])(?=[A-Z][a-z])/', '_', (string) $snake );
		$snake = strtolower( (string) $snake );
		$snake = preg_replace( '/_+/', '_', $snake );

		return trim( (string) $snake, '_' );
	}

	private static function validated_identifier( string $identifier ): string {
		if ( ! preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					'Der SQL-Bezeichner "%s" des Modells "%s" ist ungültig.',
					$identifier,
					static::class
				)
			);
		}

		return $identifier;
	}

	private static function normalize_id( $id, string $context ): int|null {
		if ( $id === null ) {
			return null;
		}
		if ( ! is_int( $id ) && ! ( is_string( $id ) && ctype_digit( $id ) ) ) {
			throw new \InvalidArgumentException(
				sprintf(
					'Die Modell-ID für "%s" in "%s" muss eine Ganzzahl oder null sein; erhalten wurde %s.',
					static::class,
					$context,
					get_debug_type( $id )
				)
			);
		}

		return (int) $id;
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
			throw new \InvalidArgumentException(
				'Datenbank-Vergleichswerte müssen skalar sein; erhalten wurde ' . get_debug_type( $value ) . '.'
			);
		}
		if ( is_bool( $value ) || is_int( $value ) ) {
			return '%d';
		}
		if ( is_float( $value ) ) {
			return '%f';
		}

		return '%s';
	}

	/**
	 * Legt die Tabelle des konkreten Modells an oder aktualisiert ihr Schema.
	 *
	 * @throws FlzWpdbObjectsException Bei Schema-, Datenbank- oder Hook-Fehlern.
	 */
	public static function create_table(): void {
		global $wpdb;
		$table_name = static::validated_identifier( static::table_name() );
		static::run_hook( 'beforeCreate', 'Vorbereitung vor dem Anlegen der Tabelle' );

		try {
			$table_schema = static::get_table_schema();
		} catch ( Throwable $error ) {
			throw FlzWpdbObjectsException::operation(
				'Ermitteln des Tabellenschemas',
				static::class,
				$error
			);
		}
		if ( trim( $table_schema ) === '' ) {
			throw FlzWpdbObjectsException::invalid_model_state(
				static::class,
				'Für das Anlegen der Tabelle wurde kein Tabellenschema definiert.'
			);
		}

		$charset_collate = $wpdb->get_charset_collate();
		// Das Schema stammt ausschließlich aus der konkreten Modellklasse.
		$sql = "CREATE TABLE $table_name $table_schema $charset_collate;";
		try {
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		} catch ( Throwable $error ) {
			throw FlzWpdbObjectsException::operation(
				'Anlegen oder Aktualisieren der Tabelle',
				$table_name,
				$error
			);
		}
		if ( static::has_database_error() ) {
			throw static::database_exception( 'Anlegen oder Aktualisieren der Tabelle', $table_name );
		}

		static::run_hook( 'afterCreate', 'Nachbereitung nach dem Anlegen der Tabelle' );
	}

	protected static function get_table_schema(): string {
		return '';
	}

	/**
	 * Speichert das Modell und liefert die Anzahl betroffener Zeilen.
	 *
	 * @throws FlzWpdbObjectsException Bei Modell-, Datenbank- oder Hook-Fehlern.
	 */
	public function save(): int {
		global $wpdb;
		$table_name = static::validated_identifier( static::table_name() );
		try {
			$data = $this->prepareDataForSaving();
		} catch ( Throwable $error ) {
			throw FlzWpdbObjectsException::operation(
				'Vorbereiten der Speicherdaten',
				static::class,
				$error
			);
		}
		if ( empty( $data ) ) {
			throw FlzWpdbObjectsException::invalid_model_state(
				static::class,
				'Die Speicherdaten enthalten keine Tabellenspalten.'
			);
		}
		foreach ( array_keys( $data ) as $column ) {
			static::validated_identifier( (string) $column );
		}

		static::run_hook( 'beforeInsert', 'Vorbereitung vor dem Speichern des Datensatzes' );
		if ( isset( $this->id ) && $this->id > 0 ) {
			$result = static::execute_database_call(
				fn() => $wpdb->update( $table_name, $data, [ 'id' => $this->id ] ),
				'Aktualisieren des Datensatzes mit ID ' . $this->id,
				$table_name
			);

			return (int) $result;
		}

		$result = static::execute_database_call(
			static fn() => $wpdb->insert( $table_name, $data ),
			'Einfügen eines neuen Datensatzes',
			$table_name
		);
		$this->id = (int) $wpdb->insert_id;
		if ( $this->id <= 0 ) {
			throw FlzWpdbObjectsException::invalid_model_state(
				static::class,
				'Der Datensatz wurde eingefügt, aber $wpdb->insert_id enthält keine positive ID.'
			);
		}
		static::run_hook(
			'afterInsert',
			'Nachbereitung nach dem Einfügen des Datensatzes mit ID ' . $this->id
		);

		return (int) $result;
	}

	protected function prepareDataForSaving(): array {
		// Unterklassen liefern hier ausschließlich bekannte Tabellenspalten.
		return [];
	}

	/**
	 * Liefert die letzte Insert-ID für historische statische afterInsert-Hooks.
	 *
	 * Neue Fachlogik sollte bevorzugt mit der Objekt-ID nach save() arbeiten.
	 * Für bestehende statische Hooks bleibt der direkte Zugriff auf $wpdb aber
	 * hier zentral geprüft und mit einer aussagekräftigen Modell-Exception
	 * gekapselt.
	 *
	 * @throws FlzWpdbObjectsException Wenn WordPress keine positive Insert-ID liefert.
	 */
	protected static function last_insert_id(): int {
		global $wpdb;

		$insert_id = (int) $wpdb->insert_id;
		if ( $insert_id <= 0 ) {
			throw FlzWpdbObjectsException::invalid_model_state(
				static::class,
				'Nach dem Einfügen des Datensatzes liegt keine positive Insert-ID vor.'
			);
		}

		return $insert_id;
	}


	/**
	 * Löscht das Modell anhand seiner positiven ID.
	 *
	 * @throws FlzWpdbObjectsException Bei Modell-, Datenbank- oder Hook-Fehlern.
	 */
	public function delete(): int {
		global $wpdb;
		if ( $this->id === null || $this->id <= 0 ) {
			throw FlzWpdbObjectsException::invalid_model_state(
				static::class,
				'Ein Datensatz ohne positive ID kann nicht gelöscht werden.'
			);
		}

		$table_name = static::validated_identifier( static::table_name() );
		static::run_hook( 'beforeDelete', 'Vorbereitung vor dem Löschen des Datensatzes' );
		$result = static::execute_database_call(
			fn() => $wpdb->delete(
				$table_name,
				array( 'id' => $this->id ),
				array( '%d' )
			),
			'Löschen des Datensatzes mit ID ' . $this->id,
			$table_name
		);
		if ( $result === 0 ) {
			throw FlzWpdbObjectsException::invalid_model_state(
				static::class,
				'Der zu löschende Datensatz mit ID ' . $this->id . ' wurde nicht gefunden.'
			);
		}
		static::run_hook( 'afterDelete', 'Nachbereitung nach dem Löschen des Datensatzes' );

		return (int) $result;
	}

	/**
	 * Zählt Datensätze anhand sicher aufgebauter Gleichheitsbedingungen.
	 *
	 * @throws FlzWpdbObjectsException Bei Datenbankfehlern.
	 */
	public static function count_by( array $fields = [] ): int {
		global $wpdb;

		$table = static::validated_identifier( static::table_name() );
		[ $where_sql, $values ] = static::build_where_clause( $fields );
		// Alle dynamischen SQL-Fragmente wurden zuvor intern validiert/erzeugt.
		$query = "SELECT COUNT(*) FROM $table$where_sql";
		if ( ! empty( $values ) ) {
			$query = static::prepare_query( $query, $values, 'Vorbereiten der Zählabfrage', $table );
		}

		try {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query enthält keine ungeprüften SQL-Fragmente.
			$count = $wpdb->get_var( $query );
		} catch ( Throwable $error ) {
			throw FlzWpdbObjectsException::operation(
				'Zählen von Datensätzen',
				'Tabelle ' . $table,
				$error
			);
		}
		if ( ! is_numeric( $count ) || static::has_database_error() ) {
			throw static::database_exception( 'Zählen von Datensätzen', $table );
		}

		return (int) $count;
	}

	/**
	 * Bereitet eine intern aufgebaute Abfrage vor und prüft das Ergebnis.
	 */
	private static function prepare_query(
		string $query,
		array $values,
		string $operation,
		string $table
	): string {
		global $wpdb;

		try {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query enthält nur validierte Fragmente und Platzhalter.
			$prepared_query = $wpdb->prepare( $query, $values );
		} catch ( Throwable $error ) {
			throw FlzWpdbObjectsException::operation(
				$operation,
				'Tabelle ' . $table,
				$error
			);
		}
		if ( ! is_string( $prepared_query ) || $prepared_query === '' ) {
			throw static::database_exception( $operation, $table );
		}

		return $prepared_query;
	}

	/**
	 * Führt eine schreibende Datenbankoperation mit einheitlicher Fehlerprüfung aus.
	 */
	private static function execute_database_call(
		callable $callback,
		string $operation,
		string $table
	): mixed {
		try {
			$result = $callback();
		} catch ( Throwable $error ) {
			throw FlzWpdbObjectsException::operation(
				$operation,
				'Tabelle ' . $table,
				$error
			);
		}

		static::assert_database_result( $result, $operation, $table );

		return $result;
	}

	/**
	 * Prüft Rückgabewert und last_error einer schreibenden Datenbankoperation.
	 */
	private static function assert_database_result( $result, string $operation, string $table ): void {
		if ( $result === false || static::has_database_error() ) {
			throw static::database_exception( $operation, $table );
		}
	}

	private static function has_database_error(): bool {
		global $wpdb;

		return trim( (string) ( $wpdb->last_error ?? '' ) ) !== '';
	}

	private static function database_exception( string $operation, string $table ): FlzWpdbObjectsException {
		global $wpdb;

		return FlzWpdbObjectsException::database(
			$operation,
			$table,
			(string) ( $wpdb->last_error ?? '' )
		);
	}

	/**
	 * Führt einen Lifecycle-Hook aus und ergänzt dessen Fehler um den Kontext.
	 */
	private static function run_hook( string $hook, string $operation ): void {
		try {
			static::{$hook}();
		} catch ( Throwable $error ) {
			throw FlzWpdbObjectsException::operation(
				$operation,
				static::class . '::' . $hook . '()',
				$error
			);
		}
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

	/**
	 * Übernimmt skalare Formulardaten in öffentliche Modell-Properties.
	 *
	 * Die Datenbank-ID wird grundsätzlich nicht übernommen. Aufrufer müssen
	 * eine explizite Feldliste übergeben, damit interne Felder
	 * wie Status oder Bestätigungstoken nicht per Mass Assignment änderbar sind.
	 *
	 * @throws \InvalidArgumentException Bei einer ungültigen Feldliste.
	 * @throws FlzWpdbObjectsException   Bei Reflection- oder Zuweisungsfehlern.
	 */
	public function assignPostData( array $data, array $allowed_fields ): void {
		foreach ( $allowed_fields as $allowed_field ) {
			if ( ! is_string( $allowed_field ) || $allowed_field === '' ) {
				throw new \InvalidArgumentException(
					'Die Liste erlaubter Formularfelder darf nur nichtleere Feldnamen enthalten.'
				);
			}
			if ( $allowed_field === 'id' ) {
				throw new \InvalidArgumentException(
					'Die Datenbank-ID darf nicht als erlaubtes Formularfeld freigegeben werden.'
				);
			}
			if ( ! property_exists( $this, $allowed_field ) ) {
				throw new \InvalidArgumentException(
					'Das erlaubte Formularfeld "' . $allowed_field . '" existiert im Modell "' . static::class . '" nicht.'
				);
			}
		}

		foreach ( $data as $key => $value ) {
			if ( ! is_string( $key ) || $key === 'id' || ! property_exists( $this, $key ) ) {
				continue;
			}
			if ( ! in_array( $key, $allowed_fields, true ) ) {
				continue;
			}
			try {
				$property = new ReflectionProperty( $this, $key );
			} catch ( ReflectionException $error ) {
				throw FlzWpdbObjectsException::operation(
					'Lesen der Formularfeld-Metadaten',
					static::class . '::$' . $key,
					$error
				);
			}
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
			try {
				$this->$key = static::sanitize_property_value( $property, $key, $value );
			} catch ( Throwable $error ) {
				throw FlzWpdbObjectsException::operation(
					'Bereinigen und Zuweisen des Formularfelds $' . $key,
					static::class,
					$error
				);
			}
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

// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
