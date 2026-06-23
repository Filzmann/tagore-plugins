<?php
/**
 * Plugin Name: Tagore AG-Verwaltung
 * Description: Verwaltung und Anmeldung für Arbeitsgemeinschaften mit Schuljahr, Vorschaubildern, wöchentlichen Slots, Klassenlogik, Demo-Setup und CSV-Export.
 * Version: 0.2.0
 * Author: Tagore-Gymnasium / Simon
 * Text Domain: tagore-ags
 */

defined('ABSPATH') || exit;

define('TG_AGS_VERSION', '0.2.0');
define('TG_AGS_FILE', __FILE__);
define('TG_AGS_DIR', plugin_dir_path(__FILE__));
define('TG_AGS_URL', plugin_dir_url(__FILE__));

require_once TG_AGS_DIR . 'includes/helpers.php';
require_once TG_AGS_DIR . 'includes/database.php';
require_once TG_AGS_DIR . 'includes/class-tagore-ags.php';

register_activation_hook(__FILE__, array('TG_AGS_Database', 'activate'));

add_action('plugins_loaded', static function () {
    TG_AGS_Database::maybe_upgrade();
    TG_AGS_Plugin::instance();
});
