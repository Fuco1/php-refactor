<?php

/**
 * A trait enabling a Context to record its textual representation.
 *
 * @author Matus Goljer
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
     */
    public function string() {
        return implode('', $this->text);
    }
}
