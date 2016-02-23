<?php
/**	\file
 *	Springy.
 *
 *	\brief      Test case for Classe de container para inversão de controle (Dependecy Injection).
 *  \copyright  Copyright (c) 2007-2015 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *	\warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version    0.1.1
 *	\ingroup    tests
 */
use Springy\Container\DIContainer;

class DIContainerTest extends PHPUnit_Framework_TestCase
{
    private $data;

    public function setUp()
    {
        $this->data = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key4' => 'value4',
        ];
    }

    /**
     *  @expectedException \InvalidArgumentException
     */
    public function testThatContainerCanStoreRawValues()
    {
        $DI = new DIContainer();

        //Basic
        $DI->raw('key1', $this->data['key1']);
        $this->assertEquals($this->data['key1'], $DI->param('key1'));

        //Array like
        $DI['key2'] = $this->data['key2'];
        $this->assertEquals($this->data['key2'], $DI['key2']);

        //Function filter #1
        $DI['key3'] = $DI->raw(function ($container) {

            return $this->data['key3'];

        });
        $this->assertEquals($this->data['key3'], $DI->param('key3'));

        //Function filter #2
        $DI->raw('key4', function ($container) {

            return $this->data['key4'];

        });
        $this->assertEquals($this->data['key4'], $DI->param('key4'));

        //Forgeting
        $DI->forget('key4');
        $DI->param('key4');
    }

    /**
     *  @expectedException \InvalidArgumentException
     */
    public function testThatContainerCanCreateObjectsOnTheFly()
    {
        $DI = new DIContainer();

        //Basic
        $DI->bind('object1', function ($attr = null, $val = null) {
            $obj = $this->getMock('SomeClass', ['someMethod']);

            if (is_string($attr)) {
                $obj->$attr = $val;
            }

            return $obj;
        });

        $this->assertTrue(is_object($DI->make('object1')));
        $this->assertTrue(method_exists($DI->make('object1'), 'someMethod'));
        $this->assertInstanceOf('SomeClass', $DI->make('object1'));

        $object1 = $DI->make('object1');
        $object2 = $DI->make('object1');
        $this->assertNotSame($object1, $object2);

        //With params
        $objectWithParam = $DI->make('object1', ['name', 'Jack']);
        $this->assertObjectHasAttribute('name', $objectWithParam);
        $this->assertEquals('Jack', $objectWithParam->name);

        //Array like
        $DI['object2'] = function () {
            return $this->getMock('AnotherClass', ['otherMethod']);
        };
        $this->assertNotInstanceOf('Closure', $DI['object2']);
        $this->assertTrue(method_exists($DI['object2'], 'otherMethod'));
        $this->assertInstanceOf('AnotherClass', $DI['object2']);

        $object3 = $DI['object2'];
        $object4 = $DI['object2'];
        $this->assertNotSame($object3, $object4);

        //Unbinding
        $DI->forget('object2');
        $DI->make('object2');
    }

    public function testThatContainerCanExtendFactories()
    {
        $DI = new DIContainer();

        $DI['some.service'] = function ($container) {
            return $this->getMock('someService');
        };

        $DI->extend('some.service', function ($someService, $container) {
            $someService->someAttribute = 'someValue';

            return $someService;
        });

        $this->assertObjectHasAttribute('someAttribute', $DI['some.service']);
        $this->assertEquals('someValue', $DI['some.service']->someAttribute);

        $extended1 = $DI['some.service'];
        $extended2 = $DI['some.service'];
        $this->assertNotSame($extended1, $extended2);
    }

    /**
     *  @expectedException \InvalidArgumentException
     */
    public function testThatContainerCanBindObjectsAndShareInstances()
    {
        $DI = new DIContainer();

        $object1 = $this->getMock('MockedClass');
        $object2 = $this->getMock('AnotherMockedClass');
        $object3 = $this->getMock('MockedInstance');
        $object4 = $this->getMock('AnotherMockedInstance');

        //Basic
        $DI->instance('object1', $object1);
        $this->assertSame($object1, $DI->shared('object1'));

        //Array like
        $DI['object2'] = $object2;
        $this->assertSame($object2, $DI['object2']);

        //Function filter #1
        $DI['object3'] = $DI->instance(function ($container) use ($object3) {

            return $object3;

        });
        $this->assertSame($object3, $DI->shared('object3'));

        //Function filter #2
        $DI->instance('object4', function ($container) use ($object4) {

            return $object4;

        });
        $this->assertSame($object4, $DI->shared('object4'));

        //Forgeting
        $DI->forget('object4');
        $DI->shared('object4');
    }
}
