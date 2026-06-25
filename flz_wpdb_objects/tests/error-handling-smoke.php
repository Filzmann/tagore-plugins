<?php

if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 404 );
	exit;
}

define( 'ABSPATH', '/tmp/' );

// Test-Exceptions werden ausschließlich von der CLI ausgewertet.
// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

function register_activation_hook(): void {}
function register_deactivation_hook(): void {}
function wp_unslash( $value ) {
	return $value;
}
function sanitize_email( $value ): string {
	return (string) filter_var( $value, FILTER_SANITIZE_EMAIL );
}
function sanitize_text_field( $value ): string {
	return strip_tags( (string) $value );
}
function sanitize_file_name( $value ): string {
	return preg_replace( '/[^A-Za-z0-9._-]/', '', (string) $value ) ?? '';
}
function wp_basename( string $path ): string {
	return basename( $path );
}
function trailingslashit( string $path ): string {
	return rtrim( $path, '/' ) . '/';
}
function wp_upload_dir(): array {
	return $GLOBALS['flz_test_upload_dir'];
}

require_once dirname( __DIR__ ) . '/flz_wpdb_objects.php';

use flz_wpdb_objects\FlzWpdbObject;
use flz_wpdb_objects\FlzWpdbObjectsException;
use flz_wpdb_objects\FlzWpdbTransaction;

final class FlzWpdbObjectsFakeWpdb {
	public string $prefix = 'wp_';
	public string $last_error = '';
	public int $insert_id = 0;
	public array $errors = [];
	public array $throws = [];
	public array $results = [];
	public int $count = 0;
	public int $delete_result = 1;
	public array $commands = [];

	public function reset(): void {
		$this->last_error = '';
		$this->insert_id  = 0;
		$this->errors     = [];
		$this->throws     = [];
		$this->results    = [];
		$this->count      = 0;
		$this->delete_result = 1;
		$this->commands = [];
	}

	public function prepare( string $query, ...$args ) {
		return $this->outcome( 'prepare', $query );
	}

	public function get_results( string $query ) {
		return $this->outcome( 'get_results', $this->results );
	}

	public function get_var( string $query ) {
		return $this->outcome( 'get_var', (string) $this->count );
	}

	public function query( string $query ) {
		$operation = match ( true ) {
			str_starts_with( $query, 'TRUNCATE ' ) => 'truncate',
			str_contains( $query, 'FOREIGN_KEY_CHECKS = 0' ) => 'disable_fk',
			str_contains( $query, 'FOREIGN_KEY_CHECKS = 1' ) => 'enable_fk',
			$query === 'START TRANSACTION' => 'start_transaction',
			$query === 'COMMIT' => 'commit',
			$query === 'ROLLBACK' => 'rollback',
			default => 'query',
		};
		$this->commands[] = $operation;

		return $this->outcome( $operation, 1 );
	}

	public function insert( string $table, array $data ) {
		$result = $this->outcome( 'insert', 1 );
		if ( $result !== false ) {
			$this->insert_id = 41;
		}

		return $result;
	}

	public function update( string $table, array $data, array $where ) {
		return $this->outcome( 'update', 1 );
	}

	public function delete( string $table, array $where, array $format ) {
		return $this->outcome( 'delete', $this->delete_result );
	}

	public function get_charset_collate(): string {
		return 'DEFAULT CHARACTER SET utf8mb4';
	}

	private function outcome( string $operation, $success ) {
		$this->last_error = '';
		if ( isset( $this->throws[ $operation ] ) ) {
			throw $this->throws[ $operation ];
		}
		if ( isset( $this->errors[ $operation ] ) ) {
			$this->last_error = $this->errors[ $operation ];
			return false;
		}

		return $success;
	}
}

class FlzWpdbObjectsTestRelation extends FlzWpdbObject {
	public static bool $missing = false;

	public function __construct( array $data = [] ) {
		parent::__construct( $data['id'] ?? null );
	}

	public static function get_by_id( $id ): null|object {
		return self::$missing || $id === null ? null : new static( [ 'id' => $id ] );
	}
}

