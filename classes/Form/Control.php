<?php


namespace HereYouGo\Form;


abstract class Control extends Fragment {
    /** @var string */
    protected $action = '';

    /** @var string */
    protected $label = '';

    /** @var string */
    protected $resource = '';

    // TODO state ...

    /**
     * Control constructor.
     *
     * @param string $action
     * @param string $label
     * @param string $resource
     */
    public function __construct($action, $label = '', $resource = '') {
        parent::__construct('button', ['type' => 'submit', 'data-action' => $action]);
    }
}