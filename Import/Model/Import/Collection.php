<?php

namespace Pimgento\Import\Model\Import;

use \Magento\Framework\Data\Collection\EntityFactoryInterface;
use \Magento\Framework\Event\ManagerInterface;

class Collection extends \Magento\Framework\Data\Collection
{

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * @var string
     */
    protected $_code = null;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        ManagerInterface $eventManager
    )
    {
        parent::__construct($entityFactory);
        $this->_eventManager = $eventManager;
    }

    /**
     * Load Import
     *
     * @return $this
     */
    public function loadImport()
    {
        $this->_eventManager->dispatch(
            'pimgento_load_import', ['collection' => $this]
        );

        return $this;
    }

    /**
     * Add code filter
     *
     * @param string $code
     * @return $this
     */
    public function addCodeFilter($code)
    {
        $this->_code = $code;

        return $this;
    }

    /**
     * Add import to collection
     *
     * @param array $data
     * @return $this
     * @throws \Exception
     */
    public function addImport($data)
    {
        if (isset($data['code'])) {
            if ($this->_code && $data['code'] !== $this->_code) {
                return $this;
            }

            $class = '\Pimgento\Import\Model\Factory\\' . ucfirst(strtolower($data['code']));

            if (isset($data['class'])) {
                $class = $data['class'];
            }

            if (class_exists($class)) {
                $import = $this->_entityFactory->create($class);
                $import->setData($data);

                $sortOrder = 0;
                if (isset($data['sort_order'])) {
                    $sortOrder = $data['sort_order'];
                }
                if (isset($this->_items[$sortOrder])) {
                    $sortOrder = max(array_keys($this->_items)) + 1;
                }
                $import->setId($sortOrder);

                $this->addItem($import);
                ksort($this->_items);
            }
        }

        return $this;
    }

}