class FlzWpdbObjectsTestRecord extends FlzWpdbObject {
	public static bool $fail_after_insert = false;
	public string|null $name = null;
	public FlzWpdbObjectsTestRelation|null $relation = null;

	public function __construct( array $data = [] ) {
		parent::__construct( $data['id'] ?? null );
		$this->name = $data['name'] ?? null;
		$this->relation = $data['relation'] ?? null;
	}

	protected static function table_name(): string {
		return 'wp_test_records';
	}

	protected function prepareDataForSaving(): array {
		return [ 'name' => $this->name ];
	}

	protected static function afterInsert(): void {
		if ( self::$fail_after_insert ) {
			throw new RuntimeException( 'Hook absichtlich fehlgeschlagen.' );
		}
	}

	public static function hydrate( object $row ): object {
		return static::createObjectFromResult( $row );
	}
}

class FlzWpdbObjectsCsvFailure {
	public function getCsvLine(): string {
		throw new RuntimeException( 'CSV-Zeile absichtlich fehlgeschlagen.' );
	}
}

function flz_test_check( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( 'Test fehlgeschlagen: ' . $message );
	}
}

function flz_test_expect_exception(
	callable $callback,
	string $exception_class,
	array $message_fragments,
	bool $expect_previous = false
): Throwable {
	try {
		$callback();
	} catch ( Throwable $error ) {
		flz_test_check( $error instanceof $exception_class, 'Unerwarteter Exception-Typ: ' . get_class( $error ) );
		foreach ( $message_fragments as $fragment ) {
			flz_test_check(
				str_contains( $error->getMessage(), $fragment ),
				'Meldung enthält nicht "' . $fragment . '": ' . $error->getMessage()
			);
		}
		if ( $expect_previous ) {
			flz_test_check( $error->getPrevious() instanceof Throwable, 'Ursprüngliche Exception fehlt.' );
		}

		return $error;
	}

	throw new RuntimeException( 'Test fehlgeschlagen: Erwartete Exception wurde nicht geworfen.' );
}

$wpdb = new FlzWpdbObjectsFakeWpdb();
$GLOBALS['wpdb'] = $wpdb;

$wpdb->results = [ (object) [ 'id' => 1, 'name' => 'Erfolg' ] ];
$records = FlzWpdbObjectsTestRecord::get_all_by();
flz_test_check( count( $records ) === 1 && $records[0]->name === 'Erfolg', 'Erfolgreiches Laden fehlgeschlagen.' );
$record = new FlzWpdbObjectsTestRecord( [ 'name' => 'Erfolg' ] );
flz_test_check( $record->save() === 1 && $record->id === 41, 'Erfolgreiches INSERT fehlgeschlagen.' );
flz_test_check( $record->save() === 1, 'Erfolgreiches UPDATE fehlgeschlagen.' );
flz_test_check( $record->delete() === 1, 'Erfolgreiches DELETE fehlgeschlagen.' );
$wpdb->count = 3;
flz_test_check( FlzWpdbObjectsTestRecord::count_by() === 3, 'Erfolgreiches COUNT fehlgeschlagen.' );
$dauer = ( new FlzZeitraum( [ 'beginn' => 0, 'ende' => 90061 ] ) )->dauer();
flz_test_check( $dauer['Tage'] === 1 && $dauer['Stunden'] === 1, 'Erfolgreiche Dauerberechnung fehlgeschlagen.' );

$wpdb->reset();
$transaction_result = FlzWpdbTransaction::run( static fn() => 17, 'Erfolgreicher Testvorgang' );
flz_test_check( $transaction_result === 17, 'Transaktions-Rückgabewert ging verloren.' );
flz_test_check(
	$wpdb->commands === [ 'start_transaction', 'commit' ],
	'Erfolgreiche Transaktion führte nicht START und COMMIT aus.'
);

