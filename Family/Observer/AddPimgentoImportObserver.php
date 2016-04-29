<?php

namespace Pimgento\Family\Observer;

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
                'code'       => 'family',
                'name'       => __('Families'),
                'class'      => '\Pimgento\Family\Model\Factory\Import',
                'sort_order' => 20,
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
                        'comment' => __('Match code with Magento ID'),
                        'method'  => 'matchEntity',
                    ),
                    array(
                        'comment' => __('Create or update families'),
                        'method'  => 'insertFamily',
                    ),
                    array(
                        'comment' => __('Init families from default skeleton'),
                        'method'  => 'initGroup',
                    ),
                    array(
                        'comment' => __('Drop temporary table'),
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