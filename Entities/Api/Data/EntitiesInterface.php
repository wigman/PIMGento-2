<?php

namespace Pimgento\Entities\Api\Data;

interface EntitiesInterface
{
    /**
     * Constants for keys of data array.
     */
    const ID         = 'id';
    const CODE       = 'code';
    const ENTITY_ID  = 'entity_id';
    const IMPORT     = 'import';
    const CREATED_AT = 'created_at';

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get code
     *
     * @return string
     */
    public function getCode();

    /**
     * Get entity id
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Get Import tpy
     *
     * @return string
     */
    public function getImport();

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
     * @return \Pimgento\Entities\Api\Data\EntitiesInterface
     */
    public function setId($id);

    /**
     * Set code
     *
     * @param string $code
     * @return \Pimgento\Entities\Api\Data\EntitiesInterface
     */
    public function setCode($code);

    /**
     * Set Entity Id
     *
     * @param int $entityId
     * @return \Pimgento\Entities\Api\Data\EntitiesInterface
     */
    public function setEntityId($entityId);

    /**
     * Set import
     *
     * @param string $import
     * @return \Pimgento\Entities\Api\Data\EntitiesInterface
     */
    public function setImport($import);

    /**
     * Set creation time
     *
     * @param string $createdAt
     * @return \Pimgento\Entities\Api\Data\EntitiesInterface
     */
    public function setCreatedAt($createdAt);

}