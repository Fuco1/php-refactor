<?php

require_once __DIR__ . '/../../bootstrap.php';

use Tester\Assert;

/**
 * @author Matus Goljer
 * @testCase
 */
class ParserTest extends MyTestCase {

    private $input1 = '<?php
$foo = 2;
$bar = $foo + \'bar\';
$quz = foo(\'foo\');
$quz = foo($foo);
$quz = foo(function () { return $bar; });
$aaa = 1;
function foo() { return 0; }
';

    private $input2 = '<?php
$x = 1;
$foo =
function () {
   $bar = "foo";
   return $a;
};
$a = 1;
';

    private $input3 = '<?php
function foo() {
    $bar = function () { return; };
}
';

    private $input4 = '<?php
function foo($open, $close) {
    $foo = 2;
    $bar = $open + $baz;
    $baz = function ($x) { return $x.$y.$foo; };
    return $bar;
}';

    public function testSimpleExpressions() {
        $parser = new Parser($this->input1);
        $parser->parse();
        $expressions = $parser->getExpressions();
        Assert::equal(' 2', $expressions[0]->string());
        Assert::equal('$foo + \'bar\'', trim($expressions[1]->string()));
        Assert::equal('foo(\'foo\')', trim($expressions[2]->string()));
        Assert::equal('foo($foo)', trim($expressions[3]->string()));
        Assert::equal('foo(function () { return $bar; })', trim($expressions[4]->string()));
    }

    public function testNestedExpressions() {
        $parser = new Parser($this->input2);
        $parser->parse();
        $expressions = $parser->getExpressions();
        Assert::equal('function () {
   $bar = "foo";
   return $a;
}', trim($expressions[1]->string()));
        Assert::equal('"foo"', trim($expressions[2]->string()));
        Assert::equal('1', trim($expressions[3]->string()));
    }

    public function testForeachExpressions() {
        $parser = new Parser('<?php
foreach ($foo + "bar" as $baz) {
    $a = $a + $baz;
}
$b = $a;
');
        $parser->parse();
        $expressions = $parser->getExpressions();
        Assert::equal('$foo + "bar"', $expressions[0]->string());
        Assert::equal(' $a + $baz', $expressions[1]->string());
        Assert::equal(' $a', $expressions[2]->string());
    }

    public function testVariableUsageAssignedExpression() {
        $parser = new Parser($this->input2);
        $parser->parse();
        $variables = $parser->getVariables();
        Assert::equal(1, $variables[1]['$a']->uses[0]->expression->id);
    }

    public function testSimpleFunctions() {
        $parser = new Parser($this->input1);
        $parser->parse();
        $functions = $parser->getFunctions();
        Assert::equal('function () { return $bar; }', $functions[1]->string());
        Assert::equal('function foo() { return 0; }', $functions[2]->string());
    }

    public function testNestedFunctions() {
        $parser = new Parser($this->input3);
        $parser->parse();
        $functions = $parser->getFunctions();
        Assert::equal('function foo() {
    $bar = function () { return; };
}', $functions[1]->string());
        Assert::equal('function () { return; }', $functions[2]->string());
    }

    /**
     * @dataProvider functionArgumentsProvider
     */
    public function testFunctionArguments($function, $variables) {
        $parser = new Parser($function);
        $parser->parse();
        $vars = $parser->getVariables();
        foreach ($variables as $variable) {
            Assert::true($vars[$variable[0]][$variable[1]]->initialized);
            Assert::true($vars[$variable[0]][$variable[1]]->argument);
        }
    }

    public function functionArgumentsProvider() {
        return array(
            array('<?php function foo($open) { return; }',
                  array([1, '$open'])
            ),
            array('<?php function ($open) { return; }',
                  array([1, '$open'])
            ),
            array('<?php function foo($open) {
return function ($bar) { return; };
}',
                  array([1, '$open'], [2, '$bar'])
            ),
        );
    }

    public function testVariablesInitialization() {
        $parser = new Parser($this->input4);
        $parser->parse();
        $vars = $parser->getVariables();
        $expressions = $parser->getExpressions();
        Assert::equal('$open + $baz', trim($expressions[1]->string()));
        Assert::true($vars[1]['$open']->initialized);
        Assert::true($vars[1]['$foo']->initialized);
        Assert::true($vars[1]['$bar']->initialized);
        // only assignment to first usage should initialize
        Assert::false($vars[1]['$baz']->initialized);
        Assert::true($vars[2]['$x']->initialized);
        Assert::false($vars[2]['$y']->initialized);
        Assert::false($vars[2]['$foo']->initialized);
    }

    public function testVariablePositions() {
        $parser = new Parser($this->input1);
        $parser->parse();
        $vars = $parser->getVariables();
        Assert::equal(7, $vars[0]['$foo']->beg());
        Assert::equal(11, $vars[0]['$foo']->end());
        Assert::equal(17, $vars[0]['$bar']->beg());
        Assert::equal(21, $vars[0]['$bar']->end());

        $parser = new Parser('<?php

$foo = 1;');
        $parser->parse();
        $vars = $parser->getVariables();
        Assert::equal(8, $vars[0]['$foo']->beg());
        Assert::equal(12, $vars[0]['$foo']->end());

        // TODO: abstract these three lines into a helper
        $parser = new Parser('<?php $foo = "ľščťž"; $bar = 1;');
        $parser->parse();
        $vars = $parser->getVariables();
        Assert::equal(23, $vars[0]['$bar']->beg());
    }

    public function testVariableUsageInNestedExpressions() {
        $parser = new Parser('
<?php
function foo() {
    $bar = function ($x) {
        $x = $y;
        $foo = $x;
    };
}');
        $parser->parse();
        $vars = $parser->getVariables();
        // not a part of expression, is on the LHS
        Assert::equal(-1, $vars[1]['$bar']->uses[0]->expression);
        Assert::equal(0, $vars[2]['$x']->uses[0]->expression->id);
        Assert::equal(0, $vars[2]['$x']->uses[1]->expression->id);
        Assert::equal(2, $vars[2]['$x']->uses[2]->expression->id);
        Assert::equal(1, $vars[2]['$y']->uses[0]->expression->id);
        // is part of the outer expression assigned to $bar
        Assert::equal(0, $vars[2]['$foo']->uses[0]->expression->id);
    }

    public function testGetFunctionAtPoint() {
        $parser = new Parser($this->input1);
        $parser->parse();
        Assert::null($parser->getFunctionAtPoint(10));
        Assert::null($parser->getFunctionAtPoint(85));
        Assert::equal(
            'function () { return $bar; }',
            $parser->getFunctionAtPoint(91)->string()
        );
        Assert::equal(
            'function foo() { return 0; }',
            $parser->getFunctionAtPoint(138)->string()
        );

        $parser = new Parser($this->input3);
        $parser->parse();
        Assert::equal(
            'function foo() {
    $bar = function () { return; };
}',
            $parser->getFunctionAtPoint(20)->string()
        );
        Assert::equal(
            'function foo() {
    $bar = function () { return; };
}',
            $parser->getFunctionAtPoint(59)->string()
        );
        Assert::equal(
            'function () { return; }',
            $parser->getFunctionAtPoint(35)->string()
        );
        Assert::equal(
            'function () { return; }',
            $parser->getFunctionAtPoint(58)->string()
        );
    }

    public function testGetFunctionIdAtPoint() {
        $parser = new Parser($this->input1);
        $parser->parse();

        Assert::equal(0, $parser->getFunctionIdAtPoint(10));
        Assert::equal(0, $parser->getFunctionIdAtPoint(85));
        Assert::equal(1, $parser->getFunctionIdAtPoint(91));
        Assert::equal(2, $parser->getFunctionIdAtPoint(138));
    }

    public function testGetVariableAtPoint() {
        $parser = new Parser($this->input1);
        $parser->parse();
        Assert::equal('$foo', $parser->getVariableAtPoint(9)->name);
        Assert::equal('$bar', $parser->getVariableAtPoint(19)->name);
        Assert::equal(0, $parser->getVariableAtPoint(19)->function);
        Assert::equal('$foo', $parser->getVariableAtPoint(27)->name);
        Assert::null($parser->getVariableAtPoint(28));
        Assert::equal('$bar', $parser->getVariableAtPoint(108)->name);
        Assert::equal(1, $parser->getVariableAtPoint(108)->function);

        $parser = new Parser($this->input4);
        $parser->parse();
        Assert::equal('$open', $parser->getVariableAtPoint(22)->name);
        Assert::equal('$foo', $parser->getVariableAtPoint(43)->name);
        Assert::equal(1, $parser->getVariableAtPoint(43)->function);
        Assert::equal('$baz', $parser->getVariableAtPoint(72)->name);
        Assert::equal('$x', $parser->getVariableAtPoint(98)->name);
        Assert::equal('$foo', $parser->getVariableAtPoint(118)->name);
        Assert::equal(2, $parser->getVariableAtPoint(118)->function);
        Assert::equal('$bar', $parser->getVariableAtPoint(138)->name);
    }

    public function testGetVariablesAtPoint() {
        $parser = new Parser('<?php
function foo($a, $b) {
    $tmp = 1;
    return $a + $tmp;
}
echo "foo";
function bar($c, $d) {
    $tmp2 = 1;
    return $d + $tmp2;
}
');
        $parser->parse();
        $variables = $parser->getVariablesAtPoint(41);
        Assert::equal(['$a', '$b', '$tmp'], array_keys($variables));
        $variables = $parser->getVariablesAtPoint(84);
        Assert::equal(['$c', '$d', '$tmp2'], array_keys($variables));
        $variables = $parser->getVariablesAtPoint(72);
        Assert::null($variables);

        $parser = new Parser('<?php $foo = 1;');
        $parser->parse();
        $variables = $parser->getVariablesAtPoint(10);
        Assert::equal(['$foo'], array_keys($variables));
    }
}

$test = new ParserTest();
$test->run();