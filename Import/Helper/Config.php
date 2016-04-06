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
     * @param string|array $arrayKey
     * @return array
     */
    public function getStores($arrayKey = 'store_id')
    {
        $stores = $this->_storeManager->getStores(true);

        $data = array();

        if (!is_array($arrayKey)) {
            $arrayKey = array($arrayKey);
        }

        $channels = $this->scopeConfig->getValue('pimgento/general/website_mapping');

        if ($channels) {
            $channels = unserialize($channels);
            if (!is_array($channels)) {
                $channels = array();
            }
        } else {
            $channels = array();
        }

        foreach ($stores as $store) {

            $website = $this->_storeManager->getWebsite($store->getWebsiteId());

            $channel = $website->getCode();

            foreach ($channels as $match) {
                if ($match['website'] == $website->getCode()) {
                    $channel = $match['channel'];
                }
            }

            $combine = array();

            foreach ($arrayKey as $key) {
                switch ($key) {
                    case 'store_id':
                        $combine[] = $store->getId();
                        break;
                    case 'store_code':
                        $combine[] = $store->getCode();
                        break;
                    case 'website_id':
                        $combine[] = $website->getId();
                        break;
                    case 'website_code':
                        $combine[] = $website->getCode();
                        break;
                    case 'channel_code':
                        $combine[] = $channel;
                        break;
                    case 'lang':
                        $combine[] = $this->scopeConfig->getValue(
                            'general/locale/code', ScopeInterface::SCOPE_STORE, $store->getId()
                        );
                        break;
                    case 'currency':
                        $combine[] = $this->scopeConfig->getValue(
                            'currency/options/default', ScopeInterface::SCOPE_STORE, $store->getId()
                        );
                        break;
                    default:
                        $combine[] = $store->getId();
                        break;
                }

            }

            $key = join('-', $combine);

            if (!isset($data[$key])) {
                $data[$key] = array();
            }

            $data[$key][] = array(
                'store_id'     => $store->getId(),
                'store_code'   => $store->getCode(),
                'website_id'   => $website->getId(),
                'website_code' => $website->getCode(),
                'channel_code' => $channel,
                'lang'         => $this->scopeConfig->getValue(
                    'general/locale/code', ScopeInterface::SCOPE_STORE, $store->getId()
                ),
                'currency'     => $this->scopeConfig->getValue(
                    'currency/options/default', ScopeInterface::SCOPE_STORE, $store->getId()
                ),
            );
        }

        return $data;
    }

}