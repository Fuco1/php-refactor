<?php

require_once __DIR__ . '/../../../bootstrap.php';

use Tester\Assert;
use Tester\TestCase;

/**
 * @author Matus Goljer
 * @TestCase
 */
class VariableContextTest extends TestCase {

    public function testExport() {
        $context = new VariableContext(10, '$foo');
        $context->id = 1;
        $context->uses[] = new VariableUsage(10, null);
        Assert::equal('{"beg":10,"id":1,"end":14,"name":"$foo","initialized":false,"argument":false,"uses":[{"beg":10,"assignedExpression":-1}]}', json_encode($context));
    }
}

$test = new VariableContextTest();
$test->run();