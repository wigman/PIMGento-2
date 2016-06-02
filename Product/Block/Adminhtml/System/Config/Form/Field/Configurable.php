<?php

namespace Pimgento\Product\Block\Adminhtml\System\Config\Form\Field;

class Configurable extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('attribute', ['label' => __('Attribute')]);
        $this->addColumn('value', ['label' => __('Value')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
        parent::_construct();
    }

}
