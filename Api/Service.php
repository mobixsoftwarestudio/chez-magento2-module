<?php

namespace Chez\Payments\Api;

use Chez\Payments\Api\ServiceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Webapi\ServiceInputProcessor;
use Magento\Checkout\Api\Data\ShippingInformationInterfaceFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Registry;
use Magento\Framework\Pricing\PriceCurrencyInterface;


class Service implements ServiceInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    private $checkoutHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    private $config;

    private $chezHelper;

    /**
     * Service constructor.
     *
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \StripeIntegration\Payments\Model\Config $config,
        \Chez\Payments\Helper\Data $chezHelper
    ) {
        $this->logger = $logger;
        $this->checkoutHelper = $checkoutHelper;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->chezHelper = $chezHelper;
    }

    /**
     * Get Quote
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        $quote = $this->checkoutHelper->getCheckout()->getQuote();
        if (!$quote->getId()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $quote = $objectManager->create('Magento\Checkout\Model\Session')->getQuote();
        }
        return $quote;
    }

    public function get_installments()
    {
        try {
            return $this->chezHelper->getInstallments($this->getQuote()->getGrandTotal());
        } catch (\Exception $e) {
            return json_encode(
                array('error' => 'Failure to get installments from Chez API (' . $e->getMessage() . ')')
            );
        }
    }
}
