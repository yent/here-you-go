<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */


namespace HereYouGo\Model\Backend;


use HereYouGo\DBI;
use HereYouGo\Model\Constant\IntSize;
use HereYouGo\Model\Constant\Type;
use HereYouGo\Model\Property;
use HereYouGo\Model\Updater;

/**
 * Class Mysql
 *
 * @package HereYouGo\Model\Backend
 */
class Mysql extends Updater {
    const INT_TYPES = [
        IntSize::INT8 => 'TINYINT',
        IntSize::INT16 => 'SMALLINT',
        IntSize::INT24 => 'MEDIUMINT',
        IntSize::INT32 => 'INT',
        IntSize::INT64 => 'BIGINT',
    ];
    
    /**
     * Check wether table exists
     *
     * @param string $table
     *
     * @return bool
     */
    protected static function tableExists($table): bool {
        $statement = DBI::prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE (TABLE_SCHEMA = :database) AND (TABLE_NAME = :table)');
        
        $statement->execute([':database' => '', ':table' => $table]);
        
        return $statement->fetchColumn() === '1';
    }
    
    /**
     * Check if column matches definition and updates it if it doesn't
     *
     * @param string $table
     * @param Property $definition
     */
    protected static function checkColumn($table, Property $definition) {
        $statement = DBI::prepare("SHOW COLUMNS FROM `$table` WHERE Field = :column");
        $statement->execute([':column' => $definition->column]);

        $todo = '';
        $found = $statement->fetch();
        if($found) {
            $update = false;

            // check type
            if(trim(strtolower($found['Type'])) !== trim(strtolower(static::getColumnType($definition))))
                $update = true;

            // check null
            if($definition->null && $found['Null'] === 'NO') // must add nullable
                $update = true;

            if(!$definition->null && $found['Null'] === 'YES') // must drop nullable
                $update = true;

            // check default
            if($found['Default'] !== $definition->default)
                $update = true;

            if($update)
                $todo = 'MODIFY';

        } else {
            // create column
            $todo = 'ADD COLUMN';
        }

        if($todo) {
            $col_def = static::columnDefinition($definition);
            if($definition->auto_increment) $col_def .= ' AUTO_INCREMENT';

            DBI::exec("ALTER TABLE `$table` $todo `$definition->column` $col_def");
        }
    }

    /**
     * Check constraints
     *
     * @param string $table
     * @param Property[] $map
     */
    protected static function checkConstraints($table, array $map) {
        $required_primaries = [];
        $required_indexes = [];
        $reverse_map = [];
        foreach($map as $definition) {
            $reverse_map[$definition->column] = $definition;

            if($definition->primary)
                $required_primaries[] = $definition->column;

            foreach($definition->indexes as $index => $unique) {
                if(!array_key_exists($index, $required_indexes))
                    $required_indexes[$index] = ['columns' => [], 'unique' => false];

                $required_indexes[$index]['columns'][] = $definition->column;
                $required_indexes[$index]['unique'] |= $unique;
            }
        }

        // drop removed primaries
        foreach(array_diff(static::getPrimaries($table), $required_primaries) as $column) {
            if (array_key_exists($column, $reverse_map)) {
                // Column kept, only primary index was removed
                DBI::exec("ALTER TABLE `$table` MODIFY COLUMN `$column` ".static::columnDefinition($reverse_map[$column]));

            } else {
                DBI::exec("ALTER TABLE `$table` DROP COLUMN `$column`");
            }
        }

        // drop all primaries and add again
        $columns = implode(', ', array_map(function($column) {
            return "`$column`";
        }, $required_primaries));
        DBI::exec("ALTER TABLE `$table` DROP PRIMARY KEY, ADD PRIMARY KEY ($columns)");

        // check indexes
        $found_indexes = self::getIndexes($table);
        foreach($required_indexes as $name => $definition) {
            $create = false;
            if(array_key_exists($name, $found_indexes['indexes'])) {
                $fnd_cols = $found_indexes[$name]['columns'];
                // check composition
                if(array_diff($definition['columns'], $fnd_cols) || array_diff($fnd_cols, $definition['columns']) || $found_indexes['unique'] !== $definition['unique']) {
                    // different, drop and flag for creation
                    DBI::exec("ALTER TABLE `$table` DROP INDEX `$name`");

                    $create = true;
                }

            } else {
                $create = true;
            }

            if($create) {
                $type = $definition['unique'] ? 'UNIQUE' : 'INDEX';
                $columns = implode(', ', array_map(function($column) {
                    return "`$column`";
                }, $definition['columns']));
                DBI::exec("ALTER TABLE `$table` ADD $type ($columns)");
            }
        }
    }

