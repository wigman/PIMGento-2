<?php

namespace Pimgento\Family\Model\Factory;

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
     * @var \Magento\Eav\Model\Entity\Attribute\SetFactory
     */
    protected $_attributeSetFactory;

    /**
     * @var TypeListInterface
     */
    protected $_cacheTypeList;

    /**
     * @param \Pimgento\Entities\Model\Entities $entities
     * @param \Pimgento\Import\Helper\Config $helperConfig
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Eav\Model\Entity\Attribute\SetFactory
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param array $data
     */
    public function __construct(
        Entities $entities,
        helperConfig $helperConfig,
        ManagerInterface $eventManager,
        SetFactory $attributeSetFactory,
        TypeListInterface $cacheTypeList,
        array $data = []
    )
    {
        parent::__construct($helperConfig, $eventManager, $data);
        $this->_entities = $entities;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_attributeSetFactory = $attributeSetFactory;
    }

    /**
     * Create temporary table
     */
    public function createTable()
    {
        $file = $this->getUploadDir() . '/' . $this->getFile();

        $this->_entities->createTmpTableFromFile($file, $this->getCode(), 'code', array('code', 'label'));
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
     * Match code with entity
     */
    public function matchEntity()
    {
        $this->_entities->matchEntity($this->getCode(), 'code', 'eav_attribute_set', 'attribute_set_id');
    }

    /**
     * Insert Family
     */
    public function insertFamily()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $values = array(
            'attribute_set_id'   => '_entity_id',
            'entity_type_id'     => new Expr(4),
            'attribute_set_name' => new Expr('CONCAT("Akeneo", " ", `label`)'),
            'sort_order'         => new Expr(1),
        );

        $families = $connection->select()->from($tmpTable, $values);

        $connection->query(
            $connection->insertFromSelect(
                $families, 'eav_attribute_set', array_keys($values), 1
            )
        );
    }

    /**
     * Init group
     */
    public function initGroup()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $query = $connection->query(
            $connection->select()->from($tmpTable, array('_entity_id'))->where('_is_new = ?', 1)
        );

        $count = 0;
        while (($row = $query->fetch())) {
            $attributeSet = $this->_attributeSetFactory->create();
            $attributeSet->load($row['_entity_id']);

            if ($attributeSet->hasData()) {
                $attributeSet->initFromSkeleton(4)->save();
            }
            $count++;
        }

        $this->setMessage(
            __('%1 family(ies) initialized', $count)
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