<?php

use JMS\Serializer\Annotation\ExclusionPolicy as ExclusionPolicy;
use JMS\Serializer\Annotation\Expose as Expose;
use JMS\Serializer\Annotation\VirtualProperty as VirtualProperty;

/**
 * @ExclusionPolicy("all")
 */
class FunctionContext extends Context {

    use TextualContext;

    public $parenDepth;
    public $curlyDepth;
    /** @Expose */
    public $arglist = null;

    public function __construct($position, $parenDepth, $curlyDepth, $id) {
        parent::__construct($position);
        $this->parenDepth = $parenDepth;
        $this->curlyDepth = $curlyDepth;
        $this->id = $id;
    }

    public function end() {
        return $this->beg() + $this->length();
    }

    public function containsPoint($point) {
        return $this->beg() <= $point && $point < $this->end();
    }
}