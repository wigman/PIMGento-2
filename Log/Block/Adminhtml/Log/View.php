<?php

namespace Pimgento\Log\Block\Adminhtml\Log;

use \Magento\Backend\Block\Template;
use \Magento\Backend\Block\Template\Context;
use \Magento\Backend\Model\UrlFactory;
use \Pimgento\Log\Model\Log as LogModel;

class View extends Template
{

    /**
     * Model Url instance
     *
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $url;

    /**
     * @var LogModel
     */
    protected $_log;

    /**
     * @param \Pimgento\Log\Model\Log $log
     * @param \Magento\Backend\Model\UrlFactory $backendUrlFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(LogModel $log, UrlFactory $backendUrlFactory, Context $context, array $data = [])
    {
        parent::__construct($context, $data);

        $this->url = $backendUrlFactory->create();
        $this->_log = $log;
    }

    /**
     * Retrieve log
     *
     * @return LogModel
     */
    public function getLog()
    {
        return $this->_log->load($this->getLogId());
    }

    /**
     * Retrieve steps
     *
     * @return array
     */
    public function getSteps()
    {
        $steps = array();

        $log = $this->getLog();

        if ($log->hasData()) {
            $steps = $log->getSteps();
        }

        return $steps;
    }

    /**
     * Retrieve log id
     *
     * @return int
     */
    public function getLogId()
    {
        return $this->getData('log_id');
    }

    /**
     * Retrieve back URL
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->url->getUrl('pimgento/log');
    }

    /**
     * Set log id
     *
     * @param int $logId
     * @return $this
     */
    public function setLogId($logId)
    {
        $this->setData('log_id', $logId);

        return $this;
    }

}