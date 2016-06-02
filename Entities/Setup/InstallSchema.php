<?php

namespace Pimgento\Entities\Setup;

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
         * Create table 'pimgento_entities'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('pimgento_entities'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'import',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Import type'
            )
            ->addColumn(
                'code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Pim Code'
            )
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                11,
                ['nullable' => true],
                'Magento Entity Id'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )
            ->addIndex(
                $installer->getIdxName(
                    'pimgento_entities',
                    ['import', 'code', 'entity_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['import', 'code', 'entity_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setComment('Pimgento Entities Relation');

        $installer->getConnection()->createTable($table);

        $installer->endSetup();

    }
}
