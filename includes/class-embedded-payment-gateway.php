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
