<?php

use Mockery as m;

class LoaderTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	/**
	 * @expectedException Illuminate\Blade\InvalidViewException
	 */
	public function testExceptionsIsThrownForViewsThatDontExist()
	{
		$loader = $this->getLoader();
		$loader->getFilesystem()->shouldReceive('exists')->once()->with(__DIR__.'/foo.blade.php')->andReturn(false);
		$loader->getPath('foo');
	}


	public function testWhenViewIsExpiredItIsCompiled()
	{
		list($compiler, $files) = $this->getMocks();
		$loader = $this->getMock('Illuminate\Blade\Loader', array('isExpired'), array($compiler, $files, __DIR__, __DIR__));
		$loader->expects($this->once())->method('isExpired')->will($this->returnValue(true));
		$loader->getFilesystem()->shouldReceive('exists')->once()->andReturn(true);
		$loader->getFilesystem()->shouldReceive('get')->once()->with(__DIR__.'/foo.blade.php')->andReturn('bar');
		$loader->getFilesystem()->shouldReceive('put')->once()->with(__DIR__.'/'.md5(__DIR__.'/foo.blade.php'), 'bar');
		$loader->getCompiler()->shouldReceive('compile')->once()->with('bar')->andReturn('bar');
		$return = $loader->getPath('foo');
		$this->assertEquals(__DIR__.'/'.md5(__DIR__.'/foo.blade.php'), $return);
	}


	public function testCompilerIsNotCalledWhenTemplateIsntExpired()
	{
		list($compiler, $files) = $this->getMocks();
		$loader = $this->getMock('Illuminate\Blade\Loader', array('isExpired'), array($compiler, $files, __DIR__, __DIR__));
		$loader->expects($this->once())->method('isExpired')->will($this->returnValue(false));
		$loader->getFilesystem()->shouldReceive('exists')->once()->andReturn(true);
		$loader->getCompiler()->shouldReceive('compile')->never();
		$this->assertEquals(__DIR__.'/'.md5(__DIR__.'/foo.blade.php'), $loader->getPath('foo'));
	}


	public function testNamedViewsMayBeCompiled()
	{
		list($compiler, $files) = $this->getMocks();
		$loader = $this->getMock('Illuminate\Blade\Loader', array('isExpired'), array($compiler, $files, __DIR__, __DIR__));
		$loader->expects($this->once())->method('isExpired')->will($this->returnValue(true));
		$loader->getFilesystem()->shouldReceive('exists')->once()->andReturn(true);
		$loader->getFilesystem()->shouldReceive('get')->once()->with(__DIR__.'/name/foo.blade.php')->andReturn('bar');
		$loader->getFilesystem()->shouldReceive('put')->once()->with(__DIR__.'/'.md5(__DIR__.'/name/foo.blade.php'), 'bar');
		$loader->getCompiler()->shouldReceive('compile')->once()->with('bar')->andReturn('bar');
		$loader->addTemplatePath('name', __DIR__.'/name');
		$return = $loader->getPath('name::foo');
		$this->assertEquals(__DIR__.'/'.md5(__DIR__.'/name/foo.blade.php'), $return);
	}


	public function testNestedViewsMayBeCompiled()
	{
		list($compiler, $files) = $this->getMocks();
		$loader = $this->getMock('Illuminate\Blade\Loader', array('isExpired'), array($compiler, $files, __DIR__, __DIR__));
		$loader->expects($this->once())->method('isExpired')->will($this->returnValue(true));
		$loader->getFilesystem()->shouldReceive('exists')->once()->andReturn(true);
		$loader->getFilesystem()->shouldReceive('get')->once()->with(__DIR__.'/foo/bar.blade.php')->andReturn('bar');
		$loader->getFilesystem()->shouldReceive('put')->once()->with(__DIR__.'/'.md5(__DIR__.'/foo/bar.blade.php'), 'bar');
		$loader->getCompiler()->shouldReceive('compile')->once()->with('bar')->andReturn('bar');
		$return = $loader->getPath('foo.bar');
		$this->assertEquals(__DIR__.'/'.md5(__DIR__.'/foo/bar.blade.php'), $return);
	}

	public function testRawPathViewPathsAreCorrectlyHandled()
	{
		$loader = $this->getLoader();
		$this->assertEquals('bar', $loader->getFullPath('path: bar'));
	}

	protected function getLoader()
	{
		list($compiler, $files) = $this->getMocks();
		return new Illuminate\Blade\Loader($compiler, $files, __DIR__, __DIR__);
	}


	protected function getMocks()
	{
		return array(m::mock('Illuminate\Blade\Compiler'), m::mock('Illuminate\Filesystem'));
	}

}
