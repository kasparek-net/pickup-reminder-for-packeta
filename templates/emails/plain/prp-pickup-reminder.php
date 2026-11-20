<?php
/**
 * Pickup reminder e-mail (plain text).
 *
 * @package PickupReminderForPacketa
 *
 * @var WC_Order $order
 * @var array    $parcel
 * @var string   $email_heading
 * @var string   $intro
 * @var string   $additional_content
 * @var bool     $sent_to_admin
 * @var bool     $plain_text
 * @var WC_Email $email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$prp_cod = (float) ( $parcel['cod'] ?? 0 );
if ( $prp_cod <= 0 && 'cod' === $order->get_payment_method() ) {
	$prp_cod = (float) $order->get_total();
}

echo "=================\n" . esc_html( wp_strip_all_tags( $email_heading ) ) . "\n=================\n\n";
echo esc_html( wp_strip_all_tags( $intro ) ) . "\n\n";
echo esc_html__( 'Pick up by', 'pickup-reminder-for-packeta' ) . ': ' . esc_html( PRP_Reminder::format_date( $parcel['stored_until'] ?? '' ) ) . "\n";
echo esc_html__( 'Pickup point', 'pickup-reminder-for-packeta' ) . ': ' . esc_html( trim( ( $parcel['point_name'] ?? '' ) . ', ' . ( $parcel['point_street'] ?? '' ) . ', ' . ( $parcel['point_city'] ?? '' ), ', ' ) ) . "\n";
if ( $prp_cod > 0 ) {
	echo esc_html__( 'To pay on pickup', 'pickup-reminder-for-packeta' ) . ': ' . esc_html( wp_strip_all_tags( wc_price( $prp_cod, array( 'currency' => $order->get_currency() ) ) ) ) . "\n";
}
echo esc_html__( 'Parcel number', 'pickup-reminder-for-packeta' ) . ': ' . esc_url( PRP_Reminder::tracking_url( $parcel['packet_id'] ?? '', get_locale() ) ) . "\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core e-mail hook.

if ( $additional_content ) {
	echo "\n" . esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n";
}
echo "\n----------------------------------------\n\n";
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core filter.
