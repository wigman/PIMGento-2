<?php

namespace Pimgento\Product\Block\Adminhtml\System\Config\Form\Field;

class Attribute extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('akeneo_attribute', ['label' => __('Akeneo')]);
        $this->addColumn('magento_attribute', ['label' => __('Magento')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
        parent::_construct();
    }

}
