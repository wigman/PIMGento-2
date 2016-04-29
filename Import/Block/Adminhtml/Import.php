<?php

namespace Pimgento\Import\Block\Adminhtml;

use \Magento\Backend\Block\Template;
use \Magento\Backend\Model\UrlFactory;
use \Magento\Framework\File\Size;
use \Magento\Backend\Block\Template\Context;
use \Pimgento\Import\Model\Import as ImportModel;
use \Magento\Framework\AuthorizationInterface;

class Import extends Template
{

    /**
     * Model Url instance
     *
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $url;

    /**
     * @var \Magento\Framework\File\Size
     */
    protected $fileConfig;

    /**
     * @var int
     */
    protected $maxFileSize;

    /**
     * @var \Pimgento\Import\Model\Import
     */
    protected $_import;

    /**
     * @var AuthorizationInterface
     */
    protected $_authorization;

    /**
     * @param \Magento\Backend\Model\UrlFactory $backendUrlFactory
     * @param \Magento\Framework\File\Size $fileConfig
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Pimgento\Import\Model\Import $import
     * @param AuthorizationInterface $authorization
     * @param array $data
     */
    public function __construct(
        UrlFactory $backendUrlFactory,
        Size $fileConfig,
        Context $context,
        ImportModel $import,
        AuthorizationInterface $authorization,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->_import = $import;
        $this->url = $backendUrlFactory->create();
        $this->fileConfig = $fileConfig;
        $this->maxFileSize = $this->getFileMaxSize();
        $this->_authorization = $authorization;
    }

    /**
     * Retrieve import collection
     *
     * @return \Pimgento\Import\Model\Import\Collection
     */
    public function getCollection()
    {
        return $this->_import->getCollection();
    }

    /**
     * Check import is allowed
     *
     * @param string $code
     * @return bool
     */
    public function isAllowed($code)
    {
        return $this->_authorization->isAllowed('Pimgento_Import::pimgento_import_' . $code);
    }

    /**
     * Return element html code
     *
     * @return string
     */
    public function _toHtml()
    {
        $this->assign([
            'htmlId' => 'pimgento-file',
            'fileMaxSize' => $this->maxFileSize,
            'uploadUrl' => $this->_escaper->escapeHtml($this->_getUploadUrl()),
            'runUrl' => $this->_escaper->escapeHtml($this->_getRunUrl()),
            'filePlaceholderText' => __('Click here or drag and drop to add files.'),
            'importFileText' => __('Import')
        ]);

        return parent::_toHtml();
    }

    /**
     * Retrieve run URL
     *
     * @return string
     */
    public function _getRunUrl()
    {
        return $this->url->getUrl('pimgento/import/run');
    }

    /**
     * Get url to upload files
     *
     * @return string
     */
    protected function _getUploadUrl()
    {
        return $this->url->getUrl('pimgento/import/upload');
    }

    /**
     * Get maximum file size to upload in bytes
     *
     * @return int
     */
    protected function getFileMaxSize()
    {
        return $this->fileConfig->getMaxFileSize();
    }
}
