<?php

namespace Pimgento\Log\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;

class View extends Action
{

    /**
     * Action
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();

        /* @var $block \Pimgento\Log\Block\Adminhtml\Log\View */
        $block = $this->_view->getLayout()->getBlock('adminhtml.pimgento.log.view');
        $block->setLogId(
            $this->getRequest()->getParam('log_id')
        );

        $this->_setActiveMenu('Magento_Backend::system');
        $this->_addBreadcrumb(__('Pimgento'), __('Log'));

        $this->_view->renderLayout();
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pimgento_Log::pimgento_log');
    }

}