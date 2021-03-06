<?php


namespace HereYouGo\Form;


use HereYouGo\Exception\BadType;
use HereYouGo\Form\Exception\ValidationFailed;

class FieldSet extends Traversable {
    /** @var (Field|DataHolder|Fragment|string)[] */
    protected $fields = [];

    /**
     * FieldSet constructor.
     *
     * @param array $fields
     *
     * @throws BadType
     */
    public function __construct(array $fields) {
        foreach((array)$fields as $field)
            $this->addField($field);

        parent::__construct('div');
    }

    /**
     * Add field to set
     *
     * @param DataHolder|Fragment|string $field
     *
     * @throws BadType
     */
    public function addField($field) {
        if(!($field instanceof DataHolder) && !($field instanceof Fragment) && !is_string($field))
            throw new BadType('field', DataHolder::class.' or '.Fragment::class.' or string');

        if($field instanceof Fragment)
            $field->parent = $this;

        $this->fields[] = $field;
    }

    /**
     * Find node
     *
     * @param string $path
     *
     * @return DataHolder|null
     */
    public function find($path) {
        foreach($this->fields as $field) {
            if($field instanceof Field) {
                if($field->getPath() === $path)
                    return $field;

            }

            if($field instanceof Traversable) {
                $found = $field->find($path);
                if($found)
                    return $found;
            }
        }

        return null;
    }

    /**
     * Build HTML
     *
     * @return string
     */
    public function getHtml(): string {
        return $this->wrap(function() {
            return implode('', array_map(function($field) {
                if($field instanceof DataHolder)
                    return $field->getHtml();

                if($field instanceof Fragment)
                    return $field->wrap();

                return (string)$field;
            }, $this->fields));
        });
    }

    /**
     * Validate own data
     *
     * @param mixed $data
     *
     * @throws BadType
     * @throws ValidationFailed
     */
    public function validate($data) {
        if(!is_array($data))
            throw new BadType($this->getPath().' data', 'array');

        foreach($this->fields as $field) {
            if(!($field instanceof DataHolder)) continue;

            if($field instanceof Field) {
                if(!array_key_exists($field->name, $data) && !$field->hasConstraint('required'))
                    continue;

                if(!array_key_exists($field->name, $data))
                    throw new ValidationFailed($field, $field->constraints->collection['required']);

                $field->validate($data[$field->name]);

                $field->value = $data[$field->name];

            } else {
                $field->validate($data);
            }
        }
    }

    /**
     * Get holder path
     *
     * @return string
     */
    public function getPath(): string {
        return $this->parent ? $this->parent->getPath() : '';
    }
}