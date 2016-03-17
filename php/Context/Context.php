<?php

use JMS\Serializer\Annotation\ExclusionPolicy as ExclusionPolicy;
use JMS\Serializer\Annotation\Expose as Expose;
use JMS\Serializer\Annotation\VirtualProperty as VirtualProperty;

/**
 * @ExclusionPolicy("all")
 */
abstract class Context implements JsonSerializable {

    public $position;
    /** @Expose */
    public $id;

    public function __construct($position) {
        $this->position = $position;
    }

    /** @VirtualProperty */
    public function beg() {
        return $this->position;
    }

    public function end() {
        return $this->beg();
    }

    public function jsonSerialize() {
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        return json_decode($serializer->serialize($this, 'json'), true);
    }

    public function __toString() {
        return json_encode($this);
    }
}