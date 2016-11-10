<?php

namespace Pimgento\Demo\Model\Factory;

use \Pimgento\Import\Model\Factory;

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

    /**
     * Third step
     */
    public function fourthStep()
    {
        sleep(1); // Treatment

        $this->setStatus(false);
        $this->setContinue(false);
        $this->setMessage(
            __('Something is wrong, now we stop import')
        );
    }

    /**
     * Clean step
     */
    public function cleanStep()
    {
        sleep(1); // Treatment

        $this->setMessage(
            __('Clean process')
        );
    }
}