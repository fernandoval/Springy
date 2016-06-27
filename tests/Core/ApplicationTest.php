<?php
/**	\file
 *	Springy.
 *
 *	\brief      Test case for Classe container de dependências de toda aplicação.
 *  \copyright  Copyright (c) 2007-2015 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *	\warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *	\version    0.1.2
 *	\ingroup    tests
 */
use Springy\Core\Application;

class ApplicationTest extends PHPUnit_Framework_TestCase
{
    private $app;

    public function setUp()
    {
        $this->app = new Application();
    }

    public function testThatApplicationCanRegisterEvents()
    {
        $this->app->on('event', function () {
        });

        $this->assertTrue($this->app['events']->hasHandlersFor('event'));
    }

    public function testThatApplicationCanUnRegisterEvents()
    {
        $this->app->on('event', function () {
        });

        $this->app->off('event');

        $this->assertFalse($this->app['events']->hasHandlersFor('event'));
    }

    public function testThatApplicationCanFireEvents()
    {
        $this->app->on('event', function () {
            return 10;
        });

        $this->assertEquals([10], $this->app->fire('event'));
    }
}
