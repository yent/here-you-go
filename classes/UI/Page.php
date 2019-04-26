<?php


namespace HereYouGo\UI;


use HereYouGo\Exception\UnknownProperty;
use HereYouGo\UI\Exception\TemplateNotFound;

/**
 * Class Page
 *
 * @package HereYouGo\UI
 *
 * @property-read string $id
 */
class Page {
    /** @var string */
    private $id = '';

    /** @var array */
    private $vars = [];

    /**
     * Page constructor.
     *
     * @param string $id
     * @param array $vars
     */
    public function __construct($id, array $vars = []) {
        $this->id = $id;
        $this->vars = $vars;
    }

    /**
     * Display page
     *
     * @throws TemplateNotFound
     */
    public function display() {
        Template::resolve('header')->display(['page-id' => $this->id]);

        Template::resolve($this->id.'.page')->display($this->vars);

        Template::resolve('footer')->display();
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
        if(in_array($name, ['id']))
            return $this->$name;

        throw new UnknownProperty($this, $name);
    }
}