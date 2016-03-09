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
            'lines_terminated'  => $this->scopeConfig->getValue('pimgento/entities/lines_terminated'),
            'fields_terminated' => $this->scopeConfig->getValue('pimgento/entities/fields_terminated'),
            'fields_enclosure'  => $this->scopeConfig->getValue('pimgento/entities/fields_enclosure'),
        );
    }

}