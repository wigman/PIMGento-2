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
        $connection = $this->_entities->getResource()->getConnection();

        $select = $connection->select()
            ->from(
                'eav_attribute',
                array(
                    'import'     => new Expr('"attribute"'),
                    'code'       => 'attribute_code',
                    'entity_id'  => 'attribute_id',
                )
            )
            ->where('entity_type_id = ?', 4);

        $connection->query(
            $connection->insertFromSelect($select,  'pimgento_entities', array('import', 'code', 'entity_id'), 2)
        );

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

            /* Insert base data (ignore if already exists) */
            $values = array(
                'attribute_id'   => $row['_entity_id'],
                'entity_type_id' => 4,
                'attribute_code' => $row['code'],
            );
            $connection->insertOnDuplicate('eav_attribute', $values, array_keys($values));

            $values = array(
                'attribute_id' => $row['_entity_id'],
            );
            $connection->insertOnDuplicate('catalog_eav_attribute', $values, array_keys($values));

            /* Retrieve default admin label */
            $stores = $this->_helperConfig->getStores('id');

            $frontendLabel = __('Unknown');
            if (isset($stores[0])) {
                $admin = reset($stores[0]);
                if (isset($row['label-' . $admin['lang']])) {
                    $frontendLabel = $row['label-' . $admin['lang']];
                }
            }

            /* Retrieve attribute scope */
            $global = 1; // Global
            if ($row['scopable'] == 1) {
                $global = 2; // Website
            }
            if ($row['localizable'] == 1) {
                $global = 0; // Store View
            }

            // @TODO remove field on update (avoid erase configuration)

            /* Attribute data */
            $data = Array(
                'entity_type_id' => 4,
                'attribute_code' => $row['code'],
                'backend_model' => $row['backend_model'],
                'backend_type' => $row['backend_type'],
                'backend_table' => null,
                'frontend_model' => null,
                'frontend_input' => $row['frontend_input'],
                'frontend_label' => $frontendLabel,
                'frontend_class' => null,
                'source_model' => $row['source_model'],
                'is_required' => 0,
                'is_user_defined' => 1,
                'default_value' => null,
                'is_unique' => $row['unique'],
                'note' => null,
                'is_global' => $global,
                'is_visible' => 1,
                'is_system' => 1,
                'input_filter' => null,
                'multiline_count' => 0,
                'validate_rules' => null,
                'data_model' => null,
                'sort_order' => 0,
                'is_used_in_grid' => 0,
                'is_visible_in_grid' => 0,
                'is_filterable_in_grid' => 0,
                'is_searchable_in_grid' => 0,
                'frontend_input_renderer' => null,
                'is_searchable' => 0,
                'is_filterable' => 0,
                'is_comparable' => 0,
                'is_visible_on_front' => 0,
                'is_wysiwyg_enabled' => 0,
                'is_html_allowed_on_front' => 0,
                'is_visible_in_advanced_search' => 0,
                'is_filterable_in_search' => 0,
                'used_in_product_listing' => 0,
                'used_for_sort_by' => 0,
                'apply_to' => null,
                'position' => 0,
                'is_used_for_promo_rules' => 0,
                'is_configurable' => 1,
            );

            $this->_eavSetup->updateAttribute(4, $row['_entity_id'], $data, null, 0);

            /* Add Attribute to group and family */
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

            /* Add store labels */
            $stores = $this->_helperConfig->getStores('lang');

            foreach ($stores as $lang => $data) {
                if (isset($row['label-' . $lang])) {
                    foreach ($data as $store) {
                        $values = array(
                            'attribute_id' => $row['_entity_id'],
                            'store_id' => $store['store_id'],
                            'value' => $row['label-' . $lang]
                        );
                        $connection->insertOnDuplicate('eav_attribute_label', $values, array_keys($values));
                    }
                }
            }

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