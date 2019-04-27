<?php


namespace HereYouGo\Model;


use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Model\Exception\Broken;

/**
 * Class Query
 *
 * @package HereYouGo\Model
 *
 * @property-read int $id
 * @property-read string $class
 * @property-read string $table
 * @property-read string[] $columns
 * @property-read string $criteria
 * @property-read string[] $placeholders
 * @property-read Query[] $joins
 * @property-read Query|null $joined_to
 * @property-read string|null $joined_on
 * @property-read string|null $joined_name
 * @property-read string|null $scope
 * @property-read Query $main
 * @property-read string $table_alias
 * @property-read string $cache_key
 */
class Query {
    const LEFT = 'left';
    const RIGHT = 'right';

    /** @var int */
    protected $id = 0;

    /** @var string */
    protected $class = '';

    /** @var string */
    protected $table = '';

    /** @var string[] */
    protected $columns = [];

    /** @var string */
    protected $criteria = '';

    /** @var string[] */
    protected $placeholders = [];

    /** @var (self|string)[] */
    protected $joins = [];

    /** @var (self|string)[]|null */
    protected $joined_to = null;

    /** @var string|null */
    protected $joined_on = null;

    /** @var string|null */
    protected $joined_name = null;

    /** @var int */
    protected static $cid = 0;

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
        $this->id = static::$cid++;

        $this->class = $class;
        $this->table = $class::model()->table;

        foreach($class::model()->data_map as $property)
            $this->columns[$property->column] = $property->name;

        $this->criteria = $criteria;

        foreach(array_keys($placeholders) as $k)
            if($k{0} !== ':')
                throw new Broken($k, 'placeholders must start with ":"');

        $this->placeholders = $placeholders;
    }

    /**
     * Set join criteria
     *
     * @param string $join_criteria
     * @param string $join_name
     */
    public function on($join_criteria, $join_name) {
        $this->joined_on = $join_criteria;
        $this->joined_name = $join_name;
    }

    /**
     * Back-reference the join
     *
     * @param Query $query
     *
     * @throws Broken
     */
    public function joinedTo(Query $query) {
        if(!$this->joined_on)
            throw new Broken($this, 'cannot join without "on" criteria');

        $this->joined_to = $query;
    }

    /**
     * JoinCollection query / queries
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
            $query->joinedTo($this);
            $this->joins[] = $query;

            return $query;
        }

        throw new Broken($query, 'expecting Query or Query[]');
    }

    /**
     * Scope class properties in sql fragment
     *
     * @param Entity|string $class
     * @param string $statement
     * @param string $scope
     *
     * @return string
     *
     * @throws Broken
     */
    protected static function castAndScopeColumns($class, $statement, $scope) {
        $properties = $class::model()->data_map;

        foreach($properties as $property) {
            $replace = ["`(?<!\.|:)$property->column`"];
            if($property->column !== $property->name)
                $replace[] = "`(?<!\.|:)$property->name`";

            $statement = preg_replace($replace, "$scope.$property->column", $statement);
        }

        // placeholders
        $statement = preg_replace('`:([a-z0-9_]+)`', ":{$scope}___$1", $statement);

        return $statement;
    }

    /**
     * Scope class properties in on clause
     *
     * @param string $side
     * @param Entity|string $class
     * @param string $statement
     * @param string $scope
     *
     * @return string
     *
     * @throws Broken
     */
    protected static function castAndScopeOn($side, $class, $statement, $scope) {
        $properties = $class::model()->data_map;

        foreach($properties as $property) {
            $replace = [($side === self::LEFT) ? "`(?<!\.)$property->column\s*=`" : "`=\s*$property->column`"];
            if($property->column !== $property->name)
                $replace[] = ($side === self::LEFT) ? "`(?<!\.)$property->name\s*=`" : "`=\s*$property->name`";

            $by = [($side === self::LEFT) ? "$scope.$property->column =" : "= $scope.$property->column"];

            $statement = preg_replace($replace, $by, $statement);
        }

        return $statement;
    }

    /**
     * Get SQL (call without arg to get compiled sql)
     *
     * @param bool $as_string
     *
     * @return array|string
     *
     * @throws Broken
     */
    public function getSql($as_string = true) {
        $query = $as_string ? $this->main : $this;

        $columns = [];
        $from = [];
        $criteria = [];

        $i = 1;
        foreach($query->joins as $joined) {
            $join = $joined->getSql(false);

            $from += $join['from'];
            $columns += $join['columns'];
            $criteria += $join['criteria'];

            $i++;
        }

        if($this->joined_to) {
            $left = $this->joined_to;
            $on = self::castAndScopeOn(self::LEFT, $left->class, $this->joined_on, $left->scope);
            $on = self::castAndScopeOn(self::RIGHT, $this->class, $on, $this->scope);

            array_unshift($from, "JOIN $this->table AS $this->scope ON ($on)");

        } else {
            array_unshift($from, "$this->table AS $this->scope");
        }

        foreach(array_keys($this->columns) as $column)
            $columns[] = "$this->table.$column AS {$this->scope}___$column";

        $criteria[] = self::castAndScopeColumns($this->class, $this->criteria, $this->scope);

        if(!$as_string)
            return ['columns' => $columns, 'from' => $from, 'criteria' => $criteria];

        $columns = implode(', ', $columns);
        $from = implode(' ', $from);

        $criteria = $criteria ? 'WHERE ('.implode(') AND (', $criteria).')' : '';

        return "SELECT $columns FROM $from $criteria";
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
            $placeholders[':'.$this->scope.'___'.substr($k, 1)] = $v;

        return $placeholders;
    }

    /**
     * Split and categorize data according to join structure
     *
     * @param array $data
     *
     * @return ResultSet
     *
     * @throws Broken
     */
    public function categorizeData(array $data) {
        $result = [];
        foreach($data as $k => $v) {
            if(!preg_match("`^{$this->scope}___(.+)$`", $k, $match)) continue;

            $result[$match[1]] = $v;
        }

        $results = [new Result($this, $result)];
        foreach($this->joins as $join)
            $results = array_merge($results, $join->categorizeData($data));

        return new ResultSet($results);
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
        if(in_array($name, ['id', 'class', 'table', 'columns', 'criteria', 'placeholders', 'joins', 'joined_to', 'joined_on', 'joined_name']))
            return $this->$name;

        if($name === 'main')
            return $this->joined_to ? $this->joined_to->main : $this;

        if($name === 'scope')
            return "query_scope_$this->id";

        if($name === 'table_alias')
            return $this->joined_to ? $this->joined_to->table_alias.'t'.array_search($this, $this->joined_to->joins) : '';

        if($name === 'cache_key')
            return base64_encode(serialize($this));

        throw new UnknownProperty($this, $name);
    }
}