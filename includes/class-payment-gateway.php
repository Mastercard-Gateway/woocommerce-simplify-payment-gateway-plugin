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

class WC_Gateway_Simplify_Commerce extends WC_Payment_Gateway_CC {

	const ID = 'simplify_commerce';
	const TXN_MODE_PURCHASE = 'purchase';
	const TXN_MODE_AUTHORIZE = 'authorize';

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
	 * @var string
	 */
	protected $txn_mode;

    /**
     * @var string
     */
    protected $embedded_title;

    /**
     * @var string
     */
    public $embedded_description;

    /**
     * @var string
     */
    public $embedded_enabled;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id                   = self::ID;
		$this->method_title         = __( 'Mastercard Payment Gateway Services - Simplify', 'woocommerce' );
		$this->method_description   = __( 'Take payments via the Simplify payment gateway - uses simplify.js to create card tokens and the Mastercard Payment Gateway Services - Simplify SDK. Requires SSL when sandbox is disabled.',
			'woocommerce' );
		$this->new_method_label     = __( 'Use a new card', 'woocommerce' );
		$this->has_fields           = true;
		$this->supports             = array(
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
		$this->title                = $this->get_option( 'title' );
		$this->embedded_title       = $this->get_option( 'embedded_title' );
		$this->description          = $this->get_option( 'description' );
		$this->embedded_description = $this->get_option( 'embedded_description' );
		$this->enabled              = $this->get_option( 'enabled' );
		$this->embedded_enabled     = $this->get_option( 'embedded_enabled' );
		$this->modal_color          = $this->get_option( 'modal_color', '#333333' );
		$this->sandbox              = $this->get_option( 'sandbox' );
		$this->txn_mode             = $this->get_option( 'txn_mode', self::TXN_MODE_PURCHASE );
		$this->public_key           = $this->sandbox == 'no' ? $this->get_option( 'public_key' ) : $this->get_option( 'sandbox_public_key' );
		$this->private_key          = $this->sandbox == 'no' ? $this->get_option( 'private_key' ) : $this->get_option( 'sandbox_private_key' );

		$this->init_simplify_sdk();

		// Hooks
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_api_wc_gateway_simplify_commerce', array( $this, 'return_handler' ) );
		add_action( 'woocommerce_order_action_simplify_capture_payment', array( $this, 'capture_authorized_order' ) );
		add_action( 'woocommerce_order_action_simplify_void_payment', array( $this, 'void_authorized_order' ) );
	}

	/**
	 * @throws Exception
	 */
	public function capture_authorized_order() {
		try {
			$order = new WC_Order( $_REQUEST['post_ID'] );
			if ( $order->get_payment_method() != $this->id ) {
				throw new Exception( 'Wrong payment method' );
			}
			if ( $order->get_status() != 'processing' ) {
				throw new Exception( 'Wrong order status, must be \'processing\'' );
			}
			if ( $order->get_meta( '_simplify_order_captured' ) !== '0' ) {
				throw new Exception( 'Order already captured' );
			}

			$authCode = $order->get_meta( '_simplify_authorization' );
			if ( ! $authCode ) {
				throw new Exception( 'Invalid or missing authorization code' );
			}

			$payment = Simplify_Payment::createPayment( array(
				'authorization' => $authCode,
				'reference'     => $order->get_id(),
				'currency'      => strtoupper( $order->get_currency() ),
				'amount'        => $this->get_total( $order )
			) );

			if ( $payment->paymentStatus === 'APPROVED' ) {
				$order->add_order_note( sprintf( __( 'Gateway captured amount %s (ID: %s)', 'woocommerce' ),
					$order->get_total(), $payment->id ) );
			} else {
				throw new Exception( 'Capture declined' );
			}

			$order->update_meta_data( '_simplify_order_captured', '1' );
			$order->save_meta_data();

		} catch ( Simplify_ApiException $e ) {
			wp_die( $e->getMessage() . '<br>Ref: ' . $e->getReference() . '<br>Code: ' . $e->getErrorCode(),
				__( 'Gateway Failure' ) );

		} catch ( Exception $e ) {
			wp_die( $e->getMessage(), __( 'Payment Process Failure' ) );
		}
	}

