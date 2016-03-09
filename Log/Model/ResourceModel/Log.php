<?php

namespace Pimgento\Log\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use \Magento\Framework\Model\ResourceModel\Db\Context;
use \Magento\Framework\Stdlib\DateTime\DateTime;
use \Magento\Framework\Model\AbstractModel;

class Log extends AbstractDb
{

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * Construct
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param string|null $resourcePrefix
     */
    public function __construct(Context $context, DateTime $date, $resourcePrefix = null)
    {
        parent::__construct($context, $resourcePrefix);
        $this->_date = $date;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('pimgento_import_log', 'log_id');
    }

    /**
     * Process post data before saving
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave(AbstractModel $object)
    {
        if ($object->isObjectNew()) {
            $object->setCreatedAt($this->_date->gmtDate());
        }

        return parent::_beforeSave($object);
    }

    /**
     * Add step to log
     *
     * @param $data
     */
    public function addStep($data)
    {
        $this->getConnection()->insert($this->getTable('pimgento_import_log_step'), $data);
    }

    /**
     * Retrieve steps
     *
     * @param int $logId
     * @return array
     */
    public function getSteps($logId)
    {
        $connection = $this->getConnection();

        return $connection->fetchAll(
            $connection->select()
                ->from($this->getTable('pimgento_import_log_step'))
                ->where('log_id = ?', $logId)
                ->order('step_id ASC')
        );
    }

}