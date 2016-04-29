<?php

namespace Pimgento\Log\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends Action
{

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $logIds = $this->getRequest()->getParam('log_ids');
        if (!is_array($logIds)) {
            $this->messageManager->addError(__('Please select logs.'));
        } else {
            try {
                foreach ($logIds as $logId) {
                    $model = $this->_objectManager->create('Pimgento\Log\Model\Log')->load($logId);
                    $model->delete();
                }
                $this->messageManager->addSuccess(__('Total of %1 record(s) were deleted.', count($logIds)));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pimgento_Log::pimgento_log');
    }

}