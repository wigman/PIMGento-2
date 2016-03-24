<?php

namespace Pimgento\Option\Observer;

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
                'code'       => 'option',
                'name'       => __('Options'),
                'class'      => '\Pimgento\Option\Model\Factory\Import',
                'sort_order' => 40,
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
                        'comment' => __('Insert options'),
                        'method'  => 'insertOptions',
                    ),
                    array(
                        'comment' => __('Insert option labels'),
                        'method'  => 'insertValues',
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