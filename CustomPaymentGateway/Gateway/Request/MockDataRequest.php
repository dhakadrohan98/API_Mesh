<?php
/**
 * Created by Rohan Ranade
 * Developed by Rohan Ranade
 * File: MockDataRequest.php
 * Date: 5/9/2021
 * Time: 11:54 AM
 */

namespace Sigma\CustomPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Sigma\CustomPaymentGateway\Gateway\Http\Client\ClientMock;

class MockDataRequest implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    const FORCE_RESULT = 'FORCE_RESULT';

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];
        $payment = $paymentDO->getPayment();

        $transactionResult = $payment->getAdditionalInformation('transaction_result');
        return [
            self::FORCE_RESULT => $transactionResult === null
                ? ClientMock::SUCCESS
                : $transactionResult
        ];
    }
}