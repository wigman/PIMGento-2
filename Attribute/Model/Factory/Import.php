<?php

namespace Pimgento\Attribute\Model\Factory;

use \Pimgento\Import\Model\Factory;
use \Pimgento\Entities\Model\Entities;
use \Pimgento\Import\Helper\Config as helperConfig;
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Pimgento\Attribute\Helper\Type as helperType;
use \Magento\Eav\Setup\EavSetup;
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
     * @var helperType
     */
    protected $_helperType;

    /**
     * @var EavSetup
     */
    protected $_eavSetup;

    /**
     * @param \Pimgento\Entities\Model\Entities $entities
     * @param \Pimgento\Import\Helper\Config $helperConfig
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Pimgento\Attribute\Helper\Type $helperType
     * @param \Magento\Eav\Setup\EavSetup $eavSetup
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param array $data
     */
    public function __construct(
        Entities $entities,
        helperConfig $helperConfig,
        ManagerInterface $eventManager,
        helperType $helperType,
        EavSetup $eavSetup,
        TypeListInterface $cacheTypeList,
        array $data = []
    )
    {
        parent::__construct($helperConfig, $eventManager, $data);
        $this->_helperType = $helperType;
        $this->_eavSetup = $eavSetup;
        $this->_entities = $entities;
        $this->_cacheTypeList = $cacheTypeList;
    }

    /**
     * Create temporary table
     */
    public function createTable()
    {
        $file = $this->getUploadDir() . '/' . $this->getFile();

        $this->_entities->createTmpTableFromFile($file, $this->getCode(), array('type', 'code', 'families'));
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
        $this->_entities->matchEntity($this->getCode(), 'code', 'eav_attribute', 'attribute_id');
    }

    /**
     * Match type with Magento logic
     */
    public function matchType()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $connection->addColumn($tmpTable, 'backend_type',   'VARCHAR(255) NULL');
        $connection->addColumn($tmpTable, 'frontend_input', 'VARCHAR(255) NULL');
        $connection->addColumn($tmpTable, 'backend_model',  'VARCHAR(255) NULL');
        $connection->addColumn($tmpTable, 'source_model',   'VARCHAR(255) NULL');

        $select = $connection->select()
            ->from(
                $tmpTable,
                array('_entity_id', 'type', 'backend_type', 'frontend_input', 'backend_model', 'source_model')
            );

        $data = $connection->fetchAssoc($select);

        foreach ($data as $id => $attribute) {
            $type = $this->_helperType->getType($attribute['type']);

            $values = array(
                'backend_type'   => $type['backend_type'],
                'frontend_input' => $type['frontend_input'],
                'backend_model'  => $type['backend_model'],
                'source_model'   => $type['source_model'],
            );

            $connection->update($tmpTable, $values, array('_entity_id = ?' => $id));
        }
    }

    /**
     * Match family code with Magento group id
     */
    public function matchFamily()
    {
        /* @TODO comma separated families */

        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $connection->addColumn($tmpTable, '_attribute_set_id', 'INT(11) NULL');

        $select = $connection->select()
            ->from(false, array())
            ->joinInner(
                array('e' => 'pimgento_entities'),
                'e.code = a.families AND e.import = "family"',
                array('_attribute_set_id' => 'e.entity_id')
            );

        $connection->query(
            $connection->updateFromSelect($select, array('a' => $tmpTable))
        );
    }

    /**
     * Add attributes if not exists
     */
    public function addAttributes()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $import = $connection->select()->from($tmpTable);
        $query  = $connection->query($import);

        while (($row = $query->fetch())) {

            /* @TODO Insert attribute with generated _entity_id */

            $global = 1; // Global
            if ($row['scopable'] == 1) {
                $global = 2; // Website
            }
            if ($row['localizable'] == 1) {
                $global = 0; // Store View
            }

            $this->_eavSetup->addAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                $row['code'],
                [
                    'type'                     => $row['backend_type'],
                    'backend'                  => $row['backend_model'],
                    'input'                    => $row['frontend_input'],
                    'source'                   => $row['source_model'],
                    'label'                    => __('Default'), // @TODO Default store label (label-xx_XX)
                    'required'                 => 0,
                    'user_defined'             => 1,
                    'unique'                   => $row['unique'],
                    'global'                   => $global,
                    'is_visible'               => 1,
                    'is_searchable'            => 0,
                    'is_comparable'            => 0,
                    'is_visible_on_front'      => 0,
                    'is_filterable'            => 0,
                    'is_filterable_in_search'  => 0,
                    'is_html_allowed_on_front' => 0,
                    'apply_to'                 => ''
                ]
            );

            if ($row['_attribute_set_id'] && $row['group']) {
                $attributeSetIds = explode(',', $row['_attribute_set_id']);

                foreach ($attributeSetIds as $attributeSetId) {
                    if (is_numeric($attributeSetId)) {
                        $this->_eavSetup->addAttributeGroup(4, $row['_attribute_set_id'], ucfirst($row['group']));
                        $this->_eavSetup->addAttributeToSet(
                            4,
                            $attributeSetId,
                            ucfirst($row['group']),
                            $row['code'],
                            null
                        );
                    }
                }
            }

            /* @TODO add store labels */
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