<?php

namespace Pimgento\Import\Helper;

use \Magento\Framework\App\Filesystem\DirectoryList;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Filesystem;
use \Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Filesystem $fileSystem,
        StoreManagerInterface $storeManager
    )
    {
        $this->_fileSystem = $fileSystem;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Retrieve upload directory
     *
     * @return string
     */
    public function getUploadDir()
    {
        /** @var $varDirectory \Magento\Framework\Filesystem\Directory\Read $mediaDirectory */
        $varDirectory = $this->_fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);

        return $varDirectory->getAbsolutePath('import/pimgento');
    }

    /**
     * Retrieve all stores information
     *
     * @param string $arrayKey
     * @return array
     */
    public function getStores($arrayKey = 'id')
    {
        $stores = $this->_storeManager->getStores(true);

        $data = array();

        foreach ($stores as $store) {

            switch ($arrayKey) {
                case 'id':
                    $key = $store->getId();
                    break;
                case 'lang':
                    $key = $this->scopeConfig->getValue(
                        'general/locale/code', ScopeInterface::SCOPE_STORE, $store->getId()
                    );
                    break;
                case 'currency':
                    $key = $this->scopeConfig->getValue(
                        'currency/options/default', ScopeInterface::SCOPE_STORE, $store->getId()
                    );
                    break;
                case 'website_id':
                    $key = $store->getWebsiteId();
                    break;
                default:
                    $key = $store->getId();
                    break;
            }

            if (!isset($data[$key])) {
                $data[$key] = array();
            }

            $data[$key][] = array(
                'store_id'   => $store->getId(),
                'code'       => $store->getCode(),
                'website_id' => $store->getWebsiteId(),
                'lang'       => $this->scopeConfig->getValue(
                    'general/locale/code', ScopeInterface::SCOPE_STORE, $store->getId()
                ),
                'currency'   => $this->scopeConfig->getValue(
                    'currency/options/default', ScopeInterface::SCOPE_STORE, $store->getId()
                ),
            );
        }

        return $data;
    }

}