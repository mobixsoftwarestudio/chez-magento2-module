<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Chez\Payments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    const CC_OWNER_NAME = 'cc_owner_name';
    const CC_OWNER_BIRTHDAY_DAY = 'cc_owner_birthday_day';
    const CC_OWNER_BIRTHDAY_MONTH = 'cc_owner_birthday_month';
    const CC_OWNER_BIRTHDAY_YEAR = 'cc_owner_birthday_year';
    const CC_OWNER_CPF_CNPJ = 'cc_owner_cpf_cnpj';
    const SELECTED_INSTALLMENT = 'selected_installment';
    const CC_CID = 'cc_cid';
    const CC_SS_START_MONTH = 'cc_ss_start_month';
    const CC_SS_START_YEAR = 'cc_ss_start_year';
    const CC_SS_ISSUE = 'cc_ss_issue';
    const CC_TYPE = 'cc_type';
    const CC_EXP_YEAR = 'cc_exp_year';
    const CC_EXP_MONTH = 'cc_exp_month';
    const CC_NUMBER = 'cc_number';
    const CREDIT_CARD_TOKEN = 'credit_card_token';
    const CC_TYPE1 = 'cc_type1';
    const IS_ADMIN = 'is_admin';

    protected $additionalInformationList = [
        self::CC_OWNER_NAME,
        self::CC_OWNER_BIRTHDAY_DAY,
        self::CC_OWNER_BIRTHDAY_MONTH,
        self::CC_OWNER_BIRTHDAY_YEAR,
        self::CC_OWNER_CPF_CNPJ,
        self::SELECTED_INSTALLMENT,
        self::CC_CID,
        self::CC_SS_START_MONTH,
        self::CC_SS_START_YEAR,
        self::CC_SS_ISSUE,
        self::CC_TYPE,
        self::CC_EXP_YEAR,
        self::CC_EXP_MONTH,
        self::CC_NUMBER,
        self::CREDIT_CARD_TOKEN,
        self::CC_TYPE1,
        self::IS_ADMIN,
    ];

    public function __construct(\Chez\Payments\Helper\Logger $loggerHelper)
    {
        $this->_logHelper  = $loggerHelper;
    }
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $method = $this->readMethodArgument($observer);
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }
        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}
