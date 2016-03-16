<?php

class Parser {

    private $debug = false;

    private $data;
    private $position;
    // 0 is global variable;
    private $functionId = 1;
    // TODO: make it so that 0 is the "no" expression, so json export
    // works properly
    private $expressionId = 0;
    private $variables;
    private $expressions;
    private $functions;

    public function __construct($data) {
        // replace \r\n with \n
        $this->data = preg_replace("/\r\n/", "\n", $data);
        $this->position = 1;
    }

    public function getVariables() {
        return $this->variables;
    }

    public function getVariableAtPoint($point) {
        $function = $this->getFunctionAtPoint($point);
        $functionId = 0; // global scope by default
        if (isset($function)) {
            $functionId = $function->id;
        }

        $variables = $this->getVariables()[$functionId];
        foreach ($variables as $name => $variable) {
            foreach ($variable->uses as $usage) {
                if ($usage->position <= $point &&
                    $point < ($usage->position + mb_strlen($name, "UTF-8"))) {
                    return $variable;
                }
            }
        }

        return null;
    }

    public function getExpressions() {
        return $this->expressions;
    }

    public function getFunctions() {
        return $this->functions;
    }

    /**
     * Return function at point
     *
     * @param int $point
     * @return FunctionContext|null Function context containing $point
     *         or null if point lies outside any function.
     */
    public function getFunctionAtPoint($point) {
        $last = null;
        foreach ($this->functions as $function) {
            if ($function->beg() > $point) {
                return $last;
            }

            if ($function->beg() <= $point && $point <= $function->end()) {
                $last = $function;
            }
        }
        return $last;
    }

    public function parse() {
        $tokens = token_get_all($this->data);

        $curlyDepth = 0;
        $parenDepth = 0;
        // last expression... separate variable for convenience
        // TODO: make into a getter method?
        $expression = null;
        // stack of expressions
        $expressions = [];
        // last function... separate variable for convenience
        // TODO: make into a getter method?
        $function = null;
        // stack of functions
        $functions = [];
        // last function... separate variable for convenience
        // TODO: make into a getter method?
        $variable = null;
        $variables = [];

        $this->expressions = [];
        $this->functions = [];

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                $token = [0, $token, 0];
            }

            // $position = sprintf("% 4d", $this->position);
            // echo "($position) Token name: " . token_name($token[0]) . " '{$token[1]}'\n";

            foreach ($expressions as $expression) {
                $expression->text[] = $token[1];
            }

            foreach ($functions as $function) {
                $function->text[] = $token[1];
            }

            switch ($token[0]) {
                case T_FUNCTION:
                    $function = new FunctionContext(
                        $this->position,
                        // TODO: presunut do triedy a vytvorit helper
                        // na vytvaranie kontextu
                        $parenDepth,
                        $curlyDepth,
                        $this->functionId++
                    );
                    $function->text[] = $token[1];
                    $functions[] = $function;
                    $this->functions[$function->id] = $function;
                    break;
                case T_VARIABLE:
                    $variable = new VariableContext($this->position, $token[1]);
                    $end = $this->position + mb_strlen($token[1], "UTF-8");
                    $this->debug("Variable ({$this->position}-{$end}) ");
                    if (!is_null($function)) {
                        $this->debug("(in function {$function->id}) ");
                        $variable->function = $function->id;
                    } else {
                        $variable->function = 0;
                    }
                    if (!is_null($expression)) {
                        $this->debug("(in expression {$expression->position}) ");
                    }
                    $this->debug("{$token[1]}" . PHP_EOL);

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
                        $function->arglist->variables[] =
                            $variables[$function->id][$token[1]];
                        $variable->argument = true;
                    }

                    $variable->uses[] = new VariableUsage(
                        $this->position,
                        // FIXME: we need to resolve expressions to
                        // current function scope too
                        isset($expression) ? $expression->id :
                        (isset($function->arglist->opened) ? 0 : -1));
                    break;
            }

            switch ($token[1]) {
                case '(':
                    if (!is_null($function)) {
                        if (is_null($function->arglist)) {
                            $function->arglist = new ArglistContext(
                                $this->position, $parenDepth
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
                            $this->debug("Function {$function->id} ({$function->position}-{$this->position}): " . $function->string() . PHP_EOL);
                            array_pop($functions);
                            $function = end($functions);
                            if (!$function) {
                                $function = null;
                            }
                        }
                    }
                    break;
                case '=':
                    // TODO: mozno su aj ine typy "vyrazu", toto je proste vyraz ktory sa priraduje.
                    $expression = new ExpressionContext(
                        // TODO: update to first non-whitespace token
                        $this->position,
                        $parenDepth,
                        $curlyDepth,
                        $this->expressionId++
                    );
                    $expressions[] = $expression;
                    $this->expressions[$expression->id] = $expression;
                    // Any variable which is active at this point must
                    // be being assigned into.  Only set the
                    // initialized state if this is the first usage.
                    // TODO: preverit, zda je tohle logicky spravne
                    if (count($variable->uses) === 1) {
                        $variable->initialized = true;
                    }
                    if (isset($variable)) {
                        $lastUsage = end(array_values($variable->uses));
                        $lastUsage->assignedExpression = $expression;
                    }
                    break;
                case ';':
                    if (!is_null($expression)) {
                        // TODO: vytvorit helper na porovnanie "balancu"
                        if ($parenDepth === $expression->parenDepth &&
                            $curlyDepth === $expression->curlyDepth) {
                            // sme na konci expression
                            $this->debug("Expression ({$expression->position}-{$this->position}): " . $expression->string() . PHP_EOL);
                            // TODO: abstract the following
                            // expressions dealing with "reseting" the
                            // top;
                            array_pop($expressions);
                            $expression = end($expressions);
                            if (!$expression) {
                                $expression = null;
                            }
                        }
                    }
                    break;
            }
            $this->position += mb_strlen($token[1], "UTF-8");
        }

        //var_export($variables);
        $this->variables = $variables;
    }

    protected function debug($str) {
        if ($this->debug) {
            echo $str;
        }
    }
}