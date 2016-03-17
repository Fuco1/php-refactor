<?php

require __DIR__ . '/../../../bootstrap.php';

use Tester\Assert;
use Tester\TestCase;

/**
 * @author Matus Goljer
 * @TestCase
 */
class FunctionContextTest extends TestCase {

    public function testExport() {
        $context = new FunctionContext(10, 0, 0, 1);
        $context->arglist = new ArglistContext(15, 0);
        $context->arglist->variables[] = new VariableContext(10, '$foo');
        $context->text[] = "function () {}";
        Assert::equal('{"beg":10,"id":1,"text":"function () {}","arglist":{"beg":15,"variables":[{"beg":10,"end":14,"name":"$foo","initialized":false,"argument":false,"uses":[]}]}}', json_encode($context));
    }
}

$test = new FunctionContextTest();
$test->run();