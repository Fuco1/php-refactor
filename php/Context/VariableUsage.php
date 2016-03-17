<?php

use JMS\Serializer\Annotation\ExclusionPolicy as ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName as SerializedName;
use JMS\Serializer\Annotation\Expose as Expose;

/**
 * @ExclusionPolicy("all")
 */
class VariableUsage extends Context {

    /** @Expose */
    public $expression = -1; // -1 = uninitialized
    /**
     * @Expose
     * @SerializedName("assignedExpression")
     */
    public $assignedExpression = -1;

    public function __construct($position, $expression) {
        parent::__construct($position);
        $this->expression = $expression;
    }
}