/*
 * Copyright (c) 2019-2023 Mastercard
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
 *
 */
jQuery(function ($) {
    'use strict';
    var wc_mastercard_admin = {
        init: function () {
            var gateway_url = $('#woocommerce_simplify_commerce_custom_gateway_url').parents('tr').eq(0);

            $('#woocommerce_simplify_commerce_gateway_url').on('change', function () {
                if ($(this).val() === 'custom') {
                    gateway_url.show();
                } else {
                    gateway_url.hide();
                }
            }).change();
        }
    };
    wc_mastercard_admin.init();
});
