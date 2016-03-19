<?php

require_once __DIR__ . '/../../../bootstrap.php';

use Tester\Assert;

/**
 * @author Matus Goljer
 * @TestCase
 */
class FunctionContextTest extends MyTestCase {

    public function testExport() {
        $context = new FunctionContext(10, 0, 0, 1);
        $context->arglist = new ArglistContext(15, 0);
        $context->arglist->variables[] = new VariableContext(10, '$foo');
        $context->text[] = "function () {}";
        Assert::equal('{"beg":10,"id":1,"text":"function () {}","arglist":{"beg":15,"variables":[{"beg":10,"end":14,"name":"$foo","initialized":false,"argument":false,"uses":[]}]}}', json_encode($context));
    }

    public function testEnd() {
        $context = new FunctionContext(10, 0, 0, 1);
        $context->text[] = "function () {}";
        Assert::equal(24, $context->end());
    }
}

$test = new FunctionContextTest();
$test->run();