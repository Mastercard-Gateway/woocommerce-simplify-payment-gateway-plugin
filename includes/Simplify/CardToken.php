<?php
/*
 * Copyright (c) 2013 - 2023 MasterCard International Incorporated
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
 * Neither the name of the MasterCard International Incorporated nor the names of its 
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


class Simplify_CardToken extends Simplify_Object {
    /**
     * Creates an Simplify_CardToken object
     * @param     array $hash a map of parameters; valid keys are:<dl style="padding-left:10px;">
     *     <dt><tt>authenticatePayer</tt></dt>    <dd>Set as true to create CardToken for EMV 3DS transaction. [default: false] </dd>
     *     <dt><tt>callback</tt></dt>    <dd>The URL callback for the cardtoken </dd>
     *     <dt><tt>card.addressCity</tt></dt>    <dd>City of the cardholder. [max length: 50, min length: 2] </dd>
     *     <dt><tt>card.addressCountry</tt></dt>    <dd>Country code (ISO-3166-1-alpha-2 code) of residence of the cardholder. [max length: 2, min length: 2] </dd>
     *     <dt><tt>card.addressLine1</tt></dt>    <dd>Address of the cardholder. [max length: 255] </dd>
     *     <dt><tt>card.addressLine2</tt></dt>    <dd>Address of the cardholder if needed. [max length: 255] </dd>
     *     <dt><tt>card.addressState</tt></dt>    <dd>State of residence of the cardholder. State abbreviations should be used. [max length: 255] </dd>
     *     <dt><tt>card.addressZip</tt></dt>    <dd>Postal code of the cardholder. The postal code size is between 5 and 9 in length and only contain numbers or letters. [max length: 32] </dd>
     *     <dt><tt>card.cvc</tt></dt>    <dd>CVC security code of the card. This is the code on the back of the card. Example: 123 </dd>
     *     <dt><tt>card.expMonth</tt></dt>    <dd>Expiration month of the card. Format is MM. Example: January = 01 [min value: 1, max value: 12] </dd>
     *     <dt><tt>card.expYear</tt></dt>    <dd>Expiration year of the card. Format is YY. Example: 2013 = 13 [min value: 0, max value: 99] </dd>
     *     <dt><tt>card.name</tt></dt>    <dd>Name as appears on the card. [max length: 50, min length: 2] </dd>
     *     <dt><tt>card.number</tt></dt>    <dd>Card number as it appears on the card. [max length: 19, min length: 13] </dd>
     *     <dt><tt>key</tt></dt>    <dd>Key used to create the card token. </dd>
     *     <dt><tt>secure3DRequestData.amount</tt></dt>    <dd>Amount of the subsequent transaction in the smallest unit of your currency. Example: 100 = $1.00 <strong>required </strong></dd>
     *     <dt><tt>secure3DRequestData.authOnly</tt></dt>    <dd>Specifies if the subsequent transaction is going to be a Payment or an Authorization (to be Captured later). If false or not specified, it refers to a Payment, otherwise it refers to an Authorization. </dd>
     *     <dt><tt>secure3DRequestData.currency</tt></dt>    <dd>Currency code (ISO-4217). Must match the currency associated with your account. <strong>required </strong></dd>
     *     <dt><tt>secure3DRequestData.description</tt></dt>    <dd>A description of the transaction. [max length: 256] </dd>
     *     <dt><tt>secure3DRequestData.id</tt></dt>    <dd>3D Secure data ID. </dd>
     *     <dt><tt>source</tt></dt>    <dd>Card Token Source [default: API] </dd></dl>
     * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.<i/>
     * @return    CardToken a CardToken object.
     */
    static public function createCardToken($hash, $authentication = null) {

        $args = func_get_args();
        $authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

        $instance = new Simplify_CardToken();
        $instance->setAll($hash);

        $object = Simplify_PaymentsApi::createObject($instance, $authentication);
        return $object;
    }



        /**
         * Retrieve a Simplify_CardToken object from the API
         *
         * @param     string id  the id of the CardToken object to retrieve
         * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
         * @return    CardToken a CardToken object
         */
        static public function findCardToken($id, $authentication = null) {

            $args = func_get_args();
            $authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 2);

            $val = new Simplify_CardToken();
            $val->id = $id;

            $obj = Simplify_PaymentsApi::findObject($val, $authentication);

            return $obj;
        }


        /**
         * Updates an Simplify_CardToken object.
         *
         * The properties that can be updated:
         * <dl style="padding-left:10px;">
         *     <dt><tt>device.browser</tt></dt>    <dd>The User-Agent header of the browser the customer used to place the order <strong>required </strong></dd>
         *     <dt><tt>device.ipAddress</tt></dt>    <dd>The IP address of the device used by the payer, in nnn.nnn.nnn.nnn format. <strong>required </strong></dd>
         *     <dt><tt>device.language</tt></dt>    <dd>The language supported for the payer's browser as defined in IETF BCP47. </dd>
         *     <dt><tt>device.screenHeight</tt></dt>    <dd>The total height of the payer's browser screen in pixels. </dd>
         *     <dt><tt>device.screenWidth</tt></dt>    <dd>The total width of the payer's browser screen in pixels. </dd>
         *     <dt><tt>device.timeZone</tt></dt>    <dd>The timezone of the device used by the payer, in Zone ID format. Example: "Europe/Dublin" <strong>required </strong></dd>
         *     <dt><tt>key</tt></dt>    <dd>The public key of the merchant to be used for the token </dd></dl>
         * @param     $authentication -  information used for the API call.  If no value is passed the global keys Simplify::public_key and Simplify::private_key are used.  <i>For backwards compatibility the public and private keys may be passed instead of the authentication object.</i>
         * @return    CardToken a CardToken object.
         */
        public function updateCardToken($authentication = null)  {

            $args = func_get_args();
            $authentication = Simplify_PaymentsApi::buildAuthenticationObject($authentication, $args, 1);

            $object = Simplify_PaymentsApi::updateObject($this, $authentication);
            return $object;
        }

    /**
     * @ignore
     */
    public function getClazz() {
        return "CardToken";
    }
}