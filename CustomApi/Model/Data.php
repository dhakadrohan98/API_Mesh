<?php
namespace Sigma\CustomApi\Model;

class Data extends \Magento\Framework\Model\AbstractModel{
    public function _construct(){
        $this->_init("Sigma\CustomApi\Model\ResourceModel\DataExample");
    }
}
?>