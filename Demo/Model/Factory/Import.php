<?php

namespace Pimgento\Demo\Model\Factory;

use \Pimgento\Import\Model\Factory;
use \Exception;

class Import extends Factory
{

    /**
     * First step
     */
    public function firstStep()
    {
        sleep(1); // Treatment
    }

    /**
     * Second step
     */
    public function secondStep()
    {
        sleep(1); // Treatment

        $this->setMessage(
            __('Second step has no custom comment, method is printed')
        );
    }

    /**
     * Third step
     */
    public function thirdStep()
    {
        sleep(1); // Treatment

        $this->setStatus(false);
        $this->setMessage(
            __('Something is wrong but we continue process')
        );
    }

}