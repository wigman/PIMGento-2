<?php

namespace Pimgento\Variant\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use \Magento\Framework\DB\Ddl\Table;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'pimgento_variant'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('pimgento_variant'))
            ->addColumn(
                'code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'primary' => true],
                'Akeneo code'
            )
            ->addColumn(
                'axis',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Product axis'
            )
            ->setComment('Pimgento Variant');

        $installer->getConnection()->createTable($table);

    }
}
