<?php namespace Illuminate\Blade;

class Compiler {

	/**
	 * The CSRF token provider implementation.
	 *
	 * @var Illuminate\Session\TokenProvider
	 */
	protected $tokenProvider;

	/**
	 * All of the available compiler functions.
	 *
	 * @var array
	 */
	protected $compilers = array(
		'Extends',
		'Comments',
		'Echos',
		'Openings',
		'Closings',
		'Else',
		'Unless',
		'EndUnless',
		'Includes',
		'Each',
		'Yields',
		'Shows',
		'SectionStart',
		'SectionStop',
	);

	/**
	 * Compile the given Blade template contents.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function compile($value)
	{
		foreach ($this->compilers as $compiler)
		{
			$value = $this->{"compile{$compiler}"}($value);
		}

		return $value;
	}

	/**
	 * Compile Blade template extensions into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileExtends($value)
	{
		// By convention, Blade views using template inheritance must begin with the
		// @extends expression, otherwise they will not be compiled with template
		// inheritance. So, if they do not start with that we will just return.
		if (strpos($value, '@extends') !== 0)
		{
			return $value;
		}

		$lines = preg_split("/(\r?\n)/", $value);

		// Next we just want to split the values by lines, and create an expression
		// to include the parent layout at the end of the templates. This allows
		// the sections to get registered before the parents view is rendered.
		$pattern = $this->createMatcher('extends');

		$replace = '$1@include$2';

		$lines[] = preg_replace($pattern, $replace, $lines[0]);

		// Once we've made the replacement, we'll slice off the first line as it's
		// now just ane empty line since the template has been moved to the end
		// of the files since we need to let all the sections register first.
		return implode("\r\n", array_slice($lines, 1));
	}

	/**
	 * Compile Blade comments into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileComments($value)
	{
		$value = preg_replace('/\{\{--(.+?)(--\}\})?\n/', "<?php // $1 ?>", $value);

		return preg_replace('/\{\{--((.|\s)*?)--\}\}/', "<?php /* $1 */ ?>\n", $value);
	}

	/**
	 * Compile Blade echos into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileEchos($value)
	{
		return preg_replace('/\{\{(.+?)\}\}/', '<?php echo $1; ?>', $value);
	}

	/**
	 * Compile Blade structure openings into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileOpenings($value)
	{
		$pattern = '/(\s*)@(if|elseif|foreach|for|while)(\s*\(.*\))/';

		return preg_replace($pattern, '$1<?php $2$3: ?>', $value);
	}

	/**
	 * Compile Blade structure closings into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileClosings($value)
	{
		$pattern = '/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/';

		return preg_replace($pattern, '$1<?php $2; ?>$3', $value);
	}

	/**
	 * Compile Blade else statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileElse($value)
	{
		return preg_replace('/(\s*)@(else)(\s*)/', '$1<?php $2: ?>$3', $value);
	}

	/**
	 * Compile Blade unless statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileUnless($value)
	{
		$pattern = '/(\s*)@unless\s*\((.*)\)/';

		return preg_replace($pattern, '$1<?php if ( ! ($2)): ?>', $value);
	}

	/**
	 * Compile Blade end unless statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileEndUnless($value)
	{
		return str_replace('@endunless', '<?php endif; ?>', $value);
	}

	/**
	 * Compile Blade include statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileIncludes($value)
	{
		$pattern = $this->createOpenMatcher('include');

		$replace = '$1<?php echo $__blade->make$2, get_defined_vars()); ?>';

		return preg_replace($pattern, $replace, $value);
	}

	/**
	 * Compile Blade each statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileEach($value)
	{
		$pattern = $this->createMatcher('each');

		return preg_replace($pattern, '$1<?php echo $__blade->showEach$2; ?>', $value);
	}

	/**
	 * Compile Blade yield statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileYields($value)
	{
		$pattern = $this->createMatcher('yield');

		return preg_replace($pattern, '$1<?php echo $__blade->yield$2; ?>', $value);
	}

	/**
	 * Compile Blade show statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileShows($value)
	{
		$replace = '<?php echo $__blade->yieldSection(); ?>';

		return str_replace('@show', $replace, $value);
	}

	/**
	 * Compile Blade section start statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileSectionStart($value)
	{
		$pattern = $this->createMatcher('section');

		return preg_replace($pattern, '$1<?php $__blade->startSection$2; ?>', $value);
	}

	/**
	 * Compile Blade section stop statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileSectionStop($value)
	{
		return preg_replace('/@stop/', '<?php $__blade->stopSection(); ?>', $value);
	}

	/**
	 * Get the regular expression for a generic Blade function.
	 *
	 * @param  string  $function
	 * @return string
	 */
	public function createMatcher($function)
	{
		return '/(\s*)@'.$function.'(\s*\(.*\))/';
	}

	/**
	 * Get the regular expression for a generic Blade function.
	 *
	 * @param  string  $function
	 * @return string
	 */
	public function createOpenMatcher($function)
	{
		return '/(\s*)@'.$function.'(\s*\(.*)\)/';
	}

}