<?php

namespace Pimgento\Product\Setup;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Upgrade Data script
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if ($context->getVersion() && version_compare($context->getVersion(), '1.0.1', '<')) {
            $data = $installer->getConnection()->fetchOne(
                $installer->getConnection()->select()
                    ->from($installer->getTable('core_config_data'), array('value'))
                    ->where('path = ?', 'pimgento/product/configurable_attributes')
                    ->limit(1)
            );

            $matches = array();

            if ($data) {
                $attributes = explode(',', $data);

                foreach ($attributes as $attribute) {
                    $matches['_' . time() . '_' . uniqid()] = array(
                        'attribute' => $attribute,
                        'value'     => '',
                    );
                }
            }

            $installer->getConnection()->update(
                $installer->getTable('core_config_data'),
                array('value' => serialize($matches)),
                array('path = ?' => 'pimgento/product/configurable_attributes')
            );
        }

        $installer->endSetup();

    }
}
