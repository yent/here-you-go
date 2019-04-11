<?php


namespace HereYouGo\Model;


use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use ReflectionException;

/**
 * Class Result
 *
 * @package HereYouGo\Model
 *
 * @property-read Query|null $query
 * @property-read array $data
 * @property-read Entity|null $entity
 * @property-read string $uid
 */
class Result {
    /** @var Query|null */
    private $query = null;

    /** @var array */
    private $data = [];

    /** @var Entity|null */
    private $entity = null;

    /**
     * Result constructor.
     *
     * @param Query $query
     * @param array $data
     */
    public function __construct(Query $query, array $data) {
        $this->query = $query;
        $this->data = $data;

        ksort($this->data);
    }

    /**
     * Get associated entity
     *
     * @return Entity
     *
     * @throws BadType
     * @throws Exception\Broken
     * @throws Exception\NotFound
     * @throws ReflectionException
     */
    public function getEntity() {
        if(!$this->entity) {
            /** @var Entity $class */
            $class = $this->query->class;
            $this->entity = $class::fromData($this->data);
        }

        return $this->entity;
    }

    /**
     * Getter
     *
     * @param $name
     *
     * @return Entity|array|Query|mixed
     *
     * @throws Exception\Broken
     * @throws Exception\NotFound
     * @throws UnknownProperty
     * @throws BadType
     * @throws ReflectionException
     */
    public function __get($name) {
        if(in_array($name, ['query', 'data']))
            return $this->$name;

        if($name === 'entity')
            return $this->getEntity();

        if($name === 'uid')
            return serialize($this->data);

        throw new UnknownProperty($this, $name);
    }
}