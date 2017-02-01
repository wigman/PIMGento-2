<?php

namespace Pimgento\Import\Model;

use \Magento\Framework\DataObject;
use \Pimgento\Import\Model\Import\CollectionFactory;
use \Exception;

class Import extends DataObject
{

    /**
     * @var \Pimgento\Import\Model\Import\Collection
     */
    protected $_importCollection;

    /**
     * @param CollectionFactory $importCollectionFactory
     * @param array $data
     */
    public function __construct(
        CollectionFactory $importCollectionFactory,
        array $data = []
    ) {
        $this->_importCollection = $importCollectionFactory->create();

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

        $collection = $this->_importCollection->addCodeFilter($code)->loadImport();
        $import = $collection->getFirstItem();

        if($import->getCode()!=$code) {
            foreach ($collection->getItems() as $item) {
                if ($item->getCode() == $code) {
                    $import = $item;
                    break;
                }
            }
        }

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
