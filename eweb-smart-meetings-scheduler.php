<?php
/**
 * Plugin Name:       EWEB - Smart Meetings Scheduler
 * Description:       Meeting booking plugin with multilingual scheduling, fixed slots, and server-side booking control.
 * Version:           1.3.4
 * Author:            Yisus_Dev
 * Author URI:        https://github.com/Yisus-Develop
 * Plugin URI:        https://github.com/Yisus-Develop/eweb-smart-meetings-scheduler
 * License:           GPL v2 or later
 * Requires at least: 6.0
 * Requires PHP:      8.1+
 * Tested up to:      6.8
 * Text Domain:       eweb-eweb-smart-meetings-scheduler
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ESMS_VERSION', '1.3.4' );
define( 'ESMS_FILE', __FILE__ );
define( 'ESMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'ESMS_URL', plugin_dir_url( __FILE__ ) );
define( 'ESMS_TABLE', 'esms_meetings' );
define( 'ESMS_TZ', 'America/New_York' );

define( 'ESMS_GITHUB_USER', 'Yisus-Develop' );
define( 'ESMS_GITHUB_REPO', 'eweb-smart-meetings-scheduler' );

require_once ESMS_DIR . 'includes/i18n.php';
require_once ESMS_DIR . 'includes/db.php';
require_once ESMS_DIR . 'includes/slots.php';
require_once ESMS_DIR . 'includes/mail.php';
require_once ESMS_DIR . 'includes/form.php';
require_once ESMS_DIR . 'includes/admin.php';
require_once ESMS_DIR . 'includes/class-eweb-github-updater.php';

add_action( 'plugins_loaded', 'esms_load_textdomain' );
add_action( 'plugins_loaded', 'esms_maybe_upgrade_schema' );
register_activation_hook( ESMS_FILE, 'esms_activate' );
add_shortcode( 'eweb_smart_meetings_form', 'esms_render_shortcode' );
add_action( 'wp_enqueue_scripts', 'esms_enqueue_assets' );

function esms_enqueue_assets(): void {
    wp_register_style( 'esms-form', ESMS_URL . 'assets/css/form.css', array(), ESMS_VERSION );
    wp_register_script( 'esms-form', ESMS_URL . 'assets/js/form.js', array(), ESMS_VERSION, true );
}

if ( class_exists( 'EWEB_GitHub_Updater' ) ) {
    new EWEB_GitHub_Updater( ESMS_FILE, ESMS_GITHUB_USER, ESMS_GITHUB_REPO );
}
