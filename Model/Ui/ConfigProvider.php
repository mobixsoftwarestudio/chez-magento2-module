<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Chez\Payments\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Chez\Payments\Gateway\Http\Client\ClientMock;
use Chez\Payments\Helper\Data;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'chez_payments';
    /**
     * PagSeguro Helper
     *
     * @var Data;
     */
    protected $helper;

    /**
     * @param Data $pagSeguroHelper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }
    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $token = $this->helper->getToken();
        $api_url = $this->helper->getApiUrl();
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        $token => $token,
                        ClientMock::FAILURE => __('Fraud')
                    ],
                    'api_token' => $token,
                    'api_url' => $api_url,
                    'installments' => [
                        '1x' => __('1x Sem Juros'),
                        '2x' => __('2x Sem Juros'),
                        '3x' => __('3x Sem Juros'),
                        '4x' => __('4x Sem Juros'),
                        '5x' => __('5x Com Juros'),
                        '6x' => __('6x Sem Juros'),
                        '7x' => __('7x Sem Juros'),
                        '8x' => __('8x Sem Juros'),
                        '9x' => __('9x Sem Juros'),
                        '10x' => __('10x Sem Juros'),
                        '11x' => __('11x Sem Juros'),
                        '12x' => __('12x Sem Juros'),
                    ],

                ]
            ]
        ];
    }
}
