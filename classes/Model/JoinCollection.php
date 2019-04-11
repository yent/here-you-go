<?php


namespace HereYouGo\Model;


use HereYouGo\Exception\UnknownProperty;

/**
 * Class JoinCollection
 * @package HereYouGo\Model
 */
class JoinCollection {
    /** @var (JoinedEntities|Entity)[] */
    private $joined = [];

    public function __construct(Query $query, array $entries) {

    }

    /**
     * Get join
     *
     * @param $name
     *
     * @return JoinedEntities|Entity
     *
     * @throws UnknownProperty
     */
    public function __get($name) {
        if(array_key_exists($name, $this->joined))
            return $this->joined[$name];

        throw new UnknownProperty($this, $name);
    }
}
;