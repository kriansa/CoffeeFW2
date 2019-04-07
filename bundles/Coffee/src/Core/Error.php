<?php

namespace Core;

class Error
{
	/**
	 * All the error levels and their names
	 * @var array
	 */
	protected static $_errorLevels = array(
		0 => 'Error',
		E_ERROR => 'Fatal Error',
		E_WARNING => 'Warning',
		E_PARSE => 'Parsing Error',
		E_NOTICE => 'Notice',
		E_CORE_ERROR => 'Core Error',
		E_CORE_WARNING => 'Core Warning',
		E_COMPILE_ERROR => 'Compile Error',
		E_COMPILE_WARNING => 'Compile Warning',
		E_DEPRECATED => 'Deprecated',
		E_USER_DEPRECATED => 'Deprecated',
		E_USER_ERROR => 'User Error',
		E_USER_WARNING => 'User Warning',
		E_USER_NOTICE => 'User Notice',
		E_STRICT => 'Runtime Notice'
	);

	/**
	 * List of fatal errors
	 * @var array
	 */
	protected static $_fatalLevels = array(E_PARSE, E_ERROR, E_CORE_ERROR, E_USER_ERROR, E_COMPILE_ERROR);

	/**
	 * PHP Exception handler
	 *
	 * @param \Exception $exception The exception
	 */
	public static function exceptionHandler(\Exception $exception)
	{
		// Log all the errors
		Debug::log($exception);

		// And output them
		static::_output($exception);
	}

	/**
	 * PHP Error handler
	 *
	 * @param int $severity
	 * @param string $message
	 * @param string $filepath
	 * @param int $line
	 * @return bool
	 */
	public static function errorHandler($severity, $message, $file, $line)
	{
		// Log all the errors
		Debug::log($message, Debug::E_ERROR, $file, $line);

		// Always display fatal errors
		// Only output non-fatal errors when not in production
		if (in_array($severity, static::$_fatalLevels) or ENVIRONMENT !== 'production')
		{
			static::_output(new \ErrorException($message, $severity, 0, $file, $line));
		}

		return true;
	}

	/**
	 * Native PHP shutdown handler to catch the non-catchable fatal errors
	 *
	 * @return void
	 */
	public static function shutdownHandler()
	{
		$last_error = error_get_last();

		// Only show valid fatal errors
		if ($last_error and in_array($last_error['type'], static::$_fatalLevels))
		{
			// Log all the errors
			Debug::log($last_error['message'], Debug::E_ERROR, $last_error['file'], $last_error['line']);
			static::_output(new \ErrorException($last_error['message'], $last_error['type'], 0, $last_error['file'], $last_error['line']));
			die;
		}
	}

	/**
	 * Shows an error. It will stop script execution.
	 *
	 * @param \Exception $exception
	 * @return void
	 */
	protected static function _output(\Exception $exception)
	{
		// In production, output all non-fatal errors
		if (ENVIRONMENT === 'production')
		{
			if ($exception instanceof \ErrorException and ! in_array($exception->getCode(), static::$_fatalLevels))
			{
				Request::getInstance()->setBody(View::make('Error' . DS . 'error-production'))->setStatus(500)->send();
				die;
			}
		}
		// Ajax requests OR non-fatal ErrorExceptions: send the errors via FirePHP
		elseif (Input::isAjax() or ($exception instanceof \ErrorException and ! in_array($exception->getCode(), static::$_fatalLevels)))
		{
			// If it's fatal, then send the response and stop script
			// If sent as ErrorException, the code will contain the severity
			// If thrown as Exception or any inherited class, it should stop
			// Because its severity doesn't matter, it's suposed to be a
			// Normal exception, not a PHP Error
			if($exception instanceof \ErrorException and ! in_array($exception->getCode(), static::$_fatalLevels))
			{
				Debug\FirePHP::getInstance()->warn(Debug::cleanPath($exception->getFile()) . ' @ line: ' . $exception->getLine() . ' - ' . $exception->getMessage());
			}
			// Ajax errors are displayed in Firebug console
			else
			{
				Debug\FirePHP::getInstance()->fb($exception, Debug::cleanPath($exception->getFile()) . ' @ line: ' . $exception->getLine() . ' - ' . $exception->getMessage(), Debug\FirePHP::EXCEPTION);
				Request::getInstance()->setStatus(500)->send(false);
				die;
			}
		}
		else
		{
			Request::getInstance()->setBody(new View('Error' . DS . 'error-' . ENVIRONMENT, static::_formatException($exception), false))->setStatus(500)->send();
			die;
		}
	}

	/**
	 * Format a Exception object to output to the HTML
	 *
	 * @param \Exception $exception
	 * @return array Lines of HTML file
	 */
	protected static function _formatException(\Exception $exception)
	{
		$data = array(
			'type' => get_class($exception),
			'message' => $exception->getMessage(),
			'filepath' => Debug::cleanPath($exception->getFile()),
			'error_line' => $exception->getLine(),
			'backtrace' => $exception->getTrace(),
			'severity' => isset(static::$_errorLevels[$exception->getCode()]) ? static::$_errorLevels[$exception->getCode()] : $exception->getCode(),
			'debug_lines' => Debug::highlightFile($exception->getFile(), $exception->getLine()),
		);


		foreach ($data['backtrace'] as $key => $trace)
		{
			if ( ! isset($trace['file']) or $trace['file'] == COREPATH . 'Classes' . DS . 'Error.php')
			{
				unset($data['backtrace'][$key]);
			}
		}

		return $data;
	}
}