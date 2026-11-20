<?php
/**
 * Admin page: WooCommerce → Pickup reminder.
 *
 * @package PickupReminderForPacketa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PRP_Admin {

	const SLUG = 'pickup-reminder-for-packeta';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 60 );
		add_action( 'admin_post_prp_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_prp_run', array( __CLASS__, 'run_now' ) );
	}

	public static function url( $args = array() ) {
		return add_query_arg( $args, admin_url( 'admin.php?page=' . self::SLUG ) );
	}

	public static function menu() {
		add_submenu_page( 'woocommerce', __( 'Pickup reminder', 'pickup-reminder-for-packeta' ), __( 'Pickup reminder', 'pickup-reminder-for-packeta' ), 'manage_woocommerce', self::SLUG, array( __CLASS__, 'render' ) );
	}

	public static function save() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'pickup-reminder-for-packeta' ) );
		}
		check_admin_referer( 'prp_save' );
		$in = array(
			'mode'       => isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '',
			'test_email' => isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : '',
			'days'       => isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 1,
			'only_cod'   => ! empty( $_POST['only_cod'] ),
		);
		update_option( PRP_Settings::OPTION, PRP_Settings::sanitize( $in ) );

		// E-mail texts are stored in the WooCommerce e-mail settings, so both places stay in sync.
		$email_opt = get_option( 'woocommerce_prp_pickup_reminder_settings', array() );
		$email_opt = is_array( $email_opt ) ? $email_opt : array();
		foreach ( array( 'subject', 'heading' ) as $f ) {
			if ( isset( $_POST[ 'email_' . $f ] ) ) {
				$email_opt[ $f ] = sanitize_text_field( wp_unslash( $_POST[ 'email_' . $f ] ) );
			}
		}
		foreach ( array( 'intro', 'additional_content' ) as $f ) {
			if ( isset( $_POST[ 'email_' . $f ] ) ) {
				$email_opt[ $f ] = sanitize_textarea_field( wp_unslash( $_POST[ 'email_' . $f ] ) );
			}
		}
		$email_opt['enabled'] = empty( $_POST['email_enabled'] ) ? 'no' : 'yes';
		update_option( 'woocommerce_prp_pickup_reminder_settings', $email_opt );

		wp_safe_redirect( self::url( array( 'prp_msg' => 'saved' ) ) );
		exit;
	}

	public static function run_now() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'pickup-reminder-for-packeta' ) );
		}
		check_admin_referer( 'prp_run' );
		$r = PRP_Reminder::run( true );
		wp_safe_redirect( self::url( array( 'prp_msg' => 'run', 'prp_sent' => $r['sent'], 'prp_due' => $r['due'] ) ) );
		exit;
	}

	private static function notice() {
		$msg = isset( $_GET['prp_msg'] ) ? sanitize_key( wp_unslash( $_GET['prp_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		if ( 'saved' === $msg ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'pickup-reminder-for-packeta' ) . '</p></div>';
		} elseif ( 'run' === $msg ) {
			$sent = isset( $_GET['prp_sent'] ) ? absint( $_GET['prp_sent'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$due  = isset( $_GET['prp_due'] ) ? absint( $_GET['prp_due'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $due && $sent < $due ) {
				/* translators: 1: sent, 2: due */
				$text = sprintf( __( 'Sent %1$d of %2$d reminders. Check that the e-mail is enabled in WooCommerce → Settings → Emails.', 'pickup-reminder-for-packeta' ), $sent, $due );
				echo '<div class="notice notice-warning"><p>' . esc_html( $text ) . '</p></div>';
			} else {
				/* translators: %d: number of e-mails */
				$text = sprintf( _n( 'Check done, %d reminder sent.', 'Check done, %d reminders sent.', $sent, 'pickup-reminder-for-packeta' ), $sent );
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $text ) . '</p></div>';
			}
		}
	}

	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$s        = PRP_Settings::all();
		$has      = PRP_Reminder::table_exists();
		$rows     = PRP_Reminder::waiting_rows();
		$target   = PRP_Reminder::target_date( time(), PRP_Settings::days(), wp_timezone() );
		$last     = get_option( 'prp_last_run' );
		$post     = esc_url( admin_url( 'admin-post.php' ) );
		$email_ok = 'no' !== ( get_option( 'woocommerce_prp_pickup_reminder_settings', array() )['enabled'] ?? 'yes' );
		$emails   = WC()->mailer()->get_emails();
		$mail     = $emails['PRP_Email'] ?? null;
		?>
		<div class="wrap prp">
			<h1><?php esc_html_e( 'Pickup reminder', 'pickup-reminder-for-packeta' ); ?></h1>
			<?php self::notice(); ?>
			<p style="max-width:720px"><?php esc_html_e( 'Customers get an e-mail before the last day their Packeta parcel can be picked up, so fewer parcels are sent back. Parcel status and the storage date come from the official Packeta plugin.', 'pickup-reminder-for-packeta' ); ?></p>

			<?php if ( ! $has ) : ?>
				<div class="notice notice-error inline"><p><?php esc_html_e( 'The Packeta plugin table was not found. Install and set up the official Packeta plugin including packet status synchronization.', 'pickup-reminder-for-packeta' ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! $email_ok ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'The reminder e-mail is turned off, customers get nothing. Turn it on below.', 'pickup-reminder-for-packeta' ); ?></p></div>
			<?php endif; ?>

			<div style="display:flex;gap:16px;flex-wrap:wrap;margin:16px 0">
				<div class="card" style="margin:0;min-width:220px">
					<h2 style="margin-top:0"><?php echo PRP_Settings::is_live() ? esc_html__( 'Live', 'pickup-reminder-for-packeta' ) : esc_html__( 'Test mode', 'pickup-reminder-for-packeta' ); ?></h2>
					<p><?php echo PRP_Settings::is_live() ? esc_html__( 'Reminders go to customers.', 'pickup-reminder-for-packeta' ) : esc_html( sprintf( /* translators: %s: e-mail */ __( 'Reminders go only to %s, customers get nothing.', 'pickup-reminder-for-packeta' ), $s['test_email'] ) ); ?></p>
				</div>
				<div class="card" style="margin:0;min-width:220px">
					<h2 style="margin-top:0"><?php echo esc_html( (string) count( $rows ) ); ?></h2>
					<p><?php esc_html_e( 'parcels waiting at a pickup point', 'pickup-reminder-for-packeta' ); ?></p>
				</div>
				<div class="card" style="margin:0;min-width:220px">
					<h2 style="margin-top:0"><?php echo $last ? esc_html( wp_date( 'j. n. G:i', (int) $last['time'] ) ) : '–'; ?></h2>
					<p>
						<?php esc_html_e( 'last check (runs every hour, e-mails 8:00–20:00)', 'pickup-reminder-for-packeta' ); ?>
					</p>
					<form method="post" action="<?php echo $post; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>">
						<?php wp_nonce_field( 'prp_run' ); ?>
						<input type="hidden" name="action" value="prp_run">
						<button class="button"><?php esc_html_e( 'Check now', 'pickup-reminder-for-packeta' ); ?></button>
					</form>
				</div>
			</div>

			<h2><?php esc_html_e( 'Settings', 'pickup-reminder-for-packeta' ); ?></h2>
			<form method="post" action="<?php echo $post; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>">
				<?php wp_nonce_field( 'prp_save' ); ?>
				<input type="hidden" name="action" value="prp_save">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Mode', 'pickup-reminder-for-packeta' ); ?></th>
						<td>
							<label><input type="radio" name="mode" value="test" <?php checked( 'live' !== $s['mode'] ); ?>> <?php esc_html_e( 'Test – send reminders only to this address:', 'pickup-reminder-for-packeta' ); ?></label>
							<input type="email" name="test_email" class="regular-text" value="<?php echo esc_attr( $s['test_email'] ); ?>"><br>
							<label><input type="radio" name="mode" value="live" <?php checked( 'live' === $s['mode'] ); ?>> <?php esc_html_e( 'Live – send reminders to customers', 'pickup-reminder-for-packeta' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="prp-days"><?php esc_html_e( 'When', 'pickup-reminder-for-packeta' ); ?></label></th>
						<td>
							<select name="days" id="prp-days">
								<?php
								$prp_labels = array(
									0 => __( 'on the last day of storage', 'pickup-reminder-for-packeta' ),
									1 => __( '1 day before the last day', 'pickup-reminder-for-packeta' ),
									2 => __( '2 days before the last day', 'pickup-reminder-for-packeta' ),
									3 => __( '3 days before the last day', 'pickup-reminder-for-packeta' ),
								);
								foreach ( $prp_labels as $prp_n => $prp_label ) {
									echo '<option value="' . esc_attr( (string) $prp_n ) . '"' . selected( PRP_Settings::days(), $prp_n, false ) . '>' . esc_html( $prp_label ) . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Which parcels', 'pickup-reminder-for-packeta' ); ?></th>
						<td><label><input type="checkbox" name="only_cod" value="1" <?php checked( PRP_Settings::only_cod() ); ?>> <?php esc_html_e( 'Cash on delivery only (unpaid parcels cost you return postage)', 'pickup-reminder-for-packeta' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'E-mail', 'pickup-reminder-for-packeta' ); ?></th>
						<td><label><input type="checkbox" name="email_enabled" value="1" <?php checked( $email_ok ); ?>> <?php esc_html_e( 'Send the reminder e-mail', 'pickup-reminder-for-packeta' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="prp-subject"><?php esc_html_e( 'Subject', 'pickup-reminder-for-packeta' ); ?></label></th>
						<td><input type="text" id="prp-subject" name="email_subject" class="large-text" value="<?php echo esc_attr( $mail ? $mail->get_option( 'subject', $mail->get_default_subject() ) : '' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="prp-heading"><?php esc_html_e( 'Heading', 'pickup-reminder-for-packeta' ); ?></label></th>
						<td><input type="text" id="prp-heading" name="email_heading" class="large-text" value="<?php echo esc_attr( $mail ? $mail->get_option( 'heading', $mail->get_default_heading() ) : '' ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="prp-intro"><?php esc_html_e( 'Text', 'pickup-reminder-for-packeta' ); ?></label></th>
						<td><textarea id="prp-intro" name="email_intro" class="large-text" rows="4"><?php echo esc_textarea( $mail ? $mail->get_option( 'intro', $mail->get_default_intro() ) : '' ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="prp-additional"><?php esc_html_e( 'Closing text', 'pickup-reminder-for-packeta' ); ?></label></th>
						<td>
							<textarea id="prp-additional" name="email_additional_content" class="large-text" rows="2"><?php echo esc_textarea( $mail ? $mail->get_option( 'additional_content', $mail->get_default_additional_content() ) : '' ); ?></textarea>
							<p class="description">
								<?php
								/* translators: %s: list of placeholders */
								echo esc_html( sprintf( __( 'Below the text the e-mail shows the pickup deadline, pickup point, amount to pay and parcel number, then the order summary. You can use: %s', 'pickup-reminder-for-packeta' ), '{first_name}, {order_number}, {stored_until}, {pickup_point}' ) );
								?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save settings', 'pickup-reminder-for-packeta' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Parcels waiting at a pickup point', 'pickup-reminder-for-packeta' ); ?></h2>
			<?php if ( ! $rows ) : ?>
				<p><?php esc_html_e( 'No parcel is waiting for pickup right now.', 'pickup-reminder-for-packeta' ); ?></p>
			<?php else : ?>
				<div style="overflow-x:auto;max-width:960px"><table class="widefat striped">
					<thead><tr>
						<th><?php esc_html_e( 'Order', 'pickup-reminder-for-packeta' ); ?></th>
						<th><?php esc_html_e( 'Stored until', 'pickup-reminder-for-packeta' ); ?></th>
						<th><?php esc_html_e( 'Cash on delivery', 'pickup-reminder-for-packeta' ); ?></th>
						<th><?php esc_html_e( 'Pickup point', 'pickup-reminder-for-packeta' ); ?></th>
						<th><?php esc_html_e( 'Reminder', 'pickup-reminder-for-packeta' ); ?></th>
					</tr></thead>
					<tbody>
					<?php
					foreach ( $rows as $row ) :
						$order = wc_get_order( (int) $row['id'] );
						if ( ! $order ) {
							continue;
						}
						$cod      = PRP_Reminder::is_cod( $row, $order->get_payment_method() );
						$reminded = (string) $order->get_meta( PRP_Settings::is_live() ? PRP_Reminder::META : PRP_Reminder::META_TEST );
						if ( $reminded === $row['stored_until'] ) {
							$state = PRP_Settings::is_live() ? __( 'sent', 'pickup-reminder-for-packeta' ) : __( 'test sent', 'pickup-reminder-for-packeta' );
						} elseif ( in_array( $order->get_status(), array( 'cancelled', 'refunded', 'failed' ), true ) ) {
							$state = __( 'not sent (order cancelled)', 'pickup-reminder-for-packeta' );
						} elseif ( PRP_Settings::only_cod() && ! $cod ) {
							$state = __( 'not sent (paid)', 'pickup-reminder-for-packeta' );
						} elseif ( $row['stored_until'] < $target ) {
							$state = __( 'not sent (storage date passed)', 'pickup-reminder-for-packeta' );
						} elseif ( $row['stored_until'] === $target ) {
							$state = __( 'today', 'pickup-reminder-for-packeta' );
						} else {
							$state = __( 'planned', 'pickup-reminder-for-packeta' );
						}
						?>
						<tr>
							<td><a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></a> <?php echo esc_html( $order->get_formatted_billing_full_name() ); ?></td>
							<td><?php echo esc_html( PRP_Reminder::format_date( $row['stored_until'] ) ); ?></td>
							<td><?php echo $cod ? esc_html__( 'yes', 'pickup-reminder-for-packeta' ) : esc_html__( 'no', 'pickup-reminder-for-packeta' ); ?></td>
							<td><?php echo esc_html( (string) $row['point_name'] ); ?></td>
							<td><?php echo esc_html( $state ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table></div>
			<?php endif; ?>
		</div>
		<?php
	}
}
