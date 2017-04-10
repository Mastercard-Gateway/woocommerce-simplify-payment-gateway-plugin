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

(function ( $ ) {

	// Form handler
	function simplifyFormHandler() {
		var $form = $( 'form.checkout, form#order_review, form#add_payment_method' );

		var simplify_checked   = $( '#payment_method_simplify_commerce' ).is( ':checked' );
		var token_field_count  = $( 'input[name="wc-simplify_commerce-payment-token"]' ).length;
		var token_value        = $( 'input[name="wc-simplify_commerce-payment-token"]:checked' ).val();
		var add_payment_method = $( '#wc-simplify_commerce-new-payment-method' ).val();

		if ( simplify_checked && ( ( 0 === token_field_count || 'new' === token_value ) || '1' === add_payment_method ) ) {


			console.log("simplify-token", $( 'input.simplify-token' ));
			if ( 0 === $( 'input.simplify-token' ).length ) {

				$form.block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				var data = {"error": {"code": "validation", "fieldErrors": []}};
				if (!$( '#simplify_commerce-card-number' ).val()) {
					data.error.fieldErrors.push({"field": "card.number", "message": "Field cannot be empty."});
				} else if (!$('#simplify_commerce-card-number').isCardSupported()) {
					data.error.fieldErrors.push({"field": "card.number", "message": "Card is not supported."});
				}
				var expiryDate = $( '#simplify_commerce-card-expiry' ).expiryDate();
				if (!expiryDate.month || !expiryDate.month.trim()) {
					data.error.fieldErrors.push({"field": "card.expMonth", "message": "Field is required."});
				}
				if (!expiryDate.year || !expiryDate.year.trim()) {
					data.error.fieldErrors.push({"field": "card.expYear", "message": "Field is required."});
				}
				if (data.error.fieldErrors.length != 0) {
					simplifyResponseHandler(data);
					return false;
				}

				var card           = $( '#simplify_commerce-card-number' ).val(),
					cvc            = $( '#simplify_commerce-card-cvc' ).val(),
					expiry         = $.payment.cardExpiryVal( $( '#simplify_commerce-card-expiry' ).val() ),
					address1       = $form.find( '#billing_address_1' ).val() || '',
					address2       = $form.find( '#billing_address_2' ).val() || '',
					addressCountry = $form.find( '#billing_country' ).val() || '',
					addressState   = $form.find( '#billing_state' ).val() || '',
					addressCity    = $form.find( '#billing_city' ).val() || '',
					addressZip     = $form.find( '#billing_postcode' ).val() || '';

				addressZip = addressZip.replace( /-/g, '' );
				card = card.replace( /\s/g, '' );

				SimplifyCommerce.generateToken({
					key: Simplify_commerce_params.key,
					card: {
						number: card,
						cvc: cvc,
						expMonth: expiry.month,
						expYear: ( expiry.year - 2000 ),
						addressLine1: address1,
						addressLine2: address2,
						addressCountry: addressCountry,
						addressState: addressState,
						addressZip: addressZip,
						addressCity: addressCity
					}
				}, simplifyResponseHandler );

				// Prevent the form from submitting
				return false;
			}
		}

		return true;
	}

	// Handle Simplify response
	function simplifyResponseHandler( data ) {

		var $form  = $( 'form.checkout, form#order_review, form#add_payment_method' ),
			ccForm = $( '#wc-simplify_commerce-cc-form' );

		if ( data.error ) {

			// Show the errors on the form
			$( '.woocommerce-error, .simplify-token', ccForm ).remove();
			$form.unblock();

			// Show any validation errors
			if ( 'validation' === data.error.code ) {
				var fieldErrors = data.error.fieldErrors,
					fieldErrorsLength = fieldErrors.length,
					errorList = '';

				for ( var i = 0; i < fieldErrorsLength; i++ ) {
					var fieldName = Simplify_commerce_params[ fieldErrors[i].field ];
					if (fieldName === undefined || !fieldName) {
						fieldName = fieldErrors[i].field;
					}
					errorList += '<li>' + fieldName + ' ' + Simplify_commerce_params.is_invalid  + ' - ' + fieldErrors[i].message + '.</li>';
				}

				ccForm.prepend( '<ul class="woocommerce-error">' + errorList + '</ul>' );
			}

		} else {

			// Insert the token into the form so it gets submitted to the server
			ccForm.append( '<input type="hidden" class="simplify-token" name="simplify_token" value="' + data.id + '"/>' );
			$form.submit();
		}
	}

	$( function () {

		$( document.body ).on( 'checkout_error', function () {
			$( '.simplify-token' ).remove();
		});

		/* Checkout Form */
		$( 'form.checkout' ).on( 'checkout_place_order_simplify_commerce', function () {
			return simplifyFormHandler();
		});

		/* Pay Page Form */
		$( 'form#order_review' ).on( 'submit', function () {
			return simplifyFormHandler();
		});

		/* Pay Page Form */
		$( 'form#add_payment_method' ).on( 'submit', function () {
			return simplifyFormHandler();
		});

		/* Both Forms */
		$( 'form.checkout, form#order_review, form#add_payment_method' ).on( 'change', '#wc-simplify_commerce-cc-form input', function() {
			$( '.simplify-token' ).remove();
		});

	});

	(function() {
		isNumeric = function(num) {
			return /[\d\s]/.test(num);
		}, isTextSelected = function(input) {
			return "number" == typeof input.prop("selectionStart") ? input.prop("selectionStart") != input.prop("selectionEnd") : "undefined" != typeof document.selection ? (input.focus(),
			document.selection.createRange().text == input.val()) : void 0;
		}, cardType = function(num) {
			var MASTERCARD = "51,52,53,54,55,22,", VISA = "4", AMEX = "34,37,", DISCOVER = "60,64,65,", CUP = "62,", JCB = "35,", DINERS = "30,36,38,39,";
			if (!num) return null;
			if (num.substring(0, 1) === VISA) return "visa";
			var prefix = num.substring(0, 2) + ",";
			return 3 != prefix.length ? null : -1 != MASTERCARD.indexOf(prefix) ? "mastercard" : -1 != AMEX.indexOf(prefix) ? "amex" : -1 != DISCOVER.indexOf(prefix) ? "discover" : -1 != CUP.indexOf(prefix) ? "cup" : -1 != JCB.indexOf(prefix) ? "jcb" : -1 != DINERS.indexOf(prefix) ? "diners" : null;
		}, restrictNumeric = function(e) {
			var keyCode = e.which;
			if (32 === keyCode) return !1;
			if (33 > keyCode) return !0;
			var keyChar = String.fromCharCode(keyCode);
			return isNumeric(keyChar);
		}, maxlength = function(e) {
			var input = $(this);
			if (!isTextSelected(input)) {
				var type = input.cardType(), keyChar = String.fromCharCode(e.which);
				if (isNumeric(keyChar)) {
					var value = input.val() + keyChar;
					return value = value.replace(/\D/g, ""), "amex" == type ? value.length <= 15 : value.length <= 16;
				}
			}
		}, needsLuhnCheck = function(value) {
			return -1 == [ "cup" ].indexOf(cardType(value));
		}, luhnCheck = function(value) {
			var luhn = function(v) {
				var t, n, p, i, s;
				for (p = !0, s = 0, n = v.split("").reverse(), i = 0; i < n.length; i++) t = parseInt(n[i], 10),
				(p = !p) && (t *= 2), t > 9 && (t -= 9), s += t;
				return s % 10 === 0;
			};
			return /^\d+$/.test(value) && luhn(value);
		}, formatCardInput = function(e) {
			var input = $(this);
			if (!isTextSelected(input)) {
				var type = input.cardType(), value = input.val(), keyChar = String.fromCharCode(e.which);
				if (isNumeric(keyChar)) {
					var maxlength = 16, pattern = /(?:^|\s)(\d{4})$/;
					"amex" === type && (maxlength = 15, pattern = /^(\d{4}|\d{4}\s\d{6})$/);
					var length = (value.replace(/\D/g, "") + keyChar).length;
					if (!(length >= maxlength)) return pattern.test(value) ? (e.preventDefault(), input.val(value + " " + keyChar)) : pattern.test(value + keyChar) ? (e.preventDefault(),
						input.val(value + keyChar + " ")) : void 0;
				}
			}
		}, formatCardBackspace = function(e) {
			var BACK_SPACE = 8, input = $(this), value = input.val();
			return isTextSelected(input) ? void 0 : e.which === BACK_SPACE && /\s\d?$/.test(value) ? (e.preventDefault(),
				input.val(value.replace(/\s\d?$/, ""))) : void 0;
		}, formatExpiryInput = function(e) {
			var input = $(this), value = $(this).val();
			if (!isTextSelected(input)) {
				var keyChar = String.fromCharCode(e.which), slash = "/" == keyChar;
				return value.replace(/\D/g, "").length >= 4 && 8 != e.which && 0 != e.which ? !1 : void ((isNumeric(keyChar) || slash) && (1 != value.length && slash || (input.val(1 == value.length && slash ? "0" + value + "/" : 1 != value.length || slash ? value + keyChar : value + keyChar + "/"),
					e.preventDefault())));
			}
		}, expiryDate = function(expiry) {
			if (expiry) {
				var dates = expiry.split("/");
				return {
					month: dates[0],
					year: dates[1]
				};
			}
			return {
				month: null,
				year: null
			};
		}, $.fn.restrictNumeric = function() {
			return this.keypress(restrictNumeric);
		}, $.fn.cardType = function() {
			return cardType(this.val());
		}, $.fn.formatCardNumber = function() {
			this.restrictNumeric(), this.keypress(maxlength), this.keypress(formatCardInput),
				this.keydown(formatCardBackspace);
		}, $.fn.formatExpiryNumber = function() {
			this.restrictNumeric(), this.keypress(formatExpiryInput);
		}, $.fn.expiryDate = function() {
			return expiryDate(this.val());
		}, $.fn.unformatValue = function() {
			return this.val() ? this.val().replace(/\s/g, "") : "";
		}, $.fn.isValid = function() {
			var v = this.unformatValue();
			return needsLuhnCheck(v) ? luhnCheck(v) : !0;
		}, $.fn.isCardSupported = function() {
			if (this.isValid()) {
				var type = this.cardType();
				console.log("card type", type);
				return Simplify_commerce_params.supported_card_types.indexOf(type) != -1;
			}
			return false;
		};
	}).call(this);

}( jQuery ) );
