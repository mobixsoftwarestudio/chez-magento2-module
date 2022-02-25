<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Chez\Payments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;

class DataAssignObserver extends AbstractDataAssignObserver
{
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
        //$paymentInfo = $method->getInfoInstance();
        $paymentInfo = $this->readPaymentModelArgument($observer);
        if ($data->getDataByKey('installment') !== null) {
            $paymentInfo->setAdditionalInformation(
                'installment',
                $data->getDataByKey('installment')
            );
        } else {
            $this->_logHelper->writeLog('Empty installment');
        }
    }
}
