<?php

use Mockery as m;

class EnvironmentTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testRenderReturnsProperViewWithData()
	{
		$loader = m::mock('Illuminate\Blade\Loader');
		$env = new Illuminate\Blade\Environment($loader);
		$loader->shouldReceive('getPath')->once()->with('foo')->andReturn(__DIR__.'/stubs/stub.html');
		$env->share('global', 'foo');
		$content = $env->make('foo', array('local' => 'bar'))->get();
		$expected = 'Hello World.'.PHP_EOL.PHP_EOL.'bar'.PHP_EOL.'foo'.PHP_EOL.'Blade Set.';
		$this->assertEquals($expected, $content);
	}


	public function testBasicSectionHandling()
	{
		$loader = m::mock('Illuminate\Blade\Loader');
		$env = new Illuminate\Blade\Environment($loader);
		$env->startSection('foo');
		echo 'hi';
		$env->stopSection();
		$this->assertEquals('hi', $env->yield('foo'));
	}


	public function testYieldSectionStopsAndYields()
	{
		$loader = m::mock('Illuminate\Blade\Loader');
		$env = new Illuminate\Blade\Environment($loader);
		$env->startSection('foo');
		echo 'hi';
		$this->assertEquals('hi', $env->yieldSection());
	}


	public function testInjectStartsSectionWithContent()
	{
		$loader = m::mock('Illuminate\Blade\Loader');
		$env = new Illuminate\Blade\Environment($loader);
		$env->inject('foo', 'hi');
		$this->assertEquals('hi', $env->yield('foo'));
	}


	public function testEmptyStringIsReturnedForNonSections()
	{
		$loader = m::mock('Illuminate\Blade\Loader');
		$env = new Illuminate\Blade\Environment($loader);
		$this->assertEquals('', $env->yield('foo'));
	}


	public function testShowEachMethod()
	{
		$loader = m::mock('Illuminate\Blade\Loader');
		$env = $this->getMock('Illuminate\Blade\Environment', array('make'), array($loader));
		$env->expects($this->exactly(2))->method('make')->will($this->onConsecutiveCalls('foo', 'bar'));
		$contents = $env->showEach('baz', array('a', 'b'), 'post');
		$this->assertEquals('foobar', $contents);
	}


	public function testShowEachPassesCorrectData()
	{
		$loader = m::mock('Illuminate\Blade\Loader');
		$env = $this->getMock('Illuminate\Blade\Environment', array('make'), array($loader));
		$env->expects($this->once())->method('make')->with($this->equalTo('foo'), $this->equalTo(array('key' => 'bar', 'post' => 'baz')));
		$env->showEach('foo', array('bar' => 'baz'), 'post');
	}


	public function testRawStringsReturned()
	{
		$loader = m::mock('Illuminate\Blade\Loader');
		$env = $this->getMock('Illuminate\Blade\Environment', array('make'), array($loader));
		$this->assertEquals('hi', $env->showEach('foo', array(), 'post', 'raw|hi'));
	}


	public function testEmptyViewsReturned()
	{
		$loader = m::mock('Illuminate\Blade\Loader');
		$env = $this->getMock('Illuminate\Blade\Environment', array('make'), array($loader));
		$env->expects($this->once())->method('make')->with($this->equalTo('foo'))->will($this->returnValue('hi'));
		$this->assertEquals('hi', $env->showEach('foo', array(), 'post', 'foo'));
	}


	public function testAddTemplatePathCallsLoader()
	{
		$loader = m::mock('Illuminate\Blade\Loader');
		$loader->shouldReceive('addTemplatePath')->once()->with('foo', 'bar');
		$env = new Illuminate\Blade\Environment($loader);
		$result = $env->addTemplatePath('foo', 'bar');
		$this->assertTrue($result === $env);
	}

}