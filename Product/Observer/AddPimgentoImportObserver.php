<?php

namespace Pimgento\Product\Observer;

use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ManagerInterface as EventManager;
use \Magento\Framework\DataObject;

class AddPimgentoImportObserver implements ObserverInterface
{

    /**
     * System event manager
     *
     * @var EventManager
     */
    protected $eventManager;

    /**
     * PHP Constructor
     *
     * @param EventManager $eventManager
     */
    public function __construct(
        EventManager $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * Add import to Collection
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var $collection \Pimgento\Import\Model\Import\Collection */
        $collection = $observer->getEvent()->getCollection();

        $collection->addImport($this->getImportDefinition());
    }

    protected function getImportDefinition()
    {
        $response = new DataObject();
        $response->setImportClassname('\Pimgento\Product\Model\Factory\Import');

        $this->eventManager->dispatch(
            'pimgento_product_import_classname',
            ['response' => $response]
        );

        $definition = array(
            'code'             => 'product',
            'name'             => __('Products'),
            'class'            => $response->getImportClassname(),
            'sort_order'       => 60,
            'file_is_required' => true,
            'steps'            => $this->getStepsDefinition()
        );

        return $definition;
    }

    protected function getStepsDefinition()
    {
        $stepsBefore = array(
            array(
                'comment' => __('Create temporary table'),
                'method'  => 'createTable',
            ),
            array(
                'comment' => __('Fill temporary table'),
                'method'  => 'insertData',
            ),
            array(
                'comment' => __('Add product required data'),
                'method'  => 'addRequiredData',
            ),
            array(
                'comment' => __('Create configurable product'),
                'method'  => 'createConfigurable',
            ),
            array(
                'comment' => __('Match code with Magento ID'),
                'method'  => 'matchEntity',
            ),
            array(
                'comment' => __('Match family code with Magento id'),
                'method'  => 'updateAttributeSetId',
            ),
            array(
                'comment' => __('Update column values for options'),
                'method'  => 'updateOption',
            ),
            array(
                'comment' => __('Create or update product entities'),
                'method'  => 'createEntities',
            ),
        );

        $responseUpdateEntities = new DataObject();
        $responseUpdateEntities->setUpdateEntitiesSteps([]);

        $this->eventManager->dispatch(
            'pimgento_product_import_update_entities_add_steps',
            ['response' => $responseUpdateEntities]
        );

        $afterEntitiesCreationSteps = array(
            array(
                'comment' => __('Set values to attributes'),
                'method'  => 'setValues',
            ),
            array(
                'comment' => __('Link configurable with children'),
                'method'  => 'linkConfigurable',
            ),
            array(
                'comment' => __('Set products to websites'),
                'method'  => 'setWebsites',
            ),
            array(
                'comment' => __('Set products to categories'),
                'method'  => 'setCategories',
            ),
            array(
                'comment' => __('Init stock'),
                'method'  => 'initStock',
            ),
            array(
                'comment' => __('Update related, up-sell and cross-sell products'),
                'method'  => 'setRelated'
            ),
            array(
                'comment' => __('Set Url Rewrite'),
                'method'  => 'setUrlRewrite',
            ),
            array(
                'comment' => __('Import media files'),
                'method'  => 'importMedia',
            ),
        );

        $responseFinalSteps = new DataObject();
        $responseFinalSteps->setFinalSteps([]);

        $this->eventManager->dispatch(
            'pimgento_product_import_add_final_steps',
            ['response' => $responseFinalSteps]
        );

        $stepsAfter = array(
            array(
                'comment' => __('Drop temporary table'),
                'method'  => 'dropTable',
            ),
            array(
                'comment' => __('Clean Media Folder'),
                'method'  => 'cleanMediaFolder',
            ),
            array(
                'comment' => __('Clean cache'),
                'method'  => 'cleanCache',
            )
        );

        return array_merge(
            $stepsBefore,
            $responseUpdateEntities->getUpdateEntitiesSteps(),
            $afterEntitiesCreationSteps,
            $responseFinalSteps->getFinalSteps(),
            $stepsAfter
        );
    }
}
