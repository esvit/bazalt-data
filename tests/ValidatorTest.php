<?php

namespace tests;

use Bazalt\Data\Validator;

class ValidatorTest extends \tests\BaseCase
{
    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    public function testName()
    {
        $data = new Validator([
            'test' => 'test'
        ]);

        $data->field('test')->required();

        $this->assertTrue($data->validate());
    }
}