$wpdb->reset();
flz_test_expect_exception(
	static fn() => FlzWpdbTransaction::run(
		static function (): void {
			throw new RuntimeException( 'Simulierter Callback-Fehler' );
		},
		'Fehlgeschlagener Testvorgang'
	),
	FlzWpdbObjectsException::class,
	[ 'Fehlgeschlagener Testvorgang', 'Simulierter Callback-Fehler' ],
	true
);
flz_test_check(
	$wpdb->commands === [ 'start_transaction', 'rollback' ],
	'Fehlgeschlagene Transaktion führte kein ROLLBACK aus.'
);

$wpdb->reset();
$wpdb->errors['rollback'] = 'Simulierter ROLLBACK-Fehler';
flz_test_expect_exception(
	static fn() => FlzWpdbTransaction::run(
		static function (): void {
			throw new RuntimeException( 'Simulierter Primärfehler' );
		},
		'Doppelter Fehlerfall'
	),
	FlzWpdbObjectsException::class,
	[ 'Simulierter Primärfehler', 'Rollback fehlgeschlagen', 'Simulierter ROLLBACK-Fehler' ],
	true
);

$wpdb->reset();
$wpdb->errors['get_results'] = 'Simulierter SELECT-Fehler';
flz_test_expect_exception(
	static fn() => FlzWpdbObjectsTestRecord::get_all_by(),
	FlzWpdbObjectsException::class,
	[ 'Laden von Datensätzen', 'wp_test_records', 'Simulierter SELECT-Fehler' ]
);

$wpdb->reset();
$wpdb->throws['get_results'] = new RuntimeException( 'Simulierter Treiberfehler' );
flz_test_expect_exception(
	static fn() => FlzWpdbObjectsTestRecord::get_all_by(),
	FlzWpdbObjectsException::class,
	[ 'Laden von Datensätzen', 'Simulierter Treiberfehler' ],
	true
);

$wpdb->reset();
$wpdb->errors['prepare'] = 'Simulierter PREPARE-Fehler';
flz_test_expect_exception(
	static fn() => FlzWpdbObjectsTestRecord::get_all_by( [ 'name' => 'Test' ] ),
	FlzWpdbObjectsException::class,
	[ 'Vorbereiten der Datensatzabfrage', 'Simulierter PREPARE-Fehler' ]
);

$wpdb->reset();
$wpdb->errors['insert'] = 'Simulierter INSERT-Fehler';
$record = new FlzWpdbObjectsTestRecord( [ 'name' => 'Test' ] );
flz_test_expect_exception(
	static fn() => $record->save(),
	FlzWpdbObjectsException::class,
	[ 'Einfügen eines neuen Datensatzes', 'Simulierter INSERT-Fehler' ]
);

$wpdb->reset();
FlzWpdbObjectsTestRecord::$fail_after_insert = true;
$record = new FlzWpdbObjectsTestRecord( [ 'name' => 'Test' ] );
flz_test_expect_exception(
	static fn() => $record->save(),
	FlzWpdbObjectsException::class,
	[ 'afterInsert()', 'Hook absichtlich fehlgeschlagen' ],
	true
);
FlzWpdbObjectsTestRecord::$fail_after_insert = false;

$wpdb->reset();
$wpdb->throws['update'] = new RuntimeException( 'Simulierter UPDATE-Treiberfehler' );
$record = new FlzWpdbObjectsTestRecord( [ 'id' => 9, 'name' => 'Test' ] );
flz_test_expect_exception(
	static fn() => $record->save(),
	FlzWpdbObjectsException::class,
	[ 'Aktualisieren des Datensatzes mit ID 9', 'UPDATE-Treiberfehler' ],
	true
);

flz_test_expect_exception(
	static fn() => ( new FlzWpdbObjectsTestRecord() )->delete(),
	FlzWpdbObjectsException::class,
	[ 'ohne positive ID', 'nicht gelöscht' ]
);

$wpdb->reset();
$wpdb->delete_result = 0;
flz_test_expect_exception(
	static fn() => ( new FlzWpdbObjectsTestRecord( [ 'id' => 10 ] ) )->delete(),
	FlzWpdbObjectsException::class,
	[ 'ID 10', 'nicht gefunden' ]
);

