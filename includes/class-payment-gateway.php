<?php
/**
 * Copyright (c) 2017-2019 Mastercard
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Simplify Commerce Payment Gateway for card payments
 *
 * @class 		WC_Gateway_Simplify_Commerce
 * @extends		WC_Payment_Gateway_CC
 * @since       2.2.0
 * @version		2.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		SimplifyCommerce
 */
class WC_Gateway_Simplify_Commerce extends WC_Payment_Gateway_CC {

	/**
	 * @var string
	 */
    protected $sandbox;

	/**
	 * @var string
	 */
    protected $modal_color;

	/**
	 * @var string
	 */
    protected $public_key;

	/**
	 * @var string
	 */
    protected $private_key;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id                 = 'simplify_commerce';
		$this->method_title       = __( 'Simplify Commerce', 'woocommerce' );
		$this->method_description = __( 'Take payments via Simplify Commerce - uses simplify.js to create card tokens and the Simplify Commerce SDK. Requires SSL when sandbox is disabled.', 'woocommerce' );
		$this->new_method_label   = __( 'Use a new card', 'woocommerce' );
		$this->has_fields         = true;
		$this->supports           = array(
			'subscriptions',
			'products',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_amount_changes',
			'subscription_payment_method_change', // Subscriptions 1.n compatibility
			'subscription_payment_method_change_customer',
			'subscription_payment_method_change_admin',
			'subscription_date_changes',
			'multiple_subscriptions',
			'refunds',
			'pre-orders'
		);
		$this->view_transaction_url = 'https://www.simplify.com/commerce/app#/payment/%s';

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values
		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );
		$this->enabled         = $this->get_option( 'enabled' );
		$this->modal_color     = $this->get_option( 'modal_color', '#333333' );
		$this->sandbox         = $this->get_option( 'sandbox' );
		$this->public_key      = $this->sandbox == 'no' ? $this->get_option( 'public_key' ) : $this->get_option( 'sandbox_public_key' );
		$this->private_key     = $this->sandbox == 'no' ? $this->get_option( 'private_key' ) : $this->get_option( 'sandbox_private_key' );

		$this->init_simplify_sdk();

		// Hooks
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_api_wc_gateway_simplify_commerce', array( $this, 'return_handler' ) );
	}

	/**
	 * Init Simplify SDK.
	 */
	protected function init_simplify_sdk() {
		// Include lib
		require_once( 'Simplify.php' );

		Simplify::$publicKey  = $this->public_key;
		Simplify::$privateKey = $this->private_key;
		Simplify::$userAgent  = 'SimplifyWooCommercePlugin/' . WC()->version;
	}

	/**
	 * Admin Panel Options.
	 * - Options for bits like 'title' and availability on a country-by-country basis.
	 */
	public function admin_options() {
		?>
		<h3><?php _e( 'Simplify Commerce by Mastercard', 'woocommerce' ); ?></h3>

		<?php if ( empty( $this->public_key ) ) : ?>
			<div class="simplify-commerce-banner updated">
				<img src="<?php echo esc_attr( plugins_url( 'assets/images/logo.png', WC_SIMPLIFY_COMMERCE_FILE ) ); ?>" />
				<p class="main"><strong><?php _e( 'Getting started', 'woocommerce' ); ?></strong></p>
				<p><?php _e( 'Simplify Commerce is your merchant account and payment gateway all rolled into one. Choose Simplify Commerce as your WooCommerce payment gateway to get access to your money quickly with a powerful, secure payment engine backed by Mastercard.', 'woocommerce' ); ?></p>

				<p><a href="https://www.simplify.com/commerce/partners/woocommerce#/signup" target="_blank" class="button button-primary"><?php _e( 'Sign up for Simplify Commerce', 'woocommerce' ); ?></a> <a href="https://www.simplify.com/commerce/partners/woocommerce#/" target="_blank" class="button"><?php _e( 'Learn more', 'woocommerce' ); ?></a></p>

			</div>
		<?php else : ?>
			<p><?php _e( 'Simplify Commerce is your merchant account and payment gateway all rolled into one. Choose Simplify Commerce as your WooCommerce payment gateway to get access to your money quickly with a powerful, secure payment engine backed by Mastercard.', 'woocommerce' ); ?></p>
		<?php endif; ?>

		<?php $this->checks(); ?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
			<script type="text/javascript">
				jQuery( '#woocommerce_simplify_commerce_sandbox' ).on( 'change', function() {
					var sandbox    = jQuery( '#woocommerce_simplify_commerce_sandbox_public_key, #woocommerce_simplify_commerce_sandbox_private_key' ).closest( 'tr' ),
						production = jQuery( '#woocommerce_simplify_commerce_public_key, #woocommerce_simplify_commerce_private_key' ).closest( 'tr' );

					if ( jQuery( this ).is( ':checked' ) ) {
						sandbox.show();
						production.hide();
					} else {
						sandbox.hide();
						production.show();
					}
				}).change();

				jQuery( '#woocommerce_simplify_commerce_mode' ).on( 'change', function() {
					var color = jQuery( '#woocommerce_simplify_commerce_modal_color' ).closest( 'tr' );
					var supportedCardTypes = jQuery( '#woocommerce_simplify_commerce_supported_card_types' ).closest( 'tr' );

					if ( 'standard' === jQuery( this ).val() ) {
						color.hide();
						supportedCardTypes.show();
					} else {
						color.show();
						supportedCardTypes.hide();
					}
				}).change();
			</script>
		</table>
		<?php
	}

	/**
	 * Check if SSL is enabled and notify the user.
	 */
	public function checks() {
		if ( 'no' === $this->enabled ) {
			return;
		}

		// PHP Version
		if ( version_compare( phpversion(), '5.3', '<' ) ) {
			echo '<div class="error"><p>' . sprintf( __( 'Simplify Commerce Error: Simplify commerce requires PHP 5.3 and above. You are using version %s.', 'woocommerce' ), phpversion() ) . '</p></div>';
		}

		$simplify_countries = apply_filters( 'woocommerce_gateway_simplify_commerce_supported_countries', array( 'US', 'IE', 'AU', 'QA') );

		if ( ! in_array( WC()->countries->get_base_country(), $simplify_countries ) ) {
			echo '<div class="error"><p>' . sprintf( __( 'Simplify Commerce Error: Simplify currently only supported in the US, Ireland, Australia and Qatar. Your base country is set to %s.', 'woocommerce' ), WC()->countries->get_base_country() ) . '</p></div>';
		}

		// Check required fields
		elseif ( ! $this->public_key || ! $this->private_key ) {
			echo '<div class="error"><p>' . __( 'Simplify Commerce Error: Please enter your public and private keys', 'woocommerce' ) . '</p></div>';
		}
	}

	/**
	 * Check if this gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled ) {
			return false;
		}

		if ( ! $this->public_key || ! $this->private_key ) {
			return false;
		}

		return true;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'woocommerce' ),
				'label'       => __( 'Enable Simplify Commerce', 'woocommerce' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Pay with Card', 'woocommerce' ),
				'desc_tip'    => true
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				'default'     => 'Pay with your card via Simplify Commerce by Mastercard.',
				'desc_tip'    => true
			),
			'modal_color' => array(
				'title'       => __( 'Modal Color', 'woocommerce' ),
				'type'        => 'color',
				'description' => __( 'Set the color of the buttons and titles on the modal dialog.', 'woocommerce' ),
				'default'     => '#a46497',
				'desc_tip'    => true
			),
			'sandbox' => array(
				'title'       => __( 'Sandbox', 'woocommerce' ),
				'label'       => __( 'Enable Sandbox Mode', 'woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Place the payment gateway in sandbox mode using sandbox API keys (real payments will not be taken).', 'woocommerce' ),
				'default'     => 'yes'
			),
			'sandbox_public_key' => array(
				'title'       => __( 'Sandbox Public Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Simplify account: Settings > API Keys.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true
			),
			'sandbox_private_key' => array(
				'title'       => __( 'Sandbox Private Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Simplify account: Settings > API Keys.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true
			),
			'public_key' => array(
				'title'       => __( 'Public Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Simplify account: Settings > API Keys.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true
			),
			'private_key' => array(
				'title'       => __( 'Private Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Get your API keys from your Simplify account: Settings > API Keys.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true
			),
		);
	}

	/**
	 * Returns the POSTed data, to be used to save the settings.
	 * @return array
	 */
	public function get_post_data() {
		foreach($this->form_fields as $form_field_key => $form_field_value) {
			if ($form_field_value['type'] == "select_card_types") {
				$form_field_key_select_card_types = $this->plugin_id . $this->id . "_" . $form_field_key;
				$select_card_types_values = array();
				$_POST[$form_field_key_select_card_types] = $select_card_types_values;
			}
		}

		if ( ! empty( $this->data ) && is_array( $this->data ) ) {
			return $this->data;
		}
		return $_POST;
	}

	/**
	 * Payment form on checkout page.
	 */
	public function payment_fields() {
		$description = $this->get_description();

		if ( 'yes' == $this->sandbox ) {
			$description .= ' ' . sprintf( __( 'TEST MODE ENABLED. Use a test card: %s', 'woocommerce' ), '<a href="https://www.simplify.com/commerce/docs/tutorial/index#testing">https://www.simplify.com/commerce/docs/tutorial/index#testing</a>' );
		}

		if ( $description ) {
			echo wpautop( wptexturize( trim( $description ) ) );
		}
	}

	/**
	 * Process standard payments.
	 *
	 * @param  WC_Order $order
	 * @param  string   $cart_token
	 * @uses   Simplify_ApiException
	 * @uses   Simplify_BadRequestException
	 * @return array
	 */
	protected function process_standard_payments( $order, $cart_token = '' ) {
		try {

			if ( empty( $cart_token ) ) {
				$error_msg = __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'woocommerce' );

				if ( 'yes' == $this->sandbox ) {
					$error_msg .= ' ' . __( 'Developers: Please make sure that you\'re including jQuery and there are no JavaScript errors on the page.', 'woocommerce' );
				}

				throw new Simplify_ApiException( $error_msg );
			}

			// We need to figure out if we want to charge the card token (new unsaved token, no customer, etc)
			// or the customer token (just saved method, previously saved method)
			$pass_tokens = array();

			if ( ! empty ( $cart_token ) ) {
				$pass_tokens['token'] = $cart_token;
			}

			$payment_response = $this->do_payment( $order, $order->get_total(), $pass_tokens );

			if ( is_wp_error( $payment_response ) ) {
				throw new Simplify_ApiException( $payment_response->get_error_message() );
			} else {
				// Remove cart
				WC()->cart->empty_cart();

				// Return thank you page redirect
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order )
				);
			}

		} catch ( Simplify_ApiException $e ) {
			if ( $e instanceof Simplify_BadRequestException && $e->hasFieldErrors() && $e->getFieldErrors() ) {
				foreach ( $e->getFieldErrors() as $error ) {
					wc_add_notice( $error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')', 'error' );
				}
			} else {
				wc_add_notice( $e->getMessage(), 'error' );
			}

			return array(
				'result'   => 'fail',
				'redirect' => ''
			);
		}
	}

 	/**
	 * do payment function.
	 *
	 * @param WC_order $order
	 * @param int $amount (default: 0)
	 * @uses  Simplify_BadRequestException
	 * @return bool|WP_Error
	 */
	public function do_payment( $order, $amount = 0, $token = array() ) {
		if ( $amount * 100 < 50 ) {
			return new WP_Error( 'simplify_error', __( 'Sorry, the minimum allowed order total is 0.50 to use this payment method.', 'woocommerce' ) );
		}

		try {
			// Charge the customer
			$data = array(
				'amount'              => round($amount * 100), // In cents. Rounding to avoid floating point errors.
				'description'         => sprintf( __( '%s - Order #%s', 'woocommerce' ), esc_html( get_bloginfo( 'name', 'display' ) ), $order->get_order_number() ),
				'currency'            => strtoupper( get_woocommerce_currency() ),
				'reference'           => $order->get_id()
			);

			$data = array_merge( $data, $token );
			$payment = Simplify_Payment::createPayment( $data );

		} catch ( Exception $e ) {

			$error_message = $e->getMessage();

			if ( $e instanceof Simplify_BadRequestException && $e->hasFieldErrors() && $e->getFieldErrors() ) {
				$error_message = '';
				foreach ( $e->getFieldErrors() as $error ) {
					$error_message .= ' ' . $error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')';
				}
			}

			$order->add_order_note( sprintf( __( 'Simplify payment error: %s', 'woocommerce' ), $error_message ) );

			return new WP_Error( 'simplify_payment_declined', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		if ( 'APPROVED' == $payment->paymentStatus ) {
			// Payment complete
			$order->payment_complete( $payment->id );

			// Add order note
			$order->add_order_note( sprintf( __( 'Simplify payment approved (ID: %s, Auth Code: %s)', 'woocommerce' ), $payment->id, $payment->authCode ) );

			return true;
		} else {
			$order->add_order_note( __( 'Simplify payment declined', 'woocommerce' ) );

			return new WP_Error( 'simplify_payment_declined', __( 'Payment was declined - please try another card.', 'woocommerce' ) );
		}
	}

	/**
	 * Process standard payments.
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	protected function process_hosted_payments( $order ) {
		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true )
		);
	}

	/**
	 * Process the payment.
	 *
	 * @param int $order_id
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );
        return $this->process_hosted_payments( $order );

	}

	/**
	 * Hosted payment args.
	 *
	 * @param  WC_Order $order
	 *
	 * @return array
	 */
	protected function get_hosted_payments_args( $order ) {
		$args = apply_filters( 'woocommerce_simplify_commerce_hosted_args', array(
			'sc-key'          => $this->public_key,
			'amount'          => $order->get_total() * 100,
			'currency'        => strtoupper(get_woocommerce_currency()),
			'reference'       => $order->get_id(),
			'name'            => esc_html( get_bloginfo( 'name', 'display' ) ),
			'description'     => sprintf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ),
			'receipt'         => 'false',
			'color'           => $this->modal_color,
			'redirect-url'    => WC()->api_request_url( 'WC_Gateway_Simplify_Commerce' ),
			'address'         => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
			'address-city'    => $order->get_billing_city(),
			'address-state'   => $order->get_billing_state(),
			'address-zip'     => $order->get_billing_postcode(),
			'address-country' => $order->get_billing_country(),
			'operation'       => 'create.payment',
		), $order->get_id() );

		return $args;
	}

	/**
	 * Receipt page.
	 *
	 * @param  int $order_id
	 */
	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );

		echo '<p>' . __( 'Thank you for your order, please click the button below to pay with card using Simplify Commerce by Mastercard.', 'woocommerce' ) . '</p>';

		$args        = $this->get_hosted_payments_args( $order );
		$button_args = array();
		foreach ( $args as $key => $value ) {
			$button_args[] = 'data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}

		echo '<script type="text/javascript" src="https://www.simplify.com/commerce/simplify.pay.js"></script>
			<button class="button alt" id="simplify-payment-button" ' . implode( ' ', $button_args ) . '>' . __( 'Pay Now', 'woocommerce' ) . '</button> <a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart', 'woocommerce' ) . '</a>
			';
	}

	/**
	 * Return handler for Hosted Payments.
	 */
	public function return_handler() {
		@ob_clean();
		header( 'HTTP/1.1 200 OK' );

		if ( isset( $_REQUEST['reference'] ) && isset( $_REQUEST['paymentId'] ) && isset( $_REQUEST['signature'] ) ) {
			$signature = strtoupper( md5( $_REQUEST['amount'] . $_REQUEST['reference'] . $_REQUEST['paymentId'] . $_REQUEST['paymentDate'] . $_REQUEST['paymentStatus'] . $this->private_key ) );
			$order_id  = absint( $_REQUEST['reference'] );
			$order     = wc_get_order( $order_id );

			if ( hash_equals( $signature, $_REQUEST['signature'] ) ) {
				$order_complete = $this->process_order_status( $order, $_REQUEST['paymentId'], $_REQUEST['paymentStatus'], $_REQUEST['paymentDate'] );

				if ( ! $order_complete ) {
					$order->update_status( 'failed', __( 'Payment was declined by Simplify Commerce.', 'woocommerce' ) );
				}

				wp_redirect( $this->get_return_url( $order ) );
				exit();
			}
		}

		wp_redirect( wc_get_page_permalink( 'cart' ) );
		exit();
	}

	/**
	 * Process the order status.
	 *
	 * @param  WC_Order $order
	 * @param  string   $payment_id
	 * @param  string   $status
	 * @param  string   $auth_code
	 *
	 * @return bool
	 */
	public function process_order_status( $order, $payment_id, $status, $auth_code ) {
		if ( 'APPROVED' == $status ) {
			// Payment complete
			$order->payment_complete( $payment_id );

			// Add order note
			$order->add_order_note( sprintf( __( 'Simplify payment approved (ID: %s, Auth Code: %s)', 'woocommerce' ), $payment_id, $auth_code ) );

			// Remove cart
			WC()->cart->empty_cart();

			return true;
		}

		return false;
	}

	/**
	 * Process refunds.
	 * WooCommerce 2.2 or later.
	 *
	 * @param  int $order_id
	 * @param  float $amount
	 * @param  string $reason
	 * @uses   Simplify_ApiException
	 * @uses   Simplify_BadRequestException
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		try {
			$payment_id = get_post_meta( $order_id, '_transaction_id', true );

			$refund = Simplify_Refund::createRefund( array(
				'amount'    => $amount * 100, // In cents.
				'payment'   => $payment_id,
				'reason'    => $reason,
				'reference' => $order_id
			) );

			if ( 'APPROVED' == $refund->paymentStatus ) {
				return true;
			} else {
				throw new Simplify_ApiException( __( 'Refund was declined.', 'woocommerce' ) );
			}

		} catch ( Simplify_ApiException $e ) {
			if ( $e instanceof Simplify_BadRequestException && $e->hasFieldErrors() && $e->getFieldErrors() ) {
				foreach ( $e->getFieldErrors() as $error ) {
					return new WP_Error( 'simplify_refund_error', $error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')' );
				}
			} else {
				return new WP_Error( 'simplify_refund_error', $e->getMessage() );
			}
		}

		return false;
	}
}
