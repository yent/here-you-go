<?php


namespace HereYouGo\Model;

use HereYouGo\Exception\BadType;
use Iterator;
use ReflectionException;

/**
 * Class JoinedEntities
 *
 * @package HereYouGo\Model
 */
class JoinedEntities implements Iterator {
    /** @var Entity[] */
    private $entities = [];

    /** @var JoinCollection[] */
    private $joins = [];

    /** @var int */
    private $index = 0;

    /**
     * JoinedEntities constructor.
     *
     * @param Query $query
     * @param ResultSet[] $result_sets
     *
     * @throws Exception\Broken
     * @throws Exception\NotFound
     * @throws BadType
     * @throws ReflectionException
     */
    public function __construct(Query $query, array $result_sets) {
        $result_set_by_entity = [];
        foreach($result_sets as $result_set)
            $result_set_by_entity[$result_set->{$query->scope}->uid][] = $result_set;

        foreach($result_set_by_entity as $result_sets) {
            $this->entities[] = reset($result_sets)->{$query->scope}->getEntity();
            $this->joins = new JoinCollection($query, $result_sets);
        }
    }

    /**
     * Return the current element
     *
     * @return JoinCollection
     */
    public function current() {
        return $this->joins[$this->index];
    }

    /**
     * Move forward to next element
     */
    public function next() {
        $this->index++;
    }

    /**
     * Return the key of the current element
     *
     * @return Entity
     */
    public function key() {
        return $this->entities[$this->index];
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid() {
        return $this->index < count($this->entities);
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind() {
        $this->index = 0;
    }
}