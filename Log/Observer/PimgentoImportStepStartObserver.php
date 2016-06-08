<?php

namespace Pimgento\Log\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Pimgento\Log\Api\Data\LogInterface;
use \Pimgento\Log\Model\Log as LogModel;
use \Pimgento\Log\Model\LogFactory;

class PimgentoImportStepStartObserver implements ObserverInterface
{

    /**
     * @var LogFactory
     */
    protected $_logFactory;

    /**
     * Constructor
     *
     * @param LogFactory $logFactory
     */
    public function __construct(LogFactory $logFactory)
    {
        $this->_logFactory = $logFactory;
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

        /** @var LogModel $log */
        $log = $this->_logFactory->create();

        if ($import->getStep() == 0) {
            $log->setIdentifier($import->getIdentifier());
            $log->setCode($import->getCode());
            $log->setName($import->getName());
            $log->setFile($import->getFile());
            $log->setStatus(3); // processing
            $log->save();
        } else {
            $log->load($import->getIdentifier(), LogInterface::IDENTIFIER);
        }

        if ($log->hasData()) {
            $log->addStep(
                array(
                    'log_id'     => $log->getId(),
                    'identifier' => $import->getIdentifier(),
                    'number'     => $import->getStep(),
                    'method'     => $import->getMethod(),
                    'message'    => $import->getComment(),
                )
            );
        }
    }

}