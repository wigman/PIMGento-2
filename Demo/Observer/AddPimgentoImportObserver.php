<?php

namespace Pimgento\Demo\Observer;

use Magento\Framework\Event\ObserverInterface;
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
                'code'       => 'demo',
                'name'       => __('Demo'),
                'class'      => '\Pimgento\Demo\Model\Factory\Import',
                'sort_order' => 0,
                'steps' => array(
                    array(
                        'comment' => __('First step'),
                        'method'  => 'firstStep',
                    ),
                    array(
                        // 'comment' => __('Second step'),
                        'method'  => 'secondStep',
                    ),
                    array(
                        'comment' => __('Third step'),
                        'method'  => 'thirdStep',
                    )
                )
            )
        );

    }

}