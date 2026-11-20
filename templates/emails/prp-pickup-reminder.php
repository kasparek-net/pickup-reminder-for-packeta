<?php
/**
 * Pickup reminder e-mail (HTML).
 *
 * Override by copying to yourtheme/woocommerce/emails/prp-pickup-reminder.php.
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

$prp_address = trim( implode( ', ', array_filter( array( $parcel['point_street'] ?? '', trim( ( $parcel['point_zip'] ?? '' ) . ' ' . ( $parcel['point_city'] ?? '' ) ) ) ) ) );
$prp_cod     = (float) ( $parcel['cod'] ?? 0 );
if ( $prp_cod <= 0 && 'cod' === $order->get_payment_method() ) {
	$prp_cod = (float) $order->get_total();
}

do_action( 'woocommerce_email_header', $email_heading, $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core e-mail hook.

echo wp_kses_post( wpautop( wptexturize( $intro ) ) );
?>
<table cellspacing="0" cellpadding="8" border="1" style="width:100%;border-collapse:collapse;margin:0 0 24px;border-color:#e5e5e5">
	<tr>
		<th scope="row" style="text-align:left;width:40%"><?php esc_html_e( 'Pick up by', 'pickup-reminder-for-packeta' ); ?></th>
		<td><strong><?php echo esc_html( PRP_Reminder::format_date( $parcel['stored_until'] ?? '' ) ); ?></strong></td>
	</tr>
	<tr>
		<th scope="row" style="text-align:left"><?php esc_html_e( 'Pickup point', 'pickup-reminder-for-packeta' ); ?></th>
		<td>
			<?php echo esc_html( $parcel['point_name'] ?? '' ); ?>
			<?php if ( $prp_address && false === strpos( (string) ( $parcel['point_name'] ?? '' ), (string) ( $parcel['point_street'] ?? '' ) ) ) : ?>
				<br><?php echo esc_html( $prp_address ); ?>
			<?php endif; ?>
			<?php if ( ! empty( $parcel['point_url'] ) ) : ?>
				<br><a href="<?php echo esc_url( $parcel['point_url'] ); ?>"><?php esc_html_e( 'Opening hours and map', 'pickup-reminder-for-packeta' ); ?></a>
			<?php endif; ?>
		</td>
	</tr>
	<?php if ( $prp_cod > 0 ) : ?>
	<tr>
		<th scope="row" style="text-align:left"><?php esc_html_e( 'To pay on pickup', 'pickup-reminder-for-packeta' ); ?></th>
		<td><?php echo wp_kses_post( wc_price( $prp_cod, array( 'currency' => $order->get_currency() ) ) ); ?></td>
	</tr>
	<?php endif; ?>
	<tr>
		<th scope="row" style="text-align:left"><?php esc_html_e( 'Parcel number', 'pickup-reminder-for-packeta' ); ?></th>
		<td><a href="<?php echo esc_url( PRP_Reminder::tracking_url( $parcel['packet_id'] ?? '', get_locale() ) ); ?>">Z<?php echo esc_html( ltrim( (string) ( $parcel['packet_id'] ?? '' ), 'Zz' ) ); ?></a></td>
	</tr>
</table>
<?php
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core e-mail hook.

if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core e-mail hook.
