<?php
/**
 * Part of the Here You Go software.
 * Released under the GNU General Public License version 3.
 * See LICENCE file
 *
 */

namespace HereYouGo\Model;

use HereYouGo\Model;
use HereYouGo\Model\Exception\Broken;

abstract class Entity {
    /** @var Model|null */
    protected static $model = null;
    
    /**
     * Get model
     *
     * @return Model
     *
     * @throws Broken
     */
    final public static function model() {
        if(is_null(static::$model))
            static::$model = new Model(static::class);

        return static::$model;
    }

    /**
     * Get cache key
     *
     * @return string
     */
    abstract public function getCacheKey(): string;

    public static function all($criteria, $placeholders = []) {
        if($criteria && !preg_match('`\swhere\s`i', $criteria))
            $criteria = static::model()->table.' WHERE '.$criteria;

        if(preg_match('`^join\s`i', $criteria))
            $criteria = static::model()->table.' '.$criteria;


    }
}