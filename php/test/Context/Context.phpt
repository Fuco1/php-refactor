<?php

require_once __DIR__ . '/../../../bootstrap.php';

use JMS\Serializer\Annotation\ExclusionPolicy as ExclusionPolicy;
use Tester\Assert;

/**
 * @author Matus Goljer
 * @TestCase
 */
class ContextTest extends MyTestCase {

    public function testExport() {
        $context = new TestContext(10);
        $context->id = 1;
        Assert::equal('{"beg":10,"id":1}', json_encode($context));
    }
}

/**
 * @ExclusionPolicy("all")
 */
class TestContext extends Context {
}

$test = new ContextTest();
$test->run();