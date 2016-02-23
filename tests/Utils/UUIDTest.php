<?php
/** \file
 *  Springy
 *
 *  \brief      Test class for UUID framework library class.
 *  \copyright  Copyright (c) 2007-2015 Fernando Val
 *  \author     Fernando Val - fernando.val@gmail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1.1
 *  \ingroup    tests
 */
use Springy\Utils\UUID;

class UUIDTest extends PHPUnit_Framework_TestCase
{
    public function testRandomUUID()
    {
        $uuid1 = UUID::random();
        $uuid2 = UUID::random();
        $this->assertRegExp('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $uuid1);
        $this->assertRegExp('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $uuid2);
        $this->assertNotEquals($uuid1, $uuid2);
    }

    public function testV3UUID()
    {
        $name = 'test';
        $uuid = UUID::random();
        $v3_1 = UUID::v3($uuid, $name);
        $v3_2 = UUID::v3($uuid, $name);

        $this->assertRegExp('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $v3_1);
        $this->assertRegExp('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $v3_2);
        $this->assertEquals($v3_1, $v3_2);
    }

    public function testV4UUID()
    {
        $uuid1 = UUID::v4();
        $uuid2 = UUID::v4();
        $this->assertRegExp('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $uuid1);
        $this->assertRegExp('/\w{8}-\w{4}-\w{4}-\w{4}-\w{12}/', $uuid2);
        $this->assertNotEquals($uuid1, $uuid2);
    }
}
