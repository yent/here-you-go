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
        // TODO: Implement checkColumn() method.
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
        $indexes = ['INDEX'=>[], 'UNIQUE'=>[], 'UNIQUE INDEX'=>[]];
        
        foreach($map as $property => $definition) {
            $column = $definition->column.' '.static::columnDefinition($definition);
            if($definition->auto_increment)
                $column .= ' AUTO_INCREMENT';
            
            $columns[] = $column;
            
            if($definition->primary)
                $primary[] = $definition->column;
    
            if($definition->index && !$definition->unique)
                $indexes['INDEX'][$definition->index][] = $definition->column;
    
            if($definition->unique && !$definition->index)
                $indexes['UNIQUE'][$definition->unique][] = $definition->column;
            
            if($definition->index && $definition->unique && ($definition->index === $definition->unique))
                $indexes['UNIQUE INDEX'][$definition->unique][] = $definition->column;
        }
        
        if($primary)
            $columns[] = 'PRIMARY KEY ('.implode(', ', $primary).')';
        
        foreach($indexes as $type => $set)
            foreach($set as $name => $cols)
                $columns[] = $type.' '.$name.' ('.implode(', ', $cols).')';
        
        $sql = "CREATE TABLE $table (".implode(', ', $columns).")";
        
        DBI::exec($sql);
    }
    
    /**
     * Get Mysql definition string from Property
     *
     * @param Property $definition
     *
     * @return string
     */
    private static function columnDefinition(Property $definition) {
        $sql = '';
        switch($definition->type) {
            case Type::BOOL: $sql = "TINYINT(1) UNSIGNED"; break;
    
            case Type::INT:
                $sql = static::INT_TYPES[$definition->size];
                if($definition->unsigned) $sql .= ' UNSIGNED';
                break;
    
            case Type::DECIMAL:
                $sql = "DECIMAL({$definition->size['precision']},{$definition->size['decimal_places']})";
                if($definition->unsigned) $sql .= ' UNSIGNED';
                break;
    
            case Type::FLOAT: $sql = 'FLOAT'; break;
    
            case Type::DOUBLE: $sql = 'DOUBLE'; break;
    
            case Type::DATE: $sql = 'DATE'; break;
    
            case Type::DATE_TIME: $sql = 'DATETIME'; break;
    
            case Type::TIME: $sql = 'TIME'; break;
    
            case Type::STRING: $sql = "VARCHAR({$definition->size})"; break;
    
            case Type::TEXT: $sql = 'TEXT'; break;
    
            case Type::LONG_TEXT: $sql = 'LONGTEXT'; break;
        }
        
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
}