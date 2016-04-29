<?php

namespace Pimgento\Log\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;

class Index extends Action
{

    /**
     * Action
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();

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