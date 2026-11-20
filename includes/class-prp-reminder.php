<?php
/**
 * Finds parcels whose pickup storage ends soon and sends the reminder.
 *
 * Reads the table of the official Packeta plugin ({prefix}packetery_order), which keeps
 * the packet status and the "stored until" date in sync with the Packeta API.
 *
 * @package PickupReminderForPacketa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PRP_Reminder {

	const HOOK      = 'prp_check';
	const GROUP     = 'pickup-reminder-for-packeta';
	const META      = '_prp_reminded';
	const META_TEST = '_prp_reminded_test';
	const READY     = 'ready for pickup';

	/** E-mails go out only between these hours (site time), never at night. */
	const HOUR_FROM = 8;
	const HOUR_TO   = 20;

	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'run' ) );
		add_action( 'init', array( __CLASS__, 'schedule' ) );
	}

	/**
	 * Hourly check; the e-mail itself is sent once per parcel and storage date.
	 */
	public static function schedule() {
		if ( ! function_exists( 'as_has_scheduled_action' ) || as_has_scheduled_action( self::HOOK, array(), self::GROUP ) ) {
			return;
		}
		as_schedule_recurring_action( time() + MINUTE_IN_SECONDS, HOUR_IN_SECONDS, self::HOOK, array(), self::GROUP );
	}

	public static function unschedule() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::HOOK, array(), self::GROUP );
		}
	}

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'packetery_order';
	}

	public static function table_exists() {
		global $wpdb;
		$table = self::table();
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Date (Y-m-d, site time) whose last storage day triggers the reminder today.
	 */
	public static function target_date( $now, $days, DateTimeZone $tz ) {
		return ( new DateTimeImmutable( '@' . (int) $now ) )->setTimezone( $tz )->modify( '+' . (int) $days . ' days' )->format( 'Y-m-d' );
	}

	public static function in_send_window( $now, DateTimeZone $tz ) {
		$hour = (int) ( new DateTimeImmutable( '@' . (int) $now ) )->setTimezone( $tz )->format( 'G' );
		return $hour >= self::HOUR_FROM && $hour < self::HOUR_TO;
	}

	public static function is_cod( array $row, $payment_method ) {
		return (float) ( $row['cod'] ?? 0 ) > 0 || 'cod' === $payment_method;
	}

	/**
	 * Whether a parcel gets the reminder now.
	 *
	 * @param array  $row            Row from packetery_order.
	 * @param string $target         Target storage date (Y-m-d).
	 * @param string $payment_method Order payment method ID.
	 * @param string $order_status   Order status without the wc- prefix.
	 * @param bool   $only_cod       Remind cash-on-delivery parcels only.
	 * @param string $reminded       Storage date the customer was already reminded about.
	 */
	public static function is_due( array $row, $target, $payment_method, $order_status, $only_cod, $reminded ) {
		if ( self::READY !== ( $row['packet_status'] ?? '' ) || empty( $row['packet_id'] ) ) {
			return false;
		}
		if ( ( $row['stored_until'] ?? '' ) !== $target ) {
			return false;
		}
		if ( in_array( $order_status, array( 'cancelled', 'refunded', 'failed', 'trash' ), true ) ) {
			return false;
		}
		if ( $only_cod && ! self::is_cod( $row, $payment_method ) ) {
			return false;
		}
		return $reminded !== $row['stored_until'];
	}

	/**
	 * Parcels waiting at a pickup point, earliest storage end first.
	 *
	 * @return array<int,array>
	 */
	public static function waiting_rows( $stored_until = '' ) {
		global $wpdb;
		if ( ! self::table_exists() ) {
			return array();
		}
		$table = self::table();
		$sql   = 'SELECT id, packet_id, packet_status, stored_until, cod, point_name, point_street, point_city, point_zip, point_url FROM %i WHERE packet_status = %s AND packet_id IS NOT NULL';
		$args  = array( $table, self::READY );
		if ( $stored_until ) {
			$sql   .= ' AND stored_until = %s';
			$args[] = $stored_until;
		}
		$sql .= ' ORDER BY stored_until ASC, id ASC LIMIT 500';
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Packeta plugin table, placeholders above.
	}

	/**
	 * @param bool $force Ignore the sending window (manual run from the admin).
	 * @return array{sent:int,due:int,checked:int}
	 */
	public static function run( $force = false ) {
		$result = array( 'sent' => 0, 'due' => 0, 'checked' => 0 );
		$tz     = wp_timezone();
		$now    = time();
		if ( ! $force && ! self::in_send_window( $now, $tz ) ) {
			return $result;
		}
		$target = self::target_date( $now, PRP_Settings::days(), $tz );
		$live   = PRP_Settings::is_live();
		$key    = $live ? self::META : self::META_TEST;
		$mailer = WC()->mailer();
		$emails = $mailer->get_emails();
		$email  = $emails['PRP_Email'] ?? null;
		if ( ! $email ) {
			return $result;
		}

		foreach ( self::waiting_rows( $target ) as $row ) {
			++$result['checked'];
			$order = wc_get_order( (int) $row['id'] );
			if ( ! $order instanceof WC_Order ) {
				continue;
			}
			if ( ! self::is_due( $row, $target, $order->get_payment_method(), $order->get_status(), PRP_Settings::only_cod(), (string) $order->get_meta( $key ) ) ) {
				continue;
			}
			++$result['due'];
			$sent = $email->trigger( $order, $row, $live ? '' : (string) PRP_Settings::get( 'test_email' ) );
			if ( ! $sent ) {
				continue;
			}
			++$result['sent'];
			// Meta only: a full save() fires woocommerce_update_order, where other plugins
			// (Packeta among them) run their own order logic.
			$order->update_meta_data( $key, $row['stored_until'] );
			$order->save_meta_data();
			if ( $live ) {
				$order->add_order_note(
					sprintf(
						/* translators: %s: last day of pickup storage */
						__( 'Pickup reminder sent to the customer (parcel stored until %s).', 'pickup-reminder-for-packeta' ),
						self::format_date( $row['stored_until'] )
					)
				);
			}
		}
		update_option( 'prp_last_run', array( 'time' => $now, 'target' => $target ) + $result, false );
		return $result;
	}

	public static function format_date( $ymd ) {
		$dt = DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $ymd, wp_timezone() );
		return $dt ? wp_date( wc_date_format(), $dt->getTimestamp() ) : (string) $ymd;
	}

	public static function tracking_url( $packet_id, $locale = 'en_US' ) {
		$lang = substr( (string) $locale, 0, 2 );
		$lang = in_array( $lang, array( 'cs', 'sk', 'hu', 'pl', 'ro', 'de' ), true ) ? $lang : 'en';
		return 'https://tracking.packeta.com/' . $lang . '/?id=Z' . rawurlencode( ltrim( (string) $packet_id, 'Zz' ) );
	}
}
