<?php

namespace Pimgento\Category\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Pimgento\Entities\Model\Entities;

class RemovePimgentoCodeObserver implements ObserverInterface
{

    /**
     * @var \Pimgento\Entities\Model\Entities
     */
    protected $_entities;

    /**
     * Constructor
     *
     * @param \Pimgento\Entities\Model\Entities $entities
     */
    public function __construct(Entities $entities)
    {
        $this->_entities = $entities;
    }

    /**
     * Remove pimento code
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var $category \Magento\Catalog\Model\Category */
        $category = $observer->getEvent()->getCategory();

        $this->_entities->setImport('category');
        $this->_entities->setEntityId($category->getId());
        $this->_entities->delete();
    }

}