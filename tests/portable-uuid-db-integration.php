<?php
/** Run with WP-CLI --skip-plugins --skip-themes in a disposable test context. */

global $wpdb;
$coverage_file = getenv('TAGORE_UUID_COVERAGE_FILE');
if ($coverage_file && function_exists('xdebug_start_code_coverage')) {
	xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
}
$original_prefix = $wpdb->prefix;
$test_prefix = 'flzuuidtest_' . substr(str_replace('-', '', wp_generate_uuid4()), 0, 12) . '_';
$suffixes = array('flz_ags_courses', 'flz_ags_slots', 'flz_ags_registrations', 'flz_est_teachers', 'flz_est_appointments', 'flz_pu_schools', 'flz_pu_participants');
$created = array();
$root = dirname(__DIR__);
require_once $root . '/repositories/flz_ags/activate-deactivate.php';
require_once $root . '/repositories/flz_elternsprechtag/includes/class-flz-est-schema-migrator.php';
require_once $root . '/repositories/flz_probeunterricht/includes/class-flz-pu-schema-migrator.php';

try {
	foreach ($suffixes as $suffix) {
		$table = $test_prefix . $suffix;
		if (null !== $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)))) {
			throw new RuntimeException('Eine Testtabelle existiert bereits.');
		}
		if (false === $wpdb->query($wpdb->prepare('CREATE TABLE %i (id int NOT NULL PRIMARY KEY, marker varchar(20) NOT NULL)', $table))) {
			throw new RuntimeException('Eine Testtabelle konnte nicht erstellt werden.');
		}
		$created[] = $table;
		if (false === $wpdb->insert($table, array('id' => 1, 'marker' => 'synthetic'))) {
			throw new RuntimeException('Ein Testdatensatz konnte nicht erstellt werden.');
		}
	}
	$wpdb->prefix = $test_prefix;
	$est = new ReflectionMethod(FlzEstSchemaMigrator::class, 'add_record_uuids');
	$pu = new ReflectionMethod(FlzPuSchemaMigrator::class, 'add_record_uuids');
	$upgrade = static function () use ($est, $pu): void {
		flz_ags_upgrade_record_uuids();
		$est->invoke(null);
		$pu->invoke(null);
	};
	$upgrade();
	$before = array();
	foreach ($created as $table) {
		$row = $wpdb->get_row($wpdb->prepare('SELECT marker, record_uuid FROM %i WHERE id = %d', $table, 1), ARRAY_A);
		if (!is_array($row) || $row['marker'] !== 'synthetic' || !wp_is_uuid($row['record_uuid'], 4)) {
			throw new RuntimeException('Die Migration hat den Bestandsdatensatz oder seine UUID nicht erhalten.');
		}
		$before[$table] = $row['record_uuid'];
	}
	$upgrade();
	foreach ($created as $table) {
		$uuid = $wpdb->get_var($wpdb->prepare('SELECT record_uuid FROM %i WHERE id = %d', $table, 1));
		if ($uuid !== $before[$table]) { throw new RuntimeException('Ein wiederholtes Upgrade hat die ID verändert.'); }
		$wpdb->suppress_errors(true);
		$duplicate = $wpdb->insert($table, array('id' => 2, 'marker' => 'synthetic', 'record_uuid' => $uuid));
		$wpdb->suppress_errors(false);
		if (false !== $duplicate) { throw new RuntimeException('Der Eindeutigkeitsindex hat ein Duplikat zugelassen.'); }
	}
	require_once $root . '/repositories/flz_wpdb_objects/flz_wpdb_objects.php';
	require_once $root . '/repositories/flz_ags/includes/helpers.php';
	require_once $root . '/repositories/flz_ags/includes/models/class-flz-ags-model.php';
	require_once $root . '/repositories/flz_ags/includes/models/class-flz-ags-course.php';
	require_once $root . '/repositories/flz_ags/includes/models/class-flz-ags-slot.php';
	require_once $root . '/repositories/flz_ags/includes/models/class-flz-ags-registration.php';
	require_once $root . '/repositories/flz_elternsprechtag/classes/FlzEstAppointment.php';
	require_once $root . '/repositories/flz_probeunterricht/classes/FlzPuSchool.php';
	require_once $root . '/repositories/flz_probeunterricht/classes/FlzPuParticipant.php';
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$models = array(FLZ_AGS_Course::class, FLZ_AGS_Slot::class, FLZ_AGS_Registration::class, FlzEstTeacher::class, FlzEstAppointment::class, FlzPuSchool::class, FlzPuParticipant::class);
	foreach ($created as $index => $table) {
		if (false === $wpdb->query($wpdb->prepare('DROP TABLE %i', $table))) { throw new RuntimeException('Die synthetische Upgrade-Tabelle konnte nicht entfernt werden.'); }
		$schema = (new ReflectionMethod($models[$index], 'get_table_schema'))->invoke(null);
		// Der Tabellenname stammt ausschließlich aus dem oben selbst erzeugten Präfix.
		dbDelta('CREATE TABLE ' . $table . ' ' . $schema . ' ' . $wpdb->get_charset_collate() . ';');
		$column = $wpdb->get_var($wpdb->prepare('SHOW COLUMNS FROM %i LIKE %s', $table, 'record_uuid'));
		$index_row = $wpdb->get_row($wpdb->prepare('SHOW INDEX FROM %i WHERE Key_name = %s', $table, 'record_uuid'), ARRAY_A);
		if ('record_uuid' !== $column || !is_array($index_row) || 'record_uuid' !== $index_row['Key_name'] || (int) $index_row['Non_unique'] !== 0) { throw new RuntimeException('Das Neuinstallationsschema enthält UUID oder eindeutigen Index nicht: ' . $models[$index]); }
	}
	$course = new FLZ_AGS_Course(array('school_year' => '2026/2027', 'title' => 'Test', 'slug' => 'test', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'));
	$course->save();
	$slot = new FLZ_AGS_Slot(array('course_id' => $course->id, 'school_year' => '2026/2027', 'weekday' => 2, 'start_time' => '14:00:00', 'end_time' => '15:00:00', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'));
	$slot->save();
	$registration = new FLZ_AGS_Registration(array('course_id' => $course->id, 'slot_id' => $slot->id, 'school_year' => '2026/2027', 'class_name' => '7a', 'grade_key' => '7', 'student_first_name' => 'Test', 'student_last_name' => 'Test', 'status' => 'active', 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00'));
	$registration->save();
	$teacher = new FlzEstTeacher(array('name' => 'Test', 'firstName' => 'Test', 'gender' => 'f', 'email' => 'test@example.invalid'));
	$teacher->save();
	$appointment = new FlzEstAppointment(array('teacher' => $teacher, 'start' => 1800000000, 'end' => 1800001800));
	$appointment->save();
	$school = new FlzPuSchool(array('name' => 'Test', 'capacity' => 8, 'available_seats' => 8));
	$school->save();
	$participant = new FlzPuParticipant(array('name' => 'Test', 'firstName' => 'Test', 'email' => 'test@example.invalid', 'class' => '6a', 'school' => $school, 'lunch' => false, 'status' => 'active', 'isConfirmed' => false, 'activationToken' => 'synthetic-token', 'activationExpiration' => '2035-01-01 00:00:00', 'created_at' => '2026-01-01 00:00:00'));
	$participant->save();
	foreach (array($course, $slot, $registration, $teacher, $appointment, $school, $participant) as $model) {
		$loaded = $model::get_by_id($model->id);
		if (!wp_is_uuid($model->record_uuid, 4) || $loaded->record_uuid !== $model->record_uuid) { throw new RuntimeException('Eine neue UUID wurde nicht dauerhaft gespeichert: ' . get_class($model)); }
	}
	require_once $root . '/repositories/flz_probeunterricht/includes/class-flz-pu-csv-contract.php';
	require_once $root . '/repositories/flz_probeunterricht/backend/schools.php';
	require_once $root . '/repositories/flz_probeunterricht/backend/participants.php';
	require_once $root . '/repositories/flz_elternsprechtag/includes/csv-contract.php';
	require_once $root . '/repositories/flz_elternsprechtag/backend/teachers.php';
	require_once $root . '/repositories/flz_elternsprechtag/backend/appointments.php';
	$participant_rows = FlzPuCsvContract::export_participants(array($participant));
	$participant_rows[0][2] = 'Neu';
	$participant_plan = flzpu_plan_participant_csv_import(FlzPuCsvContract::parse_participants(array_merge(array(FlzPuCsvContract::participant_header()), $participant_rows)));
	if ($participant_plan['updated'] !== 1 || $participant_plan['created'] !== 0 || count($participant_plan['missing_participants']) !== 0) { throw new RuntimeException('Die Teilnehmenden-UUID identifiziert den geänderten Datensatz nicht.'); }
	flzpu_apply_participant_csv_import_plan($participant_plan);
	$reloaded = FlzPuParticipant::get_by_id($participant->id);
	if ($reloaded->firstName !== 'Neu' || $reloaded->record_uuid !== $participant->record_uuid || $reloaded->isConfirmed || $reloaded->activationToken !== 'synthetic-token' || FlzPuParticipant::count_by() !== 1) { throw new RuntimeException('Der UUID-Import der unbestätigten Teilnehmenden ist nicht stabil.'); }
	$legacy_rows = FlzPuCsvContract::export_participants(array($reloaded));
	$legacy_rows[0] = array_slice($legacy_rows[0], 1, -1);
	$legacy_header = array_slice(FlzPuCsvContract::participant_header(), 1, -1);
	$legacy_plan = flzpu_plan_participant_csv_import(FlzPuCsvContract::parse_participants(array_merge(array($legacy_header), $legacy_rows)));
	flzpu_apply_participant_csv_import_plan($legacy_plan);
	$reloaded = FlzPuParticipant::get_by_id($participant->id);
	if ($reloaded->isConfirmed || $reloaded->activationToken !== 'synthetic-token' || $reloaded->activationExpiration !== '2035-01-01 00:00:00') { throw new RuntimeException('Ein Altimport hat eine unbestätigte Anmeldung bestätigt oder ihren Bestätigungslink entfernt.'); }
	$confirmed_rows = FlzPuCsvContract::export_participants(array($reloaded));
	$confirmed_rows[0][9] = '1';
	$confirmed_plan = flzpu_plan_participant_csv_import(FlzPuCsvContract::parse_participants(array_merge(array(FlzPuCsvContract::participant_header()), $confirmed_rows)));
	flzpu_apply_participant_csv_import_plan($confirmed_plan);
	$reloaded = FlzPuParticipant::get_by_id($participant->id);
	if (!$reloaded->isConfirmed || $reloaded->activationToken !== null) { throw new RuntimeException('Eine explizite Bestätigung wurde nicht korrekt importiert.'); }
	$legacy_rows = array_slice(FlzPuCsvContract::export_participants(array($reloaded))[0], 1, -1);
	$new_legacy_row = array('Neu', 'Altdatei', '6a', 'legacy@example.invalid', 'Test', '0', 'active', '2026-01-02 00:00:00');
	$legacy_plan = flzpu_plan_participant_csv_import(FlzPuCsvContract::parse_participants(array($legacy_header, $legacy_rows, $new_legacy_row)));
	flzpu_apply_participant_csv_import_plan($legacy_plan);
	$new_legacy_participant = FlzPuParticipant::get_by_fields(array('email' => 'legacy@example.invalid'));
	if (!$new_legacy_participant instanceof FlzPuParticipant || $new_legacy_participant->isConfirmed || !FlzPuParticipant::get_by_id($participant->id)->isConfirmed) { throw new RuntimeException('Ein Altimport verändert Bestätigungen oder bestätigt einen neuen Datensatz ohne Nachweis.'); }
	$confirmation_header = $legacy_header;
	$confirmation_row = $legacy_rows;
	array_splice($confirmation_header, 6, 0, array('isConfirmed'));
	array_splice($confirmation_row, 6, 0, array('Nein'));
	$old_confirmation_plan = flzpu_plan_participant_csv_import(FlzPuCsvContract::parse_participants(array($confirmation_header, $confirmation_row)));
	flzpu_apply_participant_csv_import_plan($old_confirmation_plan);
	$corrected = FlzPuParticipant::get_by_id($participant->id);
	if ($corrected->isConfirmed || $corrected->record_uuid !== $participant->record_uuid || FlzPuParticipant::count_by() !== 2) { throw new RuntimeException('Eine alte explizit unbestätigte Buchung wurde nicht korrekt und eindeutig importiert.'); }
	$new_legacy_participant->delete();
	$school = FlzPuSchool::get_by_id($school->id);
	$school->available_seats++;
	$school->save();
	$school_rows = FlzPuCsvContract::export_schools(array($school));
	$school_rows[0][1] = 'Neu';
	$school_plan = flzpu_plan_school_csv_import(FlzPuCsvContract::parse_schools(array_merge(array(FlzPuCsvContract::school_header()), $school_rows)));
	if ((int) $school_plan[0]['school']->id !== (int) $school->id) { throw new RuntimeException('Die Schul-UUID identifiziert den geänderten Datensatz nicht.'); }
	$teacher_rows = flzest_parse_teacher_csv(array(flzest_teacher_csv_header(), array($teacher->record_uuid, 'f', 'Test', 'Neu', 'test@example.invalid')));
	$teacher_plan = flzest_plan_teacher_csv_import($teacher_rows);
	if ($teacher_plan['updated'] !== 1 || $teacher_plan['created'] !== 0) { throw new RuntimeException('Die Lehrkraft-UUID identifiziert den geänderten Datensatz nicht.'); }
	flzest_apply_teacher_csv_import_plan($teacher_plan);
	if (FlzEstTeacher::get_by_id($teacher->id)->record_uuid !== $teacher->record_uuid || FlzEstTeacher::count_by() !== 1) { throw new RuntimeException('Der UUID-Import der Lehrkraft ist nicht stabil.'); }
	$appointment_rows = flzest_parse_appointment_csv(array(flzest_appointment_csv_header(), array($appointment->record_uuid, 'test@example.invalid', '1800000000', '1800001800', '', '', '', '', '', '', '', '0')));
	$appointment_plan = flzest_plan_appointment_csv_import($appointment_rows);
	if ((int) $appointment_plan['appointments'][0]['appointment']->id !== (int) $appointment->id) { throw new RuntimeException('Die Termin-UUID identifiziert den Datensatz nicht.'); }
	flzest_apply_appointment_csv_import_plan($appointment_plan);
	if (FlzEstAppointment::get_by_id($appointment->id)->record_uuid !== $appointment->record_uuid || FlzEstAppointment::count_by() !== 1) { throw new RuntimeException('Der UUID-Import des Termins ist nicht stabil.'); }
	foreach (array(flzpu_school_csv_proof_hash(array()), flzpu_participant_csv_proof_hash(array()), flzest_teacher_csv_proof_hash(array()), flzest_appointment_csv_proof_hash(array())) as $proof) {
		if (strlen($proof) !== 64) { throw new RuntimeException('Der Importprüfnachweis ist ungültig.'); }
	}
	require_once $root . '/repositories/flz_ags/includes/class-flz-ags-course-csv.php';
	require_once $root . '/repositories/flz_ags/includes/class-flz-ags-registration-csv.php';
	require_once $root . '/repositories/flz_ags/includes/class-flz-ags.php';
	$ags = (new ReflectionClass(FLZ_AGS_Plugin::class))->newInstanceWithoutConstructor();
	$build = new ReflectionMethod(FLZ_AGS_Plugin::class, 'build_course_csv_import_plan');
	$apply = new ReflectionMethod(FLZ_AGS_Plugin::class, 'apply_course_csv_import_plan');
	$course_rows = FLZ_AGS_Course_CSV::export_rows(array($course), static fn(int $id): array => array($slot));
	$course_rows[0][1] = 'Neu';
	$course_rows[1][1] = 'Neu';
	$course_data = FLZ_AGS_Course_CSV::parse_tolerant(array_merge(array(FLZ_AGS_Course_CSV::header()), $course_rows));
	$course_plan = $build->invoke($ags, $course_data['courses']);
	if ($course_plan['courses_updated'] !== 1 || $course_plan['slots_updated'] !== 1) { throw new RuntimeException('AG oder Termin wurde nicht anhand der UUID erkannt.'); }
	$apply->invoke($ags, $course_plan);
	if (FLZ_AGS_Course::get_by_id($course->id)->record_uuid !== $course->record_uuid || FLZ_AGS_Slot::get_by_id($slot->id)->record_uuid !== $slot->record_uuid || FLZ_AGS_Course::count_by() !== 1 || FLZ_AGS_Slot::count_by() !== 1) { throw new RuntimeException('AG-Import hat eine UUID verändert oder einen Datensatz dupliziert.'); }
	$registration_rows = FLZ_AGS_Registration_CSV::export_rows(FLZ_AGS_Registration::find_for_admin('2026/2027', 'all', true));
	$registration_rows[0][5] = 'Neu';
	$registration_data = FLZ_AGS_Registration_CSV::parse_tolerant(array_merge(array(FLZ_AGS_Registration_CSV::header()), $registration_rows));
	$report = (new ReflectionMethod(FLZ_AGS_Plugin::class, 'apply_registration_csv_import'))->invoke($ags, $registration_data);
	if ($report['updated'] !== 1 || $report['created'] !== 0 || FLZ_AGS_Registration::get_by_id($registration->id)->record_uuid !== $registration->record_uuid) { throw new RuntimeException('Der UUID-Import der AG-Anmeldung ist nicht stabil.'); }
	$expect_conflict = static function (callable $operation): void {
		try { $operation(); } catch (UnexpectedValueException $error) { return; }
		throw new RuntimeException('Ein widersprüchlicher UUID-Import wurde nicht abgewiesen.');
	};
	$bad_school = FlzPuCsvContract::export_schools(array(FlzPuSchool::get_by_id($school->id)));
	$bad_school[0][0] = wp_generate_uuid4();
	$expect_conflict(static fn() => flzpu_plan_school_csv_import(FlzPuCsvContract::parse_schools(array_merge(array(FlzPuCsvContract::school_header()), $bad_school))));
	$bad_participant = FlzPuCsvContract::export_participants(array($reloaded));
	$bad_participant[0][0] = wp_generate_uuid4();
	$expect_conflict(static fn() => flzpu_plan_participant_csv_import(FlzPuCsvContract::parse_participants(array_merge(array(FlzPuCsvContract::participant_header()), $bad_participant))));
	$bad_teacher = array(flzest_teacher_csv_header(), array(wp_generate_uuid4(), 'f', 'Test', 'Neu', 'test@example.invalid'));
	$expect_conflict(static fn() => flzest_plan_teacher_csv_import(flzest_parse_teacher_csv($bad_teacher)));
	$bad_appointment = array(flzest_appointment_csv_header(), array(wp_generate_uuid4(), 'test@example.invalid', '1800000000', '1800001800', '', '', '', '', '', '', '', '0'));
	$expect_conflict(static fn() => flzest_plan_appointment_csv_import(flzest_parse_appointment_csv($bad_appointment)));
	$bad_course = FLZ_AGS_Course_CSV::export_course_rows(array(FLZ_AGS_Course::get_by_id($course->id)));
	$bad_course[0][14] = wp_generate_uuid4();
	$bad_course_data = FLZ_AGS_Course_CSV::parse_tolerant(array_merge(array(FLZ_AGS_Course_CSV::course_header()), $bad_course));
	$expect_conflict(static fn() => $build->invoke($ags, $bad_course_data['courses']));
	$registration_snapshot = FLZ_AGS_Registration::get_by_id($registration->id);
	$bad_registration = FLZ_AGS_Registration_CSV::export_rows(FLZ_AGS_Registration::find_for_admin('2026/2027', 'all', true));
	$bad_registration[0][1] = 'withdrawn';
	$bad_registration[1] = $bad_registration[0];
	$bad_registration[1][17] = wp_generate_uuid4();
	$bad_registration_data = FLZ_AGS_Registration_CSV::parse_tolerant(array_merge(array(FLZ_AGS_Registration_CSV::header()), $bad_registration));
	try {
		FLZ_AGS_Model::transaction(static fn() => (new ReflectionMethod(FLZ_AGS_Plugin::class, 'apply_registration_csv_import'))->invoke($ags, $bad_registration_data), 'Test UUID-Konflikt');
		throw new LogicException('Der widersprüchliche Anmeldungsimport wurde nicht abgewiesen.');
	} catch (Throwable $error) {
		if ($error instanceof LogicException) { throw $error; }
		$messages = '';
		for ($cause = $error; $cause !== null; $cause = $cause->getPrevious()) { $messages .= $cause->getMessage(); }
		if (!str_contains($messages, 'Datensatz-ID')) { throw $error; }
	}
	if (FLZ_AGS_Registration::get_by_id($registration->id)->status !== $registration_snapshot->status) { throw new RuntimeException('Der UUID-Konflikt hat eine Teiländerung hinterlassen.'); }
	if (FLZ_AGS_Course::count_by() !== 1 || FlzEstTeacher::count_by() !== 1 || FlzPuParticipant::count_by() !== 1) { throw new RuntimeException('Ein abgewiesener UUID-Import hat Daten verändert.'); }
	$conflict_prefix = 'flzcf_' . substr(str_replace('-', '', wp_generate_uuid4()), 0, 12) . '_';
	$wpdb->prefix = $conflict_prefix;
	foreach (array('flz_ags_courses' => static fn() => flz_ags_upgrade_record_uuids(), 'flz_est_teachers' => static fn() => $est->invoke(null), 'flz_pu_schools' => static fn() => $pu->invoke(null)) as $suffix => $upgrade_conflict) {
		$table = $conflict_prefix . $suffix;
		if (null !== $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table)))) { throw new RuntimeException('Eine Konflikt-Testtabelle existiert bereits.'); }
		if (false === $wpdb->query($wpdb->prepare('CREATE TABLE %i (id int NOT NULL PRIMARY KEY, record_uuid char(36) NULL)', $table))) { throw new RuntimeException('Eine Konflikt-Testtabelle konnte nicht erstellt werden.'); }
		$created[] = $table;
		$duplicate_uuid = wp_generate_uuid4();
		$wpdb->insert($table, array('id' => 1, 'record_uuid' => $duplicate_uuid));
		$wpdb->insert($table, array('id' => 2, 'record_uuid' => $duplicate_uuid));
		$wpdb->suppress_errors(true);
		try {
			$upgrade_conflict();
			throw new LogicException('Doppelte Bestands-UUIDs wurden bei der Migration akzeptiert.');
		} catch (RuntimeException $error) {
			if ($error instanceof LogicException) { throw $error; }
		} finally {
			$wpdb->suppress_errors(false);
		}
		$count = (int) $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i WHERE record_uuid = %s', $table, $duplicate_uuid));
		if ($count !== 2) { throw new RuntimeException('Der Migrationsfehler hat Bestands-UUIDs verändert.'); }
	}
	$wpdb->prefix = $test_prefix;
	// Ohne UI-Abhängigkeit dürfen die Bootstrap-Dateien keine fachlichen Tabellen ändern.
	require_once $root . '/repositories/flz_elternsprechtag/flz_elternsprechtag.php';
	require_once $root . '/repositories/flz_probeunterricht/flz_probeunterricht.php';
	if ($coverage_file && function_exists('xdebug_get_code_coverage')) {
		if (false === file_put_contents($coverage_file, json_encode(xdebug_get_code_coverage(), JSON_THROW_ON_ERROR))) { throw new RuntimeException('Die Coverage-Daten konnten nicht gespeichert werden.'); }
		xdebug_stop_code_coverage();
	}
	WP_CLI::success('UUID-Upgrade, Neuinstallation und UUID-Importe in allen drei Komponenten mit synthetischen Daten geprüft.');
} finally {
	$wpdb->prefix = $original_prefix;
	foreach ($created as $table) {
		if (false === $wpdb->query($wpdb->prepare('DROP TABLE %i', $table))) {
			WP_CLI::warning('Eine temporäre Testtabelle konnte nicht entfernt werden: ' . $table);
		}
	}
}
