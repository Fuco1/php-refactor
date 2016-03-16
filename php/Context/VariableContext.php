<?php

class VariableContext extends Context {
    public $name;
    public $initialized = false;
    /**
     * Indicate whether the variable a function argument.
     */
    public $argument = false;
    // 0 = global
    public $function;
    /**
     * List of usages
     * @var VariableUsage[]
     */
    public $uses = [];

    public function __construct($position, $name) {
        parent::__construct($position);
        $this->name = $name;
    }

    public function end() {
        return $this->beg() + mb_strlen($this->name, "UTF-8");
    }

    public function export() {
        $export = parent::export();
        return array_merge($export, array(
            'name' => $this->name,
            'initialized' => $this->initialized,
            'argument' => $this->argument,
            'function' => $this->function,
            'uses' => array_map(function ($u) { return $u->export(); }, $this->uses)
        ));
    }
}