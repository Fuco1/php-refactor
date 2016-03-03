<?php

abstract class Context {

    public $position;
    public $id;

    public function __construct($position) {
        $this->position = $position;
    }
}