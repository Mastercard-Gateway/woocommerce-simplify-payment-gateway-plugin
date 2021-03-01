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

class WC_Gateway_Embedded_Simplify_Commerce extends WC_Gateway_Simplify_Commerce {

	const ID = 'embedded_simplify_commerce';

	/**
	 * Constructor.
	 */
	public function __construct() {
	    parent::__construct();
		$this->method_title         = __( 'Mastercard Payment Gateway Services - Simplify (Embedded)', 'woocommerce' );
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
		$args = apply_filters( 'woocommerce_' . $this->id . '_hosted_args', array(
			'sc-key'          => $this->public_key,
			'amount'          => $this->get_total( $order ),
			'currency'        => strtoupper( get_woocommerce_currency() ),
			'reference'       => $order->get_id(),
			'description'     => sprintf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ),
			'receipt'         => 'false',
			'color'           => $this->modal_color,
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
     * Receipt page.
     *
     * @param int $order_id
     */
    public function receipt_page( $order_id ) {
        $order = wc_get_order( $order_id );

        echo '<p>' . __( 'Thank you for your order, please click the button below to pay with credit card using Mastercard Payment Gateway Services - Simplify.',
                'woocommerce' ) . '</p>';

        $args        = $this->get_hosted_payments_args( $order );
        $iframe_args = array();
        foreach ( $args as $key => $value ) {
            $value = $this->attempt_transliteration($value);
            if (!$value) {
                continue;
            }
            $iframe_args[] = 'data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
        }
        // TEMPLATE VARS
        $redirect_url = WC()->api_request_url( 'WC_Gateway_Embedded_Simplify_Commerce' );
        $is_purchase = $this->txn_mode === self::TXN_MODE_PURCHASE;
        $public_key = $this->public_key;
        // TEMPLATE VARS

        require plugin_basename('embedded-template.php');
    }
}
