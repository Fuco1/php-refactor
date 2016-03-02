<?php

class ExpressionContext {
    public $parenDepth;
    public $curlyDepth;
    public $position;
    public $text = [];

    public function __construct($parenDepth, $curlyDepth, $position) {
        $this->parenDepth = $parenDepth;
        $this->curlyDepth = $curlyDepth;
        $this->position = $position;
    }
}

class ArglistContext {
    public $parenDepth;
    public $position;
    public $opened = true;
    public $variables = [];

    public function __construct($parenDepth, $position) {
        $this->parenDepth = $parenDepth;
        $this->position = $position;
    }
}

class FunctionContext {
    public $parenDepth;
    public $curlyDepth;
    public $position;
    public $text = [];
    public $id;
    public $arglist = null;

    public function __construct($parenDepth, $curlyDepth, $position, $id) {
        $this->parenDepth = $parenDepth;
        $this->curlyDepth = $curlyDepth;
        $this->position = $position;
        $this->id = $id;
    }
}

class VariableContext {
    public $name;
    public $position;
    public $initialized = false;
    // 0 = global
    public $function;
    /**
     * List of positions where the variable is used.
     */
    public $uses = [];

    public function __construct($name, $position) {
        $this->name = $name;
        $this->position = $position;
    }
}

class Tokenizer {

    private $data;
    private $position;
    private $functionId = 0;

    public function __construct($fileName) {
        $this->data = file_get_contents($fileName);
        $this->position = 0;
    }

    public function parse() {
        $tokens = token_get_all($this->data);

        $curlyDepth = 0;
        $parenDepth = 0;
        // musi byt stack
        $expression = null;
        // musi byt stack
        $function = null;
        $variables = [];

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                $token = [0, $token, 0];
            }

            if (!is_null($expression)) {
                $expression->text[] = $token[1];
            }

            if (!is_null($function)) {
                $function->text[] = $token[1];
            }

            switch ($token[0]) {
                case T_FUNCTION:
                    $function = new FunctionContext(
                        // TODO: presunut do triedy a vytvorit helper
                        // na vytvaranie kontextu
                        $parenDepth,
                        $curlyDepth,
                        $this->position,
                        $this->functionId++
                    );
                    $function->text[] = $token[1];
                    break;
                case T_VARIABLE:
                    $variable = new VariableContext($token[1], $this->position);

                    echo "Variable ";
                    if (!is_null($function)) {
                        echo "(in function {$function->id}) ";
                        $variable->function = $function->id;
                    } else {
                        $variable->function = 0;
                    }
                    if (!is_null($expression)) {
                        echo "(in expression {$expression->position}) ";
                    }
                    echo "{$token[1]}", PHP_EOL;

                    if (!isset($variables[$variable->function][$variable->name])) {
                        $variables[$variable->function][$variable->name] = $variable;
                        // resolve if the variable is initialized
                        if (isset($function->arglist->opened)) {
                            $variable->initialized = true;
                        }
                    } else {
                        $variable = $variables[$variable->function][$variable->name];
                    }

                    // handle function argument list
                    if (isset($function->arglist->opened)) {
                        $function->arglist->variables[] = $token[1];
                    }

                    $variable->uses[] = $this->position;
                    break;
            }

            switch ($token[1]) {
                case '(':
                    if (!is_null($function)) {
                        if (is_null($function->arglist)) {
                            $function->arglist = new ArglistContext(
                                $parenDepth, $this->position
                            );
                        }
                    }
                    $parenDepth++;
                    break;
                case ')':
                    $parenDepth--;
                    if (!is_null($function)) {
                        if (!is_null($function->arglist)) {
                            if ($function->arglist->parenDepth === $parenDepth) {
                                $function->arglist->opened = null;
                                echo "Arglist: " . implode(', ', $function->arglist->variables), PHP_EOL;
                            }
                        }
                    }
                    break;
                case '{':
                    $curlyDepth++;
                    break;
                case '}':
                    $curlyDepth--;
                    if (!is_null($function)) {
                        if ($parenDepth === $function->parenDepth &&
                            $curlyDepth === $function->curlyDepth) {
                            echo "Function {$function->id} ({$function->position}-{$this->position}): " . implode('', $function->text), PHP_EOL;
                            $function = null;
                        }
                    }
                    break;
                case '=':
                    $expression = new ExpressionContext(
                        $parenDepth,
                        $curlyDepth,
                        // TODO: update to first non-whitespace token
                        $this->position
                    );
                    break;
                case ';':
                    if (!is_null($expression)) {
                        // TODO: vytvorit helper na porovnanie "balancu"
                        if ($parenDepth === $expression->parenDepth &&
                            $curlyDepth === $expression->curlyDepth) {
                            // sme na konci expression
                            echo "Expression ({$expression->position}-{$this->position}): " . implode('', $expression->text), PHP_EOL;
                            $expression = null;
                        }
                    }
                    break;
            }
            $this->position += strlen($token[1]);
        }

        var_export($variables);
    }
}

$tok = new Tokenizer('example.php');
$tok->parse();