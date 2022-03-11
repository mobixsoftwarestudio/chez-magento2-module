<?php

namespace Chez\Payments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Chez\Payments\Helper\Logger;

class PaymentMethodActiveObserver extends AbstractDataAssignObserver
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
        // $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        // $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        // $subTotal = $cart->getQuote()->getSubtotal();
        // $grandTotal = $cart->getQuote()->getGrandTotal();

        // $total = $observer->getData('total');
        // $this->_logHelper->writeLog('---------subTotal');
        // $this->_logHelper->writeLog($subTotal);
        // $quote = $observer->getEvent()->getQuote();
        // $this->_logHelper->writeLog('---------total');
        // $this->_logHelper->writeLog($grandTotal);

        // if (!$this->config->isSubscriptionsEnabled())
        //     return;

        // $result = $observer->getEvent()->getResult();
        // $methodInstance = $observer->getEvent()->getMethodInstance();
        // $code = $methodInstance->getCode();
        // $isAvailable = $result->getData('is_available');

        // // No need to check if its already false
        // if (!$isAvailable)
        //     return;

        // // Can't check without a quote
        // if (!$quote)
        //     return;

        // if ($this->helper->supportsSubscriptions($code))
        //     return;

        // // Disable all other payment methods if we have subscriptions
        // if ($this->helper->hasSubscriptions())
        //     $result->setData('is_available', false);
    }
}
