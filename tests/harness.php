<?php
// Standalone test: php tests/harness.php (not shipped in the ZIP).
define( 'ABSPATH', __DIR__ );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
$GLOBALS['opts'] = array( 'admin_email' => 'admin@example.test' );
function get_option( $k, $d = false ) { return $GLOBALS['opts'][ $k ] ?? $d; }
function sanitize_email( $e ) { return trim( $e ); }
function is_email( $e ) { return (bool) filter_var( $e, FILTER_VALIDATE_EMAIL ); }
require __DIR__ . '/../includes/class-prp-settings.php';
require __DIR__ . '/../includes/class-prp-reminder.php';

$fail = 0;
function check( $name, $got, $want ) {
	global $fail;
	$ok = $got === $want;
	if ( ! $ok ) { $fail++; }
	echo ( $ok ? 'OK   ' : 'FAIL ' ) . $name . ( $ok ? '' : ' → got ' . var_export( $got, true ) . ', want ' . var_export( $want, true ) ) . "\n";
}

$tz = new DateTimeZone( 'Europe/Prague' );
$ts = function ( $local ) use ( $tz ) { return ( new DateTimeImmutable( $local, $tz ) )->getTimestamp(); };

// Target date in site time.
check( 'tomorrow, morning', PRP_Reminder::target_date( $ts( '2026-11-20 09:00' ), 1, $tz ), '2026-11-21' );
check( 'just after midnight local (UTC still yesterday)', PRP_Reminder::target_date( $ts( '2026-11-20 00:30' ), 1, $tz ), '2026-11-21' );
check( 'end of month', PRP_Reminder::target_date( $ts( '2026-11-30 10:00' ), 1, $tz ), '2026-12-01' );
check( 'same day (0)', PRP_Reminder::target_date( $ts( '2026-11-20 10:00' ), 0, $tz ), '2026-11-20' );
check( 'DST end (25. 10.)', PRP_Reminder::target_date( $ts( '2026-10-24 23:30' ), 1, $tz ), '2026-10-25' );
check( 'DST start (29. 3.)', PRP_Reminder::target_date( $ts( '2026-03-28 23:30' ), 1, $tz ), '2026-03-29' );

// Sending window.
check( 'window 7:59 closed', PRP_Reminder::in_send_window( $ts( '2026-11-20 07:59' ), $tz ), false );
check( 'window 8:00 open', PRP_Reminder::in_send_window( $ts( '2026-11-20 08:00' ), $tz ), true );
check( 'window 19:59 open', PRP_Reminder::in_send_window( $ts( '2026-11-20 19:59' ), $tz ), true );
check( 'window 20:00 closed', PRP_Reminder::in_send_window( $ts( '2026-11-20 20:00' ), $tz ), false );

// is_due.
$row = array( 'packet_id' => '1234567890', 'packet_status' => 'ready for pickup', 'stored_until' => '2026-11-21', 'cod' => '890' );
$t   = '2026-11-21';
check( 'COD, tomorrow → due', PRP_Reminder::is_due( $row, $t, 'cod', 'processing', true, '' ), true );
check( 'COD amount only in Packeta → due', PRP_Reminder::is_due( $row, $t, 'gopay', 'processing', true, '' ), true );
check( 'paid, only COD → skip', PRP_Reminder::is_due( array( 'cod' => '0' ) + $row, $t, 'bacs', 'processing', true, '' ), false );
check( 'paid, all parcels → due', PRP_Reminder::is_due( array( 'cod' => '0' ) + $row, $t, 'bacs', 'processing', false, '' ), true );
check( 'order method cod, Packeta cod empty → due', PRP_Reminder::is_due( array( 'cod' => null ) + $row, $t, 'cod', 'processing', true, '' ), true );
check( 'stored the day after → not yet', PRP_Reminder::is_due( array( 'stored_until' => '2026-11-22' ) + $row, $t, 'cod', 'processing', true, '' ), false );
check( 'picked up → skip', PRP_Reminder::is_due( array( 'packet_status' => 'delivered' ) + $row, $t, 'cod', 'completed', true, '' ), false );
check( 'no packet id → skip', PRP_Reminder::is_due( array( 'packet_id' => null ) + $row, $t, 'cod', 'processing', true, '' ), false );
check( 'cancelled order → skip', PRP_Reminder::is_due( $row, $t, 'cod', 'cancelled', true, '' ), false );
check( 'already reminded for this date → skip', PRP_Reminder::is_due( $row, $t, 'cod', 'processing', true, '2026-11-21' ), false );
check( 'storage extended → remind again', PRP_Reminder::is_due( $row, $t, 'cod', 'processing', true, '2026-11-14' ), true );

// Settings sanitizing.
$s = PRP_Settings::sanitize( array( 'mode' => 'live', 'test_email' => 'x', 'days' => 9, 'only_cod' => '' ) );
check( 'mode live', $s['mode'], 'live' );
check( 'bad e-mail → admin e-mail', $s['test_email'], 'admin@example.test' );
check( 'days clamped to 3', $s['days'], 3 );
check( 'only_cod off', $s['only_cod'], 'no' );
check( 'unknown mode → test', PRP_Settings::sanitize( array( 'mode' => 'xx' ) )['mode'], 'test' );
check( 'default mode test', PRP_Settings::defaults()['mode'], 'test' );

// Bundled translation.
check( 'cs site → cs', PRP_Settings::translation_for( 'cs_CZ' ), 'cs_CZ' );
check( 'en_US default → cs', PRP_Settings::translation_for( 'en_US' ), 'cs_CZ' );
check( 'sk → sk', PRP_Settings::translation_for( 'sk_SK' ), 'sk_SK' );
check( 'hu → none (language pack / English)', PRP_Settings::translation_for( 'hu_HU' ), '' );
check( 'pl → none', PRP_Settings::translation_for( 'pl_PL' ), '' );

// Tracking URL.
check( 'tracking url en', PRP_Reminder::tracking_url( '1234567890' ), 'https://tracking.packeta.com/en/?id=Z1234567890' );
check( 'tracking url cs with Z', PRP_Reminder::tracking_url( 'Z1234567890', 'cs_CZ' ), 'https://tracking.packeta.com/cs/?id=Z1234567890' );
check( 'tracking url unknown lang', PRP_Reminder::tracking_url( '1', 'fr_FR' ), 'https://tracking.packeta.com/en/?id=Z1' );

echo $fail ? "\n$fail FAILED\n" : "\nALL PASSED\n";
exit( $fail ? 1 : 0 );
