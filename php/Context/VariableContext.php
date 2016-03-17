<?php

use JMS\Serializer\Annotation\ExclusionPolicy as ExclusionPolicy;
use JMS\Serializer\Annotation\Expose as Expose;
use JMS\Serializer\Annotation\VirtualProperty as VirtualProperty;

/**
 * @ExclusionPolicy("all")
 */
class VariableContext extends Context {

    /** @Expose  */
    public $name;
    /** @Expose  */
    public $initialized = false;
    /**
     * Indicate whether the variable a function argument.
     * @Expose
     */
    public $argument = false;
    // 0 = global
    /** @Expose  */
    public $function;
    /**
     * List of usages
     * @var VariableUsage[]
     * @Expose
     */
    public $uses = [];

    public function __construct($position, $name) {
        parent::__construct($position);
        $this->name = $name;
    }

    /** @VirtualProperty */
    public function end() {
        return $this->beg() + mb_strlen($this->name, "UTF-8");
    }
}