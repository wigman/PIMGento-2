<?php

namespace Pimgento\Category\Observer;

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
                'code'       => 'category',
                'name'       => __('Categories'),
                'class'      => '\Pimgento\Category\Model\Factory\Import',
                'sort_order' => 10,
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
                        'comment' => __('Match code with Magento ID'),
                        'method'  => 'matchEntity',
                    ),
                    array(
                        'comment' => __('Create URL key'),
                        'method'  => 'setUrlKey',
                    ),
                    array(
                        'comment' => __('Create structure'),
                        'method'  => 'setStructure',
                    ),
                    array(
                        'comment' => __('Create position'),
                        'method'  => 'setPosition',
                    ),
                    array(
                        'comment' => __('Create and update category entities'),
                        'method'  => 'createEntities',
                    ),
                    array(
                        'comment' => __('Set values to attributes'),
                        'method'  => 'setValues',
                    ),
                    array(
                        'comment' => __('Count of child categories'),
                        'method'  => 'updateChildrenCount',
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