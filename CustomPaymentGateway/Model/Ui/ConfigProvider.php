<?php
/**
 * Created by Rohan Ranade
 * Developed by Rohan Ranade
 * File: ConfigProvider.php
 * Date: 5/9/2021
 * Time: 10:43 AM
 */

namespace Sigma\CustomPaymentGateway\Model\Ui;

use Sigma\CustomPaymentGateway\Gateway\Http\Client\ClientMock;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * Payment Gateway Code
     */
    const CODE = 'customPaymentGateway';

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    ]
                ]
            ]
        ];
    }
}