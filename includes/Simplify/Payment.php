<?php
/*
 * Copyright (c) 2013 - 2017 Mastercard International Incorporated
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


class Simplify_Payment extends Simplify_Object {
    /**
     * Creates an Simplify_Payment object
     * @param     array $hash a map of parameters; valid keys are:<dl style="padding-left:10px;">
     *     <dt><tt>amount</tt></dt>    <dd>Amount of the payment (in the smallest unit of your currency). Example: 100 = $1.00 </dd>
     *     <dt><tt>authorization</tt></dt>    <dd>The ID of the authorization being used to capture the payment. </dd>
     *     <dt><tt>card.addressCity</tt></dt>    <dd>City of the cardholder. [max length: 50, min length: 2] </dd>
     *     <dt><tt>card.addressCountry</tt></dt>    <dd>Country code (ISO-3166-1-alpha-2 code) of residence of the cardholder. [max length: 2, min length: 2] </dd>
     *     <dt><tt>card.addressLine1</tt></dt>    <dd>Address of the cardholder. [max length: 255] </dd>
     *     <dt><tt>card.addressLine2</tt></dt>    <dd>Address of the cardholder if needed. [max length: 255] </dd>
     *     <dt><tt>card.addressState</tt></dt>    <dd>State of residence of the cardholder. State abbreviations should be used. [max length: 255] </dd>
     *     <dt><tt>card.addressZip</tt></dt>    <dd>Postal code of the cardholder. The postal code size is between 5 and 9 in length and only contain numbers or letters. [max length: 32] </dd>
     *     <dt><tt>card.cvc</tt></dt>    <dd>CVC security code of the card. This is the code on the back of the card. Example: 123 </dd>
     *     <dt><tt>card.expMonth</tt></dt>    <dd>Expiration month of the card. Format is MM. Example: January = 01 [min value: 1, max value: 12] <strong>required </strong></dd>
     *     <dt><tt>card.expYear</tt></dt>    <dd>Expiration year of the card. Format is YY. Example: 2013 = 13 [min value: 0, max value: 99] <strong>required </strong></dd>
     *     <dt><tt>card.name</tt></dt>    <dd>Name as it appears on the card. [max length: 50, min length: 2] </dd>
     *     <dt><tt>card.number</tt></dt>    <dd>Card number as it appears on the card. [max length: 19, min length: 13] <strong>required </strong></dd>
     *     <dt><tt>currency</tt></dt>    <dd>Currency code (ISO-4217) for the transaction. Must match the currency associated with your account. [default: USD] <strong>required </strong></dd>
     *     <dt><tt>customer</tt></dt>    <dd>ID of customer. If specified, card on file of customer will be used. </dd>
     *     <dt><tt>description</tt></dt>    <dd>Free form text field to be used as a description of the payment. This field is echoed back with the payment on any find or list operations. [max length: 1024] </dd>
     *     <dt><tt>invoice</tt></dt>    <dd>ID of invoice for which this payment is being made. </dd>
     *     <dt><tt>order.commodityCode</tt></dt>    <dd>Standard classification code for products and services. [max length: 5] </dd>
     *     <dt><tt>order.customer</tt></dt>    <dd>ID of the customer associated with the order. </dd>
     *     <dt><tt>order.customerEmail</tt></dt>    <dd>Customer email address. </dd>
     *     <dt><tt>order.customerName</tt></dt>    <dd>Customer name. </dd>
     *     <dt><tt>order.customerNote</tt></dt>    <dd>Additional notes provided by the customer. [max length: 255] </dd>
     *     <dt><tt>order.customerReference</tt></dt>    <dd>A merchant reference for the customer. </dd>
     *     <dt><tt>order.items.amount</tt></dt>    <dd>Cost of the item. </dd>
     *     <dt><tt>order.items.description</tt></dt>    <dd>Description of the item. </dd>
     *     <dt><tt>order.items.name</tt></dt>    <dd>Item name. </dd>
     *     <dt><tt>order.items.product</tt></dt>    <dd>Product information associated with the item. </dd>
     *     <dt><tt>order.items.quantity</tt></dt>    <dd>Quantity of the item contained in the order [min value: 1, max value: 999999, default: 1] <strong>required </strong></dd>
     *     <dt><tt>order.items.reference</tt></dt>    <dd>A merchant reference for the item. [max length: 255] </dd>
     *     <dt><tt>order.items.tax</tt></dt>    <dd>Taxes associated with the item. </dd>
     *     <dt><tt>order.merchantNote</tt></dt>    <dd>Additional notes provided by the merchant. [max length: 255] </dd>
     *     <dt><tt>order.payment</tt></dt>    <dd>ID of the payment associated with the order. </dd>
     *     <dt><tt>order.reference</tt></dt>    <dd>A merchant reference for the order. [max length: 255] </dd>
     *     <dt><tt>order.shippingAddress.city</tt></dt>    <dd>City, town, or municipality. [max length: 255, min length: 2] </dd>
     *     <dt><tt>order.shippingAddress.country</tt></dt>    <dd>2-character country code. [max length: 2, min length: 2] </dd>
     *     <dt><tt>order.shippingAddress.line1</tt></dt>    <dd>Street address. [max length: 255] </dd>
     *     <dt><tt>order.shippingAddress.line2</tt></dt>    <dd>(Opt) Street address continued. [max length: 255] </dd>
     *     <dt><tt>order.shippingAddress.name</tt></dt>    <dd>Name of the entity being shipped to. [max length: 255] </dd>
     *     <dt><tt>order.shippingAddress.state</tt></dt>    <dd>State or province. [max length: 255] </dd>
     *     <dt><tt>order.shippingAddress.zip</tt></dt>    <dd>Postal code. [max length: 32] </dd>
     *     <dt><tt>order.shippingFromAddress.city</tt></dt>    <dd>City, town, or municipality. [max length: 255, min length: 2] </dd>
     *     <dt><tt>order.shippingFromAddress.country</tt></dt>    <dd>2-character country code. [max length: 2, min length: 2] </dd>
     *     <dt><tt>order.shippingFromAddress.line1</tt></dt>    <dd>Street address. [max length: 255] </dd>
     *     <dt><tt>order.shippingFromAddress.line2</tt></dt>    <dd>(Opt) Street address continued. [max length: 255] </dd>
     *     <dt><tt>order.shippingFromAddress.name</tt></dt>    <dd>Name of the entity performing the shipping. [max length: 255] </dd>
     *     <dt><tt>order.shippingFromAddress.state</tt></dt>    <dd>State or province. [max length: 255] </dd>
     *     <dt><tt>order.shippingFromAddress.zip</tt></dt>    <dd>Postal code. [max length: 32] </dd>
     *     <dt><tt>order.shippingName</tt></dt>    <dd>Name of the entity being shipped to. </dd>
     *     <dt><tt>order.source</tt></dt>    <dd>Order source. [default: WEB] <strong>required </strong></dd>
     *     <dt><tt>order.status</tt></dt>    <dd>Status of the order. [default: INCOMPLETE] <strong>required </strong></dd>
     *     <dt><tt>reference</tt></dt>    <dd>Custom reference field to be used with outside systems. </dd>
     *     <dt><tt>replayId</tt></dt>    <dd>An identifier that can be sent to uniquely identify a payment request to facilitate retries due to I/O related issues. This identifier must be unique for your account (sandbox or live) across all of your payments. If supplied, we will check for a payment on your account that matches this identifier. If found will attempt to return an identical response of the original request. [max length: 50, min length: 1] </dd>
     *     <dt><tt>statementDescription.name</tt></dt>    <dd>Merchant name. <strong>required </strong></dd>
     *     <dt><tt>statementDescription.phoneNumber</tt></dt>    <dd>Merchant contact phone number. </dd>
     *     <dt><tt>taxExempt</tt></dt>    <dd>Specify true to indicate that the payment is tax-exempt. </dd>
     *     <dt><tt>token</tt></dt>    <dd>If specified, card associated with card token will be used. [max length: 255] </dd></dl>
     * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.<i/>
     * @return    Payment a Payment object.
     */
    static public function createPayment($hash, $authentication = null) {

        $args = func_get_args();
        $authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

        $instance = new Simplify_Payment();
        $instance->setAll($hash);

        $object = Simplify_PaymentsApi::createObject($instance, $authentication);
        return $object;
    }



       /**
        * Retrieve Simplify_Payment objects.
        * @param     array criteria a map of parameters; valid keys are:<dl style="padding-left:10px;">
        *     <dt><tt>filter</tt></dt>    <dd><table class="filter_list"><tr><td>filter.id</td><td>Filter by the payment Id</td></tr><tr><td>filter.replayId</td><td>Filter by the compoundReplayId</td></tr><tr><td>filter.last4</td><td>Filter by the card number (last 4 digits)</td></tr><tr><td>filter.amount</td><td>Filter by the payment amount (in the smallest unit of your currency)</td></tr><tr><td>filter.text</td><td>Filter by the description of the payment</td></tr><tr><td>filter.amountMin & filter.amountMax</td><td>The filter amountMin must be used with amountMax to find payments with payments amounts between the min and max figures</td></tr><tr><td>filter.dateCreatedMin<sup>*</sup></td><td>Filter by the minimum created date you are searching for - Date in UTC millis</td></tr><tr><td>filter.dateCreatedMax<sup>*</sup></td><td>Filter by the maximum created date you are searching for - Date in UTC millis</td></tr><tr><td>filter.deposit</td><td>Filter by the deposit id connected to the payment</td></tr><tr><td>filter.customer</td><td>Filter using the Id of the customer to find the payments for that customer</td></tr><tr><td>filter.status</td><td>Filter by the payment status text</td></tr><tr><td>filter.reference</td><td>Filter by the payment reference text</td></tr><tr><td>filter.authCode</td><td>Filter by the payment authorization code (Not the authorization ID)</td></tr><tr><td>filter.q</td><td>You can use this to filter by the Id, the authCode or the amount of the payment</td></tr></table><br><sup>*</sup>Use dateCreatedMin with dateCreatedMax in the same filter if you want to search between two created dates  </dd>
        *     <dt><tt>max</tt></dt>    <dd>Allows up to a max of 50 list items to return. [min value: 0, max value: 50, default: 20]  </dd>
        *     <dt><tt>offset</tt></dt>    <dd>Used in paging of the list.  This is the start offset of the page. [min value: 0, default: 0]  </dd>
        *     <dt><tt>sorting</tt></dt>    <dd>Allows for ascending or descending sorting of the list.  The value maps properties to the sort direction (either <tt>asc</tt> for ascending or <tt>desc</tt> for descending).  Sortable properties are: <tt> dateCreated</tt><tt> createdBy</tt><tt> amount</tt><tt> id</tt><tt> description</tt><tt> paymentDate</tt>.</dd></dl>
        * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
        * @return    ResourceList a ResourceList object that holds the list of Payment objects and the total
        *            number of Payment objects available for the given criteria.
        * @see       ResourceList
        */
        static public function listPayment($criteria = null, $authentication = null) {

            $args = func_get_args();
            $authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

            $val = new Simplify_Payment();
            $list = Simplify_PaymentsApi::listObject($val, $criteria, $authentication);

            return $list;
        }


        /**
         * Retrieve a Simplify_Payment object from the API
         *
         * @param     string id  the id of the Payment object to retrieve
         * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
         * @return    Payment a Payment object
         */
        static public function findPayment($id, $authentication = null) {

            $args = func_get_args();
            $authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

            $val = new Simplify_Payment();
            $val->id = $id;

            $obj = Simplify_PaymentsApi::findObject($val, $authentication);

            return $obj;
        }


        /**
         * Updates an Simplify_Payment object.
         *
         * The properties that can be updated:
         * <dl style="padding-left:10px;"></dl>
         * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
         * @return    Payment a Payment object.
         */
        public function updatePayment($authentication = null)  {

            $args = func_get_args();
            $authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 1);

            $object = Simplify_PaymentsApi::updateObject($this, $authentication);
            return $object;
        }

    /**
     * @ignore
     */
    public function getClazz() {
        return "Payment";
    }
}