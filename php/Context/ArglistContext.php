<?php

class ArglistContext extends Context {

    public $parenDepth;
    public $opened = true;

    /**
     * List of variables in the argument list
     *
     * @var VariableContext[]
     */
    public $variables = [];

    public function __construct($position, $parenDepth) {
        parent::__construct($position);
        $this->parenDepth = $parenDepth;
    }
}