flz_test_expect_exception(
	static fn() => FlzWpdbObjectsTestRecord::create_table(),
	FlzWpdbObjectsException::class,
	[ 'kein Tabellenschema' ]
);

FlzWpdbObjectsTestRelation::$missing = true;
flz_test_expect_exception(
	static fn() => FlzWpdbObjectsTestRecord::hydrate(
		(object) [ 'id' => 1, 'name' => 'Test', 'relation_id' => 77 ]
	),
	FlzWpdbObjectsException::class,
	[ 'Beziehung "relation"', 'ID 77' ]
);
FlzWpdbObjectsTestRelation::$missing = false;

$wpdb->reset();
$wpdb->errors['truncate'] = 'Simulierter TRUNCATE-Fehler';
$wpdb->errors['enable_fk'] = 'Simulierter FK-Wiederherstellungsfehler';
flz_test_expect_exception(
	static fn() => FlzWpdbObjectsTestRecord::truncate_table( true ),
	FlzWpdbObjectsException::class,
	[ 'TRUNCATE-Fehler', 'FK-Wiederherstellungsfehler' ],
	true
);

$wpdb->reset();
$wpdb->errors['get_var'] = 'Simulierter COUNT-Fehler';
flz_test_expect_exception(
	static fn() => FlzWpdbObjectsTestRecord::count_by(),
	FlzWpdbObjectsException::class,
	[ 'Zählen von Datensätzen', 'Simulierter COUNT-Fehler' ]
);

flz_test_expect_exception(
	static fn() => ( new FlzWpdbObjectsTestRecord() )->assignPostData( [], [ 'id' ] ),
	InvalidArgumentException::class,
	[ 'Datenbank-ID', 'nicht' ]
);

flz_test_expect_exception(
	static fn() => FlzWpdbObjectsTestRecord::get_by_id( 'keine-id' ),
	InvalidArgumentException::class,
	[ 'get_by_id()', 'Ganzzahl' ]
);

flz_test_expect_exception(
	static fn() => ( new FlzZeitraum() )->dauer(),
	FlzWpdbObjectsException::class,
	[ 'Beginn und Ende' ]
);
flz_test_expect_exception(
	static fn() => ( new FlzZeitraum() )->calculateDurations( -1 ),
	InvalidArgumentException::class,
	[ 'nicht negativ' ]
);

$GLOBALS['flz_test_upload_dir'] = [
	'basedir' => '/tmp',
	'baseurl' => 'https://example.test/uploads',
	'error'   => 'Simulierter Uploadfehler',
];
flz_test_expect_exception(
	static fn() => flz_wpdb_objects_create_csv( [], 'test.csv' ),
	FlzWpdbObjectsException::class,
	[ 'Uploadverzeichnisses', 'Simulierter Uploadfehler' ]
);

$filename = 'flz-wpdb-error-test-' . getmypid() . '.csv';
$path = '/tmp/' . $filename;
$GLOBALS['flz_test_upload_dir'] = [
	'basedir' => '/tmp',
	'baseurl' => 'https://example.test/uploads',
	'error'   => false,
];
flz_test_expect_exception(
	static fn() => flz_wpdb_objects_create_csv( [ new FlzWpdbObjectsCsvFailure() ], $filename ),
	FlzWpdbObjectsException::class,
	[ 'CSV-Zeile mit Index 0', 'absichtlich fehlgeschlagen' ],
	true
);
if ( is_file( $path ) ) {
	unlink( $path );
}

$filename = 'flz-wpdb-success-test-' . getmypid() . '.csv';
$path = '/tmp/' . $filename;
$url = flz_wpdb_objects_create_csv( [ new FlzWpdbObjectsTestRecord( [ 'name' => 'CSV' ] ) ], $filename, "Kopf\n" );
flz_test_check( is_file( $path ), 'Erfolgreicher CSV-Export hat keine Datei erzeugt.' );
flz_test_check( $url === 'https://example.test/uploads/' . $filename, 'CSV-URL ist unerwartet.' );
if ( is_file( $path ) ) {
	unlink( $path );
}

echo "OK: flz_wpdb_objects error handling smoke test\n";

// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
