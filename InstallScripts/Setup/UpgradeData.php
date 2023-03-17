<?php

namespace Sigma\InstallScripts\Setup;

use \Magento\Framework\Setup\UpgradeDataInterface;
use \Magento\Framework\Setup\ModuleContextInterface;
use \Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class UpgradeData
 *
 * @package Thecoachsmb\Mymodule\Setup
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * Creates sample articles
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if ($context->getVersion()
            && version_compare($context->getVersion(), '1.1.1') < 0
        ) {
            $tableName = $setup->getTable('Laptop');

            $data = [
                [
                    'laptop_id' => 3,
                    'laptop_name' => 'Lenovo V14 Gen 2 (14, Intel)',
                    'laptop_details' => 'The powerful, economical, and feature-packed Lenovo V14 Gen 2 (14, Intel) laptop was designed to enhance your work experience—whether in the office or at home. Make the most of Windows 11 Pro, 11th Gen Intel® processors, and optional NVIDIA® discrete graphics for efficient multitasking, while security features keep you and your work safe.
                                         Lenovo’s CO2 Offset Services - Get the PC you want without compromising your environmental goals. Lenovo will cover the cost of your CO2 Offset services on PC purchases to help offset your emissions. ',
                    'value' => 54000
                ],
                [
                    'laptop_id' => 4,
                    'laptop_name' => 'IdeaPad Slim 3i Chromebook',
                    'laptop_details' => 'Fast, secure, and easy to use, the IdeaPad 3 Chromebook (14) boasts all your favorite Chromebook features, in a slim 180-degree hinged chassis.
                                        With the lightning-fast Chrome OS, it boots up in seconds, automatically updates, and has built-in malware and virus protection.
                                        Powered by Intel® processing, it can run for up to 10 hours, enabling you to seamlessly use all your favorite apps at the same time.',
                    'value' => 66000
                ],
            ];

            $setup
                ->getConnection()
                ->insertMultiple($tableName, $data);
        }

        $setup->endSetup();
    }
}