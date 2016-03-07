<?php

require __DIR__ . '/../../bootstrap.php';

use Tester\Assert;
use Tester\TestCase;

/**
 * @author Matus Goljer
 * @testCase
 */
class ParserTest extends TestCase {

    private $input1 = '
<?php
$foo = 2;
$bar = $foo + \'bar\';
$quz = foo(\'foo\');
$quz = foo($foo);
$quz = foo(function () { return $bar; });

function foo() { return 0; }
';

    private $input2 = '
<?php
$foo =
function () {
   $bar = "foo";
   return 0;
};
';

    private $input3 = '
<?php
function foo() {
    $bar = function () { return; };
}
';

    private $input4 = '
<?php
function foo($open, $close) {
    $foo = 2;
    $bar = $open + $baz;
    $baz = function ($x) { return $x.$y; };
    return $bar;
}';

    public function testSimpleExpressions() {
        $parser = new Parser($this->input1);
        $parser->parse();
        $expressions = $parser->getExpressions();
        Assert::equal('2;', trim($expressions[0]->string()));
        Assert::equal('$foo + \'bar\';', trim($expressions[1]->string()));
        Assert::equal('foo(\'foo\');', trim($expressions[2]->string()));
        Assert::equal('foo($foo);', trim($expressions[3]->string()));
        Assert::equal('foo(function () { return $bar; });', trim($expressions[4]->string()));
    }

    public function testNestedExpressions() {
        $parser = new Parser($this->input2);
        $parser->parse();
        $expressions = $parser->getExpressions();
        Assert::equal('function () {
   $bar = "foo";
   return 0;
};', trim($expressions[0]->string()));
        Assert::equal('"foo";', trim($expressions[1]->string()));
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
        Assert::equal('$open + $baz;', trim($expressions[1]->string()));
        Assert::true($vars[1]['$open']->initialized);
        Assert::true($vars[1]['$foo']->initialized);
        Assert::true($vars[1]['$bar']->initialized);
        // only assignment to first usage should initialize
        Assert::false($vars[1]['$baz']->initialized);
        Assert::true($vars[2]['$x']->initialized);
        Assert::false($vars[2]['$y']->initialized);
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
        Assert::equal(0, $vars[2]['$x']->uses[0]->expression);
        Assert::equal(0, $vars[2]['$x']->uses[1]->expression);
        Assert::equal(2, $vars[2]['$x']->uses[2]->expression);
        Assert::equal(1, $vars[2]['$y']->uses[0]->expression);
        // is part of the outer expression assigned to $bar
        Assert::equal(0, $vars[2]['$foo']->uses[0]->expression);
    }

    public function testGetFunctionByPoint() {
        $parser = new Parser($this->input1);
        $parser->parse();
        Assert::null($parser->getFunctionByPoint(10));
        Assert::null($parser->getFunctionByPoint(85));
        Assert::equal(
            'function () { return $bar; }',
            $parser->getFunctionByPoint(91)->string()
        );
        Assert::equal(
            'function foo() { return 0; }',
            $parser->getFunctionByPoint(138)->string()
        );

        $parser = new Parser($this->input3);
        $parser->parse();
        Assert::equal(
            'function foo() {
    $bar = function () { return; };
}',
            $parser->getFunctionByPoint(20)->string()
        );
        Assert::equal(
            'function foo() {
    $bar = function () { return; };
}',
            $parser->getFunctionByPoint(59)->string()
        );
        Assert::equal(
            'function () { return; }',
            $parser->getFunctionByPoint(35)->string()
        );
        Assert::equal(
            'function () { return; }',
            $parser->getFunctionByPoint(58)->string()
        );
    }
}

$test = new ParserTest();
$test->run();