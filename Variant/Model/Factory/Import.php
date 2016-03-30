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

        $this->_entities->createTmpTableFromFile($file, $this->getCode(), array('code', 'axis'));
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
     * Add or update data in variant table
     */
    public function updateVariant()
    {
        $connection = $this->_entities->getResource()->getConnection();
        $tmpTable = $this->_entities->getTableName($this->getCode());

        $variant = $connection->query(
            $connection->select()->from($tmpTable)
        );

        $attributes = $connection->fetchPairs(
            $connection->select()->from(
                $connection->getTableName('eav_attribute'), array('attribute_code', 'attribute_id')
            )
            ->where('entity_type_id = ?', 4)
        );

        $stores = $this->_helperConfig->getStores('lang');

        while (($row = $variant->fetch())) {

            $translate = array('name' => array());

            foreach ($stores as $lang => $affected) {
                if (isset($row['label-' . $lang])) {
                    foreach ($affected as $store) {
                        $translate['name'][$store['store_id']] = $row['label-' . $lang];
                    }
                }
            }

            $axisAttributes = explode(',', $row['axis']);

            $axis = array();

            foreach ($axisAttributes as $code) {
                if (isset($attributes[$code])) {
                    $axis[] = $attributes[$code];
                }
            }

            $values = array(
                'code' => $row['code'],
                'axis' => join(',', $axis),
                'translate' => serialize($translate),
            );

            $connection->insertOnDuplicate(
                $connection->getTableName('pimgento_variant'), $values, array_keys($values)
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