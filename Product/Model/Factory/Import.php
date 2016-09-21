<?php

namespace Pimgento\Product\Model\Factory;

use \Pimgento\Import\Model\Factory;
use \Pimgento\Entities\Model\Entities;
use \Pimgento\Import\Helper\Config as helperConfig;
use \Pimgento\Import\Helper\UrlRewrite as urlRewriteHelper;
use \Pimgento\Product\Helper\Config as productHelper;
use \Pimgento\Product\Helper\Media as mediaHelper;
use \Magento\Catalog\Model\Product\Link as Link;
use \Magento\Framework\Event\ManagerInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Eav\Model\Entity\Attribute\SetFactory;
use \Magento\Framework\Module\Manager as moduleManager;
use \Magento\Framework\App\Config\ScopeConfigInterface as scopeConfig;
use \Magento\Framework\DB\Adapter\AdapterInterface;
use \Zend_Db_Expr as Expr;

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
     * @var \Pimgento\Product\Helper\Config
     */
    protected $_productHelper;

    /**
     * @var \Pimgento\Product\Helper\Media
     */
    protected $_mediaHelper;

    /**
     * list of allowed type_id that can be imported
     * @var string[]
     */
    protected $_allowedTypeId = ['simple', 'virtual'];

    /**
     * @var urlRewriteHelper
     */
    protected $_urlRewriteHelper;

    /**
     * PHP Constructor
     *
     * @param \Pimgento\Import\Helper\Config                     $helperConfig
     * @param \Magento\Framework\Event\ManagerInterface          $eventManager
     * @param \Magento\Framework\Module\Manager                  $moduleManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Pimgento\Entities\Model\Entities                  $entities
     * @param \Magento\Framework\App\Cache\TypeListInterface     $cacheTypeList
     * @param \Magento\Eav\Model\Entity\Attribute\SetFactory     $attributeSetFactory
     * @param \Pimgento\Product\Helper\Config                    $productHelper
     * @param \Pimgento\Product\Helper\Media                     $mediaHelper
     * @param urlRewriteHelper                                   $urlRewriteHelper
     * @param array                                              $data
     */
    public function __construct(
        helperConfig $helperConfig,
        ManagerInterface $eventManager,
        moduleManager $moduleManager,
        scopeConfig $scopeConfig,
        Entities $entities,
        TypeListInterface $cacheTypeList,
        SetFactory $attributeSetFactory,
        productHelper $productHelper,
        mediaHelper $mediaHelper,
        urlRewriteHelper $urlRewriteHelper,
        array $data = []
    ) {
        parent::__construct($helperConfig, $eventManager, $moduleManager, $scopeConfig, $data);

        $this->_entities = $entities;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_attributeSetFactory = $attributeSetFactory;
        $this->_productHelper = $productHelper;
        $this->_mediaHelper = $mediaHelper;
        $this->_urlRewriteHelper = $urlRewriteHelper;
    }

    /**
     * Create temporary table
     */
    public function createTable()
    {
        $file = $this->getFileFullPath();

        if (!is_file($file)) {
            $this->setContinue(false);
            $this->setStatus(false);
            $this->setMessage($this->getFileNotFoundErrorMessage());

        } else {
            $this->_entities->createTmpTableFromFile($file, $this->getCode(), array('sku'));
        }
    }

    /**
     * Insert data into temporary table
     */
    public function insertData()
    {
        $file = $this->getFileFullPath();

        $count = $this->_entities->insertDataFromFile($file, $this->getCode());

        $this->setMessage(
            __('%1 line(s) found', $count)
        );
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
        $connection->addColumn($tmpTable, '_attribute_set_id', 'INT(11) NOT NULL DEFAULT "4"'); // Default
        $connection->addColumn($tmpTable, '_visibility', 'INT(11) NOT NULL DEFAULT "4"'); // catalog, search
        $connection->addColumn($tmpTable, '_status', 'INT(11) NOT NULL DEFAULT "2"'); // Disabled

        if (!$connection->tableColumnExists($tmpTable, 'url_key')) {
            $connection->addColumn($tmpTable, 'url_key', 'varchar(255) NOT NULL DEFAULT ""');
            $connection->update($tmpTable, array('url_key' => new Expr('LOWER(`sku`)')));
        }

        if ($connection->tableColumnExists($tmpTable, 'enabled')) {
            $connection->update($tmpTable, array('_status' => new Expr('IF(`enabled` <> 1, 2, 1)')));
        }

        if ($connection->tableColumnExists($tmpTable, 'groups')) {
            $connection->update($tmpTable, array('_visibility' => new Expr('IF(`groups` <> "", 1, 4)')));
        }

        if ($connection->tableColumnExists($tmpTable, 'type_id')) {
            $types = $connection->quote($this->_allowedTypeId);
            $connection->update(
                $tmpTable,
                array(
                    '_type_id' => new Expr("IF(`type_id` IN ($types), `type_id`, 'simple')")
                )
            );
        }

        $matches = $this->_scopeConfig->getValue('pimgento/product/attribute_mapping');

        if ($matches) {
            $matches = unserialize($matches);
            if (is_array($matches)) {
                $stores = array_merge(
                    $this->_helperConfig->getStores(array('lang')), // en_US
                    $this->_helperConfig->getStores(array('lang', 'channel_code')), // en_US-channel
                    $this->_helperConfig->getStores(array('channel_code')), // channel
                    $this->_helperConfig->getStores(array('currency')), // USD
                    $this->_helperConfig->getStores(array('channel_code', 'currency')), // channel-USD
                    $this->_helperConfig->getStores(array('lang', 'channel_code', 'currency')) // en_US-channel-USD
                );
                foreach ($matches as $match) {
                    $pimAttr = $match['pim_attribute'];
                    $magentoAttr = $match['magento_attribute'];
                    $this->_entities->copyColumn($tmpTable, $pimAttr, $magentoAttr);

                    foreach ($stores as $local => $affected) {
                        $this->_entities->copyColumn($tmpTable, $pimAttr . '-' . $local, $magentoAttr . '-' . $local);
                    }
                }

            }
        }
    }

    /**
     * Create Configurable products
     */
    public function createConfigurable()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        if (!$this->moduleIsEnabled('Pimgento_Variant')) {
            $this->setStatus(false);
            $this->setMessage(
                __('Module Pimgento_Variant is not enabled')
            );
        } else if (!$connection->tableColumnExists($tmpTable, 'groups')) {
            $this->setStatus(false);
            $this->setMessage(
                __('Column groups not found')
            );
        } else {
            $connection->addColumn($tmpTable, '_children', 'TEXT NULL');
            $connection->addColumn($tmpTable, '_axis', 'VARCHAR(255) NULL');

            $data = array(
                'sku' => 'e.groups',
                'url_key' => 'e.groups',
                '_children' => new Expr('GROUP_CONCAT(e.sku SEPARATOR ",")'),
                '_type_id' => new Expr('"configurable"'),
                '_options_container' => new Expr('"container1"'),
                '_status' => 'e._status',
                '_axis' => 'v.axis'
            );

            if ($connection->tableColumnExists($tmpTable, 'family')) {
                $data['family'] = 'e.family';
            }

            if ($connection->tableColumnExists($tmpTable, 'categories')) {
                $data['categories'] = 'e.categories';
            }

            $additional = $this->_scopeConfig->getValue('pimgento/product/configurable_attributes');

            if ($additional) {
                $additional = unserialize($additional);
                if (is_array($additional)) {

                    $stores = array_merge(
                        $this->_helperConfig->getStores(array('lang')), // en_US
                        $this->_helperConfig->getStores(array('lang', 'channel_code')), // en_US-channel
                        $this->_helperConfig->getStores(array('channel_code')), // channel
                        $this->_helperConfig->getStores(array('currency')), // USD
                        $this->_helperConfig->getStores(array('channel_code', 'currency')), // channel-USD
                        $this->_helperConfig->getStores(array('lang', 'channel_code', 'currency')) // en_US-channel-USD
                    );

                    foreach ($additional as $attribute) {
                        $attr  = $attribute['attribute'];
                        $value = $attribute['value'];

                        $columns = array(trim($attr));
                        foreach ($stores as $local => $affected) {
                            $columns[] = trim($attr) . '-' . $local;
                        }

                        foreach ($columns as $column) {

                            if ($column == 'enabled') {
                                if ($connection->tableColumnExists($tmpTable, 'enabled')) {
                                    $column = '_status';
                                    if ($value == "0") {
                                        $value = "2";
                                    }
                                }
                            }

                            if ($connection->tableColumnExists($tmpTable, $column)) {
                                if (!strlen($value)) {
                                    if ($connection->tableColumnExists($connection->getTableName('pimgento_variant'), $column)) {
                                        $data[$column] = 'v.' . $column;
                                    } else {
                                        $data[$column] = 'e.' . $column;
                                    }
                                } else {
                                    $data[$column] = new Expr('"' . $value . '"');
                                }
                            }
                        }
                    }
                }

            }

            $configurable = $connection->select()
                ->from(array('e' => $tmpTable), $data)
                ->joinInner(
                    array('v' => $connection->getTableName('pimgento_variant')),
                    'e.groups = v.code',
                    array()
                )
                ->where('groups <> ""')
                ->group('e.groups');

            $connection->query(
                $connection->insertFromSelect($configurable, $tmpTable, array_keys($data))
            );
        }
    }

    /**
     * Match code with entity
     */
    public function matchEntity()
    {
        $this->_entities->matchEntity($this->getCode(), 'sku', 'catalog_product_entity', 'entity_id');
    }

    /**
     * Update product attribute set id
     */
    public function updateAttributeSetId()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        if (!$connection->tableColumnExists($tmpTable, 'family')) {
            $this->setStatus(false);
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
     * Replace option code by id
     */
    public function updateOption()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $columns = array_keys($connection->describeTable($tmpTable));

        $except = array(
            '_entity_id',
            '_is_new',
            '_status',
            '_type_id',
            '_options_container',
            '_tax_class_id',
            '_attribute_set_id',
            '_visibility',
            '_children',
            '_axis',
            'sku',
            'categories',
            'family',
            'groups',
            'url_key',
            'enabled',
        );

        foreach ($columns as $column) {

            if (in_array($column, $except)) {
                continue;
            }

            if (preg_match('/-unit/', $column)) {
                continue;
            }

            $columnPrefix = explode('-', $column);
            $columnPrefix = reset($columnPrefix);

            if ($connection->tableColumnExists($tmpTable, $column)) {
                $select = $connection->select()
                    ->from(
                        array('p' => $tmpTable),
                        array(
                            'sku'       => 'p.sku',
                            'entity_id' => 'p._entity_id'
                        )
                    )
                    ->distinct()
                    ->joinInner(
                        array(
                            'c' => $connection->getTableName('pimgento_entities')
                        ),
                        'FIND_IN_SET(
                            REPLACE(`c`.`code`, "' . $columnPrefix . '_", ""),
                            `p`.`' . $column . '`
                        )
                        AND `c`.`import` = "option"',
                        array(
                            $column => new Expr('GROUP_CONCAT(`c`.`entity_id` SEPARATOR ",")')
                        )
                    )
                    ->group('p.sku');

                $connection->query(
                    $connection->insertFromSelect($select, $tmpTable, array('sku', '_entity_id', $column), 1)
                );
            }
        }
    }

    /**
     * Create product entities
     */
    public function createEntities()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $values = array(
            'entity_id'        => '_entity_id',
            'attribute_set_id' => '_attribute_set_id',
            'type_id'          => '_type_id',
            'sku'              => 'sku',
            'has_options'      => new Expr(0),
            'required_options' => new Expr(0),
            'updated_at'       => new Expr('now()'),
        );

        $parents = $connection->select()->from($tmpTable, $values);
        $connection->query(
            $connection->insertFromSelect(
                $parents, $connection->getTableName('catalog_product_entity'), array_keys($values), 1
            )
        );

        $values = array(
            'created_at' => new Expr('now()')
        );
        $connection->update($connection->getTableName('catalog_product_entity'), $values, 'created_at IS NULL');
    }

    /**
     * Set values to attributes
     */
    public function setValues()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $stores = array_merge(
            $this->_helperConfig->getStores(array('lang')), // en_US
            $this->_helperConfig->getStores(array('lang', 'channel_code')), // en_US-channel
            $this->_helperConfig->getStores(array('channel_code')), // channel
            $this->_helperConfig->getStores(array('currency')), // USD
            $this->_helperConfig->getStores(array('channel_code', 'currency')), // channel-USD
            $this->_helperConfig->getStores(array('lang', 'channel_code', 'currency')) // en_US-channel-USD
        );

        $columns = array_keys($connection->describeTable($tmpTable));

        $except = array(
            '_entity_id',
            '_is_new',
            '_status',
            '_type_id',
            '_options_container',
            '_tax_class_id',
            '_attribute_set_id',
            '_visibility',
            '_children',
            '_axis',
            'sku',
            'categories',
            'family',
            'groups',
            'enabled',
        );

        $values = array(
            0 => array(
                'options_container' => '_options_container',
                'tax_class_id'      => '_tax_class_id',
                'visibility'        => '_visibility',
            )
        );

        if ($connection->tableColumnExists($tmpTable, 'enabled')) {
            $values[0]['status'] = '_status';
        }

        $taxClasses = $this->_productHelper->getProductTaxClasses();
        if (count($taxClasses)) {
            foreach ($taxClasses as $storeId => $taxClassId) {
                $values[$storeId]['tax_class_id'] = new Expr($taxClassId);
            }
        }

        foreach ($columns as $column) {
            if (in_array($column, $except)) {
                continue;
            }

            if (preg_match('/-unit/', $column)) {
                continue;
            }

            $columnPrefix = explode('-', $column);
            $columnPrefix = reset($columnPrefix);

            $values[0][$columnPrefix] = $column;

            foreach ($stores as $suffix => $affected) {
                if (preg_match('/' . $suffix . '$/', $column)) {
                    foreach ($affected as $store) {
                        if (!isset($values[$store['store_id']])) {
                            $values[$store['store_id']] = array();
                        }
                        $values[$store['store_id']][$columnPrefix] = $column;
                    }
                }
            }

        }

        foreach($values as $storeId => $data) {
            $this->_entities->setValues(
                $this->getCode(), $connection->getTableName('catalog_product_entity'), $data, 4, $storeId, 1
            );
        }
    }

    /**
     * Link configurable with children
     */
    public function linkConfigurable()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        if (!$this->moduleIsEnabled('Pimgento_Variant')) {
            $this->setStatus(false);
            $this->setMessage(
                __('Module Pimgento_Variant is not enabled')
            );
        } else if (!$connection->tableColumnExists($tmpTable, 'groups')) {
            $this->setStatus(false);
            $this->setMessage(
                __('Column groups not found')
            );
        } else {
            $stores = $this->_helperConfig->getStores('store_id');

            $query = $connection->query(
                $connection->select()
                    ->from(
                        $tmpTable,
                        array(
                            '_entity_id',
                            '_axis',
                            '_children'
                        )
                    )
                    ->where('_type_id = ?', 'configurable')
                    ->where('_axis IS NOT NULL')
                    ->where('_children IS NOT NULL')
            );

            while (($row = $query->fetch())) {
                $attributes = explode(',', $row['_axis']);

                $position = 0;

                foreach ($attributes as $id) {
                    if (!is_numeric($id)) {
                        continue;
                    }

                    $hasOptions = $connection->fetchOne(
                        $connection->select()
                            ->from($connection->getTableName('eav_attribute_option'), array(new Expr(1)))
                            ->where('attribute_id = ?', $id)
                            ->limit(1)
                    );

                    if (!$hasOptions) {
                        continue;
                    }

                    /* catalog_product_super_attribute */
                    $values = array(
                        'product_id' => $row['_entity_id'],
                        'attribute_id' => $id,
                        'position' => $position++,
                    );
                    $connection->insertOnDuplicate(
                        $connection->getTableName('catalog_product_super_attribute'), $values, array()
                    );

                    /* catalog_product_super_attribute_label */
                    $superAttributeId = $connection->fetchOne(
                        $connection->select()
                            ->from($connection->getTableName('catalog_product_super_attribute'))
                            ->where('attribute_id = ?', $id)
                            ->where('product_id = ?', $row['_entity_id'])
                            ->limit(1)
                    );

                    foreach ($stores as $storeId => $affected) {
                        $values = array(
                            'product_super_attribute_id' => $superAttributeId,
                            'store_id' => $storeId,
                            'use_default' => 0,
                            'value' => ''
                        );
                        $connection->insertOnDuplicate(
                            $connection->getTableName('catalog_product_super_attribute_label'), $values, array()
                        );
                    }

                    $children = explode(',', $row['_children']);

                    /* catalog_product_relation & catalog_product_super_link */
                    foreach ($children as $child) {
                        $childId = $connection->fetchOne(
                            $connection->select()
                                ->from(
                                    $connection->getTableName('catalog_product_entity'),
                                    array(
                                        'entity_id'
                                    )
                                )
                                ->where('sku = ?', $child)
                                ->limit(1)
                        );

                        if ($childId) {
                            /* catalog_product_relation */
                            $values = array(
                                'parent_id' => $row['_entity_id'],
                                'child_id' => $childId,
                            );
                            $connection->insertOnDuplicate(
                                $connection->getTableName('catalog_product_relation'), $values, array()
                            );

                            /* catalog_product_super_link */
                            $values = array(
                                'product_id' => $childId,
                                'parent_id' => $row['_entity_id'],
                            );
                            $connection->insertOnDuplicate(
                                $connection->getTableName('catalog_product_super_link'), $values, array()
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Set website
     */
    public function setWebsites()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $websites = $this->_helperConfig->getStores('website_id');

        foreach ($websites as $websiteId => $affected) {
            if ($websiteId == 0) {
                continue;
            }

            $select = $connection->select()
                ->from(
                    $tmpTable,
                    array(
                        'product_id' => '_entity_id',
                        'website_id' => new Expr($websiteId)
                    )
                );
            $connection->query(
                $connection->insertFromSelect(
                    $select, $connection->getTableName('catalog_product_website'), array('product_id', 'website_id'), 1
                )
            );
        }
    }

    /**
     * Set categories
     */
    public function setCategories()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        if (!$connection->tableColumnExists($tmpTable, 'categories')) {
            $this->setStatus(false);
            $this->setMessage(
                __('Column categories not found')
            );
        } else {

            $select = $connection->select()
                ->from(
                    array(
                        'c' => $connection->getTableName('pimgento_entities')
                    ),
                    array()
                )
                ->joinInner(
                    array('p' => $tmpTable),
                    'FIND_IN_SET(`c`.`code`, `p`.`categories`) AND `c`.`import` = "category"',
                    array(
                        'category_id' => 'c.entity_id',
                        'product_id'  => 'p._entity_id',
                        'position'    => new Expr(1)
                    )
                )
                ->joinInner(
                    array('e' => $connection->getTableName('catalog_category_entity')),
                    'c.entity_id = e.entity_id',
                    array()
                );

            $connection->query(
                $connection->insertFromSelect(
                    $select,
                    $connection->getTableName('catalog_category_product'),
                    array('category_id', 'product_id', 'position'),
                    1
                )
            );

        }
    }

    /**
     * Init Stock
     */
    public function initStock()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $websiteId = $this->_helperConfig->getDefaultScopeId();

        $values = array(
            'product_id' => '_entity_id',
            'stock_id' => new Expr(1),
            'qty' => new Expr(0),
            'is_in_stock' => new Expr(0),
            'low_stock_date' => new Expr('NULL'),
            'stock_status_changed_auto' => new Expr(0),
            'website_id' => new Expr($websiteId),
        );

        $select = $connection->select()->from($tmpTable, $values);

        $connection->query(
            $connection->insertFromSelect(
                $select,
                $connection->getTableName('cataloginventory_stock_item'),
                array_keys($values),
                AdapterInterface::INSERT_IGNORE
            )
        );
    }

    /**
     * Set Url Rewrite
     */
    public function setUrlRewrite()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $stores = $this->_helperConfig->getStores('lang');
        $this->_urlRewriteHelper->createUrlTmpTable();

        foreach ($stores as $local => $affected) {

            $column = 'url_key';

            if ($connection->tableColumnExists($tmpTable, 'url_key-' . $local)) {
                $column = 'url_key-' . $local;
            }

            if ($connection->tableColumnExists($tmpTable, $column)) {
                foreach ($affected as $store) {

                    if ($store['store_id'] == 0) {
                        continue;
                    }

                    $this->_urlRewriteHelper->rewriteUrls($this->getCode(), $store['store_id'], $column);
                }
            }
        }

        $this->_urlRewriteHelper->dropUrlRewriteTmpTable();
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

    /**
     * Set related, up-sell and cross-sell
     *
     * @return void
     */
    public function setRelated()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $related = array();

        // Product relations
        if ($connection->tableColumnExists($tmpTable, 'RELATED-products')) {
            $related[] = array(
                'type_id' => Link::LINK_TYPE_RELATED,
                'column'  => 'RELATED-products',
            );
        }
        if ($connection->tableColumnExists($tmpTable, 'UPSELL-products')) {
            $related[] = array(
                'type_id' => Link::LINK_TYPE_UPSELL,
                'column'  => 'UPSELL-products',
            );
        }
        if ($connection->tableColumnExists($tmpTable, 'X_SELL-products')) {
            $related[] = array(
                'type_id' => Link::LINK_TYPE_CROSSSELL,
                'column'  => 'X_SELL-products',
            );
        }
        if ($connection->tableColumnExists($tmpTable, 'CROSSSELL-products')) {
            $related[] = array(
                'type_id' => Link::LINK_TYPE_CROSSSELL,
                'column'  => 'CROSSSELL-products',
            );
        }

        // Product group relations
        if ($connection->tableColumnExists($tmpTable, 'RELATED-groups')) {
            $related[] = array(
                'type_id' => Link::LINK_TYPE_RELATED,
                'column'  => 'RELATED-groups',
            );
        }
        if ($connection->tableColumnExists($tmpTable, 'UPSELL-groups')) {
            $related[] = array(
                'type_id' => Link::LINK_TYPE_UPSELL,
                'column'  => 'UPSELL-groups',
            );
        }
        if ($connection->tableColumnExists($tmpTable, 'X_SELL-groups')) {
            $related[] = array(
                'type_id' => Link::LINK_TYPE_CROSSSELL,
                'column'  => 'X_SELL-groups',
            );
        }
        if ($connection->tableColumnExists($tmpTable, 'CROSSSELL-groups')) {
            $related[] = array(
                'type_id' => Link::LINK_TYPE_CROSSSELL,
                'column'  => 'CROSSSELL-groups',
            );
        }

        $this->relatedCreateTmpTables();
        foreach ($related as $type) {
            $this->relatedImportColumn($type);
        }
        $this->relatedDropTmpTables();
    }

    /**
     * Create the temporary tables needed bby related product import
     *
     * @return void
     */
    protected function relatedCreateTmpTables()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tableNumber = $connection->getTableName('tmp_pimgento_numbers');
        $tableRelated = $connection->getTableName('tmp_pimgento_related');

        $connection->dropTable($tableNumber);
        $connection->dropTable($tableRelated);

        $table = $connection->newTable($tableNumber);
        $table->addColumn('n', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 11, []);
        $connection->createTable($table);

        $table = $connection->newTable($tableRelated);
        $table->addColumn('parent_sku', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, []);
        $table->addColumn('parent_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 11, []);
        $table->addColumn('child_sku', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, []);
        $table->addColumn('child_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 11, []);
        $connection->createTable($table);

        $values = [];
        for ($k=0; $k<10000; $k++) {
            $values[] = ['n' => $k+1];
        }
        $connection->insertMultiple($tableNumber, $values);
    }

    /**
     * Drop the temporary tables needed bby related product import
     *
     * @return void
     */
    protected function relatedDropTmpTables()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tableNumber = $connection->getTableName('tmp_pimgento_numbers');
        $tableRelated = $connection->getTableName('tmp_pimgento_related');

        $connection->dropTable($tableNumber);
        $connection->dropTable($tableRelated);
    }

    /**
     * Manage one related column
     *
     * @param array $type
     *
     * @return void
     */
    protected function relatedImportColumn($type)
    {
        $connection = $this->_entities->getResource()->getConnection();

        $tmpTable     = $this->_entities->getTableName($this->getCode());
        $tableNumber  = $connection->getTableName('tmp_pimgento_numbers');
        $tableRelated = $connection->getTableName('tmp_pimgento_related');
        $tableProduct = $connection->getTableName('catalog_product_entity');

        $column = $type['column'];

        $ids = $this->getMaxAndMinIds($column);
        if ($ids['max'] < 1) {
            return false;
        }

        // we must do this step by step because of a mysql usage limitation
        $step = 1000;
        $min = $ids['min'] + $step;
        $max = $ids['max'] + $step;
        for ($limit = $min; $limit <= $max; $limit += $step) {
            // transform one row for multiple links => one row for one link
            $select = $connection->select()
                ->from(
                    ['t' => $tmpTable],
                    ['parent_sku' => 't.sku']
                )->joinInner(
                    ['n' => $tableNumber],
                    "
                        `t`.`$column` <> ''
                        AND `t`.`_entity_id` <= $limit
                        AND (char_length(t.`$column`) - char_length(replace(t.`$column`, ',', ''))) >= n.n-1 
                    ",
                    ['child_sku' => new Expr("substring_index(substring_index(t.`$column`, ',', n.n), ',', -1)")]
                );

            $query = $connection->insertFromSelect(
                $select,
                $tableRelated,
                ['parent_sku', 'child_sku'],
                AdapterInterface::INSERT_ON_DUPLICATE
            );

            $connection->query($query);
            $connection->update(
                $tmpTable,
                [$column => ''],
                ["_entity_id <= $limit" ]
            );
        }

        // get the product ids for parents
        // @todo use Zend methods for mass update
        $query = "
            UPDATE $tableRelated, $tableProduct
            SET $tableRelated.parent_id = $tableProduct.entity_id
            WHERE $tableRelated.parent_sku = $tableProduct.sku
        ";
        $connection->query($query);

        // get the product ids for links
        // @todo use Zend methods for mass update
        $query = "
            UPDATE $tableRelated, $tableProduct
            SET $tableRelated.child_id = $tableProduct.entity_id
            WHERE $tableRelated.child_sku = $tableProduct.sku
        ";
        $connection->query($query);

        // delete bad links
        $connection->delete($tableRelated, 'child_id IS NULL or parent_id IS NULL');

        // save the links
        $select = $connection->select()
            ->from(
                ['l' => $tableRelated],
                [
                    'product_id'        => 'l.parent_id',
                    'linked_product_id' => 'l.child_id',
                    'link_type_id'      => new Expr($type['type_id'])
                ]
            );
        $query = $connection->insertFromSelect(
            $select,
            $connection->getTableName('catalog_product_link'),
            ['product_id', 'linked_product_id', 'link_type_id'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $connection->query($query);

        $connection->delete($tableRelated);
    }

    /**
     * Get the min and the max of the product entity_id where a column is not empty
     *
     * @param string $column
     *
     * @return int[]
     */
    protected function getMaxAndMinIds($column)
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable     = $this->_entities->getTableName($this->getCode());

        $select = $connection->select()
            ->from(
                ['t' => $tmpTable],
                [
                    'min_id' => new Expr('MIN(_entity_id)'),
                    'max_id' => new Expr('MAX(_entity_id)'),
                ]
            )
            ->where("`$column` <> ''");

        $ids = $connection->fetchAll($select);
        $ids = [
            'min' => (int) $ids[0]['min_id'],
            'max' => (int) $ids[0]['max_id'],
        ];

        return  $ids;
    }

    /**
     * Import the medias
     *
     * @return boolean
     */
    public function importMedia()
    {
        $this->_mediaHelper->initHelper(dirname($this->getFileFullPath()));

        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable   = $this->_entities->getTableName($this->getCode());

        $tableColumns     = array_keys($connection->describeTable($tmpTable));
        $fields = $this->_mediaHelper->getFields();

        $this->mediaCreateTmpTables();
        foreach ($fields as $field) {
            foreach ($field['columns'] as $column) {
                if (in_array($column, $tableColumns)) {
                    $this->mediaPrepareValues($column, $field['attribute_id']);
                }
            }
        }
        $this->mediaCleanValues();
        $this->mediaRemoveUnknownFiles();
        $this->mediaCopyFiles();
        $this->mediaUpdateDataBase();
        $this->mediaDropTmpTables();
    }

    /**
     * Clean the media folder
     *
     * @return boolean
     */
    public function cleanMediaFolder()
    {
        if ($this->_mediaHelper->isCleanFiles()) {
            $this->_mediaHelper->cleanFiles();
        }

        return true;
    }

    /**
     * Create the temporary tables needed bby related product import
     *
     * @return void
     */
    protected function mediaCreateTmpTables()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tableMedia = $connection->getTableName('tmp_pimgento_media');

        $connection->dropTable($tableMedia);


        $table = $connection->newTable($tableMedia);
        $table->addColumn('sku', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, []);
        $table->addColumn('entity_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 10, ['unsigned' => true]);
        $table->addColumn('attribute_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5, ['unsigned' => true]);
        $table->addColumn('store_id', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, 5, ['unsigned' => true]);
        $table->addColumn('value_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 10, ['unsigned' => true]);
        $table->addColumn('record_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, 10, ['unsigned' => true]);
        $table->addColumn('media_original', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, []);
        $table->addColumn('media_cleaned', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, []);
        $table->addColumn('media_value', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, []);
        $table->addIndex(
            $tableMedia.'_entity_id',
            ['entity_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        );
        $table->addIndex(
            $tableMedia.'_attribute_id',
            ['attribute_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        );
        $table->addIndex(
            $tableMedia.'_store_id',
            ['store_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        );
        $table->addIndex(
            $tableMedia.'_value_id',
            ['value_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        );
        $table->addIndex(
            $tableMedia.'_record_id',
            ['record_id'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        );
        $table->addIndex(
            $tableMedia.'_media_value',
            ['media_value'],
            ['type' => AdapterInterface::INDEX_TYPE_INDEX]
        );
        $connection->createTable($table);
    }

    /**
     * Drop the temporary tables needed by media product import
     *
     * @return void
     */
    protected function mediaDropTmpTables()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tableMedia = $connection->getTableName('tmp_pimgento_media');

        $connection->dropTable($tableMedia);
    }


    /**
     * Import one column of media import - Database values
     *
     * @param string $column
     * @param int    $attributeId
     *
     * @return boolean
     */
    protected function mediaPrepareValues($column, $attributeId)
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable   = $this->_entities->getTableName($this->getCode());
        $tableMedia = $connection->getTableName('tmp_pimgento_media');

        if (is_null($attributeId)) {
            $attributeId = 'NULL';
        }

        $select = $connection->select()
            ->from(
                ['t' => $tmpTable],
                [
                    'sku'            => 't.sku',
                    'entity_id'      => 't._entity_id',
                    'attribute_id'   => new Expr($attributeId),
                    'store_id'       => new Expr('0'),
                    'media_original' => "t.$column",
                ]
            )->where("`t`.`$column` <> ''");

        $query = $connection->insertFromSelect(
            $select,
            $tableMedia,
            ['sku', 'entity_id', 'attribute_id', 'store_id', 'media_original'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );

        $connection->query($query);

        return true;
    }

    /**
     * Clean the values to import for medias :
     * - Remove files/ and /files/ from the beginning of the value
     * - remove bad chars
     * - change folder separator into -
     *
     * @return void
     */
    protected function mediaCleanValues()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tableMedia = $connection->getTableName('tmp_pimgento_media');

        $expr = "REPLACE(REPLACE(REPLACE(LOWER(`media_original`), '/', '-'), '-files-', ''), 'files-', '')";
        $connection->update($tableMedia, ['media_cleaned' => new Expr($expr)]);

        $expr = "TRIM(CONCAT_WS('-', LOWER(`sku`), `media_cleaned`))";
        $connection->update($tableMedia, ['media_cleaned' => new Expr($expr)]);

        $expr = "LEFT(REPLACE(`media_cleaned`, '-', ''), 4)";
        $connection->update($tableMedia, ['media_value' => new Expr($expr)]);

        $expr = "CONCAT_WS('/', '', SUBSTR(`media_value`, 1, 1), SUBSTR(`media_value`, 2, 1), `media_cleaned`)";
        $connection->update($tableMedia, ['media_value' => new Expr($expr)]);

        $connection->addColumn(
            $tableMedia,
            'id',
            [
                'type'     => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length'   => 10,
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary'  => true,
                'comment'  => 'primary key',
            ]
        );
    }

    /**
     * Get the max id to treat for media import
     *
     * @return int
     */
    protected function mediaGetMaxId($tableName, $columnId)
    {
        $connection = $this->_entities->getResource()->getConnection();

        $select = $connection->select()
            ->from(
                ['t' => $tableName],
                ['max_id'         => new Expr('MAX('.$columnId.')')]
            );

        $values = $connection->fetchAll($select);

        return (int) $values[0]['max_id'];

    }

    /**
     * Remove the lines that corresponds to unknown files on disc
     *
     * @return void
     */
    protected function mediaRemoveUnknownFiles()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tableMedia = $connection->getTableName('tmp_pimgento_media');

        $maxId = $this->mediaGetMaxId($tableMedia, 'id');
        if ($maxId<1) {
            return;
        }

        $step = 5000;
        for ($k=1; $k<=$maxId; $k+= $step) {
            $min = $k;
            $max = $k + $step;
            $select = $connection->select()
            ->from(
                ['t' => $tableMedia],
                [
                    'id'    => 'id',
                    'file' => 'media_original',
                ]
            )->where("id >= $min AND id < $max");
            $medias = $connection->fetchAll($select);

            $idsToDelete = [];
            foreach ($medias as $media) {
                $file = $this->_mediaHelper->getImportFolder().$media['file'];
                if (!is_file($file)) {
                    $idsToDelete[] = (int) $media['id'];
                }
            }

            if (count($idsToDelete)) {
                $connection->delete($tableMedia, $connection->quoteInto('id IN (?)', $idsToDelete));
            }
        }
    }

    /**
     * Copy the medias to the media folder of magento
     *
     * @return void
     */
    protected function mediaCopyFiles()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tableMedia = $connection->getTableName('tmp_pimgento_media');

        $maxId = $this->mediaGetMaxId($tableMedia, 'id');
        if ($maxId<1) {
            return;
        }

        $step = 5000;
        for ($k=1; $k<=$maxId; $k+= $step) {
            $min = $k;
            $max = $k + $step;
            $select = $connection->select()
            ->from(
                ['t' => $tableMedia],
                [
                    'from' => 'media_original',
                    'to'   => 'media_value',
                ]
            )->where(
                "id >= $min AND id < $max"
            );
            $medias = $connection->fetchAll($select);
            foreach ($medias as $media) {
                $from = $this->_mediaHelper->getImportFolder().$media['from'];
                $to = $this->_mediaHelper->getMediaAbsolutePath().$media['to'];

                // if it does not exist, we pass
                if (!is_file($from)) {
                    continue;
                }

                // create the final folder
                if (!is_dir(dirname($to))) {
                    mkdir(dirname($to), 0775, true);
                }

                // remove the file if it exist
                if (is_file($to)) {
                    unlink($to);
                }

                copy($from, $to);
            }
        }
    }

    /**
     * Update the media database
     *
     * @return void
     */
    protected function mediaUpdateDataBase()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tableMedia = $connection->getTableName('tmp_pimgento_media');
        $step = 5000;

        // add the media in the varchar product table
        $select = $connection->select()
            ->from(
                ['t' => $tableMedia],
                [
                    'attribute_id' => 'attribute_id',
                    'store_id'     => 'store_id',
                    'entity_id'    => 'entity_id',
                    'value'        => 'media_value',
                ]
            )
            ->where('t.attribute_id is not null');
        $query = $connection->insertFromSelect(
            $select,
            $connection->getTableName('catalog_product_entity_varchar'),
            ['attribute_id', 'store_id', 'entity_id', 'value'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $connection->query($query);

        // working on "media gallery"
        $tableGallery = $connection->getTableName('catalog_product_entity_media_gallery');

        // get the value id from gallery (for already existing medias)
        $maxId = $this->mediaGetMaxId($tableGallery, 'value_id');
        for ($k=1; $k<=$maxId; $k+= $step) {
            $min = $k;
            $max = $k + $step;
            // @todo use Zend methods for mass update
            $query = "
                UPDATE $tableMedia, $tableGallery
                SET $tableMedia.value_id = $tableGallery.value_id
                WHERE $tableGallery.value_id >= $min AND $tableGallery.value_id < $max
                AND BINARY $tableGallery.value = $tableMedia.media_value
            ";
            $connection->query($query);
        }

        // add the new medias to the gallery
        $select = $connection->select()
            ->from(
                ['t' => $tableMedia],
                [
                    'attribute_id' => new Expr($this->_mediaHelper->getMediaGalleryAttributeId()),
                    'value'        => 'media_value',
                    'media_type'   => new Expr("'image'"),
                    'disabled'     => new Expr('0'),
                ]
            )->where(
                't.value_id IS NULL'
            )->group('t.media_value');

        $query = $connection->insertFromSelect(
            $select,
            $tableGallery,
            ['attribute_id', 'value', 'media_type', 'disabled'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $connection->query($query);

        // get the value id from gallery (for new medias)
        $maxId = $this->mediaGetMaxId($tableGallery, 'value_id');
        for ($k=1; $k<=$maxId; $k+= $step) {
            $min = $k;
            $max = $k + $step;
            // @todo use Zend methods for mass update
            $query = "
                UPDATE $tableMedia, $tableGallery
                SET $tableMedia.value_id = $tableGallery.value_id
                WHERE $tableGallery.value_id >= $min AND $tableGallery.value_id < $max
                AND BINARY $tableGallery.value = $tableMedia.media_value
                AND $tableMedia.value_id IS NULL
            ";
            $connection->query($query);
        }

        // working on "media gallery value"
        $tableGallery = $connection->getTableName('catalog_product_entity_media_gallery_value');

        // get the record id from gallery value (for new medias)
        $maxId = $this->mediaGetMaxId($tableGallery, 'record_id');
        for ($k=1; $k<=$maxId; $k+= $step) {
            $min = $k;
            $max = $k+$step;
            // @todo use Zend methods for mass update
            $query = "
                UPDATE $tableMedia, $tableGallery
                SET $tableMedia.record_id = $tableGallery.record_id
                WHERE $tableGallery.record_id >= $min AND $tableGallery.record_id < $max
                AND $tableGallery.entity_id = $tableMedia.entity_id
                AND $tableGallery.value_id = $tableMedia.value_id
                AND $tableGallery.store_id = $tableMedia.store_id
            ";
            $connection->query($query);
        }

        // add the new medias to the gallery value
        $select = $connection->select()
            ->from(
                ['t' => $tableMedia],
                [
                    'value_id'  => 'value_id',
                    'store_id'  => new Expr('0'),
                    'entity_id' => 'entity_id',
                    'label'     => new Expr("''"),
                    'position'  => new Expr('0'),
                ]
            )->where(
                't.record_id IS NULL'
            );
        $query = $connection->insertFromSelect(
            $select,
            $tableGallery,
            ['value_id', 'store_id', 'entity_id', 'label', 'position'],
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $connection->query($query);

        // working on "media gallery value to entity"
        $tableGallery = $connection->getTableName('catalog_product_entity_media_gallery_value_to_entity');

        // add the new medias to the gallery linked to entity
        $select = $connection->select()
            ->from(
                ['t' => $tableMedia],
                [
                    'value_id'  => 'value_id',
                    'entity_id' => 'entity_id',
                ]
            );
        $query = $connection->insertFromSelect(
            $select,
            $tableGallery,
            ['value_id', 'entity_id'],
            AdapterInterface::INSERT_IGNORE
        );
        $connection->query($query);
    }
}