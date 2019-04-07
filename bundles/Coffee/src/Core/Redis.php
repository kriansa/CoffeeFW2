<?php

namespace Core;

class RedisException extends \Exception {}

class Redis
{
	/**
	 * The connection to the Redis database.
	 * @var resource
	 */
	protected $_connection;

	/**
	 * The active Redis database instances.
	 * @var array
	 */
	protected static $_databases = array();

	/**
	 * Get a Redis database connection instance.
	 *
	 * The given name should correspond to a Redis database in the configuration file.
	 *
	 * <code>
	 *		// Get the default Redis database instance
	 *		$redis = Redis::getInstance();
	 *
	 *		// Get a specified Redis database instance
	 *		$reids = Redis::getInstance('redis_2');
	 * </code>
	 *
	 * @param string $name
	 * @return \Core\Redis
	 */
	public static function getInstance($name = 'default')
	{
		if ( ! isset(static::$_databases[$name]))
		{
			if (is_null($config = Config::get('database.redis.' . $name)))
			{
				throw new RedisException('Redis database "' . $name . '" is not defined.');
			}

			static::$_databases[$name] = new static($config['host'], $config['port']);
		}

		return static::$_databases[$name];
	}

	/**
	 * Create a new Redis connection instance.
	 *
	 * @param string $host
	 * @param string $port
	 * @return void
	 */
	public function  __construct($host, $port)
	{
		$this->_connection = fsockopen($host, $port, $errno, $errstr);

		if ( ! $this->_connection)
		{
			throw new RedisException($errstr, $errno);
		}
	}

	/**
	 * Close the connection to the Redis database.
	 */
	public function __destruct()
	{
		if ($this->_connection)
		{
			fclose($this->_connection);
		}
	}

	/**
	 * Using Redis class statically will call the methods using the default instance
	 *
	 * NOT recommended due to 'call_user_func_array' low performance
	 *
	 * @param string $name
	 * @param array $args
	 * @return type
	 */
	public static function __callStatic($name, array $args = array())
	{
		return call_user_func_array(array(static::getInstance(), $name), $args);
	}

	/**
	 * Dynamically make calls to the Redis database.
	 *
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 */
	public function __call($name, array $args)
	{
		$response = null;

		/*
		Build the Redis command based from a given method and parameters.

		Redis protocol states that a command should conform to the following format:
		    *<number of arguments> CR LF
		    $<number of bytes of argument 1> CR LF
		    <argument data> CR LF
		    ...
		    $<number of bytes of argument N> CR LF
		    <argument data> CR LF

		More information regarding the Redis protocol: http://redis.io/topics/protocol
		*/

		$name = strtoupper($name);

		$command = '*' . (count($args) + 1) . CRLF;
		$command .= '$' . strlen($name) . CRLF;
		$command .= $name . CRLF;

		foreach ($args as $arg)
		{
			$command .= '$' . strlen($arg) . CRLF;
			$command .= $arg . CRLF;
		}

		// Send the formatted command
		fwrite($this->_connection, $command);

		// Get the response from server
		$reply = trim(fgets($this->_connection, 512));

		// Handle with its response
		switch (substr($reply, 0, 1))
		{
			// Error
			case '-':
				throw new RedisException(substr(trim($reply), 4));
			break;

			// In-line reply
			case '+':
				$response = substr(trim($reply), 1);
			break;

			// Bulk reply
			case '$':
				if ($reply == '$-1')
				{
					$response = null;
					break;
				}
				$read = 0;
				$size = substr($reply, 1);
				if ($size > 0)
				{
					do
					{
						$block_size = ($size - $read) > 1024 ? 1024 : ($size - $read);
						$response .= fread($this->_connection, $block_size);
						$read += $block_size;
					} while ($read < $size);
				}
				fread($this->_connection, 2);
			break;

			// Mult-Bulk reply
			case '*':
				$count = substr($reply, 1);
				if ($count == '-1')
				{
					return null;
				}
				$response = array();
				for ($i = 0; $i < $count; $i++)
				{
					$bulk_head = trim(fgets($this->_connection, 512));
					$size = substr($bulk_head, 1);
					if ($size == '-1')
					{
						$response[] = null;
					}
					else
					{
						$read = 0;
						$block = '';
						do
						{
							$block_size = ($size - $read) > 1024 ? 1024 : ($size - $read);
							$block .= fread($this->_connection, $block_size);
							$read += $block_size;
						} while ($read < $size);
						fread($this->_connection, 2); /* discard crlf */
						$response[] = $block;
					}
				}
			break;

			// Integer Reply
			case ':':
				$response = substr(trim($reply), 1);
			break;

			// Don't know what to do?  Throw it outta here
			default:
				throw new RedisException('Invalid server response: "' . $reply . '"');
			break;
		}

		return $response;
	}
}