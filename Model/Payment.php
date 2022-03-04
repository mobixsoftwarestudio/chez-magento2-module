<?php

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Chez\Payments\Model;

use Magento\Framework\Simplexml\Element;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Payment\Model\Method\Adapter;
use Magento\Payment\Model\Method\Cc;

/**
 * Pay In Store payment method model
 */
class Payment extends Cc
{
    const CODE = 'chez_payments';
    protected $_code = self::CODE;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_isGateway = true;
    protected $_countryFactory;
    protected $cart = null;
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Chez\Payments\Helper\Logger $loggerHelper,
        \Chez\Payments\Helper\Data $chezHelper,
        array $data = array()
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $paymentData, $scopeConfig, $logger, $moduleList, $localeDate, null, null, $data);
        $this->cart = $cart;
        $this->_countryFactory = $countryFactory;
        $this->_logHelper  = $loggerHelper;
        $this->_helper = $chezHelper;
        $this->_helper->writeLog('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXx');
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  object
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);

        $this->_helper->writeLog('============ assignData');

        if (!$data instanceof \Magento\Framework\DataObject) {
            $data = new \Magento\Framework\DataObject($data);
        }

        $info = $this->getInfoInstance();
        // $info->setAdditionalInformation('sender_hash', $data['additional_data']['sender_hash'] ?? null)
        //     ->setAdditionalInformation(
        //         'credit_card_token',
        //         $data['additional_data']['credit_card_token'] ?? null
        //     )
        //     ->setAdditionalInformation('credit_card_owner', $data['additional_data']['cc_owner_name'] ?? null)
        //     ->setCcType($data['additional_data']['cc_type'] ?? null)
        //     ->setCcLast4(substr($data['additional_data']['cc_number'] ?? null, -4))
        //     ->setCcExpYear($data['additional_data']['cc_exp_year'] ?? null)
        //     ->setCcExpMonth($data['additional_data']['cc_exp_month'] ?? null);

        // set cpf
        $ccOwnerCpf = $data['additional_data']['cc_owner_cpf_cnpj'] ?? null;
        $info->setAdditionalInformation($this->getCode() . '_cc_owner_cpf_cnpj', $ccOwnerCpf);

        // //DOB value
        // if ($this->pagSeguroHelper->isDobVisible()) {
        //     $dobDay = isset($data['additional_data']['cc_owner_birthday_day']) ? trim(
        //         $data['additional_data']['cc_owner_birthday_day']
        //     ) : '01';
        //     $dobMonth = isset($data['additional_data']['cc_owner_birthday_month']) ? trim(
        //         $data['additional_data']['cc_owner_birthday_month']
        //     ) : '01';
        //     $dobYear = isset($data['additional_data']['cc_owner_birthday_year']) ? trim(
        //         $data['additional_data']['cc_owner_birthday_year']
        //     ) : '1970';
        //     $info->setAdditionalInformation(
        //         'credit_card_owner_birthdate',
        //         date(
        //             'd/m/Y',
        //             strtotime(
        //                 $dobMonth . '/' . $dobDay . '/' . $dobYear
        //             )
        //         )
        //     );
        // }

        // //Installments value
        // if (isset($data['additional_data']['cc_installments'])) {
        //     $installments = explode('|', $data['additional_data']['cc_installments']);
        //     if (false !== $installments && count($installments) == 2) {
        //         $info->setAdditionalInformation('installment_quantity', (int)$installments[0]);
        //         $info->setAdditionalInformation('installment_value', $installments[1]);
        //     }
        // }

        // //Sandbox Mode
        // if ($this->pagSeguroHelper->isSandbox()) {
        //     $info->setAdditionalInformation('is_sandbox', '1');
        // }

        return $this;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $this->_logHelper->writeLog('---------capure');
            $this->_logHelper->writeLog($payment);
            //check if payment has not been authorized then authorize it
            if (is_null($payment->getParentTransactionId())) {

                $this->authorize($payment, $amount);
            }
            //build array of all necessary details to pass to your Payment Gateway..
            $request = ['CardCVV2' => $payment->getCcCid(), 'CardNumber' => $payment->getCcNumber(), 'CardExpiryDate' => $this->getCardExpiryDate($payment), 'Amount' => $amount, 'Currency' => $this->cart->getQuote()->getBaseCurrencyCode(),];
            //make API request to credit card processor.
            $response = $this->captureRequest($request);
            //Handle Response accordingly.
            //transaction is completed.
            $payment->setTransactionId($response['tid'])->setIsTransactionClosed(0);
        } catch (\Exception $e) {
            $this->debug($payment->getData(), $e->getMessage());
        }
        return $this;
    }
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $this->_logHelper->writeLog('---------authorize');
            $this->_logHelper->writeLog($payment);

            //build array of all necessary details to pass to your Payment Gateway..
            $request = ['CardCVV2' => $payment->getCcCid(), 'CardNumber' => $payment->getCcNumber(), 'CardExpiryDate' => $this->getCardExpiryDate($payment), 'Amount' => $amount, 'Currency' => $this->cart->getQuote()->getBaseCurrencyCode(),];
            //check if payment has been authorized
            $response = $this->authRequest($request);
        } catch (\Exception $e) {
            $this->debug($payment->getData(), $e->getMessage());
        }
        if (isset($response['tid'])) { // Successful auth request.
            // Set the transaction id on the payment so the capture request knows auth has happened.
            $payment->setTransactionId($response['tid']);
            $payment->setParentTransactionId($response['tid']);
        }
        //processing is not done yet.
        $payment->setIsTransactionClosed(0);
        return $this;
    }
    /*This function is defined to set the Payment Action Type that is - - Authorize - Authorize and Capture Whatever has been set under Configuration of this Payment Method in Admin Panel, that will be fetched and set for this Payment Method by passing that into getConfigPaymentAction() function. */
    public function getConfigPaymentAction()
    {
        $this->_logHelper->writeLog('---------getConfigPayment');


        return $this->getConfigData('payment_action');
    }
    public function authRequest($request)
    {
        $this->_logHelper->writeLog('--------- auth request');

        //Process Request and receive the response from Payment Gateway---
        $response = ['tid' => rand(100000, 99999999)];
        //Here, check response and process accordingly---
        if (!$response) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Failed authorize request.'));
        }
        return $response;
    }
    /**
     * Test method to handle an API call for capture request.
     *
     * @param $request
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function captureRequest($request)
    {
        //Process Request and receive the response from Payment Gateway---
        $this->_logHelper->writeLog('--------- capure request');

        $response = ['tid' => rand(100000, 99999999)];
        //Here, check response and process accordingly---
        if (!$response) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Failed capture request.'));
        }
        return $response;
    }
}
