<?php

namespace Pimgento\Variant\Model\Factory;

use \Pimgento\Import\Model\Factory;
use \Pimgento\Entities\Model\Entities;
use \Pimgento\Import\Helper\Config as helperConfig;
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Eav\Model\Entity\Attribute\SetFactory;
use \Zend_Db_Expr as Expr;
use \Exception;

class Import extends Factory
{

    /**
     * @var Entities
     */
    protected $_entities;

    /**
     * @var TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @param \Pimgento\Entities\Model\Entities $entities
     * @param \Pimgento\Import\Helper\Config $helperConfig
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param array $data
     */
    public function __construct(
        Entities $entities,
        helperConfig $helperConfig,
        ManagerInterface $eventManager,
        TypeListInterface $cacheTypeList,
        array $data = []
    )
    {
        parent::__construct($helperConfig, $eventManager, $data);
        $this->_entities = $entities;
        $this->_cacheTypeList = $cacheTypeList;
    }

    /**
     * Create temporary table
     */
    public function createTable()
    {
        $file = $this->getUploadDir() . '/' . $this->getFile();

        $this->_entities->createTmpTableFromFile($file, $this->getCode(), 'code', array('code', 'axis'));
    }

    /**
     * Insert data into temporary table
     */
    public function insertData()
    {
        $file = $this->getUploadDir() . '/' . $this->getFile();

        $count = $this->_entities->insertDataFromFile($file, $this->getCode());

        $this->setMessage(
            __('%1 line(s) found', $count)
        );
    }

    /**
     * Drop temporary table
     */
    public function dropTable()
    {
        $this->_entities->dropTable($this->getCode());
    }

    /**
     * Clean cache
     */
    public function cleanCache()
    {
        $types = array(
            \Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER,
            \Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER
        );

        foreach ($types as $type) {
            $this->_cacheTypeList->cleanType($type);
        }

        $this->setMessage(
            __('Cache cleaned for: %1', join(', ', $types))
        );
    }

}