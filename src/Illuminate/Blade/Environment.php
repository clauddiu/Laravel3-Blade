<?php namespace Illuminate\Blade;

use Illuminate\Events\Dispatcher;

class Environment {

	/**
	 * The Blade loader implementation.
	 *
	 * @var Illuminate\Blade\Loader
	 */
	protected $loader;

	/**
	 * The event dispatcher instance.
	 *
	 * @var Illuminate\Events\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * Data that should be available to all templates.
	 *
	 * @var array
	 */
	protected $shared = array();

	/**
	 * All of the finished, captured sections.
	 *
	 * @var array
	 */
	protected $sections = array();

	/**
	 * The stack of in-progress sections.
	 *
	 * @var array
	 */
	protected $sectionStack = array();

	/**
	 * Create a new Blade factory.
	 *
	 * @param  Illuminate\Blade\Loader  $loader
	 * @return void
	 */
	public function __construct(Loader $loader)
	{
		$this->loader = $loader;
	}

	/**
	 * Determine if the given template exists.
	 *
	 * @param  string  $view
	 * @return bool
	 */
	public function exists($view)
	{
		return $this->loader->exists($view);
	}

	/**
	 * Create a new Blade template instance.
	 *
	 * @param  string  $view
	 * @param  array   $parameters
	 * @return Illuminate\Blade\View
	 */
	public function make($view, array $parameters = array())
	{
		return new View($this, $view, $parameters);
	}

	/**
	 * Get the evaluated contents of a partial from a loop.
	 *
	 * @param  string  $view
	 * @param  array   $parameters
	 * @param  string  $iterator
	 * @param  string  $empty
	 * @return string
	 */
	public function showEach($view, array $parameters, $iterator, $empty = 'raw|')
	{
		$result = '';

		// If there is data within the parameters we will loop through the data and
		// append an instance of the partial view to the final result HTML while
		// passing in the iterated value of the parameter arrays to the child.
		if (count($parameters) > 0)
		{
			foreach ($parameters as $key => $value)
			{
				$with = array('key' => $key, $iterator => $value);

				$result .= (string) $this->make($view, $with);
			}
		}

		// If there is not data in the parameter array, we will render the contents
		// of the "empty" view. Alternatively, the "empty views" can simply be a
		// raw string which is prefixed with "raw|" for convenient injections.
		else
		{
			if (strpos($empty, 'raw|') === 0)
			{
				$result = substr($empty, 4);
			}
			else
			{
				$result = (string) $this->make($empty);
			}
		}

		return $result;
	}

	/**
	 * Get the data that should be available to a template.
	 *
	 * @param  array  $parameters
	 * @return array
	 */
	public function getViewData($parameters)
	{
		$parameters['__blade'] = $this;

		return array_merge($this->shared, $parameters);
	}

	/**
	 * Add a named template path to the loader.
	 *
	 * @param  string  $name
	 * @param  string  $path
	 * @return Illuminate\Blade\Factory
	 */
	public function addTemplatePath($name, $path)
	{
		$this->loader->addTemplatePath($name, $path);

		return $this;
	}

	/**
	 * Start injecting content into a section.
	 *
	 * @param  string  $section
	 * @param  string  $content
	 * @return void
	 */
	public function startSection($section, $content = '')
	{
		if ($content === '')
		{
			ob_start() and $this->sectionStack[] = $section;
		}
		else
		{
			$this->extendSection($section, $content);
		}
	}

	/**
	 * Inject inline content into a section.
	 *
	 * @param  string  $section
	 * @param  string  $content
	 * @return void
	 */
	public function inject($section, $content)
	{
		return $this->startSection($section, $content);
	}

	/**
	 * Stop injecting content into a section and return its contents.
	 *
	 * @return string
	 */
	public function yieldSection()
	{
		return $this->yield($this->stopSection());
	}

	/**
	 * Stop injecting content into a section.
	 *
	 * @return string
	 */
	public function stopSection()
	{
		$last = array_pop($this->sectionStack);

		$this->extendSection($last, ob_get_clean());

		return $last;
	}

	/**
	 * Append content to a given section.
	 *
	 * @param  string  $section
	 * @param  string  $content
	 * @return void
	 */
	protected function extendSection($section, $content)
	{
		if (isset($this->sections[$section]))
		{
			$content = str_replace('@parent', $content, $this->sections[$section]);

			$this->sections[$section] = $content;
		}
		else
		{
			$this->sections[$section] = $content;
		}
	}

	/**
	 * Get the string contents of a section.
	 *
	 * @param  string  $section
	 * @return string
	 */
	public function yield($section)
	{
		return isset($this->sections[$section]) ? $this->sections[$section] : '';
	}

	/**
	 * Add a shared piece of data to the factory.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return Illuminate\View\Factory
	 */
	public function share($key, $value)
	{
		$this->shared[$key] = $value;

		return $this;
	}

	/**
	 * Get the shared template data.
	 *
	 * @return array
	 */
	public function getShared()
	{
		return $this->shared;
	}

	/**
	 * Get the Blade loader implementation.
	 *
	 * @return Illuminate\Blade\Loader
	 */
	public function getLoader()
	{
		return $this->loader;
	}

	/**
	 * Set the Blade loader implementation.
	 *
	 * @param  Illuminate\Blade\Loader
	 * @return void
	 */
	public function setLoader(Loader $loader)
	{
		$this->loader = $loader;
	}

	/**
	 * Get the event dispatcher instance.
	 *
	 * @return Illuminate\Events\Dispatcher
	 */
	public function getDispatcher()
	{
		return $this->dispatcher;
	}

	/**
	 * Determine if the event dispatcher instance is set.
	 *
	 * @return bool
	 */
	public function hasDispatcher()
	{
		return isset($this->dispatcher);
	}

	/**
	 * Set the event dispatcher instance.
	 *
	 * @param  Illuminate\Events\Dispatcher
	 * @return void
	 */
	public function setDispatcher(Dispatcher $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}

}
