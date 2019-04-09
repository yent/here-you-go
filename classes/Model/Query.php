<?php


namespace HereYouGo\Model;


use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Model\Exception\Broken;

/**
 * Class Query
 *
 * @package HereYouGo\Model
 *
 * @property string $class
 * @property string $table
 * @property string[] $columns
 * @property string $criteria
 * @property string[] $placeholders
 * @property Query[] $joins
 * @property Query|null $joined_to
 * @property string|null $joined_on
 * @property Query $main
 */
class Query {
    /** @var string */
    private $class = '';

    /** @var string */
    private $table = '';

    /** @var string[] */
    private $columns = [];

    /** @var string */
    private $criteria = '';

    /** @var string[] */
    private $placeholders = [];

    /** @var (self|string)[] */
    private $joins = [];

    /** @var (self|string)[]|null */
    private $joined_to = null;

    /** @var string|null */
    private $joined_on = null;

    /**
     * Query constructor.
     *
     * @param Entity|string $class
     * @param string $criteria
     * @param string[] $placeholders
     *
     * @throws Broken
     */
    public function __construct($class, $criteria, $placeholders = []) {
        $this->class = $class;
        $this->table = $class::model()->table;

        foreach($class::model()->data_map as $property)
            $this->columns[$property->column] = $property->name;

        $this->criteria = $criteria;

        foreach(array_keys($placeholders) as $k)
            if(substr($k, 0, 1) === ':')
                throw new Broken($k, 'placeholders must start with ":"');

        $this->placeholders = $placeholders;
    }

    /**
     * Set join criteria
     *
     * @param $join_criteria
     */
    public function on($join_criteria) {
        $this->joined_on = $join_criteria;
    }

    /**
     * Join query / queries
     *
     * @param self|array $query
     *
     * @return Query
     *
     * @throws Broken
     */
    public function join($query) {
        if(is_array($query)) {
            foreach($query as $q)
                $this->join($q);

            return $this;
        }

        if($query instanceof self) {
            if(!$query->joined_on)
                throw new Broken($query, 'cannot join without criteria');

            $query->joined_to = $this;
            $this->joins[] = $query;

            return $query;
        }

        throw new Broken($query, 'expecting Query or Query[]');
    }

    protected static function castAndScopeColumns($class, $criteria) {

    }

    /**
     * Get SQL (call without arg to get compiled sql)
     *
     * @param string|null $idx
     *
     * @return array|string
     */
    public function getSql($idx = null) {
        $query = $idx ? $this : $this->main;

        $columns = [];
        $froms = [];
        $criterias = [];

        $i = 1;
        foreach($query->joins as $joined) {
            $join = $joined->getSql(($idx ? $idx : '').'j'.$i);



            $i++;
        }

        if($this->joined_to) {
            array_unshift($froms, "JOIN $this->table ON ($this->joined_on)");

        } else {
            array_unshift($froms, $this->table);
        }

        foreach(array_keys($this->columns) as $column)
            $columns[] = "$this->table.$column AS {$this->class}___$column";
    }

    /**
     * Get combined, scoped placeholders (call witout arg to get all placeholders)
     *
     * @param bool $at_level
     *
     * @return array
     */
    public function getAggregatedPlaceholders($at_level = false) {
        $query = $at_level ? $this : $this->main;

        $placeholders = [];
        foreach($query->joins as $joined)
            $placeholders = array_merge($placeholders, $joined->getAggregatedPlaceholders(true));

        foreach($this->placeholders as $k => $v)
            $placeholders[':'.$this->class.'___'.substr($k, 1)] = $v;

        return $placeholders;
    }

    /**
     * Getter
     *
     * @param $name
     *
     * @return mixed
     *
     * @throws UnknownProperty
     */
    public function __get($name) {
        if(in_array($name, ['class', 'table', 'columns', 'criteria', 'placeholders', 'joins', 'joined_to', 'joined_on']))
            return $this->$name;

        if($name === 'main')
            return $this->joined_to ? $this->joined_to->main : $this;

        throw new UnknownProperty($this, $name);
    }
}