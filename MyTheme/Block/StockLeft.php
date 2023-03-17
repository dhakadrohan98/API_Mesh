<?php

namespace Sigma\MyTheme\Block;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

class StockLeft extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * StockLeft constructor.
     *
     * @param Template\Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct
    (
        Template\Context $context,
        Registry $registry,
        StockRegistryInterface $stockRegistry,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @return string
     */
    public function getRemainingQty() {
        // 1.Fetch the product model
        // 2.Get the qty from product model
        // 3.Return it here.
        $product = $this->getCurrentProduct();
        $stock = $this->stockRegistry->getStockItem($product->getId());
        return  $stock->getQty();
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    protected function getCurrentProduct()
    {
        return $this->registry->registry('product');
    }
}