<?php

namespace Sigma\CustomApi\Model\ResourceModel\DataExample;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection{
    public function _construct(){
        $this->_init("Sigma\CustomApi\Model\Data","Sigma\CustomApi\Model\ResourceModel\DataExample");
    }
}
?>