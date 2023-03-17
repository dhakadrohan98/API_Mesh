<?php

namespace Sigma\CustomApi\Model\ResourceModel;

class DataExample extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
    public function _construct(){
        $this->_init("Rest_API_logs","id");
    }
}
?>