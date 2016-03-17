<?php

require __DIR__ . '/../../../bootstrap.php';

use Tester\Assert;
use Tester\TestCase;

/**
 * @author Matus Goljer
 * @TestCase
 */
class ContextTest extends TestCase {

    public function testExport() {
        $context = new TestContext(10);
        $context->id = 1;
        Assert::equal('{"beg":10,"id":1}', json_encode($context));
    }
}

class TestContext extends Context {
}

$test = new ContextTest();
$test->run();