<?php

namespace Chez\Payments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Chez\Payments\Helper\Logger;

class QuoteObserver extends AbstractDataAssignObserver
{
    public $hasSubscriptions = null;

    // public function __construct(
    //     \StripeIntegration\Payments\Helper\Generic $helper,
    //     \StripeIntegration\Payments\Model\Config $config,
    //     \StripeIntegration\Payments\Model\Tax\Calculation $taxCalculation
    // )
    // {
    //     $this->helper = $helper;
    //     $this->config = $config;
    //     $this->taxCalculation = $taxCalculation;
    // }
    public function __construct(\Chez\Payments\Helper\Logger $loggerHelper)
    {
        $this->_logHelper  = $loggerHelper;
        $this->_logHelper->writeLog('Construct');
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();

        if (empty($quote))
            $this->_logHelper->writeLog('Empty quote');
        else
            $this->_logHelper->writeLog($quote->getAllItems());

        $eventName = $observer->getEvent()->getName();

        // if (empty($quote) || (!$this->config->isEnabled() && !$this->config->isEnabled("checkout")))
        //     return;

        // if ($this->config->priceIncludesTax())
        //     return;

        // $this->taxCalculation->method = null;

        // if ($this->hasSubscriptions === null)
        //     $this->hasSubscriptions = $this->helper->hasSubscriptionsIn($quote->getAllItems());

        // if ($this->hasSubscriptions) {
        //     $this->taxCalculation->method = \Magento\Tax\Model\Calculation::CALC_ROW_BASE;
        //     return;
        // }

        // if ($quote->getPayment() && $quote->getPayment()->getMethod() == "stripe_payments_invoice") {
        //     $this->taxCalculation->method = \Magento\Tax\Model\Calculation::CALC_ROW_BASE;
        //     return;
        // }
    }
}
