<?php

namespace HereYouGo\Form;

use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;

/**
 * Class Fragment
 *
 * @package HereYouGo
 *
 * @property-read string $tag
 * @property string[] $attributes
 * @property Fragment $parent
 */
class Fragment {
    /** @var string */
    private $tag = '';

    /** @var string[] */
    private $attributes = [];

    /** @var self */
    private $parent = null;

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
     * Generate HTML
     *
     * @param string|callable $content
     *
     * @return string
     */
    public function render($content) {
        $attributes = implode(' ', array_map(function($k, $v) {
            return $k.'='.htmlspecialchars($v);
        }, array_keys($this->attributes), array_values($this->attributes)));

        $html = "<$this->tag $attributes>";

        $html .= is_callable($content) ? $content() : $content;

        $html .= "</$this->tag>";

        return $html;
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