<?php

namespace Pimgento\Attribute\Observer;

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
                'code'       => 'attribute',
                'name'       => __('Attributes'),
                'class'      => '\Pimgento\Attribute\Model\Factory\Import',
                'sort_order' => 30,
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
                        'comment' => __('Match types'),
                        'method'  => 'matchType',
                    ),
                    array(
                        'comment' => __('Match family'),
                        'method'  => 'matchFamily',
                    ),
                    array(
                        'comment' => __('Add or update attributes'),
                        'method'  => 'addAttributes',
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