<?php

namespace Pimgento\Log\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Pimgento\Log\Api\Data\LogInterface;
use \Pimgento\Log\Model\Log as LogModel;

class PimgentoImportStepFinishObserver implements ObserverInterface
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

        $this->_log->load($import->getIdentifier(), LogInterface::IDENTIFIER);

        if ($this->_log->hasData()) {
            if ($import->getStep() + 1 == $import->countSteps()) {
                $this->_log->setStatus(1)->save(); // Success
            }

            if (!$import->getContinue() && !$import->getStatus()) {
                $this->_log->setStatus(2)->save(); // Error
            }

            $this->_log->addStep(
                array(
                    'log_id' => $this->_log->getId(),
                    'identifier' => $import->getIdentifier(),
                    'number' => $import->getStep(),
                    'method' => $import->getMethod(),
                    'message' => $import->getMessage(),
                    'continue' => $import->getContinue() ? 1 : 0,
                    'status' => $import->getStatus() ? 1 : 0
                )
            );
        }
    }

}