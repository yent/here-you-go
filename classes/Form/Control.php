<?php


namespace HereYouGo\Form;


use HereYouGo\Exception\UnknownProperty;
use HereYouGo\UI\Locale;
use HereYouGo\UI\Translation;

/**
 * Class Control
 *
 * @package HereYouGo\Form
 *
 * @property-read string $action
 * @property string $resource
 * @property string $goto
 * @property string $label
 * @property string $prompt
 * @property string $disabled
 */
abstract class Control extends Fragment {
    /** @var string */
    protected $resource = '';

    /** @var string */
    protected $goto = '';

    /** @var string|Translation */
    protected $label = '';

    /** @var string|Translation */
    protected $prompt = '';

    /** @var bool */
    protected $disabled = false;

    /**
     * Control constructor.
     *
     * @param string $resource
     * @param string $goto
     * @param Translation|string $label
     * @param Translation|string $prompt
     * @param bool $disabled
     */
    public function __construct($resource, $goto = '', $label = '', $prompt = '', $disabled = false) {
        $this->resource = $resource;
        $this->goto = $goto;
        $this->label = (string)$label;
        $this->prompt = (string)$prompt;
        $this->disabled = $disabled;

        parent::__construct('button', ['type' => 'submit']);
    }

    /**
     * Get HTML
     *
     * @return string
     */
    public function getHtml() {
        $attributes = ['data-action' => $this->action];

        foreach(['resource', 'goto', 'prompt'] as $k)
            if($this->$k)
                $attributes["data-$k"] = $this->$k;

        if($this->disabled)
            $attributes['disabled'] = 'disabled';

        $this->addAttributes($attributes, false);

        $label = $this->label ? $this->label : Locale::translate('form.action.'.$this->action);

        return $this->wrap((string)$label);
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
        if(in_array($name, ['resource', 'goto', 'label', 'prompt', 'disabled']))
            return $this->$name;

        if($name === 'action')
            return strtolower(substr(static::class, strrpos(static::class, '\\')));

        throw new UnknownProperty($this, $name);
    }

    /**
     * Setter
     *
     * @param string $name
     * @param mixed $value
     *
     * @throws UnknownProperty
     */
    public function __set($name, $value) {
        if(in_array($name, ['resource', 'goto', 'label', 'prompt'])) {
            $this->$name = (string)$value;

        } else if($name === 'disabled') {
            $this->disabled = (bool)$value;
        }

        throw new UnknownProperty($this, $name);
    }
}