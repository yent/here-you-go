<?php

namespace HereYouGo\Form;

use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Form;

/**
 * Class Fragment
 *
 * @package HereYouGo
 *
 * @property-read string $tag
 * @property string[] $attributes
 * @property Fragment|DataHolder $parent
 * @property Form $form
 */
class Fragment {
    const CONTENT_LESS = ['input'];

    /** @var string */
    protected $tag = '';

    /** @var string[] */
    protected $attributes = [];

    /** @var self|DataHolder */
    protected $parent = null;

    /**
     * Fragment constructor.
     *
     * @param string $tag
     * @param array $attributes
     * @param Fragment|null $parent
     */
    public function __construct($tag, array $attributes = [], Fragment $parent = null) {
        $this->tag = $tag;
        $this->attributes = $attributes;

        $this->parent = $parent;
    }

    /**
     * Add attributes to the set
     *
     * @param array $attributes
     * @param string|bool $concat_if_exists
     */
    public function addAttributes(array $attributes, $concat_if_exists = true) {
        foreach($attributes as $k => $v) {
            if(array_key_exists($k, $this->attributes)) {
                if(strpos($this->attributes[$k], $v) !== false)
                    continue;

                if($concat_if_exists !== false) {
                    $this->attributes[$k] .= (is_string($concat_if_exists) ? $concat_if_exists : ' ').$v;
                } else {
                    $this->attributes[$k] = $v;
                }

            } else {
                $this->attributes[$k] = $v;
            }
        }
    }

    /**
     * Wrap content in own tag
     *
     * @param string|callable $content
     *
     * @return string
     */
    protected function wrap($content = '') {
        $attributes = implode(' ', array_map(function($k, $v) {
            return $k.'='.htmlspecialchars($v);
        }, array_keys($this->attributes), array_values($this->attributes)));

        $end = in_array($this->tag, self::CONTENT_LESS) ? '/' : '';

        $html = "<$this->tag $attributes $end>";

        if(!$end) {
            $html .= is_callable($content) ? $content() : $content;

            $html .= "</$this->tag>";
        }

        return $html;
    }

    /**
     * Get Html
     *
     * @return string
     */
    public function getHtml():string {
        return $this->wrap('');
    }

    /**
     * Getter
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws UnknownProperty
     */
    public function __get($name) {
        if(in_array($name, ['tag', 'attributes', 'parent']))
            return $this->$name;

        if($name === 'form')
            return ($this instanceof Form) ? $this : $this->parent;

        throw new UnknownProperty($this, $name);
    }

    /**
     * Setter
     *
     * @param string $name
     * @param mixed $value
     *
     * @throws BadType
     * @throws UnknownProperty
     */
    public function __set($name, $value) {
        if($name === 'attributes') {
            $this->attributes = (array)$value;

        } else if($name === 'parent') {
            if($value && !($value instanceof Fragment))
                throw new BadType('parent', Fragment::class);

            $this->parent = $value;

        } else {
            throw new UnknownProperty($this, $name);
        }
    }
}