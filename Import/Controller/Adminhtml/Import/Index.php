<?php

namespace Pimgento\Import\Controller\Adminhtml\Import;

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
        $this->_addBreadcrumb(__('Pimgento'), __('Import'));

        $this->_view->renderLayout();
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }

}