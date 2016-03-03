<?php

class Parser {

    private $data;
    private $position;
    private $functionId = 0;
    private $expressionId = 0;
    private $variables;
    private $expressions;
    private $functions;

    public function __construct($data) {
        $this->data = $data;
        $this->position = 0;
    }

    public function getVariables() {
        return $this->variables;
    }

    public function getExpressions() {
        return $this->expressions;
    }

    // TODO: add "getFunctionByName"
    public function getFunctions() {
        return $this->functions;
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
                            echo "Function {$function->id} ({$function->position}-{$this->position}): " . $function->string(), PHP_EOL;
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
                    break;
                case ';':
                    if (!is_null($expression)) {
                        // TODO: vytvorit helper na porovnanie "balancu"
                        if ($parenDepth === $expression->parenDepth &&
                            $curlyDepth === $expression->curlyDepth) {
                            // sme na konci expression
                            echo "Expression ({$expression->position}-{$this->position}): " . $expression->string(), PHP_EOL;
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
            $this->position += strlen($token[1]);
        }

        var_export($variables);
        $this->variables = $variables;
    }
}