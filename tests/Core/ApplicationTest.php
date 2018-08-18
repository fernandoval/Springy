<?php
/**
 * Test case for application dependency container.
 *
 * @copyright 2015 Fernando Val
 * @author    Allan Marques <allan.marques@ymail.com>
 * @author    Fernando Val <fernando.val@gmail.com>
 *
 * @version   1.0.0.3
 */
use PHPUnit\Framework\TestCase;
use Springy\Core\Application;

class ApplicationTest extends TestCase
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
