<?php

namespace Chez\Payments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use StripeIntegration\Payments\Helper\Logger;

class OrderObserver extends AbstractDataAssignObserver
{
    public function __construct(
        \Chez\Payments\Helper\Data $helper,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->helper = $helper;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $eventName = $observer->getEvent()->getName();
        $method = $order->getPayment()->getMethod();

        if ($method == 'chez_payments' && $eventName == "sales_order_place_after") {
            $this->updateOrderState($observer);
        }
    }

    public function updateOrderState($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();

        $transaction_result = $this->helper->setTransaction($order, $payment);
        $response = json_decode($transaction_result);

        if ($response->error) {
            $comment = __("CHEZ: Ocorreu um erro ao processar o pagamento!");
            $order->addStatusToHistory($status = \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT, $comment, $isCustomerNotified = true);
            $order->save();
        } else {
            $comment = __("CHEZ: Pagamento processado com sucesso!");
            $order->addStatusToHistory($status = \Magento\Sales\Model\Order::STATE_COMPLETE, $comment, $isCustomerNotified = true);
            $order->save();
        }
    }
}
