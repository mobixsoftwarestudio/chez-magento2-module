<?php

/**
 * @author Vendor
 * @copyright Copyright (c) 2019 Vendor (https://www.vendor.com/)
 */

declare(strict_types=1);

namespace Chez\Payments\Observer;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Exception;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

/**
 * Class MyObserver
 */
class LoggerObserver implements ObserverInterface
{
    /**
     * @var PsrLoggerInterface
     */
    private $logger;

    /**
     * MyObserver constructor.
     *
     * @param PsrLoggerInterface $logger
     */
    public function __construct(PsrLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        try {
            // some code goes here
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
