<?php

namespace Salla\ContentGenerator\Tests;

use Silex\Application;
use Silex\Provider\UrlGeneratorServiceProvider;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Salla\ContentGenerator\Generator;

class GeneratorTest extends \PHPUnit_Framework_TestCase
{

    protected $workspace;

    protected function setUp()
    {
        $this->workspace = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid() . DIRECTORY_SEPARATOR . 'content-generator';

        mkdir($this->workspace, 0777, true);

        $this->workspace = realpath($this->workspace);
    }

    protected function tearDown()
    {
        if (!is_dir($this->workspace)) {
            return;
        }

        $this->rrmdir($this->workspace);
    }

    protected function rrmdir($dir)
    {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->rrmdir($file);
            } else {
                unlink($file);
            }
        }
    }

    protected function createApplication()
    {
        $app = new Application();
        $app->register(new UrlGeneratorServiceProvider());

        $app->get('/hello', function () {
            return new Response('Hello!');
        })->bind('greet');

        $app->get('/hello/{name}', function ($name) {
            return new Response(sprintf('Hello %s!', $name));
        })->bind('hello');

        $app->get('/see/you/{name}', function ($name) {
            return new Response(sprintf('Bye %s!', $name));
        })->bind('bye');

        return $app;
    }

    public function testGenerateForRoute()
    {
        $app = $this->createApplication();

        $generator = new Generator($app, $app['url_generator'], $app['routes']);

        $responses = $generator->generateForRoute('hello', array(
            array('name' => 'Gyula'),
            array('name' => 'John'),
            array('name' => 'Matt'),
        ));

        $this->assertEquals(array(
            '/hello/Gyula' => 'Hello Gyula!',
            '/hello/John'  => 'Hello John!',
            '/hello/Matt'  => 'Hello Matt!',
        ), $responses);
    }

    public function testGenerateForRouteWithoutDataSource()
    {
        $app = $this->createApplication();

        $generator = new Generator($app, $app['url_generator'], $app['routes']);

        $responses = $generator->generateForRoute('greet');

        $this->assertEquals(array(
            '/hello' => 'Hello!',
        ), $responses);
    }

    public function testGenerateForRouteIgnoresUnsuccessfulResponses()
    {
        $app = $this->createApplication();
        $app->get('/fail', function (Application $app) {
            throw new HttpException(500, 'Something nasty happened!');
        })->bind('fail');

        $generator = new Generator($app, $app['url_generator'], $app['routes']);

        $responses = $generator->generateForRoute('fail');

        $this->assertEquals(array(), $responses);
    }

    public function testGenerateForRouteThrowsExceptionOnInvalidDataSources()
    {
        $this->setExpectedException('RuntimeException');

        $app = $this->createApplication();

        $generator = new Generator($app, $app['url_generator'], $app['routes']);

        $generator->generateForRoute('hello', function ($variables) {});
    }

    public function testGenerateForRouteThrowsExceptionOnInvalidRoute()
    {
        $this->setExpectedException('InvalidArgumentException');

        $app = $this->createApplication();

        $generator = new Generator($app, $app['url_generator'], $app['routes']);

        $generator->generateForRoute('no_such_route', array());
    }

    public function testGenerate()
    {
        $app = $this->createApplication();

        $generator = new Generator($app, $app['url_generator'], $app['routes']);

        $generator->addRoutes(array('greet', 'hello', 'bye'), array(
            array('name' => 'Gyula'),
            array('name' => 'John'),
            array('name' => 'Matt'),
        ));

        $this->assertEquals(array(
            '/hello' => 'Hello!',
            '/hello/Gyula' => 'Hello Gyula!',
            '/hello/John'  => 'Hello John!',
            '/hello/Matt'  => 'Hello Matt!',
            '/see/you/Gyula' => 'Bye Gyula!',
            '/see/you/John'  => 'Bye John!',
            '/see/you/Matt'  => 'Bye Matt!',
        ), $generator->generate());

    }

    public function testDump()
    {
        $app = $this->createApplication();

        $generator = new Generator($app, $app['url_generator'], $app['routes']);

        $generator->addRoutes(array('greet', 'hello', 'bye'), array(
            array('name' => 'Gyula'),
            array('name' => 'John'),
            array('name' => 'Matt'),
        ));

        $generator->dump($this->workspace);

        $this->assertFileExists($this->workspace . '/hello.html');
        $this->assertFileExists($this->workspace . '/hello/Gyula.html');
        $this->assertFileExists($this->workspace . '/see/you/Gyula.html');
    }

}
