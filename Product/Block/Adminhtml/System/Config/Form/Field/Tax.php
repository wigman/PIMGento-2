<?php

namespace Pimgento\Product\Block\Adminhtml\System\Config\Form\Field;

class Tax extends \Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray
{

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;

    /**
     * @var \Magento\Tax\Model\TaxClass\Source\Product
     */
    protected $_productTaxClassSource;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param \Magento\Tax\Model\TaxClass\Source\Product $productTaxClassSource
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\Form\Element\Factory $elementFactory,
        \Magento\Tax\Model\TaxClass\Source\Product $productTaxClassSource,
        array $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        $this->_productTaxClassSource = $productTaxClassSource;
        parent::__construct($context, $data);
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('website', ['label' => __('Website')]);
        $this->addColumn('tax_class', ['label' => __('Tax Class')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
        parent::_construct();
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName == 'website' && isset($this->_columns[$columnName])) {

            $websites = $this->_storeManager->getWebsites();

            $options = array();
            foreach ($websites as $website) {
                $options[$website->getId()] = $website->getCode();
            }

            $element = $this->_elementFactory->create('select');
            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $options
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        if ($columnName == 'tax_class' && isset($this->_columns[$columnName])) {

            $options = $this->_productTaxClassSource->getAllOptions();

            $element = $this->_elementFactory->create('select');
            $element->setForm(
                $this->getForm()
            )->setName(
                $this->_getCellInputElementName($columnName)
            )->setHtmlId(
                $this->_getCellInputElementId('<%- _id %>', $columnName)
            )->setValues(
                $options
            );
            return str_replace("\n", '', $element->getElementHtml());
        }

        return parent::renderCellTemplate($columnName);
    }

}
