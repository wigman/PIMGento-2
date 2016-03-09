<?php

namespace Pimgento\Log\Model\ResourceModel\Log;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'log_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Pimgento\Log\Model\Log', 'Pimgento\Log\Model\ResourceModel\Log');
    }

}