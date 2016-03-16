<?php

class VariableUsage extends Context {

    public $expression = -1; // -1 = uninitialized

    public function __construct($position, $expression) {
        parent::__construct($position);
        $this->expression = $expression;
    }
}