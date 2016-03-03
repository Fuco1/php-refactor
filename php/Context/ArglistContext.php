<?php

class ArglistContext extends Context {

    public $parenDepth;
    public $opened = true;
    // TODO: store the variablecontexts here?
    public $variables = [];

    public function __construct($position, $parenDepth) {
        parent::__construct($position);
        $this->parenDepth = $parenDepth;
    }
}