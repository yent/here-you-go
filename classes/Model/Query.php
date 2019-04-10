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
 * @property string|null $joined_name
 * @property Query $main
 * @property string $table_alias
 * @property string $cache_key
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

    /** @var string|null */
    private $joined_name = null;

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
     * @param string $join_criteria
     * @param string $join_name
     */
    public function on($join_criteria, $join_name) {
        $this->joined_on = $join_criteria;
        $this->joined_name = $join_name;
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

    /**
     * Scope class properties in sql fragment
     *
     * @param Entity|string $class
     * @param string $query
     * @param string|null $tables
     *
     * @return string
     *
     * @throws Broken
     */
    protected static function castAndScopeColumns($class, $query, $table = null) {
        $properties = $class::model()->data_map;

        if(!$table)
            $table = $class::model()->table;

        foreach($properties as $property) {
            if($property->column !== $property->name)
                $query = preg_replace("`(?<!\.)`$property->name", $property->column, $query);

            $query = preg_replace("`(?<!\.)`$property->column", "$table.$property->column", $query);
        }

        return $query;
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
            array_unshift($from, "JOIN $this->table AS $this->joined_name ON ($this->joined_on)");

        } else {
            array_unshift($from, $this->table);
        }

        $column_prefix = $this->joined_name ? $this->joined_name : $this->table;
        foreach(array_keys($this->columns) as $column)
            $columns[] = "$this->table.$column AS {$column_prefix}___$column";

        $criteria[] = self::castAndScopeColumns($this->class, $this->criteria, $this->joined_name);

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
            $placeholders[':'.$this->class.'___'.substr($k, 1)] = $v;

        return $placeholders;
    }

    /**
     * Split and categorize data according to join structure
     *
     * @param array $joined_data
     *
     * @return array
     */
    public function categorizeJoinedData(array $joined_data) {
        $entry = ['class' => $this->class, 'data' => [], 'joins' => []];

        $column_prefix = preg_quote($this->joined_name ? $this->joined_name : $this->table, '`');
        foreach($joined_data as $k => $v) {
            if(!preg_match("`^{$column_prefix}___(.+)$`", $k, $match)) continue;

            $entry['data'][$match[1]] = $v;
        }

        foreach($this->joins as $join)
            $entry['joins'][$join->joined_name] = $join->categorizeJoinedData($joined_data);

        return $entry;
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
        if(in_array($name, ['class', 'table', 'columns', 'criteria', 'placeholders', 'joins', 'joined_to', 'joined_on', 'joined_name']))
            return $this->$name;

        if($name === 'main')
            return $this->joined_to ? $this->joined_to->main : $this;

        if($name === 'table_alias')
            return $this->joined_to ? $this->joined_to->table_alias.'t'.array_search($this, $this->joined_to->joins) : '';

        if($name === 'cache_key') {
            $ph = serialize($this->placeholders);
            return md5("$this->class/$this->criteria($ph)");
        }

        throw new UnknownProperty($this, $name);
    }
}