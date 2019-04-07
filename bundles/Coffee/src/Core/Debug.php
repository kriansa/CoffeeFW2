<?php

namespace Core;

class Debug
{

	const E_ERROR = 'error';
	const E_WARNING = 'warning';
	const E_DEBUG = 'debug';
	const E_LOG = 'log';

	/**
	 * Stores all the const-to-string values
	 * @var array
	 */
	public static $errorConstToString = array(
		self::E_ERROR => 'Error',
		self::E_WARNING => 'Warning',
		self::E_DEBUG => 'Debug',
		self::E_LOG => 'Log',
	);

	/**
	 * Stores FirePHP instance
	 * @var \Core\Debug\FirePHP
	 */
	protected static $_firePhp = null;

	/**
	 * Store all the files opened by fileHighlight
	 * @var array
	 */
	protected static $_filesCache = array();

	/**
	 * Start the FirePHP handler
	 *
	 * @return void
	 */
	public static function _init()
	{
		static::$_firePhp = Debug\FirePHP::getInstance();
	}

	/**
	 * Log something into the today's log file
	 *
	 * @param string|\Exception $message
	 * @param int $level
	 * @param string $file
	 * @param int $line
	 * @return void
	 */
	public static function log($message, $level = null, $file = null, $line = null)
	{
		if ($message instanceof \Exception)
		{
			$level = static::$errorConstToString[static::E_ERROR];
			$file = $message->getFile();
			$line = $message->getLine();
			$message = (string) $message;
		}
		else
		{
			// If not defined, set the file and line of the callee function
			if ($file === null or $line === null)
			{
				// If called from inside functions like call_user_func
				// the file and line are not defined in backtrace
				// so we have to get the next trace, where it was defined
				$backtrace = debug_backtrace();
				foreach ($backtrace as $trace)
				{
					if (isset($trace['file'], $trace['line']) and $trace['file'] !== __FILE__)
					{
						$file = $trace['file'];
						$line = $trace['line'];
						break;
					}
				}
			}

			if ($level === null)
			{
				$level = static::E_LOG;
			}
			elseif (isset(static::$errorConstToString[$level]))
			{
				$level = static::$errorConstToString[$level];
			}
		}


		$template = "Severity: %s - IP: %s @ %s\nFile: %s @ line %d\nMessage: %s\n---\n";
		$dir = APPPATH . 'Data' . DS . 'Log' . DS . date('Y') . DS . date('m') . '.log';
		is_dir($dir) or File::createDir($dir);
		File::append($dir, date('d') . '.log', sprintf($template, $level, Input::ip(), date('l, jS \o\f F Y h:i:s A P'), $file, $line, $message));
	}

	/**
	 * Quick and nice way to output a mixed variable to the browser
	 *
	 * @var mixed ...
	 * @return void
	 */
	public static function dump()
	{
		$backtrace = debug_backtrace();

		// If being called from within, show the file above in the backtrack
		if (strpos($backtrace[0]['file'], 'Core/Classes/Debug.php') !== false)
		{
			$callee = $backtrace[1];
		}
		else
		{
			$callee = $backtrace[0];
		}

		$callee['file'] = static::cleanPath($callee['file']);

		$arguments = func_get_args();
		$i = 0;

		static::$_firePhp->group($callee['file'].' @ line: '.$callee['line']);
		foreach($arguments as $argument)
		{
			static::$_firePhp->log($argument, 'Variable #'.++$i);
		}
		static::$_firePhp->groupEnd();
	}

	/**
	 * Quick and nice way to output a mixed variable to the browser
	 *
	 * @param mixed ...
	 * @return void
	 */
	public static function inspect()
	{
		$backtrace = debug_backtrace();

		$callee = $backtrace[0];

		$arguments = func_get_args();
		$total_arguments = count($arguments);

		$callee['file'] = static::cleanPath($callee['file']);

		$i=0;
		$table = array();
		$table[] = array('Var', 'Type', 'Value');

		foreach($arguments as $argument)
		{
			$table[] = array('#' . ++$i . '/' . $total_arguments, static::getType($argument), $argument);
		}

		static::$_firePhp->table($callee['file'].' @ line: '.$callee['line'], $table);
	}

	/**
	 * Get the type and info about the variable
	 *
	 * @param mixed	$var
	 * @return string
	 */
	public static function getType($var)
	{
		if (is_array($var))
		{
			return 'Array, ' . count($var) . ' elements';
		}
		elseif (is_string($var))
		{
			return 'String, ' . strlen($var) . ' characters';
		}
		elseif (is_float($var))
		{
			return 'Float';
		}
		elseif (is_int($var))
		{
			return 'Integer';
		}
		elseif (is_null($var))
		{
			return 'Null';
		}
		elseif (is_bool($var))
		{
			return 'Boolean';
		}
		elseif (is_object($var))
		{
			return 'Object: ' . get_class($var);
		}
		elseif (is_resource($var))
		{
			return 'Resource: '  . get_resource_type($var);
		}

		return gettype($var);
	}

