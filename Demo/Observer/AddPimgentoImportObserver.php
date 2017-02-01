<?php

namespace Pimgento\Demo\Observer;

use Magento\Framework\Event\ObserverInterface;
use Pimgento\Import\Observer\AbstractAddImportObserver;

class AddPimgentoImportObserver extends AbstractAddImportObserver implements ObserverInterface
{
    /**
     * Get the import code
     *
     * @return string
     */
    protected function getImportCode()
    {
        return 'demo';
    }

    /**
     * Get the import name
     *
     * @return string
     */
    protected function getImportName()
    {
        return __('Demo');
    }

    /**
     * Get the default import classname
     *
     * @return string
     */
    protected function getImportDefaultClassname()
    {
        return '\Pimgento\Demo\Model\Factory\Import';
    }

    /**
     * Get the sort order
     *
     * @return int
     */
    protected function getImportSortOrder()
    {
        return 0;
    }

    /**
     * Is a file is required for thie import
     *
     * @return bool
     */
    protected function isImportFileRequired()
    {
        return false;
    }

    /**
     * get the steps definition
     *
     * @return array
     */
    protected function getStepsDefinition()
    {
        $stepsBefore = array(
            array(
                'comment' => __('First step'),
                'method'  => 'firstStep',
            ),
            array(
                'comment' => __('Second step'),
                'method'  => 'secondStep',
            ),
            array(
                'comment' => __('Third step'),
                'method'  => 'thirdStep',
            ),
            array(
                'comment' => __('Fourth step'),
                'method'  => 'fourthStep',
            )
        );

        $stepsAfter = array(
            array(
                'comment' => __('Clean step'),
                'method'  => 'cleanStep',
            )
        );

        return array_merge(
            $stepsBefore,
            $this->getAdditionnalSteps(),
            $stepsAfter
        );
    }
}
