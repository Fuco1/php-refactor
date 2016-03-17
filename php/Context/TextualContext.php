<?php

use JMS\Serializer\Annotation\VirtualProperty as VirtualProperty;
use JMS\Serializer\Annotation\ExclusionPolicy as ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName as SerializedName;

/**
 * A trait enabling a Context to record its textual representation.
 *
 * @ExclusionPolicy("all")
 */
trait TextualContext {

    /**
     * Array of token strings making up this context.
     */
    public $text = [];

    /**
     * Return the code string spanning this context.
     *
     * @return string Code
     * @VirtualProperty
     * @SerializedName("text")
     */
    public function string() {
        return implode('', $this->text);
    }

    public function length() {
        return mb_strlen($this->string(), "UTF-8");
    }
}
