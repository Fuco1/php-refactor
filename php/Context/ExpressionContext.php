<?php

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
}