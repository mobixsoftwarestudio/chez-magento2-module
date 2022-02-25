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
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Payment/js/model/credit-card-validation/validator',
        'mage/storage',
    ],
    function (
        Component,
        $,
        fullScreenLoader,
        uiRegistry,
        globalMessageList,
        quote,
        urlBuilder,
        additionalValidators,
        validator,
        storage
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
                quote.totals.subscribe(this.fetchInstallments.bind(this));
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
                //TODO: remover a linha abaixo e verificar porque a validação do CC está falhando
                return true;
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },
            /**
             * Triggers the update of the installments (consulted on Chez API)
             */

            fetchInstallments: function (totals) {
                let apiUrl = window.checkoutConfig.payment.chez_payments.api_url;
                let apiToken = window.checkoutConfig.payment.chez_payments.api_token;
                console.log('apiToken:' + apiToken);
                console.log('getCode:' + this.getCode());
                console.log('isChecked:' + this.isChecked());
                console.log('this.grandTotal:' + this.grandTotal);
                console.log('totals.grand_total:' + totals.grand_total);
                if (apiToken.trim() != '' && this.getCode() == this.isChecked()) {
                    this.grandTotal = totals.grand_total;
                    const serviceUrl = urlBuilder.createUrl('/chez/payments/get_installments', {});
                    console.log('serviceUrl: ' + serviceUrl);

                    const payload = {
                        total: this.grandTotal
                    };

                    const request = storage.post(serviceUrl, JSON.stringify(payload));
                    request.done(function (res) {
                        const response = JSON.parse(res);
                        if (response.success) {
                            const selectInstallments = $('#chez_payments_cc_installments');
                            selectInstallments.empty();
                            const installments = response.success;
                            console.log(installments);
                            for (const installment of installments) {
                                selectInstallments.append('<option value="' + installment.key + '">' + installment.installmentDescription + ' juros </option>');
                            }
                        }

                    }).error(function (err) {
                        self.stripeCreatingToken(false);
                        console.warn(err);

                        // If for any reason we can't fetch the installment plans, just place the order
                        self.placeOrderWithToken();
                    });

                }
            },

            getInstallments: function () {
                return _.map(window.checkoutConfig.payment.chez_payments.installments, function (value, key) {
                    return {
                        'value': key,
                        'installment': value
                    }
                });
            }
        });
    }
);
