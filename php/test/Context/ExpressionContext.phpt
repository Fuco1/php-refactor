<?php

require __DIR__ . '/../../../bootstrap.php';

use Tester\Assert;
use Tester\TestCase;

/**
 * @author Matus Goljer
 * @TestCase
 */
class ExpressionContextTest extends TestCase {

    public function testExport() {
        $context = new ExpressionContext(10, 0, 0, 1);
        $context->text[] = "foo";
        $context->text[] = " bar";
        Assert::equal('{"beg":10,"id":1,"end":17,"text":"foo bar"}', json_encode($context));
    }
}

$test = new ExpressionContextTest();
$test->run();