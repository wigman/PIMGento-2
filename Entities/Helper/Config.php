<?php

namespace Pimgento\Entities\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    /**
     * Retrieve CSV configuration
     *
     * @return array
     */
    public function getCsvConfig()
    {
        return array(
            'lines_terminated'  => $this->scopeConfig->getValue('pimgento/general/lines_terminated'),
            'fields_terminated' => $this->scopeConfig->getValue('pimgento/general/fields_terminated'),
            'fields_enclosure'  => $this->scopeConfig->getValue('pimgento/general/fields_enclosure'),
        );
    }

    /**
     * Retrieve Load Data Infile option
     *
     * @return int
     */
    public function getLoadDataLocal()
    {
        return $this->scopeConfig->getValue('pimgento/general/load_data_local');
    }

}