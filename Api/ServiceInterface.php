<?php

namespace Chez\Payments\Api;

interface ServiceInterface
{
    /**
     * Get available installment plans for a specific payment method
     *
     * @api
     * @param string $paymentMethodId
     *
     * @return mixed Json object with params
     */
    public function get_installments();
}
