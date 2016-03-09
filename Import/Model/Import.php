<?php

namespace Pimgento\Import\Model;

use \Magento\Framework\DataObject;
use \Pimgento\Import\Model\Import\Collection;

class Import extends DataObject
{

    /**
     * @var \Pimgento\Import\Model\Import\Collection
     */
    protected $_importCollection;

    /**
     * @param \Pimgento\Import\Model\Import\Collection $importCollection
     * @param array $data
     */
    public function __construct(
        Collection $importCollection,
        array $data = []
    )
    {
        $this->_importCollection = $importCollection;
        parent::__construct($data);
    }

    /**
     * Load import
     *
     * @param string $code
     * @return \Pimgento\Import\Model\Factory
     */
    public function load($code)
    {
        return $this->_importCollection->addCodeFilter($code)->loadImport()->getFirstItem();
    }

    /**
     * Retrieve Import Collection
     *
     * @return \Pimgento\Import\Model\Import\Collection
     */
    public function getCollection()
    {
        return $this->_importCollection->loadImport();
    }

}