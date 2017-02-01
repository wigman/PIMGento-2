<?php

namespace Pimgento\Import\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\DataObject;

abstract class AbstractAddImportObserver
{
    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * PHP Constructor
     *
     * @param EventManager $eventManager
     *
     * @throws \Exception
     */
    public function __construct(
        EventManager $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * Add import to Collection
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var $collection \Pimgento\Import\Model\Import\Collection */
        $collection = $observer->getEvent()->getCollection();

        $collection->addImport($this->getImportDefinition());
    }

    /**
     * Get the import definition
     *
     * @return array
     */
    protected function getImportDefinition()
    {
        return [
            'code'             => $this->getImportCode(),
            'name'             => $this->getImportName(),
            'class'            => $this->getImportClassname(),
            'sort_order'       => $this->getImportSortOrder(),
            'file_is_required' => $this->isImportFileRequired(),
            'steps'            => $this->getStepsDefinition()
        ];
    }

    /**
     * Get the import classname to use
     *
     * @return string
     */
    protected function getImportClassname()
    {
        $response = new DataObject();
        $response->setData('import_classname', $this->getImportDefaultClassname());

        $this->eventManager->dispatch(
            'pimgento_'.$this->getImportCode().'product_import_classname',
            ['response' => $response]
        );

        return $response->getData('import_classname');
    }

    /**
     * Get additionnal steps to add
     *
     * @param string $eventPrefix
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getAdditionnalSteps($eventPrefix = 'add_final_steps', $fieldName = 'final_steps')
    {
        $response = new DataObject();
        $response->setData($fieldName, []);

        $this->eventManager->dispatch(
            'pimgento_'.$this->getImportCode().'_import_'.$eventPrefix,
            ['response' => $response]
        );

        return $response->getData($fieldName);
    }

    /**
     * Is a file is required for thie import
     *
     * @return bool
     */
    protected function isImportFileRequired()
    {
        return true;
    }

    /**
     * Get the import code
     *
     * @return string
     */
    abstract protected function getImportCode();

    /**
     * Get the import code
     *
     * @return string
     */
    abstract protected function getImportName();

    /**
     * Get the sort order
     *
     * @return int
     */
    abstract protected function getImportSortOrder();

    /**
     * Get the default import classname
     *
     * @return string
     */
    abstract protected function getImportDefaultClassname();

    /**
     * get the steps definition
     *
     * @return array
     */
    abstract protected function getStepsDefinition();
}
