<?php

class VariableUsage extends Context {

    public $expression = -1; // -1 = uninitialized
    public $assignedExpression = -1;

    public function __construct($position, $expression) {
        parent::__construct($position);
        $this->expression = $expression;
    }

    public function export() {
        $export = parent::export();
        return array_merge($export, array(
            'expression' => is_object($this->expression) ?
            $this->expression->export() :
            $this->expression,
            'assignedExpression' => is_object($this->assignedExpression) ?
            $this->assignedExpression->export() :
            $this->assignedExpression,
        ));
    }
}