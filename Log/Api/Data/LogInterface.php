<?php

namespace Pimgento\Log\Api\Data;

interface LogInterface
{
    /**
     * Constants for keys of data array.
     */
    const LOG_ID  = 'log_id';
    const IDENTIFIER = 'identifier';
    const CODE       = 'code';
    const NAME       = 'name';
    const FILE       = 'file';
    const STATUS     = 'status';
    const CREATED_AT = 'created_at';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get Identifier
     *
     * @return int|null
     */
    public function getIdentifier();

    /**
     * Get code
     *
     * @return string
     */
    public function getCode();

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Get file
     *
     * @return string|null
     */
    public function getFile();

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus();

    /**
     * Get creation time
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set ID
     *
     * @param int $id
     * @return \Pimgento\Log\Api\Data\LogInterface
     */
    public function setId($id);

    /**
     * Set Identifier
     *
     * @param string $identifier
     * @return \Pimgento\Log\Api\Data\LogInterface
     */
    public function setIdentifier($identifier);

    /**
     * Set code
     *
     * @param string $code
     * @return \Pimgento\Log\Api\Data\LogInterface
     */
    public function setCode($code);

    /**
     * Set name
     *
     * @param string $name
     * @return \Pimgento\Log\Api\Data\LogInterface
     */
    public function setName($name);

    /**
     * Set file
     *
     * @param string $file
     * @return \Pimgento\Log\Api\Data\LogInterface
     */
    public function setFile($file);

    /**
     * Set status
     *
     * @param string $status
     * @return \Pimgento\Log\Api\Data\LogInterface
     */
    public function setStatus($status);

    /**
     * Set creation time
     *
     * @param string $createdAt
     * @return \Pimgento\Log\Api\Data\LogInterface
     */
    public function setCreatedAt($createdAt);

}