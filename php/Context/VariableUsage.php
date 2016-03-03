<?php

class VariableUsage {

    public $position;
    public $expression = -1; // -1 = uninitialized

    public function __construct($position, $expression) {
        $this->position = $position;
        $this->expression = $expression;
    }
}