<?php

namespace Pimgento\Import\Api\Data;

interface FactoryInterface
{
    /**
     * Constants for keys of data array.
     */
    const IDENTIFIER       = 'identifier';
    const CODE             = 'code';
    const SORT_ORDER       = 'sort_order';
    const NAME             = 'name';
    const CLASS_NAME       = 'class_name';
    const STEPS            = 'steps';
    const STEP             = 'step';
    const MESSAGE          = 'message';
    const STATUS           = 'status';
    const CONTINUE_STEP    = 'continue';
    const FILE             = 'file';
    const FILE_IS_REQUIRED = 'file_is_required';

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier();

    /**
     * Get code
     *
     * @return string
     */
    public function getCode();

    /**
     * Get sort order
     *
     * @return int
     */
    public function getSortOrder();

    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Get class
     *
     * @return string
     */
    public function getClass();

    /**
     * Get Steps
     *
     * @return array
     */
    public function getSteps();

    /**
     * Get Message
     *
     * @return string
     */
    public function getMessage();

    /**
     * Get Continue
     *
     * @return bool
     */
    public function getContinue();

    /**
     * Get Status
     *
     * @return bool
     */
    public function getStatus();

    /**
     * Get Step
     *
     * @return int
     */
    public function getStep();

    /**
     * Get File
     *
     * @return string
     */
    public function getFile();

    /**
     * Get File is required
     *
     * @return bool
     */
    public function getFileIsRequired();

    /**
     * Set Identifier
     *
     * @param string $identifier
     * @return \Pimgento\Import\Api\Data\FactoryInterface
     */
    public function setIdentifier($identifier);

    /**
     * Set Code
     *
     * @param string $code
     * @return \Pimgento\Import\Api\Data\FactoryInterface
     */
    public function setCode($code);

    /**
     * Set Sort order
     *
     * @param int $sort
     * @return \Pimgento\Import\Api\Data\FactoryInterface
     */
    public function setSortOrder($sort);

    /**
     * Set Name
     *
     * @param string $name
     * @return \Pimgento\Import\Api\Data\FactoryInterface
     */
    public function setName($name);

    /**
     * Set Class
     *
     * @param string $class
     * @return \Pimgento\Import\Api\Data\FactoryInterface
     */
    public function setClass($class);

    /**
     * Set Steps
     *
     * @param string $steps
     * @return \Pimgento\Import\Api\Data\FactoryInterface
     */
    public function setSteps($steps);

    /**
     * Set Message
     *
     * @param string $message
     * @return \Pimgento\Import\Api\Data\FactoryInterface
     */
    public function setMessage($message);

    /**
     * Set Continue
     *
     * @param bool $continue
     * @return \Pimgento\Import\Api\Data\FactoryInterface
     */
    public function setContinue($continue);

    /**
     * Set Status
     *
     * @param bool $status
     * @return \Pimgento\Import\Api\Data\FactoryInterface
     */
    public function setStatus($status);

    /**
     * Set Step
     *
     * @param int $step
     * @return \Pimgento\Import\Api\Data\FactoryInterface
     */
    public function setStep($step);

    /**
     * Set File
     *
     * @param string $file
     * @return \Pimgento\Import\Api\Data\FactoryInterface
     */
    public function setFile($file);

    /**
     * Set File is Required
     *
     * @param bool $isRequired
     * @return \Pimgento\Import\Api\Data\FactoryInterface
     */
    public function setFileIsRequired($isRequired);

}