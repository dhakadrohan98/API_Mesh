<?php
namespace Sigma\SAPIntegration\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Sigma\SAPIntegration\Helper\Data;

class SapOrderObserver implements ObserverInterface
{
  //  $flag=0;
    protected $logger;
    public function __construct(
        LoggerInterface $logger,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Sigma\SAPIntegration\Helper\Data $helperData
    )
    {
        $this->logger = $logger;
        $this->_productFactory = $productFactory;
        $this->helperData = $helperData;
    }
        public function execute(\Magento\Framework\Event\Observer $observer)
        {
            try
            {

                $order = $observer->getEvent()->getOrder();
                $this->helperData->triggerSapApi($order);
            }
            catch (\Exception $e)
            {
                $this->logger->info($e->getMessage());
            }
    }
}
