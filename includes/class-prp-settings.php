<?php
/**
 * Plugin settings.
 *
 * @package PickupReminderForPacketa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PRP_Settings {

	const OPTION = 'prp_settings';

	public static function defaults() {
		return array(
			'mode'       => 'test',
			'test_email' => get_option( 'admin_email' ),
			'days'       => 1,
			'only_cod'   => 'yes',
		);
	}

	public static function all() {
		$saved = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
	}

	public static function get( $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	public static function is_live() {
		return 'live' === self::get( 'mode' );
	}

	public static function days() {
		return max( 0, min( 3, (int) self::get( 'days' ) ) );
	}

	public static function only_cod() {
		return 'yes' === self::get( 'only_cod' );
	}

	public static function sanitize( array $in ) {
		$email = sanitize_email( (string) ( $in['test_email'] ?? '' ) );
		return array(
			'mode'       => ( $in['mode'] ?? '' ) === 'live' ? 'live' : 'test',
			'test_email' => is_email( $email ) ? $email : get_option( 'admin_email' ),
			'days'       => max( 0, min( 3, (int) ( $in['days'] ?? 1 ) ) ),
			'only_cod'   => empty( $in['only_cod'] ) ? 'no' : 'yes',
		);
	}
}
