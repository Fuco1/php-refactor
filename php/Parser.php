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
        $functionId = $this->getFunctionIdAtPoint($point);

        $variables = $this->getVariables()[$functionId];
        foreach ($variables as $name => $variable) {
            foreach ($variable->uses as $usage) {
                if ($usage->beg() <= $point &&
                    $point < ($usage->beg() + mb_strlen($name, "UTF-8"))) {
                    return $variable;
                }
            }
        }

        return null;
    }

    public function getExpressions() {
        return $this->expressions;
    }

    public function getExpressionsAtPoint($point) {
        $expressions = [];
        foreach ($this->getExpressions() as $expression) {
            if ($expression->beg() <= $point && $point < $expression->end()) {
                $expressions[] = $expression;
            }
        }
        return $expressions;
    }

    public function getFunctions() {
        return $this->functions;
    }

    /**
     * Return id of the function at point
     *
     * @param int $point
     * @return int Function id or 0 for global scope
     */
    public function getFunctionIdAtPoint($point) {
        $function = $this->getFunctionAtPoint($point);
        $functionId = 0; // global scope by default
        if (isset($function)) {
            $functionId = $function->id;
        }
        return $functionId;
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

    /**
     * Return variables defined in current function's scope.
     *
     * @param int $point
     * @return VariableContext[]
     */
    public function getVariablesAtPoint($point) {
        $functionId = $this->getFunctionIdAtPoint($point);
        $variables = $this->getVariables();
        if (isset($variables[$functionId])) {
            return $variables[$functionId];
        }
        return null;
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

        // 0 = foreach is not initialized
        // 1 = T_FOREACH read
        // 2 = ( read, next token begins an expression
        // 3 = waiting for T_AS
        $foreach = 0;

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                $token = [0, $token, 0];
            }

            // $position = sprintf("% 4d", $this->position);
            // echo "($position) Token name: " . token_name($token[0]) . " '{$token[1]}'\n";
            if ($foreach === 2) {
                $expression = new ExpressionContext(
                    $this->position, // +1 for the = character
                    $parenDepth,
                    $curlyDepth,
                    $this->expressionId++
                );
                $expressions[] = $expression;
                $this->expressions[$expression->id] = $expression;
                $foreach++;
            }

            foreach ($expressions as $expr) {
                $expr->text[] = $token[1];
            }

            foreach ($functions as $func) {
                $func->text[] = $token[1];
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
                        $this->debug("(in expression {$expression->id}:{$expression->position}) ");
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
                        isset($expression) ? $expression :
                        (isset($function->arglist->opened) ? 0 : -1));
                    break;
                case T_FOREACH:
                    $foreach = 1;
                    break;
                case T_AS:
                    if ($foreach === 3) {
                        $expression = $this->closeExpression(
                            $expression, $expressions,
                            $parenDepth, $curlyDepth
                        );
                        $foreach = 0;
                    }
                    break;
            }

            switch ($token[1]) {
                case '(':
                    if ($foreach === 1) { $foreach++; }
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
                        $this->position + 1, // +1 for the = character
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
                        $values = array_values($variable->uses);
                        $lastUsage = end($values);
                        $lastUsage->assignedExpression = $expression;
                    }
                    break;
                case ';':
                    $expression = $this->closeExpression(
                        $expression, $expressions,
                        $parenDepth, $curlyDepth
                    );
                    break;
            }
            $this->position += mb_strlen($token[1], "UTF-8");
        }

        //var_export($variables);
        $this->variables = $variables;
    }

    // TODO: vytvorit objekt "ParseState"
    protected function closeExpression($expression, &$expressions, $parenDepth, $curlyDepth) {
        if (!is_null($expression)) {
            // TODO: vytvorit helper na porovnanie "balancu"
            if ($parenDepth === $expression->parenDepth &&
                $curlyDepth === $expression->curlyDepth) {
                // sme na konci expression
                // we don't want the closing expression
                array_pop($expression->text);
                // also get rid of any trailing whitespace
                for ($i = count($expression->text) - 1; $i >=0; $i--) {
                    if (trim($expression->text[$i]) == '') {
                        unset($expression->text[$i]);
                    } else {
                        break;
                    }
                }
                $this->debug("Expression ({$expression->position}-{$this->position}): " . $expression->string() . PHP_EOL);
                // TODO: abstract the following expressions dealing
                // with "reseting" the top;
                array_pop($expressions);
                $expression = end($expressions);
                if (!$expression) {
                    $expression = null;
                }
            }
        }
        return $expression;
    }

    protected function debug($str) {
        if ($this->debug) {
            echo $str;
        }
    }
}