	/**
	 * @throws Exception
	 */
	public function void_authorized_order() {
		try {
			$order = new WC_Order( $_REQUEST['post_ID'] );
			if ( $order->get_payment_method() != $this->id ) {
				throw new Exception( 'Wrong payment method' );
			}
			if ( $order->get_status() != 'processing' ) {
				throw new Exception( 'Wrong order status, must be \'processing\'' );
			}
			if ( $order->get_meta( '_simplify_order_captured' ) !== '0' ) {
				throw new Exception( 'Order already reversed' );
			}

			$authCode = $order->get_meta( '_simplify_authorization' );
			if ( ! $authCode ) {
				throw new Exception( 'Invalid or missing authorization code' );
			}

			$authTxn = Simplify_Authorization::findAuthorization( $authCode );
			$authTxn->deleteAuthorization();

			$order->add_order_note( sprintf( __( 'Gateway reverse authorization (ID: %s)', 'woocommerce' ),
				$authCode ) );

			wc_create_refund( array(
				'order_id'       => $order->get_id(),
				'reason'         => 'Reverse',
				'refund_payment' => false,
				'restock_items'  => true,
				'amount'         => $order->get_remaining_refund_amount()
			) );

		} catch ( Simplify_ApiException $e ) {
			wp_die( $e->getMessage() . '<br>Ref: ' . $e->getReference() . '<br>Code: ' . $e->getErrorCode(),
				__( 'Gateway Failure' ) );

		} catch ( Exception $e ) {
			wp_die( $e->getMessage(), __( 'Payment Process Failure' ) );
		}
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
        <h3><?php _e( 'Mastercard Payment Gateway Services - Simplify', 'woocommerce' ); ?></h3>

		<?php $this->checks(); ?>
        <table class="form-table">
            <?php $this->generate_settings_html($this->get_payment_form_fields()); ?>
        </table>

        <h3><?php _e( 'Hosted Payment', 'woocommerce' ); ?></h3>

        <table class="form-table">
            <?php $this->generate_settings_html($this->get_hosted_form_fields()); ?>
        </table>

        <h3><?php _e( 'Embedded Payment', 'woocommerce' ); ?></h3>

        <table class="form-table">
			<?php $this->generate_settings_html($this->get_embedded_form_fields()); ?>
            <script type="text/javascript">
                jQuery('#woocommerce_simplify_commerce_sandbox').on('change', function () {
                    var sandbox = jQuery('#woocommerce_simplify_commerce_sandbox_public_key, #woocommerce_simplify_commerce_sandbox_private_key').closest('tr'),
                        production = jQuery('#woocommerce_simplify_commerce_public_key, #woocommerce_simplify_commerce_private_key').closest('tr');

                    if (jQuery(this).is(':checked')) {
                        sandbox.show();
                        production.hide();
                    } else {
                        sandbox.hide();
                        production.show();
                    }
                }).change();

                jQuery('#woocommerce_simplify_commerce_mode').on('change', function () {
                    var color = jQuery('#woocommerce_simplify_commerce_modal_color').closest('tr');
                    var supportedCardTypes = jQuery('#woocommerce_simplify_commerce_supported_card_types').closest('tr');

                    if ('standard' === jQuery(this).val()) {
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
		if ( 'no' === $this->enabled && 'no' === $this->embedded_enabled ) {
			return;
		}

		// PHP Version
		if ( version_compare( phpversion(), '5.3', '<' ) ) {
			echo '<div class="error"><p>' . sprintf( __( 'Gateway Error: Simplify commerce requires PHP 5.3 and above. You are using version %s.',
					'woocommerce' ), phpversion() ) . '</p></div>';
		} // Check required fields
        elseif ( ! $this->public_key || ! $this->private_key ) {
			echo '<div class="error"><p>' . __( 'Gateway Error: Please enter your public and private keys',
					'woocommerce' ) . '</p></div>';
		}
	}

	/**
	 * Check if this gateway is enabled.
	 *
	 * @return bool
	 */
	public function is_available() {
		if ( 'yes' !== $this->enabled && 'yes' !== $this->embedded_enabled ) {
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
        $this->form_fields = array_merge(
            $this->get_payment_form_fields(),
            $this->get_hosted_form_fields(),
            $this->get_embedded_form_fields()
        );
	}

	protected function get_payment_form_fields() {
        return $this->form_fields = array(
            'sandbox'             => array(
                'title'       => __( 'Sandbox', 'woocommerce' ),
                'label'       => __( 'Enable Sandbox Mode', 'woocommerce' ),
                'type'        => 'checkbox',
                'description' => __( 'Place the payment gateway in sandbox mode using sandbox API keys (real payments will not be taken).',
                    'woocommerce' ),
                'default'     => 'yes'
            ),
            'sandbox_public_key'  => array(
                'title'       => __( 'Sandbox Public Key', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'Get your API keys from your merchant account: Account Settings > API Keys.',
                    'woocommerce' ),
                'default'     => '',
                'desc_tip'    => true
            ),
            'sandbox_private_key' => array(
                'title'       => __( 'Sandbox Private Key', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'Get your API keys from your merchant account: Account Settings > API Keys.',
                    'woocommerce' ),
                'default'     => '',
                'desc_tip'    => true
            ),
            'public_key'          => array(
                'title'       => __( 'Public Key', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'Get your API keys from your merchant account: Account Settings > API Keys.',
                    'woocommerce' ),
                'default'     => '',
                'desc_tip'    => true
            ),
            'private_key'         => array(
                'title'       => __( 'Private Key', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'Get your API keys from your merchant account: Account Settings > API Keys.',
                    'woocommerce' ),
                'default'     => '',
                'desc_tip'    => true
            ),
            'txn_mode'            => array(
                'title'       => __( 'Transaction Mode', 'woocommerce' ),
                'type'        => 'select',
                'options'     => array(
                    self::TXN_MODE_PURCHASE  => __( 'Payment', 'woocommerce' ),
                    self::TXN_MODE_AUTHORIZE => __( 'Authorization', 'woocommerce' )
                ),
                'default'     => self::TXN_MODE_PURCHASE,
                'description' => __( 'In "Payment" mode, the customer is charged immediately. In "Authorization" mode, the transaction is only authorized and the capturing of funds is a manual process that you do using the Woocommerce admin panel. You will need to capture the authorization typically within a week of an order being placed. If you do not, you will lose the payment and will be unable to capture it again even though you might have shipped the order. Please contact your gateway for more details.',
                    'woocommerce' ),
            ),
            'modal_color'         => array(
                'title'       => __( 'Modal Color', 'woocommerce' ),
                'type'        => 'color',
                'description' => __( 'Set the color of the buttons and titles on the modal dialog.', 'woocommerce' ),
                'default'     => '#a46497',
                'desc_tip'    => true
            ),
        );
    }

    protected function get_hosted_form_fields() {
	    return array(
            'enabled'             => array(
                'title'       => __( 'Enable/Disable', 'woocommerce' ),
                'label'       => __( 'Enable Popup Mastercard Payment Gateway Services - Simplify', 'woocommerce' ),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'title'               => array(
                'title'       => __( 'Title', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default'     => __( 'Pay with Card (Popup)', 'woocommerce' ),
                'desc_tip'    => true
            ),
            'description'         => array(
                'title'       => __( 'Description', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'This controls the description which the user sees during checkout.',
                    'woocommerce' ),
                'default'     => 'Pay with your card via Mastercard Payment Gateway Services - Simplify.',
                'desc_tip'    => true
            ),
        );
    }

    protected function get_embedded_form_fields() {
        return array(
            'embedded_enabled'             => array(
                'title'       => __( 'Enable/Disable', 'woocommerce' ),
                'label'       => __( 'Enable Embedded Mastercard Payment Gateway Services - Simplify', 'woocommerce' ),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ),
            'embedded_title'               => array(
                'title'       => __( 'Title', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                'default'     => __( 'Pay with Card (Embedded)', 'woocommerce' ),
                'desc_tip'    => true
            ),
            'embedded_description'         => array(
                'title'       => __( 'Description', 'woocommerce' ),
                'type'        => 'text',
                'description' => __( 'This controls the description which the user sees during checkout.',
                    'woocommerce' ),
                'default'     => 'Pay with your card via Mastercard Payment Gateway Services - Simplify.',
                'desc_tip'    => true
            ),
        );
    }

	/**
	 * Returns the POSTed data, to be used to save the settings.
	 * @return array
	 */
	public function get_post_data() {
		foreach ( $this->form_fields as $form_field_key => $form_field_value ) {
			if ( $form_field_value['type'] == "select_card_types" ) {
				$form_field_key_select_card_types           = $this->plugin_id . $this->id . "_" . $form_field_key;
				$select_card_types_values                   = array();
				$_POST[ $form_field_key_select_card_types ] = $select_card_types_values;
			}
		}

		if ( ! empty( $this->data ) && is_array( $this->data ) ) {
			return $this->data;
		}

		return $_POST;
	}

    /**
     * Return the gateway's title.
     *
     * @return string
     */
    public function get_title() {
        return apply_filters(
            'woocommerce_gateway_title',
            $this->enabled === 'yes' ? $this->title : $this->embedded_title,
            $this->id
        );
    }

    /**
     * Return the gateway's description.
     *
     * @return string
     */
    public function get_description() {
        return apply_filters(
            'woocommerce_gateway_description',
            $this->enabled === 'yes' ? $this->description : $this->embedded_description,
            $this->id
        );
    }

	/**
	 * Payment form on checkout page.
	 */
	public function payment_fields() {
		$description = $this->get_description();

		if ( 'yes' == $this->sandbox ) {
			$description .= ' ' . sprintf( __( 'TEST MODE ENABLED. Use a test card: %s', 'woocommerce' ),
					'<a href="https://www.simplify.com/commerce/docs/tutorial/index#testing">https://www.simplify.com/commerce/docs/tutorial/index#testing</a>' );
		}

		if ( $description ) {
			echo wpautop( wptexturize( trim( $description ) ) );
		}
	}

	/**
	 * Process standard payments.
	 *
	 * @param WC_Order $order
	 * @param string $cart_token
	 *
	 * @return array
	 * @uses   Simplify_BadRequestException
	 * @uses   Simplify_ApiException
	 */
	protected function process_standard_payments( $order, $cart_token = '' ) {
		try {

			if ( empty( $cart_token ) ) {
				$error_msg = __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.',
					'woocommerce' );

				if ( 'yes' == $this->sandbox ) {
					$error_msg .= ' ' . __( 'Developers: Please make sure that you\'re including jQuery and there are no JavaScript errors on the page.',
							'woocommerce' );
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
					wc_add_notice( $error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')',
						'error' );
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
	 * @param array $token
	 *
	 * @return bool|WP_Error
	 * @uses  Simplify_BadRequestException
	 */
	public function do_payment( $order, $amount = 0, $token = array() ) {
		if ( $this->get_total( $order ) < 50 ) {
			return new WP_Error( 'simplify_error',
				__( 'Sorry, the minimum allowed order total is 0.50 to use this payment method.', 'woocommerce' ) );
		}

		try {
			// Charge the customer
			$data = array(
				'amount'      => $this->get_total(), // In cents. Rounding to avoid floating point errors.
				'description' => sprintf( __( '%s - Order #%s', 'woocommerce' ), $order->get_order_number() ),
				'currency'    => strtoupper( get_woocommerce_currency() ),
				'reference'   => $order->get_id()
			);

			$data    = array_merge( $data, $token );
			$payment = Simplify_Payment::createPayment( $data );

		} catch ( Exception $e ) {

			$error_message = $e->getMessage();

			if ( $e instanceof Simplify_BadRequestException && $e->hasFieldErrors() && $e->getFieldErrors() ) {
				$error_message = '';
				foreach ( $e->getFieldErrors() as $error ) {
					$error_message .= ' ' . $error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')';
				}
			}

			$order->add_order_note( sprintf( __( 'Gateway payment error: %s', 'woocommerce' ), $error_message ) );

			return new WP_Error( 'simplify_payment_declined', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		if ( 'APPROVED' == $payment->paymentStatus ) {
			// Payment complete
			$order->payment_complete( $payment->id );

			// Add order note
			$order->add_order_note( sprintf( __( 'Gateway payment approved (ID: %s, Auth Code: %s)', 'woocommerce' ),
				$payment->id, $payment->authCode ) );

			return true;
		} else {
			$order->add_order_note( __( 'Gateway payment declined', 'woocommerce' ) );

			return new WP_Error( 'simplify_payment_declined',
				__( 'Payment was declined by your gateway - please try another card.', 'woocommerce' ) );
		}
	}

	/**
	 * Process standard payments.
	 *
	 * @param WC_Order $order
	 *
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
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		return $this->process_hosted_payments( $order );

	}

	/**
	 * Hosted payment args.
	 *
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	protected function get_hosted_payments_args( $order ) {
		$args = apply_filters( 'woocommerce_simplify_commerce_hosted_args', array(
			'sc-key'          => $this->public_key,
			'amount'          => $this->get_total( $order ),
			'currency'        => strtoupper( get_woocommerce_currency() ),
			'reference'       => $order->get_id(),
			'description'     => sprintf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ),
			'receipt'         => 'false',
			'color'           => $this->modal_color,
			'redirect-url'    => WC()->api_request_url( 'WC_Gateway_Simplify_Commerce' ),
			'operation'       => $this->get_payment_operation(),
		), $order->get_id() );

		return $args;
	}

	protected function attempt_transliteration($field) {
		$encode = mb_detect_encoding($field);
		if ($encode !== 'ASCII') {
		    if (function_exists('transliterator_transliterate')) {
		        $field = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove', $field);
		    } else {
		        // fall back to iconv if intl module not available
		        $field = remove_accents($field);
		        $field = iconv($encode, 'ASCII//TRANSLIT//IGNORE', $field);
		        $field = str_ireplace('?', '', $field);
		        $field = trim($field);
		    }
		}
		return $field;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return int
	 */
	protected function get_total( $order ) {
		return (int) round( $order->get_total() * 100 );
	}

	/**
	 * @return string
	 */
	protected function get_payment_operation() {
		return $this->txn_mode === self::TXN_MODE_PURCHASE ?
			'create.payment' :
			'create.token';
	}

	/**
	 * Receipt page.
	 *
	 * @param int $order_id
	 */
	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );

		echo '<p>' . __( 'Thank you for your order, please click the button below to pay with credit card using Mastercard Payment Gateway Services - Simplify.',
				'woocommerce' ) . '</p>';

		$args        = $this->get_hosted_payments_args( $order );
		$button_args = array();
		foreach ( $args as $key => $value ) {
            $value = $this->attempt_transliteration($value);
            if (!$value) {
                continue;
            }
			$button_args[] = 'data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
		}

		$paymentScript = '<script type="text/javascript" src="https://www.simplify.com/commerce/simplify.pay.js"></script>';

		if ($this->enabled === 'yes') {
            $paymentBodyHtml = '<button class="button alt" id="simplify-payment-button" ' . implode( ' ',
                    $button_args ) . '>' . __( 'Pay Now',
                    'woocommerce' ) . '</button>';
        } else {
            $paymentBodyHtml = '<iframe name="simplifycommerce_embedded" width="100%" height="450px" '
                . implode( ' ', $button_args ) . '></iframe>';
        }

		$cancelButtonHtml = '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . __( 'Cancel order &amp; restore cart',
				'woocommerce' ) . '</a>';

		echo $paymentScript . $paymentBodyHtml . ' ' . $cancelButtonHtml;
	}

	/**
	 * Return handler for Hosted Payments.
	 */
	public function return_handler() {
		@ob_clean();
		header( 'HTTP/1.1 200 OK' );

		// Transaction mode = Payment/Purchase
		if ( isset( $_REQUEST['reference'] ) && isset( $_REQUEST['paymentId'] ) && isset( $_REQUEST['signature'] ) ) {
			$signature = strtoupper( md5( $_REQUEST['amount'] . $_REQUEST['reference'] . $_REQUEST['paymentId'] . $_REQUEST['paymentDate'] . $_REQUEST['paymentStatus'] . $this->private_key ) );
			$order_id  = absint( $_REQUEST['reference'] );
			$order     = wc_get_order( $order_id );

			if ( hash_equals( $signature, $_REQUEST['signature'] ) ) {
				$order_complete = $this->process_order_status( $order, $_REQUEST['paymentId'],
					$_REQUEST['paymentStatus'], $_REQUEST['authCode'], true );

				if ( ! $order_complete ) {
					$order->update_status( 'failed',
						__( 'Payment was declined by your gateway.', 'woocommerce' ) );
				}

				wp_redirect( $this->get_return_url( $order ) );
				exit();
			}
		}

		// Transaction mode = Authorize
		if ( isset( $_REQUEST['reference'] ) && isset( $_REQUEST['cardToken'] ) && $this->txn_mode === self::TXN_MODE_AUTHORIZE ) {
			$order_id = absint( $_REQUEST['reference'] );
			$order    = wc_get_order( $order_id );

			$order_complete = $this->authorize( $order, $_REQUEST['cardToken'], $_REQUEST['amount'] );

			if ( ! $order_complete ) {
				$order->update_status( 'failed',
					__( 'Authorization was declined by your gateway.', 'woocommerce' ) );
			}

			wp_redirect( $this->get_return_url( $order ) );
			exit();
		}

		wc_add_notice( 'Unexpected response', 'error' );
		wp_redirect( wc_get_page_permalink( 'cart' ) );
		exit();
	}

	/**
	 * @param WC_Order $order
	 * @param string $card_token
	 * @param string $amount
	 *
	 * @return bool
	 */
	protected function authorize( $order, $card_token, $amount ) {

		if ( (int) $amount !== $this->get_total( $order ) ) {
			wc_add_notice( 'Amount mismatch', 'error' );
			wp_redirect( wc_get_page_permalink( 'cart' ) );
		}

		$authorization = Simplify_Authorization::createAuthorization( array(
			'amount'    => $amount,
			'token'     => $card_token,
			'reference' => $order->get_id(),
			'currency'  => strtoupper( $order->get_currency() ),
		) );

		return $this->process_order_status(
			$order,
			$authorization->id,
			$authorization->paymentStatus,
			$authorization->authCode,
			$authorization->captured
		);
	}

	/**
	 * Process the order status.
	 *
	 * @param WC_Order $order
	 * @param string $payment_id
	 * @param string $status
	 * @param string $auth_code
	 * @param bool $is_capture
	 *
	 * @return bool
	 */
	public function process_order_status( $order, $payment_id, $status, $auth_code, $is_capture = false ) {
		if ( 'APPROVED' == $status ) {
			$order->add_meta_data( '_simplify_order_captured', $is_capture ? '1' : '0' );
			$order->add_meta_data( '_simplify_authorization', $payment_id );

			// Payment complete
			$order->payment_complete( $payment_id );

			// Add order note
			if ( $is_capture ) {
				$order->add_order_note( sprintf( __( 'Gateway payment approved (ID: %s, Auth Code: %s)',
					'woocommerce' ), $payment_id, $auth_code ) );

			} else {
				$order->add_order_note( sprintf( __( 'Gateway authorization approved (ID: %s, Auth Code: %s)',
					'woocommerce' ), $payment_id, $auth_code ) );
			}

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
	 * @param int $order_id
	 * @param float $amount
	 * @param string $reason
	 *
	 * @return bool|WP_Error
	 * @uses   Simplify_BadRequestException
	 * @uses   Simplify_ApiException
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		try {
			$order = wc_get_order( $order_id );

			$payment_id = get_post_meta( $order_id, '_transaction_id', true );

			$refund = Simplify_Refund::createRefund( array(
				'amount'    => (int) round( (float) $amount * 100 ),
				'payment'   => $payment_id,
				'reason'    => $reason,
				'reference' => $order_id
			) );

			if ( 'APPROVED' == $refund->paymentStatus ) {
				$order->add_order_note( sprintf( __( 'Gateway refund approved (ID: %s, Amount: %s)', 'woocommerce' ),
					$refund->id, $amount ) );

				return true;
			} else {
				throw new Simplify_ApiException( __( 'Refund was declined.', 'woocommerce' ) );
			}

		} catch ( Simplify_ApiException $e ) {
			if ( $e instanceof Simplify_BadRequestException && $e->hasFieldErrors() && $e->getFieldErrors() ) {
				foreach ( $e->getFieldErrors() as $error ) {
					return new WP_Error( 'simplify_refund_error',
						$error->getFieldName() . ': "' . $error->getMessage() . '" (' . $error->getErrorCode() . ')' );
				}
			} else {
				return new WP_Error( 'simplify_refund_error', $e->getMessage() );
			}
		}

		return false;
	}
}
