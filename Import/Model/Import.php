<?php

namespace Pimgento\Import\Model;

use \Magento\Framework\DataObject;
use \Pimgento\Import\Model\Import\Collection;
use \Exception;

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
     * @throws Exception
     */
    public function load($code)
    {
        if (!$code) {
            throw new Exception(__('Import code is empty'));
        }

        $import = $this->_importCollection->addCodeFilter($code)->loadImport()->getFirstItem();

        if (!$import->hasData()) {
            throw new Exception(__('Import %1 not found', $code));
        }

        return $import;
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