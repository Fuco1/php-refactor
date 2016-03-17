<?php

use JMS\Serializer\Annotation\VirtualProperty as VirtualProperty;
use JMS\Serializer\Annotation\ExclusionPolicy as ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ExpressionContext extends Context {

    use TextualContext;

    public $parenDepth;
    public $curlyDepth;

    public function __construct($position, $parenDepth, $curlyDepth, $id) {
        parent::__construct($position);
        $this->parenDepth = $parenDepth;
        $this->curlyDepth = $curlyDepth;
        $this->id = $id;
    }

    /** @VirtualProperty */
    public function end() {
        return $this->beg() + $this->length();
    }
}
