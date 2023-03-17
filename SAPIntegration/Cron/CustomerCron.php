<?php

/**
 * @category  Sigma
 * @package   Sigma_SAPIntegration
 * @author    SigmaInfo Team
 * @copyright 2022 Sigma (https://www.sigmainfo.net/)
 */

namespace Sigma\SAPIntegration\Cron;

use Sigma\SAPIntegration\Model\CustomerDetails;

class CustomerCron
{
    public function __construct(
        CustomerDetails $customerDetails
    ) {
        $this->customerDetails = $customerDetails;
    }
    public function execute()
    {
        $result = $this->customerDetails->customerInfo();
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/testcron1.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Customer Cron is working');
        $logger->info(print_r($result));
        return $this;
    }
}