	/**
	 * Returns the debug lines from the specified file
	 *
	 * @param string $filepath The file path
	 * @param int $line_num The line number to highlight
	 * @param int $padding The amount of line padding
	 * @return array lines
	 */
	public static function highlightFile($filepath, $line_num, $padding = 5)
	{
		// We cache the entire file to reduce disk IO for multiple errors
		if ( ! isset(static::$_filesCache[$filepath]))
		{
			static::$_filesCache[$filepath] = file($filepath, FILE_IGNORE_NEW_LINES);
			array_unshift(static::$_filesCache[$filepath], '');
		}

		$start = $line_num - $padding;
		if ($start < 0)
			$start = 0;

		$length = ($line_num - $start) + $padding + 1;
		if (($start + $length) > count(static::$_filesCache[$filepath]) - 1)
			$length = null;

		$debug_lines = array_slice(static::$_filesCache[$filepath], $start, $length, true);

		$to_replace = array('<code>', '</code>', '<span style="color: #0000BB">&lt;?php&nbsp;', "\n");
		$replace_with = array('', '', '<span style="color: #0000BB">', '');

		foreach ($debug_lines as &$line)
		{
			$line = str_replace($to_replace, $replace_with, highlight_string('<?php ' . $line, true));
		}

		return $debug_lines;
	}

	/**
	 * Clean the input path and replaces default locals with string for easy debugging
	 *
	 * @param string $path
	 * @return string The path cleaned
	 */
	public static function cleanPath($path)
	{
		static $search = array(APPPATH, COREPATH, '\\');
		static $replace = array('APPPATH/', 'COREPATH/', '/');
		return str_ireplace($search, $replace, $path);
	}

	/**
	 * Display a backtrace
	 *
	 * @return void
	 */
	public static function backtrace()
	{
		$backtrace = debug_backtrace();
		array_shift($backtrace);

		$callee = $backtrace[0];
		$callee['file'] = static::cleanPath($callee['file']);

		return static::$_firePhp->fb($backtrace, $callee['file'].' @ line: '.$callee['line'], Debug\FirePHP::TRACE);
	}

	/**
	 * Prints a list of all currently declared classes.
	 *
	 * @return void
	 */
	public static function classes()
	{
		static::dump(get_declared_classes());
	}

	/**
	 * Prints a list of all currently declared interfaces.
	 *
	 * @return void
	 */
	public static function interfaces()
	{
		static::dump(get_declared_interfaces());
	}

	/**
	 * Prints a list of all currently included (or required) files.
	 *
	 * @return void
	 */
	public static function includes()
	{
		static::dump(get_included_files());
	}

	/**
	 * Prints a list of all currently declared functions.
	 *
	 * @return void
	 */
	public static function functions()
	{
		static::dump(get_defined_functions());
	}

	/**
	 * Prints a list of all currently declared constants.
	 *
	 * @return void
	 */
	public static function constants()
	{
		static::dump(get_defined_constants());
	}

	/**
	 * Prints a list of all currently loaded PHP extensions.
	 *
	 * @return void
	 */
	public static function extensions()
	{
		static::dump(get_loaded_extensions());
	}

	/**
	 * Prints a list of all HTTP request headers.
	 *
	 * @return void
	 */
	public static function headers()
	{
		static::dump(getallheaders());
	}

	/**
	 * Prints a list of the configuration settings read from <i>php.ini</i>
	 *
	 * @return bool False if the php.ini is not readable
	 */
	public static function phpini()
	{
		if ( ! is_readable(get_cfg_var('cfg_file_path')))
		{
			return false;
		}

		// render it
		static::dump(parse_ini_file(get_cfg_var('cfg_file_path'), true));
	}

	/**
	 * Benchmark anything callable
	 *
	 * @param callback $callable
	 * @param array $params
	 * @return array [user, system, result]
	 */
	public static function benchmark($callable, array $params = array())
	{
		// get the before-benchmark time
		if (function_exists('getrusage'))
		{
			$dat = getrusage();
			$utime_before = $dat['ru_utime.tv_sec'] + round($dat['ru_utime.tv_usec']/1000000, 4);
			$stime_before = $dat['ru_stime.tv_sec'] + round($dat['ru_stime.tv_usec']/1000000, 4);
		}
		else
		{
			list($usec, $sec) = explode(" ", microtime());
			$utime_before = ((float)$usec + (float)$sec);
			$stime_before = 0;
		}

		// call the function to be benchmarked
		$result = is_callable($callable) ? call_user_func_array($callable, $params) : null;

		// get the after-benchmark time
		if (function_exists('getrusage'))
		{
			$dat = getrusage();
			$utime_after = $dat['ru_utime.tv_sec'] + round($dat['ru_utime.tv_usec']/1000000, 4);
			$stime_after = $dat['ru_stime.tv_sec'] + round($dat['ru_stime.tv_usec']/1000000, 4);
		}
		else
		{
			list($usec, $sec) = explode(" ", microtime());
			$utime_after = ((float)$usec + (float)$sec);
			$stime_after = 0;
		}

		return array(
			'user' => sprintf('%1.6f', $utime_after - $utime_before),
			'system' => sprintf('%1.6f', $stime_after - $stime_before),
			'result' => $result
		);
	}

}