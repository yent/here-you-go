<?php


namespace HereYouGo;


use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Form\Control;
use HereYouGo\Form\Field;
use HereYouGo\Form\Fragment;

/**
 * Class Form
 *
 * @package HereYouGo
 *
 * @property-read string $name
 */
class Form extends Fragment {
    /** @var string */
    private $name = '';

    /** @var (Field|string)[] */
    private $fields = [];

    /** @var (Control|string)[] */
    private $controls = [];

    /**
     * Form constructor.
     *
     * @param string $name
     * @param array $fields
     * @param array $controls
     * @throws BadType
     */
    public function __construct($name, array $fields = [], array $controls = []) {
        parent::__construct('form', ['data-name' => $name, 'method' => 'post', 'action' => '']);

        $this->name = $name;

        foreach($fields as $field)
            $this->addField($field);

        foreach($controls as $control)
            $this->addControl($control);
    }

    /**
     * Add component
     *
     * @param string $type
     * @param Fragment|string $thing
     *
     * @throws BadType
     */
    private function add($type, $thing) {
        if(!in_array($type, ['field', 'control']))
            throw new BadType('type', '"fields" or "controls"');

        if(!($thing instanceof Fragment) && !is_string($thing))
            throw new BadType($type, Fragment::class.' or string');

        if($thing instanceof Fragment)
            $thing->parent = $this;

        $type .= 's';
        $this->{$type}[] = $thing;
    }

    /**
     * Add field to set
     *
     * @param Fragment|string $field
     *
     * @throws BadType
     */
    public function addField($field) {
        $this->add('field', $field);
    }

    /**
     * Add control to set
     *
     * @param Fragment|string $control
     *
     * @throws BadType
     */
    public function addControl($control) {
        $this->add('control', $control);
    }

    /**
     * Generate HTML
     *
     * @return string
     */
    public function getHtml() {
        return parent::render(function() {
            $html = '';

            foreach($this->fields as $field)
                $html .= ($field instanceof Field) ? $field->getHtml() : (string)$field;

            if($this->controls) {
                $html .= (new Fragment('div', ['class' => 'form-row justify-content-center'], $this))->render(function() {
                    $html = '';

                    foreach($this->controls as $control)
                        $html .= ($control instanceof Field) ? $control->getHtml() : (string)$control;

                    return $html;
                });
            }

            return $html;
        });
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
        if(in_array($name, ['name']))
            return $this->$name;

        throw new UnknownProperty($this, $name);
    }
}