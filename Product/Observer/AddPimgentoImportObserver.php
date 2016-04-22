<?php

namespace Pimgento\Product\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;

class AddPimgentoImportObserver implements ObserverInterface
{

    /**
     * Add import to Collection
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var $collection \Pimgento\Import\Model\Import\Collection */
        $collection = $observer->getEvent()->getCollection();

        $collection->addImport(
            array(
                'code'       => 'product',
                'name'       => __('Products'),
                'class'      => '\Pimgento\Product\Model\Factory\Import',
                'sort_order' => 60,
                'file_is_required' => true,
                'steps' => array(
                    array(
                        'comment' => __('Create temporary table'),
                        'method'  => 'createTable',
                    ),
                    array(
                        'comment' => __('Fill temporary table'),
                        'method'  => 'insertData',
                    ),
                    array(
                        'comment' => __('Add product required data'),
                        'method'  => 'addRequiredData',
                    ),
                    array(
                        'comment' => __('Create configurable product'),
                        'method'  => 'createConfigurable',
                    ),
                    array(
                        'comment' => __('Match code with Magento ID'),
                        'method'  => 'matchEntity',
                    ),
                    array(
                        'comment' => __('Match family code with Magento id'),
                        'method'  => 'updateAttributeSetId',
                    ),
                    array(
                        'comment' => __('Update column values for options'),
                        'method'  => 'updateOption',
                    ),
                    array(
                        'comment' => __('Create or update product entities'),
                        'method'  => 'createEntities',
                    ),
                    array(
                        'comment' => __('Set values to attributes'),
                        'method'  => 'setValues',
                    ),
                    array(
                        'comment' => __('Link configurable with children'),
                        'method'  => 'linkConfigurable',
                    ),
                    array(
                        'comment' => __('Set products to websites'),
                        'method'  => 'setWebsites',
                    ),
                    array(
                        'comment' => __('Set products to categories'),
                        'method'  => 'setCategories',
                    ),
                    array(
                        'comment' => __('Init stock'),
                        'method'  => 'initStock',
                    ),
                    array(
                        'comment' => __('Set Url Rewrite'),
                        'method'  => 'setUrlRewrite',
                    ),
                    array(
                        'comment' => __('Drop  temporary table'),
                        'method'  => 'dropTable',
                    ),
                    array(
                        'comment' => __('Clean cache'),
                        'method'  => 'cleanCache',
                    )
                )
            )
        );

    }

}