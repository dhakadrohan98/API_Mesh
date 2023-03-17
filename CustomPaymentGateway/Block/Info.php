<?php
/**
 * Created by PhpStorm.
 * User: sigma
 * Date: 21/1/23
 * Time: 12:42 PM
 */

namespace Sigma\CustomPaymentGateway\Block;

use Magento\Framework\Phrase;
// Frontend part for our custom payment gateway.
class Info extends \Magento\Payment\Block\ConfigurableInfo
{
    protected function getLabel($field)
    {
        return __($field);
    }

    protected function getValueView($field, $value)
    {
        return parent::getValueView($field, $value);
    }
}