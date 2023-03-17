<?php
/**
 * @category  Sigma
 * @package   Sigma_SAPIntegration
 * @author    SigmaInfo Team
 * @copyright 2022 Sigma (https://www.sigmainfo.net/)
 */

namespace Sigma\SAPIntegration\Cron;
use Sigma\SAPIntegration\Model\ProductDetails;

class ProductCron
{
    public function __construct(
        ProductDetails $productDetails
    ) {
        $this->productDetails = $productDetails;
    }
    public function execute()
    {
        $result = $this->productDetails->productInfo();
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/testcron.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('Cron is working');
        $logger->info(print_r($result));
        return $this;
    }
}
