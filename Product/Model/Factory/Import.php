<?php

namespace Pimgento\Product\Model\Factory;

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

        $this->_entities->createTmpTableFromFile($file, $this->getCode(), array('sku'));
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
        $this->_entities->matchEntity($this->getCode(), 'sku', 'catalog_product_entity', 'entity_id');
    }

    /**
     * Add required data
     */
    public function addRequiredData()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $connection->addColumn($tmpTable, '_type_id', 'VARCHAR(255) NOT NULL DEFAULT "simple"');
        $connection->addColumn($tmpTable, '_options_container', 'VARCHAR(255) NOT NULL DEFAULT "container2"');
        $connection->addColumn($tmpTable, '_tax_class_id', 'INT(11) NOT NULL DEFAULT 0'); // None
        $connection->addColumn($tmpTable, '_attribute_set_id', 'VARCHAR(255) NOT NULL DEFAULT "4"'); // Default
    }

    /**
     * Update product attribute set id
     */
    public function updateAttributeSetId()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        if (!$connection->tableColumnExists($tmpTable, 'family')) {
            $this->setMessage(
                __('Column family is missing')
            );
        } else {
            $families = $connection->select()
                ->from(false, array('_attribute_set_id' => 'c.entity_id'))
                ->joinLeft(
                    array('c' => $connection->getTableName('pimgento_entities')),
                    'p.family = c.code AND c.import = "family"',
                    array()
                );

            $connection->query(
                $connection->updateFromSelect($families, array('p' => $tmpTable))
            );
        }
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