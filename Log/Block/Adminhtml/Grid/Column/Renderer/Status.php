<?php

namespace Pimgento\Log\Block\Adminhtml\Grid\Column\Renderer;

use \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use \Magento\Framework\DataObject;

class Status extends AbstractRenderer
{

    /**
     * Render indexer status
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $class = '';
        $text = '';
        switch ($this->_getValue($row)) {
            case 1:
                $class = 'grid-severity-notice';
                $text = __('Success');
                break;
            case 2:
                $class = 'grid-severity-critical';
                $text = __('Error');
                break;
            case 3:
                $class = 'grid-severity-minor';
                $text = __('Processing');
                break;
        }
        return '<span class="' . $class . '"><span>' . $text . '</span></span>';
    }

}
