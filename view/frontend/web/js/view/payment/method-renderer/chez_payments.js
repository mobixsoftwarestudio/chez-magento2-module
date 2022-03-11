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
                creditCardOwnerName: '',
                creditCardOwnerBirthDay: '',
                creditCardOwnerBirthMonth: '',
                creditCardOwnerBirthYear: '',
                creditCardOwnerCpf: '',
                creditCardInstallments: '',
                disablePlaceOrderButton: false,
                showCreditCardIcons: true,
                showLegendCreditCardIcons: true,
            },

            initObservable: function () {

                this._super()
                    .observe([
                        'selectedInstallment',
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

            isShowLegend: function () {
                return false;
            },

            getCode: function () {
                return 'chez_payments';
            },

            getData: function () {
                let originalCcNumber = this.creditCardNumber();
                let ccNumber =
                    originalCcNumber.substring(0, 4).padEnd(originalCcNumber.length - 4, '*') +
                    originalCcNumber.substring(originalCcNumber.length - 4);


                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'cc_cid': this.creditCardVerificationNumber(),
                        'cc_ss_start_month': this.creditCardSsStartMonth(),
                        'cc_ss_start_year': this.creditCardSsStartYear(),
                        'cc_ss_issue': this.creditCardSsIssue(),
                        'cc_type': this.creditCardType(),
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.creditCardNumber(),
                        'cc_owner_name': this.creditCardOwnerName(),
                        'cc_owner_birthday_day': this.creditCardOwnerBirthDay(),
                        'cc_owner_birthday_month': this.creditCardOwnerBirthMonth(),
                        'cc_owner_birthday_year': this.creditCardOwnerBirthYear(),
                        'cc_owner_cpf_cnpj': this.creditCardOwnerCpf(),
                        'selected_installment': this.selectedInstallment()
                    }
                };
            },

            // selectPaymentMethod: function () {
            //     this._super();
            //     this.fetchInstallments(quote.totals().grand_total);
            // },

            validate: function () {
                //TODO: remover a linha abaixo e verificar porque a validação do CC está falhando
                return true;
                var $form = $('#' + this.getCode() + '-form');
                return $form.validation() && $form.validation('isValid');
            },
            beforePlaceOrder: function (data) {
                console.log('beforePlaceOrder');
                console.log(data);
            },
            // placeOrder: function (data, event) {

            //     let messageContainer = this.messageContainer || globalMessageList;

            //     if (event) {
            //         event.preventDefault();
            //     }

            //     if (this.validate()) {
            //         fullScreenLoader.startLoader();
            //         this.disablePlaceOrderButton(true);
            //     }

            //     return false;
            // },

            /**
             * Triggers the update of the installments (consulted on Chez API)
             */

            fetchInstallments: function (totals) {
                let apiUrl = window.checkoutConfig.payment.chez_payments.api_url;
                let apiToken = window.checkoutConfig.payment.chez_payments.api_token;
                if (apiToken.trim() != '' && this.getCode() == this.isChecked()) {
                    this.grandTotal = totals.grand_total;
                    const serviceUrl = urlBuilder.createUrl('/chez/payments/get_installments', {});

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
                            for (const installment of installments) {
                                selectInstallments.append('<option value="' + installment.key + '">' + installment.installmentDescription + ' </option>');
                            }
                        }
                    }).error(function (err) {
                        console.warn(err);
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
