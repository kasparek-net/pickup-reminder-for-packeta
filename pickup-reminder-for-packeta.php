<?php
/**
 * Plugin Name: Pickup Reminder for Packeta
 * Plugin URI: https://wp.kasparek.app/
 * Description: E-mails customers before the last day their Packeta (Zásilkovna) parcel can be picked up, so fewer cash-on-delivery parcels come back. Uses the parcel status and storage date synced by the official Packeta plugin.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce, packeta
 * Author: Jakub Kašpárek
 * Author URI: https://github.com/kasparek-net
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pickup-reminder-for-packeta
 * Domain Path: /languages
 * WC requires at least: 8.0
 * WC tested up to: 11.3
 *
 * @package PickupReminderForPacketa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PRP_VERSION', '1.0.0' );
define( 'PRP_PLUGIN_FILE', __FILE__ );
define( 'PRP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

require_once PRP_PLUGIN_PATH . 'includes/class-prp-settings.php';
require_once PRP_PLUGIN_PATH . 'includes/class-prp-reminder.php';
require_once PRP_PLUGIN_PATH . 'includes/class-prp-admin.php';

register_deactivation_hook( __FILE__, array( 'PRP_Reminder', 'unschedule' ) );

/**
 * Bundled Czech and Slovak translations for Czech and Slovak sites, where Packeta is
 * used the most, until language packs exist.
 *
 * @param string $locale Active locale.
 */
function prp_load_translation( $locale = '' ) {
	$locale = $locale ? $locale : determine_locale();
	$lang   = substr( $locale, 0, 2 );
	if ( 'cs' !== $lang && 'sk' !== $lang ) {
		return;
	}
	unload_textdomain( 'pickup-reminder-for-packeta', true );
	load_textdomain( 'pickup-reminder-for-packeta', PRP_PLUGIN_PATH . 'languages/pickup-reminder-for-packeta-' . ( 'sk' === $lang ? 'sk_SK' : 'cs_CZ' ) . '.mo', $locale );
}
add_action( 'init', 'prp_load_translation', 0 );
add_action( 'change_locale', 'prp_load_translation' );

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

add_action( 'plugins_loaded', function () {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	PRP_Reminder::init();
	if ( is_admin() ) {
		PRP_Admin::init();
	}
} );

add_filter( 'woocommerce_email_classes', function ( $emails ) {
	require_once PRP_PLUGIN_PATH . 'includes/class-prp-email.php';
	$emails['PRP_Email'] = new PRP_Email();
	return $emails;
} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=pickup-reminder-for-packeta' ) ) . '">' . esc_html__( 'Settings', 'pickup-reminder-for-packeta' ) . '</a>' );
	return $links;
} );
