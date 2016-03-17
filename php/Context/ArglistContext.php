<?php

use JMS\Serializer\Annotation\ExclusionPolicy as ExclusionPolicy;
use JMS\Serializer\Annotation\Expose as Expose;

/**
 * @ExclusionPolicy("all")
 */
class ArglistContext extends Context {

    public $parenDepth;
    public $opened = true;

    /**
     * List of variables in the argument list
     *
     * @var VariableContext[]
     * @Expose
     */
    public $variables = [];

    public function __construct($position, $parenDepth) {
        parent::__construct($position);
        $this->parenDepth = $parenDepth;
    }
}