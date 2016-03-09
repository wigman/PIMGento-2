<?php

namespace Pimgento\Entities\Model\ResourceModel\Entities;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Pimgento\Entities\Model\Entities', 'Pimgento\Entities\Model\ResourceModel\Entities');
    }

}