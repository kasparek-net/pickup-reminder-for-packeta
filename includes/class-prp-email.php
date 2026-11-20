<?php
/**
 * Customer e-mail: the parcel is waiting at the pickup point, last day of storage.
 *
 * @package PickupReminderForPacketa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PRP_Email extends WC_Email {

	/** @var array Row from packetery_order. */
	public $parcel = array();

	/** @var string Set in test mode: the e-mail goes here instead of to the customer. */
	public $test_recipient = '';

	public function __construct() {
		$this->id             = 'prp_pickup_reminder';
		$this->customer_email = true;
		$this->title          = __( 'Pickup reminder (Packeta)', 'pickup-reminder-for-packeta' );
		$this->description    = __( 'Sent to the customer before the last day their Packeta parcel can be picked up at the pickup point. When and to whom is set in WooCommerce → Pickup reminder.', 'pickup-reminder-for-packeta' );
		$this->template_html  = 'emails/prp-pickup-reminder.php';
		$this->template_plain = 'emails/plain/prp-pickup-reminder.php';
		$this->template_base  = PRP_PLUGIN_PATH . 'templates/';
		$this->placeholders   = array(
			'{order_number}' => '',
			'{first_name}'   => '',
			'{stored_until}' => '',
			'{pickup_point}' => '',
		);
		parent::__construct();
	}

	public function get_default_subject() {
		return __( 'Your parcel is waiting – pick it up by {stored_until}', 'pickup-reminder-for-packeta' );
	}

	public function get_default_heading() {
		return __( 'Your parcel is waiting for you', 'pickup-reminder-for-packeta' );
	}

	public function get_default_intro() {
		return __( 'Hello, your parcel for order {order_number} is waiting for you at the pickup point. You can pick it up until {stored_until}; after that it will be sent back to us.', 'pickup-reminder-for-packeta' );
	}

	public function get_default_additional_content() {
		return __( 'Thank you for picking it up in time.', 'pickup-reminder-for-packeta' );
	}

	public function get_intro() {
		return $this->format_string( $this->get_option( 'intro', $this->get_default_intro() ) );
	}

	/**
	 * @param WC_Order $order          Order.
	 * @param array    $parcel         Row from packetery_order.
	 * @param string   $test_recipient Send here instead of to the customer (test mode).
	 * @return bool Whether the e-mail was sent.
	 */
	public function trigger( $order, $parcel = array(), $test_recipient = '' ) {
		$this->setup_locale();
		$this->object         = $order;
		$this->parcel         = (array) $parcel;
		$this->test_recipient = (string) $test_recipient;
		$this->recipient      = $this->test_recipient ? $this->test_recipient : $order->get_billing_email();

		$this->placeholders['{order_number}'] = $order->get_order_number();
		$this->placeholders['{first_name}']   = $order->get_billing_first_name();
		$this->placeholders['{stored_until}'] = PRP_Reminder::format_date( $this->parcel['stored_until'] ?? '' );
		$this->placeholders['{pickup_point}'] = (string) ( $this->parcel['point_name'] ?? '' );

		$sent = false;
		if ( $this->is_enabled() && $this->get_recipient() ) {
			$subject = $this->get_subject();
			if ( $this->test_recipient ) {
				/* translators: 1: customer e-mail, 2: original subject */
				$subject = sprintf( __( '[Test for %1$s] %2$s', 'pickup-reminder-for-packeta' ), $order->get_billing_email(), $subject );
			}
			$sent = (bool) $this->send( $this->get_recipient(), $subject, $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
		$this->restore_locale();
		return $sent;
	}

	private function template_args( $plain ) {
		return array(
			'order'              => $this->object,
			'parcel'             => $this->parcel,
			'email_heading'      => $this->get_heading(),
			'intro'              => $this->get_intro(),
			'additional_content' => $this->get_additional_content(),
			'sent_to_admin'      => false,
			'plain_text'         => $plain,
			'email'              => $this,
		);
	}

	public function get_content_html() {
		return wc_get_template_html( $this->template_html, $this->template_args( false ), '', $this->template_base );
	}

	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, $this->template_args( true ), '', $this->template_base );
	}

	public function init_form_fields() {
		parent::init_form_fields();
		$fields = array();
		foreach ( $this->form_fields as $key => $field ) {
			// Pre-fill with the real text instead of a grey placeholder.
			if ( 'subject' === $key ) {
				$field['default'] = $this->get_default_subject();
			} elseif ( 'heading' === $key ) {
				$field['default'] = $this->get_default_heading();
			}
			$fields[ $key ] = $field;
			if ( 'heading' === $key ) {
				$fields['intro'] = array(
					'title'       => __( 'Text', 'pickup-reminder-for-packeta' ),
					'type'        => 'textarea',
					'css'         => 'width:400px; height: 90px;',
					'desc_tip'    => true,
					/* translators: %s: list of placeholders */
					'description' => sprintf( __( 'Text above the parcel details. Available placeholders: %s', 'pickup-reminder-for-packeta' ), '{first_name}, {order_number}, {stored_until}, {pickup_point}' ),
					'placeholder' => '',
					'default'     => $this->get_default_intro(),
				);
			}
		}
		$this->form_fields = $fields;
	}
}
