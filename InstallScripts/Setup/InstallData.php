<?php

namespace Sigma\InstallScripts\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class InstallData
 *
 * @package Thecoachsmb\Mymodule\Setup
 */
class InstallData implements InstallDataInterface
{
    /**
     * Creates sample articles
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $conn = $setup->getConnection();
        $tableName = $setup->getTable('Laptop');

        $data = [
            [
                'laptop_id' => 1,
                'laptop_name' => 'Dell Vostro 3400',
                'laptop_details' => 'Display: 35.56 cms (14 inches), FHD LED
                                    Memory: 8GB DDR4 RAM, 1TB HDD & 256GB SSD ROM
                                    Processor: Intel Core i5 11th Generation
                                    OS: Windows 10
                                    Graphics: Intel UHD
                                    Included Software: MS Office Home & Student 2019, McAfee
                                    Warranty: 1 Year Onsite',
                'value' => 54000
            ],
            [
                'laptop_id' => 2,
                'laptop_name' => 'Lenovo',
                'laptop_details' => 'How to insert data in table of Magento2',
                'value' => 66000
            ],
        ];
        $conn->insertMultiple($tableName, $data);
        $setup->endSetup();
    }
}