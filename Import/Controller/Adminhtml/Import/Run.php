<?php

namespace Pimgento\Import\Controller\Adminhtml\Import;

use \Magento\Backend\App\Action;
use \Magento\Backend\App\Action\Context;
use \Magento\Framework\Controller\Result\JsonFactory;
use \Pimgento\Import\Model\Import as ImportModel;

class Run extends Action
{

    /**
     * @var \Pimgento\Import\Model\Import
     */
    protected $_import;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Pimgento\Import\Model\Import $import
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ImportModel $import
    ) {
        parent::__construct($context);
        $this->_import = $import;
        $this->_resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Action
     *
     * @return Object
     */
    public function execute()
    {
        $step = $this->getRequest()->getParam('step');
        $code = $this->getRequest()->getParam('code');
        $file = $this->getRequest()->getParam('file');
        $identifier = $this->getRequest()->getParam('identifier');

        $execute = array();

        if (!is_null($step) && $code) {
            $import = $this->_import->load($code);

            if ($identifier) {
                $import->setIdentifier($identifier);
            }

            $import->setFile($file)->setStep((int)$step)->execute();

            $execute = array(
                'status'     => $import->getStatus(),
                'message'    => $import->getMessage(),
                'continue'   => $import->getContinue(),
                'comment'    => $import->getComment(),
                'method'     => $import->getMethod(),
                'identifier' => $import->getIdentifier(),
                'next'       => $import->next()->getComment()
            );
        }

        $resultJson = $this->_resultJsonFactory->create();

        return $resultJson->setData($execute);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pimgento_Import::pimgento_import');
    }

}