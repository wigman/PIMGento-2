<?php

namespace Pimgento\Variant\Observer;

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
                'code'       => 'variant',
                'name'       => __('Variant'),
                'class'      => '\Pimgento\Variant\Model\Factory\Import',
                'sort_order' => 50,
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
                        'comment' => __('Clean up variant'),
                        'method'  => 'removeColumns',
                    ),
                    array(
                        'comment' => __('Variant data enrichment'),
                        'method'  => 'addColumns',
                    ),
                    array(
                        'comment' => __('Fill variant data'),
                        'method'  => 'updateData',
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