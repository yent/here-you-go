<?php


namespace HereYouGo\Model;


use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Model\Exception\Broken;

/**
 * Class ResultSet
 *
 * @package HereYouGo\Model
 *
 * @property-read Result $...
 */
class ResultSet {
    /** @var Result[] */
    private $results = [];

    /**
     * ResultSet constructor.
     *
     * @param Result[] $results
     *
     * @throws Broken
     */
    public function __construct(array $results) {
        foreach($results as $result) {
            if($result instanceof Result) {
                $this->results[$result->query->scope] = $result;

            } else {
                throw new Broken($this, 'not a Result');
            }
        }
    }

    /**
     * Getter
     *
     * @param string $name
     *
     * @return Result
     *
     * @throws UnknownProperty
     */
    public function __get($name) {
        if(array_key_exists($name, $this->results))
            return $this->results[$name];

        throw new UnknownProperty($this, $name);
    }

}