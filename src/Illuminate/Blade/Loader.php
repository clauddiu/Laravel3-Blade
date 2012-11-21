<?php namespace Illuminate\Blade;

use Illuminate\Filesystem;

class Loader {

	/**
	 * The paths to the template files.
	 *
	 * @var array
	 */
	protected $paths = array();

	/**
	 * The path where cached templates are stored.
	 *
	 * @var string
	 */
	protected $cache;

	/**
	 * The Illuminate filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * The Blade compiler implementation.
	 *
	 * @var Illuminate\Blade\Compiler
	 */
	protected $compiler;

	/**
	 * Create a new Blade template loader.
	 *
	 * @param  Illuminate\Blade\CompilerInterface  $compiler
	 * @param  Illuminate\Filesystem  $files
	 * @param  string  $path
	 * @param  string  $cache
	 * @return void
	 */
	public function __construct(Compiler $compiler, Filesystem $files, $path, $cache)
	{
		$this->cache = $cache;
		$this->files = $files;
		$this->compiler = $compiler;
		$this->addTemplatePath('*', $path);
	}

	/**
	 * Create a new Blade template loader.
	 *
	 * @param  string  $path
	 * @param  string  $cache
	 * @return void
	 */
	public static function make($path, $cache)
	{
		return new static(new Compiler, new Filesystem, $path, $cache);
	}

	/**
	 * Determine if a given template exists.
	 *
	 * @param  string  $view
	 * @return bool
	 */
	public function exists($view)
	{
		return $this->files->exists($this->getFullPath($view));
	}

	/**
	 * Get the path to the given template.
	 *
	 * @param  string  $view
	 * @return string
	 */
	public function getPath($view)
	{
		// We'll store all compiled templates using the MD5 hash of their full path
		// since that gives us a convenient, valid file name to use for all the
		// templates that will be used and should be a quick view identifier.
		if ( ! $this->exists($view))
		{
			throw new InvalidViewException;
		}

		$hash = md5($path = $this->getFullPath($view));

		$cachePath = $this->getFullCachePath($hash);

		// If the template source has been changed since it was last compiled we'll
		// re-compile the template and write a new version of the compiled view
		// contents to the cache directory on disk then return its full path.
		if ($this->isExpired($path, $cachePath))
		{
			$this->recompile($path, $cachePath);
		}

		return $cachePath;
	}

	/**
	 * Determine if the given template is expired.
	 *
	 * @param  string  $path
	 * @param  string  $cachePath
	 * @return bool
	 */
	protected function isExpired($path, $cachePath)
	{
		if ( ! $this->files->exists($cachePath))
		{
			return true;
		}

		$lastModified = $this->files->lastModified($path);

		return $lastModified >= $this->files->lastModified($cachePath);
	}

	/**
	 * Recompile the view at a given path.
	 *
	 * @param  string  $path
	 * @param  string  $cachePath
	 * @return void
	 */
	protected function recompile($path, $cachePath)
	{
		$compiled = $this->compiler->compile($this->files->get($path));

		$this->files->put($cachePath, $compiled);
	}

	/**
	 * Get the fully qualified path to a view.
	 *
	 * @param  string  $view
	 * @return string
	 */
	public function getFullPath($view)
	{
		if (strpos($view, 'path: ') === 0)
		{
			return substr($view, 6);
		}

		// If the view begins with a double colon, it means we are loading the view
		// from a named view path, so we will grab the path for the named views
		// and return it to the caller so the view can be loaded from there.
		if (strpos($view, '::') !== false)
		{
			return $this->getNamedViewPath($view);
		}
		else
		{
			$view = $this->formatViewName($view);

			return rtrim($this->paths['*'], '/').'/'.$view;
		}
	}

	/**
	 * Get the fully qualified path to a view.
	 *
	 * @param  string  $view
	 * @return string
	 */
	protected function getNamedViewPath($view)
	{
		list($name, $view) = explode('::', $view);

		$view = $this->formatViewName($view);

		// If the given name hasn't been registered with a path, we'll throw an
		// exception to alert the developer that the given view is not valid.
		// Probably this is a simple misspelling or something of the like.
		if ( ! isset($this->paths[$name]))
		{
			throw new InvalidViewException;
		}

		return rtrim($this->paths[$name], '/').'/'.$view;
	}

	/**
	 * Format the view name to use directory slashes.
	 *
	 * @param  string  $view
	 * @return string
	 */
	protected function formatViewName($view)
	{
		return str_replace('.', '/', $view).'.blade.php';
	}

	/**
	 * Get the fully qualified path to a cached template.
	 *
	 * @param  string  $hash
	 * @return string
	 */
	protected function getFullCachePath($hash)
	{
		return rtrim($this->cache, '/').'/'.$hash;
	}

	/**
	 * Add a path to the template loader.
	 *
	 * @param  string  $name
	 * @param  string  $path
	 * @return void
	 */
	public function addTemplatePath($name, $path)
	{
		$this->paths[$name] = $path;
	}

	/**
	 * Get the Blade compiler used by the loader.
	 *
	 * @return Illuminate\Blade\Compiler
	 */
	public function getCompiler()
	{
		return $this->compiler;
	}

	/**
	 * Get the Illuminate filesystem used by the loader.
	 *
	 * @return Illuminate\Filesystem
	 */
	public function getFilesystem()
	{
		return $this->files;
	}

}
