<?php namespace Illuminate\Blade;

use ArrayAccess;

class View implements ArrayAccess {

	/**
	 * The Blade environment instance.
	 *
	 * @var Illuminate\Blade\Environment
	 */
	protected $environment;

	/**
	 * The name of the view being rendered.
	 *
	 * @var string
	 */
	protected $view;

	/**
	 * The array of parameters to give to the view.
	 *
	 * @var array
	 */
	protected $parameters;

	/**
	 * Create a new Blade template instance.
	 *
	 * @param  Illuminate\Blade\Environment  $environment
	 * @param  string  $view
	 * @param  array  $parameters
	 * @return void
	 */
	public function __construct(Environment $environment, $view, array $parameters = array())
	{
		$this->view = $view;
		$this->parameters = $parameters;
		$this->environment = $environment;
	}

	/**
	 * Get the given item from the view.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return Illuminate\Blade\View
	 */
	public function with($key, $value)
	{
		$this->parameters[$key] = $value;

		return $this;
	}

	/**
	 * Retrieve the evaluated contents of the template.
	 *
	 * @return string
	 */
	public function get()
	{
		$env = $this->environment;

		// First we fire the composing event for the view if the view environment has
		// an event dispatcher instance. This allows the event handlers a spot to
		// assign extra data to the views or log the view's creation for debug.
		if ($env->hasDispatcher())
		{
			$event = $this->getComposingEvent();

			$env->getDispatcher()->fire($event, array($this));
		}

		// First we need to get all of the data that should be available to the view
		// and the fully qualified path to the view. Then we will start an output
		// buffer then include in this template's code contents for evaluation.
		$__data = $env->getViewData($this->parameters);

		$__path = $env->getLoader()->getPath($this->view);

		ob_start() and extract($__data, EXTR_SKIP);

		// We'll include the template itself inside of a try-catch block so we will
		// easily catch any error, destroying the output buffer before throwing
		// the exception back out to the consuming function for any handling.
		try
		{
			include $__path;
		}

		// If an exception is caught we will clean the output buffer and throw the
		// exception back out for rendering. We just clean the buffers so there
		// is no errant stuff escaping back out to the browser from the apps.
		catch (\Exception $e)
		{
			ob_get_clean(); throw $e;
		}

		return ob_get_clean();
	}

	/**
	 * Get the name of the composing event name.
	 *
	 * @return string
	 */
	public function getComposingEvent()
	{
		return 'illuminate.composing: '.$this->view;
	}

	/**
	 * Determine if the item exists on the view.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return array_key_exists($key, $this->parameters);
	}

	/**
	 * Get the given item from the view.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->parameters[$key];
	}

	/**
	 * Get the given item from the view.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->parameters[$key] = $value;
	}

	/**
	 * Unset the given item from the view.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		unset($this->parameters[$key]);
	}

	/**
	 * Get the string contents of the view.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->get();
	}

}