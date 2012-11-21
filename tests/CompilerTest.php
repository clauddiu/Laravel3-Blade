<?php

class CompilerTest extends PHPUnit_Framework_TestCase {

	public function testExtends()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile("@extends('foo')\r\nfoo");
		$this->assertEquals('foo'."\r\n".'<?php echo $__blade->make(\'foo\', get_defined_vars()); ?>', $value);
	}


	public function testComments()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile('{{-- test --}}');
		$this->assertEquals('<?php /*  test  */ ?>'."\n", $value);
		$value = $compiler->compile('{{-- test'."\n");
		$this->assertEquals('<?php //  test ?>', $value);
	}


	public function testEchos()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile('{{time()}}');
		$this->assertEquals('<?php echo time(); ?>', $value);
	}


	public function testOpenings()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile('@foreach ($comments as $comment)');
		$this->assertEquals('<?php foreach ($comments as $comment): ?>', $value);
		$value = $compiler->compile('@while (true)');
		$this->assertEquals('<?php while (true): ?>', $value);
	}


	public function testClosings()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile('@endforeach');
		$this->assertEquals('<?php endforeach; ?>', $value);
	}


	public function testElse()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile('@else');
		$this->assertEquals('<?php else: ?>', $value);
	}


	public function testUnless()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile('@unless (true)');
		$this->assertEquals('<?php if ( ! (true)): ?>', $value);
	}


	public function testEndUnless()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile('@endunless');
		$this->assertEquals('<?php endif; ?>', $value);
	}


	public function testIncludes()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile("@include('foo')");
		$this->assertEquals('<?php echo $__blade->make(\'foo\', get_defined_vars()); ?>', $value);
	}


	public function testEach()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile("@each('foo')");
		$this->assertEquals('<?php echo $__blade->showEach(\'foo\'); ?>', $value);
	}


	public function testYields()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile("@yield('foo')");
		$this->assertEquals('<?php echo $__blade->yield(\'foo\'); ?>', $value);
	}


	public function testShow()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile("@show");
		$this->assertEquals('<?php echo $__blade->yieldSection(); ?>', $value);
	}


	public function testSectionStart()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile("@section('foo')");
		$this->assertEquals('<?php $__blade->startSection(\'foo\'); ?>', $value);
	}


	public function testSectionStop()
	{
		$compiler = new Illuminate\Blade\Compiler;
		$value = $compiler->compile("@stop");
		$this->assertEquals('<?php $__blade->stopSection(); ?>', $value);
	}

}