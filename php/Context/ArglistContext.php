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
        $a = function ($position) { return $a; };
        $a = $this->export($position);
        $this->parenDepth = $parenDepth;
    }

    public function export() {
        $export = parent::export();
        return array_merge($export, array(
            'variables' => array_map(function ($v) { return $v->export(); }, $this->variables)
        ));
    }
}