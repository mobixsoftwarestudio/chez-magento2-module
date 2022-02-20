/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Payment/js/view/payment/cc-form',
        'jquery',
        'Magento_Checkout/js/model/full-screen-loader',
        'uiRegistry',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/validator',

    ],
    function (
        Component,
        $,
        fullScreenLoader,
        uiRegistry,
        globalMessageList,
        quote
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Chez_Payments/payment/form',
                transactionResult: ''
            },

            initObservable: function () {

                this._super()
                    .observe([
                        'transactionResult',
                        'creditCardOwnerName',
                        'creditCardOwnerBirthDay',
                        'creditCardOwnerBirthMonth',
                        'creditCardOwnerBirthYear',
                        'creditCardOwnerCpf',
                        'creditCardInstallments',
                        'disablePlaceOrderButton'

                    ]);
                return this;
            },

            initialize: function () {
                this._super();

                uiRegistry.get(this.name + '.' + this.name + '.messages', (function (component) {
                    component.hideTimeout = 12000;
                }));

                this.grandTotal = quote.totals().grand_total;

            },

            isActive: function () {
                return true;
            },

            getCode: function () {
                return 'chez_payments';
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'transaction_result': this.transactionResult()
                    }
                };
            },

            validate: function () {
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },

            getTransactionResults: function () {
                return _.map(window.checkoutConfig.payment.chez_payments.transactionResults, function (value, key) {
                    return {
                        'value': key,
                        'transaction_result': value
                    }
                });
            }
        });
    }
);
