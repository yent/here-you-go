<?php


namespace HereYouGo\Model;


class JoinedEntities {
    /** @var Entity[] */
    private $entities = [];

    /** @var JoinCollection[] */
    private $joins = [];

    /** @var int */
    private $index = 0;

    /**
     * JoinedEntities constructor.
     *
     * @param ResultSet[] $results
     */
    public function __construct(array $results) {
        foreach($results as $result_set) {
            
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