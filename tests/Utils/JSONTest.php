<?php

/**
 * phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */

use PHPUnit\Framework\TestCase;
use Springy\Utils\JSON;

class JSONTest extends TestCase
{
    public function testAddAndGetData()
    {
        $json = new JSON();
        $json->add(['test' => 'ok']);
        $json->add(['next' => 'yes']);
        $res = $json->getData();

        $this->assertIsArray($res);
        $this->assertArrayHasKey('test', $res);
        $this->assertArrayHasKey('next', $res);
        $this->assertEquals('ok', $res['test']);
        $this->assertEquals('yes', $res['next']);
    }

    public function testFetch()
    {
        $arr = ['test' => 'ok'];
        $json = new JSON();
        $json->add($arr);
        $fetch = $json->fetch();

        $this->assertIsString($fetch);
        $this->assertEquals(json_encode($arr), $fetch);
    }
}
