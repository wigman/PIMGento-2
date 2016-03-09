<?php

namespace Pimgento\Family\Observer;

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
        /** @var $attributeSet \Magento\Eav\Model\Entity\Attribute\set */
        $attributeSet = $observer->getEvent()->getObject();

        $this->_entities->setImport('family');
        $this->_entities->setEntityId($attributeSet->getId());
        $this->_entities->delete();
    }

}