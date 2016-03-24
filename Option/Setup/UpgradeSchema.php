<?php
/**
 * Colissimo
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial licence, do not copy or distribute without authorization
 */
namespace Pimgento\Option\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Class UpgradeSchema
 * @package Magentix\Colissimo\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $setup->getConnection()->addIndex(
            $installer->getTable('eav_attribute_option_value'),
            $installer->getIdxName(
                'eav_attribute_option_value',
                ['option_id', 'store_id'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            ),
            ['option_id', 'store_id'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
        );

        $installer->endSetup();
    }

}