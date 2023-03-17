<?php

namespace Sigma\SchemaPatches\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class TestData implements DataPatchInterface
{
    private $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $data[] = ['book_id' => '1', 'book_name' => 'Thaliva', 'book_details' => 'details'];
        $data[] = ['book_id' => '2', 'book_name' => 'Data Structures and Algorithms in Java', 'book_details' =>
            'Bestselling author Robert Lafore has perfectly timed this book to ride the wave of universities who are 
            switching to Java for introductory programming courses, in which data structures and algorithms are key topic areas.'];

        $this->moduleDataSetup->getConnection()->insertArray(
            $this->moduleDataSetup->getTable('book'),
            ['book_id', 'book_name', 'book_details'],
            $data
        );
        $this->moduleDataSetup->endSetup();
    }

    public function getAliases()
    {
        return [];
    }

    public static function getDependencies()
    {
        return [];
    }
}