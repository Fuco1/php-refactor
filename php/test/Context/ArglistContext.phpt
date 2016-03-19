<?php

require_once __DIR__ . '/../../../bootstrap.php';

use Tester\Assert;
use Tester\TestCase;

/**
 * @author Matus Goljer
 * @TestCase
 */
class ArglistContextTest extends TestCase {

    public function testExport() {
        $context = new ArglistContext(10, 0);
        $context->id = 1;
        $context->variables[] = new VariableContext(10, '$foo');
        Assert::equal('{"beg":10,"id":1,"variables":[{"beg":10,"end":14,"name":"$foo","initialized":false,"argument":false,"uses":[]}]}', json_encode($context));
    }

    public function testEnd() {
        $context = new ArglistContext(10, 0);
        $context->variables[] = new VariableContext(10, '$foo');
        $context->variables[] = new VariableContext(16, '$bar');
        Assert::equal(20, $context->end());
    }
}

$test = new ArglistContextTest();
$test->run();