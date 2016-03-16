<?php

abstract class Context {

    public $position;
    public $id;

    public function __construct($position) {
        $this->position = $position;
    }

    public function beg() {
        return $this->position;
    }

    public function export() {
        return array(
            'position' => $this->position,
            'id' => $this->id
        );
    }

    public function __toString() {
        return json_encode($this->export());
    }
}