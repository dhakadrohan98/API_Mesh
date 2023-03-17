<?php
namespace Sigma\InstallScripts\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $table = $installer->getConnection()
            ->newTable($installer->getTable('Laptop'))
            ->addColumn(
                'laptop_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Laptop ID'
            )
            ->addColumn(
                'laptop_name',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'default' => ''],
                'Laptop Name'
            )
            ->addColumn(
                'laptop_details',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'default' => ''],
                ' Laptop Details'
            )
            ->addColumn(
                'value',
                Table::TYPE_DECIMAL,
                '12,4',
                [],
                'Value'
            )
            ->setComment('Laptop Table');
        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}