    /**
     * Create table
     *
     * @param string $table
     * @param Property[] $map
     */
    protected static function createTable($table, array $map) {
        $columns = [];
        $primary = [];
        $indexes = ['INDEX'=>[], 'UNIQUE'=>[]];
        
        foreach($map as $property => $definition) {
            $column = "`$definition->column` ".static::columnDefinition($definition);
            if($definition->auto_increment)
                $column .= ' AUTO_INCREMENT';
            
            $columns[] = $column;
            
            if($definition->primary)
                $primary[] = $definition->column;

            foreach($definition->indexes as $index => $unique)
                $indexes[$unique ? 'UNIQUE' : 'INDEX'][$index][] = $definition->column;
        }
        
        if($primary)
            $columns[] = 'PRIMARY KEY ('.implode(', ', $primary).')';
        
        foreach($indexes as $type => $set)
            foreach($set as $name => $cols)
                $columns[] = "$type `$name` (".implode(', ', $cols).")";
        
        $sql = "CREATE TABLE `$table` (".implode(', ', $columns).")";
        
        DBI::exec($sql);
    }
    
    /**
     * Get Mysql definition string from Property
     *
     * @param Property $definition
     *
     * @return string
     */
    protected static function columnDefinition(Property $definition) {
        $sql = static::getColumnType($definition);

        $sql .= $definition->null ? ' NULL' : ' NOT NULL';
        
        if(!is_null($definition->default)) {
            $value = $definition->converter ? $definition->converter->encode($definition->default) : $definition->default;
            
            if($definition->type === Type::BOOL)
                $value = $value ? '1' : '0';
            
            if(in_array($definition->type, [Type::DATE, Type::DATE_TIME, Type::TIME, Type::STRING, Type::TEXT, Type::LONG_TEXT]))
                $value = DBI::quote($value);
            
            $sql .= " DEFAULT $value";
        }
        
        return $sql;
    }

    /**
     * Get type string
     *
     * @param Property $definition
     *
     * @return string
     */
    protected static function getColumnType(Property $definition) {
        switch($definition->type) {
            case Type::BOOL:        return "TINYINT(1) UNSIGNED";
            case Type::INT:         return static::INT_TYPES[$definition->size].($definition->unsigned ? ' UNSIGNED' : '');
            case Type::DECIMAL:     return "DECIMAL({$definition->size['precision']},{$definition->size['decimal_places']})".($definition->unsigned ? ' UNSIGNED' : '');
            case Type::FLOAT:       return 'FLOAT';
            case Type::DOUBLE:      return 'DOUBLE';
            case Type::DATE:        return 'DATE';
            case Type::DATE_TIME:   return 'DATETIME';
            case Type::TIME:        return 'TIME';
            case Type::STRING:      return "VARCHAR({$definition->size})";
            case Type::TEXT:        return 'TEXT';
            case Type::LONG_TEXT:   return 'LONGTEXT';
        }

        return '';
    }

    /**
     * get table primary key columns
     *
     * @param string $table
     *
     * @return string[]
     */
    protected static function getPrimaries($table) {
        $statement = DBI::prepare("SHOW INDEX FROM :table WHERE Key_name = 'PRIMARY'");
        $statement->execute([':table' => $table]);

        return array_map(function(array $row) {
            return $row['Column_name'];
        }, $statement->fetchAll());
    }

    /**
     * Get table indexes names and columns
     *
     * @param string $table
     *
     * @return array
     */
    protected static function getIndexes($table) {
        $statement = DBI::prepare("SHOW INDEX FROM :table WHERE Key_name != 'PRIMARY'");
        $statement->execute([':table' => $table]);

        $indexes = [];

        foreach($statement->fetchAll() as $row) {
            if(!array_key_exists($row['Key_name'], $indexes))
                $indexes[$row['Key_name']] = ['columns' => [], 'unique' => !$row['Non_unique']];

            $indexes[$row['Key_name']]['columns'][] = $row['Column_name'];
        }

        return $indexes;
    }
}