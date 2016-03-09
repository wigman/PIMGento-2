<?php

namespace Pimgento\Log\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Pimgento\Log\Api\Data\LogInterface;
use \Pimgento\Log\Model\Log as LogModel;

class PimgentoImportStepStartObserver implements ObserverInterface
{

    /**
     * @var \Pimgento\Log\Model\Log
     */
    protected $_log;

    /**
     * Constructor
     *
     * @param \Pimgento\Log\Model\Log $log
     */
    public function __construct(LogModel $log)
    {
        $this->_log = $log;
    }

    /**
     * Avoid address update for pickup delivery
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        /** @var $import \Pimgento\Import\Model\Factory */
        $import = $observer->getEvent()->getImport();

        if ($import->getStep() == 0) {
            $this->_log->setIdentifier($import->getIdentifier());
            $this->_log->setCode($import->getCode());
            $this->_log->setName($import->getName());
            $this->_log->setFile($import->getFile());
            $this->_log->setStatus(3); // processing
            $this->_log->save();
        } else {
            $this->_log->load($import->getIdentifier(), LogInterface::IDENTIFIER);
        }

        if ($this->_log->hasData()) {
            $this->_log->addStep(
                array(
                    'log_id' => $this->_log->getId(),
                    'identifier' => $import->getIdentifier(),
                    'number' => $import->getStep(),
                    'method' => $import->getMethod(),
                    'message' => $import->getComment(),
                )
            );
        }
    }

}