<?php


namespace HereYouGo\Model;


use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use ReflectionException;

/**
 * Class JoinCollection
 * @package HereYouGo\Model
 */
class JoinCollection {
    /** @var (JoinedEntities|Entity)[] */
    private $joined = [];

    /**
     * JoinCollection constructor.
     *
     * @param Query $query
     * @param array $result_sets
     *
     * @throws Exception\Broken
     * @throws Exception\NotFound
     * @throws BadType
     * @throws ReflectionException
     */
    public function __construct(Query $query, array $result_sets) {
        foreach($query->joins as $join) {
            if($join->joins) {
                $this->joined[$join->joined_name] = new JoinedEntities($join, $result_sets);

            } else {
                foreach($result_sets as $result_set) {
                    $this->joined[$join->joined_name][] = $result_set->{$join->scope}->getEntity();
                }
            }
        }
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