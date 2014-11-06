<?php

namespace PhpParser;

class ElispNodeDumper
{
    /**
     * Dumps a node or array.
     *
     * @param array|Node $node Node or array to dump
     *
     * @return string Dumped value
     */
    public function dump($node) {
        if ($node instanceof Node) {
            $r = '(' . $node->getType()
                . " (:beg . " . $node->getAttribute('begFilePos') . ')'
                . " (:end . " . $node->getAttribute('endFilePos') . ') ';
        } elseif (is_array($node)) {
            $r = '(';
        } else {
            throw new \InvalidArgumentException('Can only dump nodes and arrays.');
        }

        foreach ($node as $key => $value) {
            if (!is_numeric($key)) {
                $r .= "(:" . $key . ' . ';
            }

            if (null === $value || false === $value) {
                $r .= 'nil' . $this->close($key);
            } elseif (true === $value) {
                $r .= 't' . $this->close($key);
            } elseif (is_string($value)) {
                $r .= '"' . addslashes($value) . '"' . $this->close($key);
            } elseif (is_scalar($value)) {
                $r .= $value . $this->close($key);
            } else {
                $r .= $this->dump($value) . $this->close($key);
            }
        }

        return $r . ')';
    }

    private function close($key) {
        if (is_numeric($key)) { return ""; }
        else { return ")"; }
    }
}