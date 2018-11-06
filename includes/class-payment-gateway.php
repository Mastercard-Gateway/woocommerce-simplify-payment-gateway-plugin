<?php
/*
 * Copyright (c) 2013 - 2018 Mastercard International Incorporated
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this list of
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of
 * conditions and the following disclaimer in the documentation and/or other materials
 * provided with the distribution.
 * Neither the name of the Mastercard International Incorporated nor the names of its
 * contributors may be used to endorse or promote products derived from this software
 * without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
 * OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING
 * IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Simplify Commerce Payment Gateway for Credit Card.
 *
 * @class 		WC_Gateway_Simplify_Commerce
 * @extends		WC_Payment_Gateway_CC
 * @since       2.2.0
 * @version		1.4.3
 * @package		WooCommerce/Classes/Payment
 * @author 		SimplifyCommerce
 */
class WC_Gateway_Simplify_Commerce extends WC_Payment_Gateway_CC {


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
			'default_credit_card_form',
			'refunds',
			'pre-orders'
		);
		$this->view_transaction_url = 'https://www.simplify.com/commerce/app#/payment/%s';

		$this->availableCardTypes = array('Mastercard', 'Visa', 'Discover', 'Amex', 'Diners', 'JCB');

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values
		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );
		$this->enabled         = $this->get_option( 'enabled' );
		$this->mode            = $this->get_option( 'mode', 'hosted' );
		$this->modal_color     = $this->get_option( 'modal_color', '#333333' );
		$this->sandbox         = $this->get_option( 'sandbox' );
		$this->public_key      = $this->sandbox == 'no' ? $this->get_option( 'public_key' ) : $this->get_option( 'sandbox_public_key' );
		$this->private_key     = $this->sandbox == 'no' ? $this->get_option( 'private_key' ) : $this->get_option( 'sandbox_private_key' );
		$this->supported_card_types = $this->get_option( 'supported_card_types' );

		$this->init_simplify_sdk();

		// Hosted creates payment requests, not tokens.
		if ( 'hosted' !== $this->mode ) {
			$this->supports[] = 'tokenization';
		}

		// Hooks
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
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

		// Show message when using standard mode and no SSL on the checkout page
		elseif ( 'standard' == $this->mode && ! wc_checkout_is_https() ) {
			echo '<div class="error"><p>' . sprintf( __( 'Simplify Commerce is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - Simplify Commerce will only work in sandbox mode.', 'woocommerce'), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '</p></div>';
		}
		elseif ( 'standard' == $this->mode && empty($this->supported_card_types) ) {
			echo '<div class="error"><p>' . __( 'You must select a card type that you support for checkout.', 'woocommerce') . '</p></div>';
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

		if ( 'standard' === $this->mode && 'yes' !== $this->sandbox && ! wc_checkout_is_https() ) {
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
				'default'     => __( 'Credit card', 'woocommerce' ),
				'desc_tip'    => true
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
				'default'     => 'Pay with your credit card via Simplify Commerce by Mastercard.',
				'desc_tip'    => true
			),
			'mode' => array(
				'title'       => __( 'Payment Mode', 'woocommerce' ),
				'label'       => __( 'Enable Hosted Payments', 'woocommerce' ),
				'type'        => 'select',
				'description' => sprintf( __( 'Standard will display the credit card fields on your store (SSL required). %1$s Hosted Payments will display a Simplify Commerce modal dialog on your store (if SSL) or will redirect the customer to Simplify Commerce hosted page (if not SSL). %1$s Note: Hosted Payments need a new API Key pair with the hosted payments flag selected. %2$sFor more details check the Simplify Commerce docs%3$s.', 'woocommerce' ), '<br />', '<a href="https://simplify.desk.com/customer/portal/articles/1792405-how-do-i-enable-hosted-payments" target="_blank">', '</a>' ),
				'default'     => 'hosted',
				'options'     => array(
					'standard' => __( 'Standard', 'woocommerce' ),
					'hosted'   => __( 'Hosted Payments', 'woocommerce' )
				)
			),
			'supported_card_types' => array(
				'title'       => __( 'Supported Card Types', 'woocommerce' ),
				'type'        => 'select_card_types',
				'default'     => array('Mastercard', 'Visa'),
				'description' =>  __( 'Please make sure your merchant account supports the brand(s) selected.', 'woocommerce' ),
				'desc_tip'    => false
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
				foreach($this->availableCardTypes as $card_type_value) {
					$card_type = strtolower($card_type_value);
					$form_field_key_card_type = $form_field_key_select_card_types . "_" . $card_type;
					$card_type_post_value = $_POST[$form_field_key_card_type];
					if (!empty($card_type_post_value)) {
						$select_card_types_values[] = $card_type_post_value;
					}
				}
				$_POST[$form_field_key_select_card_types] = $select_card_types_values;
			}
		}

		if ( ! empty( $this->data ) && is_array( $this->data ) ) {
			return $this->data;
		}
		return $_POST;
	}

	/**
	 * Validate Select Card Types Field.
	 *
	 * @param  string $key
	 * @param  string $value Posted Value
	 * @return string
	 */
	public function validate_select_card_types_field( $key, $value ) {
		return $value;
	}

	public function generate_select_card_types_html($key, $data) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'label'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'type'              => 'select_card_types',
			'desc_tip'          => false,
			'description'       => ''
		);

		$data = wp_parse_args( $data, $defaults );

		if ( ! $data['label'] ) {
			$data['label'] = $data['title'];
		}

		$supported_card_types = $this->get_option( $key );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset id="<?php echo esc_attr( $field_key ); ?>">
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
		            <?php foreach ( $this->availableCardTypes as $cardType ) : ?>
						<input <?php disabled( $data['disabled'], true ); ?> class="<?php echo esc_attr( $data['class'] ); ?>" type="checkbox" name="<?php echo esc_attr( $field_key . '_' . strtolower($cardType)); ?>" title="<?php echo esc_attr($cardType); ?>" alt="<?php echo esc_attr($cardType); ?>" id="<?php echo esc_attr( $field_key . '_' . strtolower($cardType) ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>; margin-bottom: 10px;" value="<?php echo $cardType; ?>" <?php echo in_array($cardType, $supported_card_types) ? 'checked' : ''; ?> <?php echo $this->get_custom_attribute_html( $data ); ?> />
						<img src="<?php echo plugins_url( '/assets/images/icons/credit-cards/', WC_SIMPLIFY_COMMERCE_FILE ) . strtolower($cardType) . '.png'; ?>" title="<?php echo esc_attr($cardType); ?>" alt="<?php echo esc_attr($cardType); ?>" style="margin-right: 15px; vertical-align: text-bottom; height: 28px; width: 42px;"/>
					<?php endforeach; ?>
					<div style="margin-top: 10px;"><?php echo $this->get_description_html( $data ); ?></div>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
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

		if ( 'standard' == $this->mode ) {
			parent::payment_fields();
		}
	}

	/**
	 * Outputs scripts used for simplify payment.
	 */
	public function payment_scripts() {
		if ( ! $this->is_available() ) {
			return;
		}

		wp_enqueue_script( 'simplify-commerce', 'https://www.simplify.com/commerce/v1/simplify.js', array( 'jquery' ), WC_VERSION, true );
		wp_enqueue_script( 'wc-simplify-commerce', plugins_url( 'assets/js/simplify-commerce.js', WC_SIMPLIFY_COMMERCE_FILE ), array( 'simplify-commerce', 'wc-credit-card-form' ), WC_VERSION, true );
		wp_localize_script( 'wc-simplify-commerce', 'Simplify_commerce_params', array(
			'key'           => $this->public_key,
			'card.number'   => __( 'Card Number', 'woocommerce' ),
			'card.expMonth' => __( 'Expiry Month', 'woocommerce' ),
			'card.expYear'  => __( 'Expiry Year', 'woocommerce' ),
			'is_invalid'    => __( 'is invalid', 'woocommerce' ),
			'mode'          => $this->mode,
			'is_ssl'        => is_ssl(),
			'supported_card_types' => array_map('strtolower', $this->supported_card_types)
		) );
	}

	/**
	 * Add payment method.
	 */
	public function add_payment_method() {
		if ( empty ( $_POST['simplify_token'] ) ) {
			wc_add_notice( __( 'There was a problem adding this card.', 'woocommerce' ), 'error' );
			return;
		}

		$cart_token     = wc_clean( $_POST['simplify_token'] );
		$customer_token = $this->get_users_token();
		$current_user   = wp_get_current_user();
		$customer_info  = array(
			'email' => $current_user->user_email,
			'name'  => $current_user->display_name,
		);

		$token = $this->save_token( $customer_token, $cart_token, $customer_info );
		if ( is_null( $token ) ) {
			wc_add_notice( __( 'There was a problem adding this card.', 'woocommerce' ), 'error' );
			return;
		}

		return array(
			'result'   => 'success',
			'redirect' => wc_get_endpoint_url( 'payment-methods' ),
		);
	}

	/**
	 * Actualy saves a customer token to the database.
	 *
	 * @param  WC_Payment_Token   $customer_token Payment Token
	 * @param  string             $cart_token     CC Token
	 * @param  array              $customer_info  'email', 'name'
	 */
	public function save_token( $customer_token, $cart_token, $customer_info ) {
		if ( ! is_null( $customer_token ) ) {
			$customer = Simplify_Customer::findCustomer( $customer_token->get_token() );
			$updates  = array( 'token' => $cart_token );
			$customer->setAll( $updates );
			$customer->updateCustomer();
			$customer = Simplify_Customer::findCustomer( $customer_token->get_token() ); // get updated customer with new set card
			$token    = $customer_token;
		} else {
			$customer = Simplify_Customer::createCustomer( array(
				'token' => $cart_token,
				'email' => $customer_info['email'],
				'name'  => $customer_info['name'],
			) );
			$token = new WC_Payment_Token_CC();
			$token->set_token( $customer->id );
		}

		// If we were able to create an save our card, save the data on our side too
		if ( is_object( $customer ) && $customer->id ) {
			$customer_properties = $customer->getProperties();
			$card                = $customer_properties['card'];
			$token->set_gateway_id( $this->id );
			$token->set_card_type( strtolower( $card->type ) );
			$token->set_last4( $card->last4 );
			$token->set_expiry_month( $card->expMonth );
			$token->set_expiry_year( '20' . $card->expYear );

			if ( is_user_logged_in() ) {
				$token->set_user_id( get_current_user_id() );
			}

			$token->save();

			return $token;
		}

		return null;
	}

	/**
	 * Process customer: updating or creating a new customer/saved CC
	 *
	 * @param  WC_Order           $order          Order object
	 * @param  WC_Payment_Token   $customer_token Payment Token
	 * @param  string             $cart_token     CC Token
	 */
	protected function process_customer( $order, $customer_token = null, $cart_token = '' ) {
		// Are we saving a new payment method?
		if ( is_user_logged_in() && isset( $_POST['wc-simplify_commerce-new-payment-method'] ) && true === (bool) $_POST['wc-simplify_commerce-new-payment-method'] ) {
			$customer_info = array(
				'email' => $order->get_billing_email(),
				'name'  => trim( $order->get_formatted_billing_full_name() ),
			);
			$token = $this->save_token( $customer_token, $cart_token, $customer_info );

			if ( ! is_null( $token ) ) {
				$order->add_payment_token( $token );
			}

			return $token;
		}

		return $customer_token;
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
	protected function process_standard_payments( $order, $cart_token = '', $customer_token = '' ) {
		try {

			if ( empty( $cart_token ) && empty( $customer_token ) ) {
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

			if ( ! empty ( $customer_token ) ) {
				$pass_tokens['customer'] = $customer_token;
				// Use the customer token only, since we already saved the (one time use) card token to the customer
				if ( isset( $_POST['wc-simplify_commerce-new-payment-method'] ) && true === (bool) $_POST['wc-simplify_commerce-new-payment-method'] ) {
					unset( $pass_tokens['token'] );
				}
			}

			// Did we create an account and save a payment method? We might need to use the customer token instead of the card token
			if ( isset( $_POST['createaccount'] ) && true === (bool) $_POST['createaccount'] && empty ( $customer_token ) ) {
				$user_token = $this->get_users_token();
				if ( ! is_null( $user_token ) ) {
					$pass_tokens['customer'] = $user_token->get_token();
					unset( $pass_tokens['token'] );
				}
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

	protected function get_users_token() {
		$customer_token  = null;
		if ( is_user_logged_in() ) {
			$tokens = WC_Payment_Tokens::get_customer_tokens( get_current_user_id() ) ;
			foreach ( $tokens as $token ) {
				if ( $token->get_gateway_id() === $this->id ) {
					$customer_token = $token;
					break;
				}
			}
		}
		return $customer_token;
	}

	/**
	 * Process the payment.
	 *
	 * @param int $order_id
	 */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		// Payment/CC form is hosted on Simplify
		if ( 'hosted' === $this->mode ) {
			return $this->process_hosted_payments( $order );
		}

		// New CC info was entered
		if ( isset( $_POST['simplify_token'] ) ) {
			$cart_token           = wc_clean( $_POST['simplify_token'] );
			$customer_token       = $this->get_users_token();
			$customer_token = $this->process_customer( $order, $customer_token, $cart_token );
			$customer_token_value = ( ! is_null( $customer_token ) ? $customer_token->get_token() : '' );
			return $this->process_standard_payments( $order, $cart_token, $customer_token_value );
		}

		// Possibly Create (or update) customer/save payment token, use an existing token, and then process the payment
		if ( isset( $_POST['wc-simplify_commerce-payment-token'] ) && 'new' !== $_POST['wc-simplify_commerce-payment-token'] ) {
			$token_id = wc_clean( $_POST['wc-simplify_commerce-payment-token'] );
			$token    = WC_Payment_Tokens::get( $token_id );
			if ( $token->get_user_id() !== get_current_user_id() ) {
				wc_add_notice( __( 'Please make sure your card details have been entered correctly and that your browser supports JavaScript.', 'woocommerce' ), 'error' );
				return;
			}
			$this->process_customer( $order, $token );
			return $this->process_standard_payments( $order, '', $token->get_token() );
		}
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

		echo '<p>' . __( 'Thank you for your order, please click the button below to pay with credit card using Simplify Commerce by Mastercard.', 'woocommerce' ) . '</p>';

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

	/**
	 * Get gateway icon.
	 *
	 * @access public
	 * @return string
	 */
	public function get_icon() {
		$icon = '';
		if ( 'hosted' !== $this->mode ) {
			if (!empty($this->supported_card_types)) {
				foreach (array_reverse($this->supported_card_types) as $cardType) {

					$icon .= '<img src="' . plugins_url('/assets/images/icons/credit-cards/', WC_SIMPLIFY_COMMERCE_FILE) . strtolower($cardType) . '.png' . '" alt="' . $cardType . '" style="max-height: 28px; height: 28px; width: 42px;"/>';
				}
			}
		}
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}
}
