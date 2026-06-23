<?php

defined('ABSPATH') || exit;

class TG_AGS_Plugin
{
    private static ?TG_AGS_Plugin $instance = null;

    public static function instance(): TG_AGS_Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_post_tg_ags_save_course', array($this, 'handle_save_course'));
        add_action('admin_post_tg_ags_save_settings', array($this, 'handle_save_settings'));
        add_action('admin_post_tg_ags_update_registration', array($this, 'handle_update_registration'));
        add_action('admin_post_tg_ags_export_csv', array($this, 'handle_export_csv'));
        add_action('admin_post_tg_ags_install_demo', array($this, 'handle_install_demo'));

        add_shortcode('tagore_ag_liste', array($this, 'shortcode_list'));
        add_shortcode('tagore_ag_anmeldung', array($this, 'shortcode_registration'));

        add_action('wp_enqueue_scripts', array($this, 'register_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_assets'));
    }

    public function register_frontend_assets(): void
    {
        wp_register_style('tagore-ags', TG_AGS_URL . 'assets/css/tagore-ags.css', array(), TG_AGS_VERSION);
        wp_register_script('tagore-ags', TG_AGS_URL . 'assets/js/tagore-ags.js', array(), TG_AGS_VERSION, true);
    }

    public function register_admin_assets(string $hook): void
    {
        if (strpos($hook, 'tagore-ags') === false) {
            return;
        }

        wp_enqueue_style('tagore-ags-admin', TG_AGS_URL . 'assets/css/tagore-ags.css', array(), TG_AGS_VERSION);
        wp_enqueue_media();
        wp_enqueue_script('tagore-ags-admin', TG_AGS_URL . 'assets/js/tagore-ags-admin.js', array('jquery'), TG_AGS_VERSION, true);
    }

    public function register_admin_menu(): void
    {
        $capability = tg_ags_manage_capability();

        add_menu_page(
            'Tagore AGs',
            'Tagore AGs',
            $capability,
            'tagore-ags',
            array($this, 'render_admin_courses_page'),
            'dashicons-groups',
            26
        );

        add_submenu_page('tagore-ags', 'AGs', 'AGs', $capability, 'tagore-ags', array($this, 'render_admin_courses_page'));
        add_submenu_page('tagore-ags', 'Anmeldungen', 'Anmeldungen', $capability, 'tagore-ags-registrations', array($this, 'render_admin_registrations_page'));
        add_submenu_page('tagore-ags', 'Demo-Setup', 'Demo-Setup', $capability, 'tagore-ags-demo', array($this, 'render_admin_demo_page'));
        add_submenu_page('tagore-ags', 'Einstellungen', 'Einstellungen', $capability, 'tagore-ags-settings', array($this, 'render_admin_settings_page'));
    }

    private function assert_admin_permission(): void
    {
        if (!current_user_can(tg_ags_manage_capability())) {
            wp_die(esc_html__('Keine Berechtigung.', 'tagore-ags'));
        }
    }

    public function render_admin_courses_page(): void
    {
        $this->assert_admin_permission();

        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : '';
        $course_id = isset($_GET['course_id']) ? absint($_GET['course_id']) : 0;

        echo '<div class="wrap tg-ags-admin">';
        echo '<h1>Tagore AGs</h1>';

        if (isset($_GET['saved'])) {
            echo tg_ags_notice('AG gespeichert.');
        }
        if (isset($_GET['demo'])) {
            $count = absint($_GET['demo']);
            echo tg_ags_notice($count . ' Demo-AGs wurden angelegt. Bereits vorhandene Demo-AGs wurden übersprungen.');
        }

        if ($action === 'new' || ($action === 'edit' && $course_id > 0)) {
            $this->render_course_form($course_id);
        } else {
            $this->render_course_list();
        }

        echo '</div>';
    }

    private function get_course(int $course_id): ?object
    {
        global $wpdb;
        $course = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . tg_ags_table('courses') . ' WHERE id = %d', $course_id));
        return $course ?: null;
    }

    private function get_courses(string $school_year, bool $public_only = false): array
    {
        global $wpdb;
        $where = 'school_year = %s';
        if ($public_only) {
            $where .= ' AND is_active = 1 AND is_visible = 1';
        }

        return $wpdb->get_results($wpdb->prepare(
            'SELECT * FROM ' . tg_ags_table('courses') . " WHERE {$where} ORDER BY sort_order ASC, title ASC",
            $school_year
        ));
    }

    private function get_course_slots(int $course_id, bool $include_inactive = true): array
    {
        global $wpdb;
        $where = 'course_id = %d';
        if (!$include_inactive) {
            $where .= ' AND is_active = 1';
        }

        return $wpdb->get_results($wpdb->prepare(
            'SELECT * FROM ' . tg_ags_table('slots') . " WHERE {$where} ORDER BY sort_order ASC, weekday ASC, start_time ASC",
            $course_id
        ));
    }

    private function get_slot_with_course(int $slot_id): ?object
    {
        global $wpdb;

        $sql = 'SELECT s.*, c.title, c.allowed_grades, c.only_grade_7, c.registration_open, c.is_active AS course_active, c.is_visible AS course_visible
                FROM ' . tg_ags_table('slots') . ' s
                INNER JOIN ' . tg_ags_table('courses') . ' c ON c.id = s.course_id
                WHERE s.id = %d';

        $row = $wpdb->get_row($wpdb->prepare($sql, $slot_id));
        return $row ?: null;
    }

    private function render_course_list(): void
    {
        $school_year = isset($_GET['school_year']) ? tg_ags_sanitize_school_year(wp_unslash($_GET['school_year'])) : tg_ags_current_school_year();
        $courses = $this->get_courses($school_year, false);

        echo '<p>';
        echo '<a class="button button-primary" href="' . esc_url(tg_ags_admin_url(array('page' => 'tagore-ags', 'action' => 'new'))) . '">Neue AG anlegen</a> ';
        echo '<a class="button" href="' . esc_url(tg_ags_admin_url(array('page' => 'tagore-ags-demo'))) . '">Demo-Setup</a>';
        echo '</p>';

        echo '<form method="get" class="tg-ags-admin-filter">';
        echo '<input type="hidden" name="page" value="tagore-ags">';
        echo '<label>Schuljahr <input type="text" name="school_year" value="' . esc_attr($school_year) . '" placeholder="2026/2027"></label> ';
        echo '<button class="button">Filtern</button>';
        echo '</form>';

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Bild</th><th>AG</th><th>Bereich</th><th>Zielgruppe</th><th>Slots</th><th>Status</th><th></th></tr></thead><tbody>';

        if (empty($courses)) {
            echo '<tr><td colspan="7">Keine AGs für dieses Schuljahr angelegt.</td></tr>';
        }

        foreach ($courses as $course) {
            $slots = $this->get_course_slots((int) $course->id, false);
            $slot_labels = array();
            foreach ($slots as $slot) {
                $slot_labels[] = tg_ags_weekday_label($slot->weekday) . ', ' . tg_ags_format_time($slot->start_time) . '–' . tg_ags_format_time($slot->end_time) . ($slot->room ? ', ' . $slot->room : '');
            }

            $target = $course->only_grade_7 ? 'nur Klasse 7' : ($course->allowed_grades ? $course->allowed_grades : 'alle');
            $status = array();
            $status[] = $course->is_active ? 'aktiv' : 'inaktiv';
            $status[] = $course->is_visible ? 'sichtbar' : 'versteckt';
            $status[] = $course->registration_open ? 'Anmeldung offen' : 'Anmeldung geschlossen';

            echo '<tr>';
            echo '<td><img class="tg-ags-admin-thumb" src="' . esc_url(tg_ags_course_image_url($course->image_url ?? '')) . '" alt=""></td>';
            echo '<td><strong>' . esc_html($course->title) . '</strong><br><small>' . esc_html($course->school_year) . '</small></td>';
            echo '<td>' . esc_html((string) $course->category) . '</td>';
            echo '<td>' . esc_html($target) . '</td>';
            echo '<td>' . esc_html(implode(' | ', $slot_labels)) . '</td>';
            echo '<td>' . esc_html(implode(', ', $status)) . '</td>';
            echo '<td><a class="button" href="' . esc_url(tg_ags_admin_url(array('page' => 'tagore-ags', 'action' => 'edit', 'course_id' => (int) $course->id))) . '">Bearbeiten</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private function render_course_form(int $course_id): void
    {
        $course = $course_id > 0 ? $this->get_course($course_id) : null;
        $slots = $course ? $this->get_course_slots((int) $course->id, true) : array();
        $weekdays = tg_ags_weekdays();

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="tg-ags-admin-form">';
        wp_nonce_field('tg_ags_save_course');
        echo '<input type="hidden" name="action" value="tg_ags_save_course">';
        echo '<input type="hidden" name="course_id" value="' . esc_attr($course_id) . '">';

        echo '<h2>' . ($course ? 'AG bearbeiten' : 'Neue AG') . '</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';
        $this->admin_text_row('Schuljahr', 'school_year', $course->school_year ?? tg_ags_current_school_year(), 'Format: 2026/2027');
        $this->admin_text_row('Titel', 'title', $course->title ?? '', 'Pflichtfeld');
        $this->admin_text_row('Bereich/Kategorie', 'category', $course->category ?? '', 'z. B. Sport, Musik, Naturwissenschaften');
        $this->admin_text_row('Leitung', 'leader_name', $course->leader_name ?? '', 'Name oder Funktionsbezeichnung');
        $this->admin_image_row('Vorschaubild', 'image_url', $course->image_url ?? '');
        $this->admin_text_row('Info-Link', 'info_url', $course->info_url ?? '', 'Optional: bestehende AG-Seite oder Detailseite');
        $this->admin_textarea_row('Kurzbeschreibung', 'short_description', $course->short_description ?? '', 3);
        $this->admin_textarea_row('Beschreibung', 'description', $course->description ?? '', 6);
        $this->admin_text_row('Erlaubte Jahrgänge', 'allowed_grades', $course->allowed_grades ?? '', 'Leer = alle; erlaubt: 7,8,9,10,11,12,WKK');

        echo '<tr><th scope="row">Regeln/Status</th><td>';
        $this->admin_checkbox('only_grade_7', 'nur Klasse 7', !empty($course->only_grade_7));
        $this->admin_checkbox('is_active', 'aktiv', $course ? !empty($course->is_active) : true);
        $this->admin_checkbox('is_visible', 'im Frontend sichtbar', $course ? !empty($course->is_visible) : true);
        $this->admin_checkbox('registration_open', 'Anmeldung geöffnet', $course ? !empty($course->registration_open) : true);
        echo '</td></tr>';
        $this->admin_text_row('Sortierung', 'sort_order', isset($course->sort_order) ? (string) $course->sort_order : '0', 'kleinere Zahl = weiter oben');
        echo '</tbody></table>';

        echo '<h2>Wöchentliche Slots</h2>';
        echo '<p>Eine Anmeldung bezieht sich auf genau einen aktiven Slot. Maximal vier Slots pro AG.</p>';
        echo '<table class="widefat striped tg-ags-slots-table"><thead><tr><th>Aktiv</th><th>Wochentag</th><th>Beginn</th><th>Ende</th><th>Raum</th><th>Max. TN</th><th>Sortierung</th></tr></thead><tbody>';

        for ($i = 0; $i < 4; $i++) {
            $slot = $slots[$i] ?? null;
            echo '<tr>';
            echo '<td><input type="hidden" name="slots[' . esc_attr($i) . '][id]" value="' . esc_attr($slot->id ?? 0) . '">';
            echo '<label><input type="checkbox" name="slots[' . esc_attr($i) . '][is_active]" value="1" ' . checked($slot ? (int) $slot->is_active : ($i === 0), true, false) . '> aktiv</label></td>';
            echo '<td><select name="slots[' . esc_attr($i) . '][weekday]"><option value="0">–</option>';
            foreach ($weekdays as $value => $label) {
                echo '<option value="' . esc_attr($value) . '" ' . selected((int) ($slot->weekday ?? 0), $value, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select></td>';
            echo '<td><input type="time" name="slots[' . esc_attr($i) . '][start_time]" value="' . esc_attr(tg_ags_format_time($slot->start_time ?? '')) . '"></td>';
            echo '<td><input type="time" name="slots[' . esc_attr($i) . '][end_time]" value="' . esc_attr(tg_ags_format_time($slot->end_time ?? '')) . '"></td>';
            echo '<td><input type="text" name="slots[' . esc_attr($i) . '][room]" value="' . esc_attr($slot->room ?? '') . '"></td>';
            echo '<td><input type="number" min="0" name="slots[' . esc_attr($i) . '][max_participants]" value="' . esc_attr($slot->max_participants ?? 0) . '"></td>';
            echo '<td><input type="number" name="slots[' . esc_attr($i) . '][sort_order]" value="' . esc_attr($slot->sort_order ?? $i) . '"></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        submit_button('AG speichern');
        echo '</form>';
    }

    private function admin_text_row(string $label, string $name, string $value, string $description = ''): void
    {
        echo '<tr><th scope="row"><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label></th><td>';
        echo '<input type="text" class="regular-text" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
        if ($description !== '') {
            echo '<p class="description">' . esc_html($description) . '</p>';
        }
        echo '</td></tr>';
    }

    private function admin_image_row(string $label, string $name, string $value): void
    {
        $preview = tg_ags_course_image_url($value);
        echo '<tr><th scope="row"><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label></th><td>';
        echo '<div class="tg-ags-image-field">';
        echo '<img class="tg-ags-image-preview" data-tg-ags-image-preview src="' . esc_url($preview) . '" alt="">';
        echo '<input type="url" class="regular-text" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" data-tg-ags-image-input placeholder="https://..."> ';
        echo '<button type="button" class="button" data-tg-ags-media-button>Bild aus Mediathek wählen</button> ';
        echo '<button type="button" class="button" data-tg-ags-clear-image>Bild entfernen</button>';
        echo '</div><p class="description">URL eines Vorschaubilds. Über die Mediathek eingefügte Bilder bleiben in WordPress verwaltet.</p>';
        echo '</td></tr>';
    }

    private function admin_textarea_row(string $label, string $name, string $value, int $rows): void
    {
        echo '<tr><th scope="row"><label for="' . esc_attr($name) . '">' . esc_html($label) . '</label></th><td>';
        echo '<textarea class="large-text" rows="' . esc_attr($rows) . '" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea>';
        echo '</td></tr>';
    }

    private function admin_checkbox(string $name, string $label, bool $checked): void
    {
        echo '<label class="tg-ags-admin-checkbox"><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked($checked, true, false) . '> ' . esc_html($label) . '</label><br>';
    }

    public function handle_save_course(): void
    {
        $this->assert_admin_permission();
        check_admin_referer('tg_ags_save_course');

        global $wpdb;
        $now = current_time('mysql');
        $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';

        if ($title === '') {
            wp_die('Der Titel ist erforderlich.');
        }

        $school_year = isset($_POST['school_year']) ? tg_ags_sanitize_school_year(wp_unslash($_POST['school_year'])) : tg_ags_current_school_year();
        $data = array(
            'school_year' => $school_year,
            'title' => $title,
            'slug' => sanitize_title($title),
            'short_description' => isset($_POST['short_description']) ? sanitize_textarea_field(wp_unslash($_POST['short_description'])) : '',
            'description' => isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '',
            'image_url' => isset($_POST['image_url']) ? esc_url_raw(wp_unslash($_POST['image_url'])) : '',
            'info_url' => isset($_POST['info_url']) ? esc_url_raw(wp_unslash($_POST['info_url'])) : '',
            'category' => isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : '',
            'leader_name' => isset($_POST['leader_name']) ? sanitize_text_field(wp_unslash($_POST['leader_name'])) : '',
            'allowed_grades' => isset($_POST['allowed_grades']) ? tg_ags_sanitize_allowed_grades(wp_unslash($_POST['allowed_grades'])) : '',
            'only_grade_7' => isset($_POST['only_grade_7']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
            'registration_open' => isset($_POST['registration_open']) ? 1 : 0,
            'sort_order' => isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0,
            'updated_at' => $now,
        );

        if ($course_id > 0) {
            $wpdb->update(tg_ags_table('courses'), $data, array('id' => $course_id));
        } else {
            $data['created_at'] = $now;
            $wpdb->insert(tg_ags_table('courses'), $data);
            $course_id = (int) $wpdb->insert_id;
        }

        $slots = isset($_POST['slots']) && is_array($_POST['slots']) ? wp_unslash($_POST['slots']) : array();
        foreach ($slots as $slot) {
            if (!is_array($slot)) {
                continue;
            }

            $slot_id = isset($slot['id']) ? absint($slot['id']) : 0;
            $weekday = isset($slot['weekday']) ? absint($slot['weekday']) : 0;
            $start_time = isset($slot['start_time']) ? sanitize_text_field($slot['start_time']) : '';
            $end_time = isset($slot['end_time']) ? sanitize_text_field($slot['end_time']) : '';
            $room = isset($slot['room']) ? sanitize_text_field($slot['room']) : '';
            $max_participants = isset($slot['max_participants']) ? max(0, intval($slot['max_participants'])) : 0;
            $is_active = isset($slot['is_active']) ? 1 : 0;
            $sort_order = isset($slot['sort_order']) ? intval($slot['sort_order']) : 0;

            $has_values = $weekday > 0 || $start_time !== '' || $end_time !== '' || $room !== '';
            if (!$has_values && $slot_id === 0) {
                continue;
            }

            if (!$has_values && $slot_id > 0) {
                $wpdb->update(tg_ags_table('slots'), array('is_active' => 0, 'updated_at' => $now), array('id' => $slot_id, 'course_id' => $course_id));
                continue;
            }

            if ($weekday < 1 || $weekday > 7 || !preg_match('/^\d{2}:\d{2}$/', $start_time) || !preg_match('/^\d{2}:\d{2}$/', $end_time)) {
                continue;
            }

            $slot_data = array(
                'course_id' => $course_id,
                'school_year' => $school_year,
                'weekday' => $weekday,
                'start_time' => $start_time . ':00',
                'end_time' => $end_time . ':00',
                'room' => $room,
                'max_participants' => $max_participants,
                'is_active' => $is_active,
                'sort_order' => $sort_order,
                'updated_at' => $now,
            );

            if ($slot_id > 0) {
                $wpdb->update(tg_ags_table('slots'), $slot_data, array('id' => $slot_id, 'course_id' => $course_id));
            } else {
                $slot_data['created_at'] = $now;
                $wpdb->insert(tg_ags_table('slots'), $slot_data);
            }
        }

        wp_safe_redirect(tg_ags_admin_url(array('page' => 'tagore-ags', 'action' => 'edit', 'course_id' => $course_id, 'saved' => 1)));
        exit;
    }

    public function render_admin_settings_page(): void
    {
        $this->assert_admin_permission();

        echo '<div class="wrap tg-ags-admin">';
        echo '<h1>Tagore AGs – Einstellungen</h1>';
        if (isset($_GET['saved'])) {
            echo tg_ags_notice('Einstellungen gespeichert.');
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('tg_ags_save_settings');
        echo '<input type="hidden" name="action" value="tg_ags_save_settings">';
        echo '<table class="form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row"><label for="tg_ags_current_school_year">Aktuelles Schuljahr</label></th><td>';
        echo '<input type="text" class="regular-text" id="tg_ags_current_school_year" name="current_school_year" value="' . esc_attr(tg_ags_current_school_year()) . '">';
        echo '<p class="description">Format: 2026/2027. AGs und Anmeldungen werden schuljahrbezogen geführt.</p>';
        echo '</td></tr>';
        echo '<tr><th scope="row"><label for="tg_ags_classes">Klassen/Kurse</label></th><td>';
        echo '<textarea class="large-text code" rows="12" id="tg_ags_classes" name="classes_text">' . esc_textarea(implode("\n", tg_ags_get_classes())) . '</textarea>';
        echo '<p class="description">Eine Klasse/ein Kurs pro Zeile. Diese Liste wird im Anmeldeformular als Dropdown verwendet.</p>';
        echo '</td></tr>';
        echo '</tbody></table>';
        submit_button('Einstellungen speichern');
        echo '</form>';
        echo '</div>';
    }

    public function handle_save_settings(): void
    {
        $this->assert_admin_permission();
        check_admin_referer('tg_ags_save_settings');

        $school_year = isset($_POST['current_school_year']) ? tg_ags_sanitize_school_year(wp_unslash($_POST['current_school_year'])) : tg_ags_default_school_year();
        $classes_text = isset($_POST['classes_text']) ? (string) wp_unslash($_POST['classes_text']) : '';
        $classes = tg_ags_sanitize_classes_from_text($classes_text);

        update_option('tg_ags_current_school_year', $school_year, false);
        update_option('tg_ags_classes', !empty($classes) ? $classes : tg_ags_default_classes(), false);

        wp_safe_redirect(tg_ags_admin_url(array('page' => 'tagore-ags-settings', 'saved' => 1)));
        exit;
    }

    public function render_admin_demo_page(): void
    {
        $this->assert_admin_permission();
        $school_year = isset($_GET['school_year']) ? tg_ags_sanitize_school_year(wp_unslash($_GET['school_year'])) : tg_ags_current_school_year();
        $demo = tg_ags_demo_courses();

        echo '<div class="wrap tg-ags-admin">';
        echo '<h1>Tagore AGs – Demo-Setup</h1>';
        echo '<p>Legt eine Auswahl vorhandener AGs als Demo-Datensatz für das gewählte Schuljahr an. Vorhandene AGs mit gleichem Slug und Schuljahr werden nicht dupliziert.</p>';
        echo '<form method="get" class="tg-ags-admin-filter">';
        echo '<input type="hidden" name="page" value="tagore-ags-demo">';
        echo '<label>Schuljahr <input type="text" name="school_year" value="' . esc_attr($school_year) . '"></label> ';
        echo '<button class="button">Anzeigen</button>';
        echo '</form>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('tg_ags_install_demo');
        echo '<input type="hidden" name="action" value="tg_ags_install_demo">';
        echo '<input type="hidden" name="school_year" value="' . esc_attr($school_year) . '">';
        submit_button('Demo-AGs für ' . $school_year . ' anlegen', 'primary');
        echo '</form>';

        echo '<h2>Enthaltene Demo-AGs</h2>';
        echo '<div class="tg-ags-grid tg-ags-demo-grid">';
        foreach ($demo as $course) {
            echo '<article class="tg-ags-card">';
            echo '<div class="tg-ags-card-image-wrap"><img class="tg-ags-card-image" src="' . esc_url($course['image_url']) . '" alt=""></div>';
            echo '<div class="tg-ags-card-body"><h3>' . esc_html($course['title']) . '</h3>';
            echo '<p>' . esc_html($course['short_description']) . '</p>';
            echo '<p><small>' . esc_html($course['category'] . ' · Jahrgänge: ' . $course['allowed_grades']) . '</small></p></div>';
            echo '</article>';
        }
        echo '</div>';
        echo '</div>';
    }

    public function handle_install_demo(): void
    {
        $this->assert_admin_permission();
        check_admin_referer('tg_ags_install_demo');

        global $wpdb;
        $school_year = isset($_POST['school_year']) ? tg_ags_sanitize_school_year(wp_unslash($_POST['school_year'])) : tg_ags_current_school_year();
        $now = current_time('mysql');
        $inserted = 0;

        foreach (tg_ags_demo_courses() as $course) {
            $slug = sanitize_title($course['title']);
            $exists = (int) $wpdb->get_var($wpdb->prepare(
                'SELECT COUNT(*) FROM ' . tg_ags_table('courses') . ' WHERE school_year = %s AND slug = %s',
                $school_year,
                $slug
            ));
            if ($exists > 0) {
                continue;
            }

            $wpdb->insert(tg_ags_table('courses'), array(
                'school_year' => $school_year,
                'title' => sanitize_text_field($course['title']),
                'slug' => $slug,
                'short_description' => sanitize_textarea_field($course['short_description']),
                'description' => wp_kses_post($course['description']),
                'image_url' => esc_url_raw($course['image_url']),
                'info_url' => esc_url_raw($course['info_url']),
                'category' => sanitize_text_field($course['category']),
                'leader_name' => sanitize_text_field($course['leader_name']),
                'allowed_grades' => tg_ags_sanitize_allowed_grades($course['allowed_grades']),
                'only_grade_7' => !empty($course['only_grade_7']) ? 1 : 0,
                'is_active' => 1,
                'is_visible' => 1,
                'registration_open' => 1,
                'sort_order' => intval($course['sort_order']),
                'created_at' => $now,
                'updated_at' => $now,
            ));

            $course_id = (int) $wpdb->insert_id;
            if ($course_id <= 0) {
                continue;
            }

            foreach ($course['slots'] as $index => $slot) {
                $wpdb->insert(tg_ags_table('slots'), array(
                    'course_id' => $course_id,
                    'school_year' => $school_year,
                    'weekday' => absint($slot['weekday']),
                    'start_time' => sanitize_text_field($slot['start_time']) . ':00',
                    'end_time' => sanitize_text_field($slot['end_time']) . ':00',
                    'room' => sanitize_text_field($slot['room']),
                    'max_participants' => max(0, intval($slot['max_participants'])),
                    'is_active' => 1,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ));
            }
            $inserted++;
        }

        wp_safe_redirect(tg_ags_admin_url(array('page' => 'tagore-ags', 'school_year' => $school_year, 'demo' => $inserted)));
        exit;
    }

    private function count_active_registrations(int $slot_id): int
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . tg_ags_table('registrations') . ' WHERE slot_id = %d AND status = %s',
            $slot_id,
            'active'
        ));
    }

    private function get_public_slots(string $school_year): array
    {
        global $wpdb;

        $sql = 'SELECT s.*, c.title, c.short_description, c.description, c.image_url, c.info_url, c.category, c.leader_name, c.allowed_grades, c.only_grade_7, c.registration_open
                FROM ' . tg_ags_table('slots') . ' s
                INNER JOIN ' . tg_ags_table('courses') . ' c ON c.id = s.course_id
                WHERE s.school_year = %s
                  AND s.is_active = 1
                  AND c.is_active = 1
                  AND c.is_visible = 1
                ORDER BY c.sort_order ASC, c.title ASC, s.weekday ASC, s.start_time ASC';

        return $wpdb->get_results($wpdb->prepare($sql, $school_year));
    }

    private function get_public_courses_with_slots(string $school_year): array
    {
        $courses = $this->get_courses($school_year, true);
        $result = array();
        foreach ($courses as $course) {
            $course->slots = $this->get_course_slots((int) $course->id, false);
            if (!empty($course->slots)) {
                $result[] = $course;
            }
        }
        return $result;
    }

    public function shortcode_list($atts): string
    {
        $atts = shortcode_atts(array(
            'school_year' => tg_ags_current_school_year(),
            'registration_url' => '',
        ), (array) $atts, 'tagore_ag_liste');

        $school_year = tg_ags_sanitize_school_year($atts['school_year']);
        $registration_url = esc_url_raw((string) $atts['registration_url']);
        $courses = $this->get_public_courses_with_slots($school_year);

        wp_enqueue_style('tagore-ags');
        wp_enqueue_script('tagore-ags');

        ob_start();
        echo '<div class="tg-ags tg-ags-list" data-tg-ags-list>';
        echo '<h2>Arbeitsgemeinschaften ' . esc_html($school_year) . '</h2>';
        $this->render_frontend_filters(false);

        if (empty($courses)) {
            echo '<p>Derzeit sind keine AGs für dieses Schuljahr veröffentlicht.</p>';
        } else {
            echo '<div class="tg-ags-grid">';
            foreach ($courses as $course) {
                $this->render_course_card($course, $registration_url);
            }
            echo '</div>';
        }

        echo '</div>';
        return (string) ob_get_clean();
    }

    private function render_frontend_filters(bool $include_classes, bool $show_class_filter = true): void
    {
        $weekdays = tg_ags_weekdays();
        echo '<div class="tg-ags-filters">';
        if ($show_class_filter && $include_classes) {
            echo '<label>Klasse/Kurs <select name="class_name" data-tg-ags-class-select required>';
            echo '<option value="">– Bitte auswählen –</option>';
            foreach (tg_ags_get_classes() as $class) {
                echo '<option value="' . esc_attr($class) . '">' . esc_html($class) . '</option>';
            }
            echo '</select></label>';
        } elseif ($show_class_filter) {
            echo '<label>Klasse/Kurs <select data-tg-ags-class-select>';
            echo '<option value="">alle anzeigen</option>';
            foreach (tg_ags_get_classes() as $class) {
                echo '<option value="' . esc_attr($class) . '">' . esc_html($class) . '</option>';
            }
            echo '</select></label>';
        }

        echo '<label>Wochentag <select data-tg-ags-weekday-select>';
        echo '<option value="">alle Tage</option>';
        foreach ($weekdays as $value => $label) {
            echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
        }
        echo '</select></label>';
        echo '</div>';
    }

    private function render_course_card(object $course, string $registration_url = ''): void
    {
        $weekdays = array();
        $slot_lines = array();
        $total_max = 0;
        $total_free = 0;
        $all_full = true;

        foreach ($course->slots as $slot) {
            $taken = $this->count_active_registrations((int) $slot->id);
            $max = (int) $slot->max_participants;
            $free = $max > 0 ? max(0, $max - $taken) : null;
            $weekdays[] = (string) $slot->weekday;
            $slot_lines[] = tg_ags_weekday_label($slot->weekday) . ', ' . tg_ags_format_time($slot->start_time) . '–' . tg_ags_format_time($slot->end_time) . (!empty($slot->room) ? ' · ' . $slot->room : '');
            if ($max > 0) {
                $total_max += $max;
                $total_free += (int) $free;
                if ($free > 0) {
                    $all_full = false;
                }
            } else {
                $all_full = false;
            }
        }

        $weekdays = array_values(array_unique($weekdays));
        $target = $course->only_grade_7 ? 'nur Klasse 7' : ($course->allowed_grades ? 'Jahrgänge ' . $course->allowed_grades : 'alle Jahrgänge');

        echo '<article class="tg-ags-card tg-ags-course-card" data-tg-ags-filter-item data-weekdays="' . esc_attr(implode(',', $weekdays)) . '" data-only-grade-7="' . esc_attr((int) $course->only_grade_7) . '" data-allowed-grades="' . esc_attr($course->allowed_grades) . '" data-full="' . esc_attr($all_full ? '1' : '0') . '">';
        echo '<div class="tg-ags-card-image-wrap"><img class="tg-ags-card-image" src="' . esc_url(tg_ags_course_image_url($course->image_url ?? '')) . '" alt=""></div>';
        echo '<div class="tg-ags-card-body">';
        echo '<h3>' . esc_html($course->title) . '</h3>';
        if (!empty($course->short_description)) {
            echo '<p class="tg-ags-card-text">' . esc_html($course->short_description) . '</p>';
        }
        echo '<dl class="tg-ags-meta">';
        if (!empty($course->category)) {
            echo '<dt>Bereich</dt><dd>' . esc_html($course->category) . '</dd>';
        }
        if (!empty($course->leader_name)) {
            echo '<dt>Leitung</dt><dd>' . esc_html($course->leader_name) . '</dd>';
        }
        echo '<dt>Zielgruppe</dt><dd>' . esc_html($target) . '</dd>';
        echo '<dt>Termine</dt><dd>' . esc_html(implode(' | ', $slot_lines)) . '</dd>';
        if ($total_max > 0) {
            echo '<dt>Plätze</dt><dd>' . esc_html($total_free . ' frei von ' . $total_max) . '</dd>';
        } else {
            echo '<dt>Plätze</dt><dd>keine Begrenzung hinterlegt</dd>';
        }
        echo '</dl>';
        echo '<div class="tg-ags-card-actions">';
        if (!empty($course->info_url)) {
            echo '<a class="button" href="' . esc_url($course->info_url) . '">Details</a> ';
        }
        if ($registration_url !== '' && !$all_full && !empty($course->registration_open)) {
            echo '<a class="button button-primary" href="' . esc_url($registration_url) . '">Zur Anmeldung</a>';
        }
        echo '</div>';
        if ($all_full) {
            echo '<p class="tg-ags-badge">ausgebucht</p>';
        }
        echo '</div></article>';
    }

    private function render_slot_card(object $slot, string $registration_url = ''): void
    {
        $taken = $this->count_active_registrations((int) $slot->id);
        $max = (int) $slot->max_participants;
        $free = $max > 0 ? max(0, $max - $taken) : null;
        $is_full = $max > 0 && $taken >= $max;

        echo '<article class="tg-ags-card tg-ags-slot-option" data-tg-ags-filter-item data-weekday="' . esc_attr($slot->weekday) . '" data-only-grade-7="' . esc_attr((int) $slot->only_grade_7) . '" data-allowed-grades="' . esc_attr($slot->allowed_grades) . '" data-full="' . esc_attr($is_full ? '1' : '0') . '">';
        echo '<div class="tg-ags-card-image-wrap"><img class="tg-ags-card-image" src="' . esc_url(tg_ags_course_image_url($slot->image_url ?? '')) . '" alt=""></div>';
        echo '<div class="tg-ags-card-body"><h3>' . esc_html($slot->title) . '</h3>';
        if (!empty($slot->short_description)) {
            echo '<p>' . esc_html($slot->short_description) . '</p>';
        }
        echo '<dl class="tg-ags-meta">';
        echo '<dt>Zeit</dt><dd>' . esc_html(tg_ags_weekday_label($slot->weekday) . ', ' . tg_ags_format_time($slot->start_time) . '–' . tg_ags_format_time($slot->end_time)) . '</dd>';
        if (!empty($slot->room)) {
            echo '<dt>Raum</dt><dd>' . esc_html($slot->room) . '</dd>';
        }
        if (!empty($slot->leader_name)) {
            echo '<dt>Leitung</dt><dd>' . esc_html($slot->leader_name) . '</dd>';
        }
        echo '<dt>Zielgruppe</dt><dd>' . esc_html($slot->only_grade_7 ? 'nur Klasse 7' : ($slot->allowed_grades ? $slot->allowed_grades : 'alle')) . '</dd>';
        echo '<dt>Plätze</dt><dd>' . esc_html($max > 0 ? ($free . ' frei von ' . $max) : 'keine Begrenzung hinterlegt') . '</dd>';
        echo '</dl>';
        if ($registration_url !== '' && !$is_full && !empty($slot->registration_open)) {
            echo '<p><a class="button" href="' . esc_url($registration_url) . '">Zur Anmeldung</a></p>';
        }
        if ($is_full) {
            echo '<p class="tg-ags-badge">ausgebucht</p>';
        }
        echo '</div></article>';
    }

    public function shortcode_registration($atts): string
    {
        $atts = shortcode_atts(array(
            'school_year' => tg_ags_current_school_year(),
        ), (array) $atts, 'tagore_ag_anmeldung');

        $school_year = tg_ags_sanitize_school_year($atts['school_year']);
        $messages = array();
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tg_ags_registration_submit'])) {
            $result = $this->handle_frontend_registration($school_year);
            $messages = $result['messages'];
            $success = $result['success'];
        }

        wp_enqueue_style('tagore-ags');
        wp_enqueue_script('tagore-ags');

        ob_start();
        echo '<div class="tg-ags tg-ags-registration">';
        echo '<h2>AG-Anmeldung ' . esc_html($school_year) . '</h2>';

        foreach ($messages as $message) {
            echo '<p class="tg-ags-message ' . ($success ? 'tg-ags-message-success' : 'tg-ags-message-error') . '">' . esc_html($message) . '</p>';
        }

        if (!$success) {
            $this->render_registration_form($school_year);
        }

        echo '</div>';
        return (string) ob_get_clean();
    }

    private function render_registration_form(string $school_year): void
    {
        $slots = array_filter($this->get_public_slots($school_year), static function ($slot) {
            return !empty($slot->registration_open);
        });

        echo '<form method="post" class="tg-ags-registration-form" data-tg-ags-registration-form>';
        wp_nonce_field('tg_ags_frontend_registration', 'tg_ags_nonce');
        echo '<input type="hidden" name="tg_ags_registration_submit" value="1">';
        echo '<div class="tg-ags-form-grid">';
        echo '<label>Klasse/Kurs <select name="class_name" data-tg-ags-class-select required><option value="">– Bitte auswählen –</option>';
        foreach (tg_ags_get_classes() as $class) {
            $selected = isset($_POST['class_name']) && sanitize_text_field(wp_unslash($_POST['class_name'])) === $class;
            echo '<option value="' . esc_attr($class) . '" ' . selected($selected, true, false) . '>' . esc_html($class) . '</option>';
        }
        echo '</select></label>';
        echo '<label>Vorname Schüler*in <input type="text" name="student_first_name" required value="' . esc_attr(isset($_POST['student_first_name']) ? sanitize_text_field(wp_unslash($_POST['student_first_name'])) : '') . '"></label>';
        echo '<label>Nachname Schüler*in <input type="text" name="student_last_name" required value="' . esc_attr(isset($_POST['student_last_name']) ? sanitize_text_field(wp_unslash($_POST['student_last_name'])) : '') . '"></label>';
        echo '<label>E-Mail Erziehungsberechtigte*r / Kontakt <input type="email" name="guardian_email" value="' . esc_attr(isset($_POST['guardian_email']) ? sanitize_email(wp_unslash($_POST['guardian_email'])) : '') . '"></label>';
        echo '</div>';

        $this->render_frontend_filters(false, false);

        echo '<fieldset class="tg-ags-slot-fieldset"><legend>AG-Slot auswählen</legend>';
        if (empty($slots)) {
            echo '<p>Derzeit sind keine Anmeldungen möglich.</p>';
        } else {
            foreach ($slots as $slot) {
                $taken = $this->count_active_registrations((int) $slot->id);
                $max = (int) $slot->max_participants;
                $is_full = $max > 0 && $taken >= $max;
                $free_label = $max > 0 ? max(0, $max - $taken) . ' freie Plätze' : 'keine Begrenzung hinterlegt';
                $checked = isset($_POST['slot_id']) && absint($_POST['slot_id']) === (int) $slot->id;

                echo '<label class="tg-ags-slot-choice tg-ags-slot-option" data-tg-ags-filter-item data-weekday="' . esc_attr($slot->weekday) . '" data-only-grade-7="' . esc_attr((int) $slot->only_grade_7) . '" data-allowed-grades="' . esc_attr($slot->allowed_grades) . '" data-full="' . esc_attr($is_full ? '1' : '0') . '">';
                echo '<input type="radio" name="slot_id" value="' . esc_attr($slot->id) . '" ' . checked($checked, true, false) . ' ' . disabled($is_full, true, false) . ' required> ';
                echo '<img class="tg-ags-slot-image" src="' . esc_url(tg_ags_course_image_url($slot->image_url ?? '')) . '" alt="">';
                echo '<span><strong>' . esc_html($slot->title) . '</strong><br>';
                echo esc_html(tg_ags_weekday_label($slot->weekday) . ', ' . tg_ags_format_time($slot->start_time) . '–' . tg_ags_format_time($slot->end_time));
                if (!empty($slot->room)) {
                    echo ' · ' . esc_html($slot->room);
                }
                echo '<br><small>' . esc_html(($slot->only_grade_7 ? 'nur Klasse 7' : ($slot->allowed_grades ? 'Jahrgänge: ' . $slot->allowed_grades : 'alle Jahrgänge')) . ' · ' . $free_label) . '</small>';
                echo '</span></label>';
            }
        }
        echo '</fieldset>';

        echo '<label class="tg-ags-consent"><input type="checkbox" name="consent_privacy" value="1" required> Ich habe die Datenschutzhinweise zur AG-Anmeldung zur Kenntnis genommen und stimme der Verarbeitung der Angaben für die AG-Anmeldung zu.</label>';
        echo '<p><button type="submit" class="button button-primary">AG verbindlich anmelden</button></p>';
        echo '</form>';
    }

    private function handle_frontend_registration(string $school_year): array
    {
        global $wpdb;

        $messages = array();
        if (!isset($_POST['tg_ags_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['tg_ags_nonce'])), 'tg_ags_frontend_registration')) {
            return array('success' => false, 'messages' => array('Die Anmeldung konnte aus Sicherheitsgründen nicht verarbeitet werden. Bitte Formular neu laden.'));
        }

        $class_name = isset($_POST['class_name']) ? sanitize_text_field(wp_unslash($_POST['class_name'])) : '';
        $first_name = isset($_POST['student_first_name']) ? sanitize_text_field(wp_unslash($_POST['student_first_name'])) : '';
        $last_name = isset($_POST['student_last_name']) ? sanitize_text_field(wp_unslash($_POST['student_last_name'])) : '';
        $guardian_email = isset($_POST['guardian_email']) ? sanitize_email(wp_unslash($_POST['guardian_email'])) : '';
        $slot_id = isset($_POST['slot_id']) ? absint($_POST['slot_id']) : 0;
        $consent = isset($_POST['consent_privacy']) ? 1 : 0;

        if (!tg_ags_is_valid_class($class_name)) {
            $messages[] = 'Bitte eine gültige Klasse/einen gültigen Kurs auswählen.';
        }
        if ($first_name === '' || $last_name === '') {
            $messages[] = 'Bitte Vor- und Nachname der Schülerin/des Schülers eintragen.';
        }
        if ($guardian_email !== '' && !is_email($guardian_email)) {
            $messages[] = 'Bitte eine gültige E-Mail-Adresse eintragen oder das Feld leer lassen.';
        }
        if ($slot_id <= 0) {
            $messages[] = 'Bitte einen AG-Slot auswählen.';
        }
        if (!$consent) {
            $messages[] = 'Die Datenschutzhinweise müssen bestätigt werden.';
        }

        if (!empty($messages)) {
            return array('success' => false, 'messages' => $messages);
        }

        $slot = $this->get_slot_with_course($slot_id);
        if (!$slot || $slot->school_year !== $school_year || empty($slot->is_active) || empty($slot->course_active) || empty($slot->course_visible) || empty($slot->registration_open)) {
            return array('success' => false, 'messages' => array('Der gewählte AG-Slot ist nicht verfügbar.'));
        }

        if (!tg_ags_grade_is_allowed($class_name, (string) $slot->allowed_grades, !empty($slot->only_grade_7))) {
            return array('success' => false, 'messages' => array('Dieser AG-Slot ist für die gewählte Klasse nicht freigegeben.'));
        }

        $taken = $this->count_active_registrations((int) $slot->id);
        if ((int) $slot->max_participants > 0 && $taken >= (int) $slot->max_participants) {
            return array('success' => false, 'messages' => array('Dieser AG-Slot ist inzwischen ausgebucht.'));
        }

        $duplicate = (int) $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM ' . tg_ags_table('registrations') . ' WHERE school_year = %s AND class_name = %s AND student_first_name = %s AND student_last_name = %s AND status = %s',
            $school_year,
            $class_name,
            $first_name,
            $last_name,
            'active'
        ));

        if ($duplicate > 0) {
            return array('success' => false, 'messages' => array('Für diese Schüler*in existiert in diesem Schuljahr bereits eine aktive AG-Anmeldung. Änderungen bitte über die Schule veranlassen.'));
        }

        $now = current_time('mysql');
        $wpdb->insert(tg_ags_table('registrations'), array(
            'course_id' => (int) $slot->course_id,
            'slot_id' => (int) $slot->id,
            'school_year' => $school_year,
            'class_name' => $class_name,
            'grade_key' => tg_ags_extract_grade_key($class_name),
            'student_first_name' => $first_name,
            'student_last_name' => $last_name,
            'guardian_email' => $guardian_email,
            'status' => 'active',
            'consent_privacy' => $consent,
            'created_at' => $now,
            'updated_at' => $now,
        ));

        if ($wpdb->insert_id <= 0) {
            return array('success' => false, 'messages' => array('Die Anmeldung konnte nicht gespeichert werden.'));
        }

        return array('success' => true, 'messages' => array('Die AG-Anmeldung wurde gespeichert. Die Teilnahme gilt bis auf Widerruf.'));
    }

    public function render_admin_registrations_page(): void
    {
        $this->assert_admin_permission();
        global $wpdb;

        $school_year = isset($_GET['school_year']) ? tg_ags_sanitize_school_year(wp_unslash($_GET['school_year'])) : tg_ags_current_school_year();
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'active';
        if (!array_key_exists($status, tg_ags_status_labels())) {
            $status = 'active';
        }

        $sql = 'SELECT r.*, c.title, s.weekday, s.start_time, s.end_time, s.room
                FROM ' . tg_ags_table('registrations') . ' r
                INNER JOIN ' . tg_ags_table('courses') . ' c ON c.id = r.course_id
                INNER JOIN ' . tg_ags_table('slots') . ' s ON s.id = r.slot_id
                WHERE r.school_year = %s AND r.status = %s
                ORDER BY r.class_name ASC, r.student_last_name ASC, r.student_first_name ASC';

        $registrations = $wpdb->get_results($wpdb->prepare($sql, $school_year, $status));

        echo '<div class="wrap tg-ags-admin">';
        echo '<h1>AG-Anmeldungen</h1>';
        if (isset($_GET['updated'])) {
            echo tg_ags_notice('Anmeldung aktualisiert.');
        }

        echo '<form method="get" class="tg-ags-admin-filter">';
        echo '<input type="hidden" name="page" value="tagore-ags-registrations">';
        echo '<label>Schuljahr <input type="text" name="school_year" value="' . esc_attr($school_year) . '"></label> ';
        echo '<label>Status <select name="status">';
        foreach (tg_ags_status_labels() as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($status, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label> ';
        echo '<button class="button">Filtern</button> ';
        echo '<a class="button" href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=tg_ags_export_csv&school_year=' . rawurlencode($school_year) . '&status=' . rawurlencode($status)), 'tg_ags_export_csv')) . '">CSV exportieren</a>';
        echo '</form>';

        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Schüler*in</th><th>Klasse</th><th>AG</th><th>Slot</th><th>Kontakt</th><th>Status</th><th>Datum</th><th></th></tr></thead><tbody>';
        if (empty($registrations)) {
            echo '<tr><td colspan="8">Keine Anmeldungen gefunden.</td></tr>';
        }
        foreach ($registrations as $registration) {
            echo '<tr>';
            echo '<td>' . esc_html($registration->student_last_name . ', ' . $registration->student_first_name) . '</td>';
            echo '<td>' . esc_html($registration->class_name) . '</td>';
            echo '<td>' . esc_html($registration->title) . '</td>';
            echo '<td>' . esc_html(tg_ags_weekday_label($registration->weekday) . ', ' . tg_ags_format_time($registration->start_time) . '–' . tg_ags_format_time($registration->end_time) . ($registration->room ? ', ' . $registration->room : '')) . '</td>';
            echo '<td>' . esc_html($registration->guardian_email) . '</td>';
            echo '<td>' . esc_html(tg_ags_status_label($registration->status)) . '</td>';
            echo '<td>' . esc_html($registration->created_at) . '</td>';
            echo '<td>';
            if ($registration->status === 'active') {
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                wp_nonce_field('tg_ags_update_registration');
                echo '<input type="hidden" name="action" value="tg_ags_update_registration">';
                echo '<input type="hidden" name="registration_id" value="' . esc_attr($registration->id) . '">';
                echo '<input type="hidden" name="new_status" value="withdrawn">';
                echo '<button class="button button-small">widerrufen</button>';
                echo '</form>';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }

    public function handle_update_registration(): void
    {
        $this->assert_admin_permission();
        check_admin_referer('tg_ags_update_registration');

        global $wpdb;
        $registration_id = isset($_POST['registration_id']) ? absint($_POST['registration_id']) : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_key(wp_unslash($_POST['new_status'])) : '';

        if ($registration_id <= 0 || !array_key_exists($new_status, tg_ags_status_labels())) {
            wp_die('Ungültige Anfrage.');
        }

        $data = array(
            'status' => $new_status,
            'updated_at' => current_time('mysql'),
        );
        if ($new_status === 'withdrawn') {
            $data['withdrawn_at'] = current_time('mysql');
        }

        $wpdb->update(tg_ags_table('registrations'), $data, array('id' => $registration_id));

        wp_safe_redirect(tg_ags_admin_url(array('page' => 'tagore-ags-registrations', 'updated' => 1)));
        exit;
    }

    public function handle_export_csv(): void
    {
        $this->assert_admin_permission();
        check_admin_referer('tg_ags_export_csv');

        global $wpdb;
        $school_year = isset($_GET['school_year']) ? tg_ags_sanitize_school_year(wp_unslash($_GET['school_year'])) : tg_ags_current_school_year();
        $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'active';
        if (!array_key_exists($status, tg_ags_status_labels())) {
            $status = 'active';
        }

        $sql = 'SELECT r.*, c.title, s.weekday, s.start_time, s.end_time, s.room
                FROM ' . tg_ags_table('registrations') . ' r
                INNER JOIN ' . tg_ags_table('courses') . ' c ON c.id = r.course_id
                INNER JOIN ' . tg_ags_table('slots') . ' s ON s.id = r.slot_id
                WHERE r.school_year = %s AND r.status = %s
                ORDER BY c.title ASC, s.weekday ASC, r.class_name ASC, r.student_last_name ASC';
        $rows = $wpdb->get_results($wpdb->prepare($sql, $school_year, $status), ARRAY_A);

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="tagore-ag-anmeldungen-' . sanitize_file_name($school_year) . '-' . sanitize_file_name($status) . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, array('Schuljahr', 'Status', 'Klasse', 'Jahrgang', 'Nachname', 'Vorname', 'E-Mail', 'AG', 'Wochentag', 'Beginn', 'Ende', 'Raum', 'Anmeldedatum'), ';');

        foreach ($rows as $row) {
            fputcsv($out, array(
                $row['school_year'],
                tg_ags_status_label($row['status']),
                $row['class_name'],
                $row['grade_key'],
                $row['student_last_name'],
                $row['student_first_name'],
                $row['guardian_email'],
                $row['title'],
                tg_ags_weekday_label($row['weekday']),
                tg_ags_format_time($row['start_time']),
                tg_ags_format_time($row['end_time']),
                $row['room'],
                $row['created_at'],
            ), ';');
        }

        fclose($out);
        exit;
    }
}
