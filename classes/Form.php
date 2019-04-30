<?php


namespace HereYouGo;


use HereYouGo\Exception\BadType;
use HereYouGo\Exception\UnknownProperty;
use HereYouGo\Form\Control;
use HereYouGo\Form\DataHolder;
use HereYouGo\Form\Field;
use HereYouGo\Form\FieldSet;
use HereYouGo\Form\Fragment;

/**
 * Class Form
 *
 * @package HereYouGo
 *
 * @property-read string $name
 */
class Form extends FieldSet {
    /** @var string */
    protected $name = '';

    /** @var (Control|string)[] */
    protected $controls = [];

    /**
     * Form constructor.
     *
     * @param string $name
     * @param array $fields
     * @param array $controls
     *
     * @throws BadType
     */
    public function __construct($name, array $fields = [], array $controls = []) {
        parent::__construct($fields);

        $this->tag = 'form';
        $this->addAttributes(['data-name' => $name, 'method' => 'post', 'action' => ''], false);

        $this->name = $name;

        foreach($controls as $control)
            $this->addControl($control);
    }

    /**
     * Add control to set
     *
     * @param Control|string $control
     *
     * @throws BadType
     */
    public function addControl($control) {
        if(!($control instanceof Fragment) && !is_string($control))
            throw new BadType('control', Fragment::class.' or string');

        if($control instanceof Fragment)
            $control->parent = $this;

        $this->controls[] = $control;
    }

    /**
     * Generate HTML
     *
     * @return string
     */
    public function getHtml(): string {
        return parent::wrap(function() {
            $html = '';

            foreach($this->fields as $field)
                $html .= ($field instanceof DataHolder) ? $field->getHtml() : (string)$field;

            if($this->controls) {
                $html .= (new Fragment('div', ['class' => 'form-row justify-content-center'], $this))->wrap(function() {
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

        return parent::__get($name);
    }
}