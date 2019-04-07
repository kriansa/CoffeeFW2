<?php

namespace Core;

/**
 * Default system events:
 *
 * - shutdown
 * - before_send_body
 * - before_send_headers
 *
 */
abstract class Event
{
	/**
	 * @var array An array of listeners
	 */
	protected static $_events = [];

	/**
	 * Register
	 *
	 * Registers a Callback for a given event
	 *
	 * @param string $event The name of the event
	 * @param callable $callback The callback function
	 * @param bool $first_on_callstack Whether to insert the callback on TOP of callstack
	 * @return bool
	 */
	public static function register($event, callable $callback, $first_on_callstack = false)
	{
		// if the arguments are valid, register the event
		if (isset($event) and is_string($event) and isset($callback) and is_callable($callback))
		{
			// make sure we have an array for this event
			isset(static::$_events[$event]) or static::$_events[$event] = array();

			// store the callback on the call stack
			if ($first_on_callstack)
			{
				static::$_events[$event][] = $callback;
			}
			else
			{
				array_unshift(static::$_events[$event], $callback);
			}

			// and report success
			return true;
		}

		// can't register the event
		return false;
	}

	/**
	 * Unregister/remove one or all callbacks from event
	 *
	 * @param string $event Event to remove from
	 * @param callable $callback Callback to remove [optional, null for all]
	 * @return bool Wether one or all callbacks have been removed
	 */
 	public static function unregister($event, callable $callback = null)
	{
		if (isset(static::$_events[$event]))
		{
			if ($callback === null)
			{
				static::$_events[$event] = array();
				return true;
			}

			foreach (static::$_events[$event] as $i => $arguments)
			{
				if($callback === $arguments)
				{
					unset(static::$_events[$event][$i]);
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Trigger
	 *
	 * Triggers an event and returns the results.  The results can be returned
	 * in the following formats:
	 *
	 * 'array'
	 * 'json'
	 * 'serialized'
	 * 'string'
	 *
	 * @param string $event The name of the event
	 * @param mixed $data Any data that is to be passed to the listener as an argument
	 * @param string $return_type The return type
	 * @param bool $reversed Wether to fire events ordered LIFO instead of FIFO
	 * @return mixed The return of the listeners, in the return type
	 */
	public static function trigger($event, $data = null, $return_type = 'array', $reversed = false)
	{
		$calls = array();

		// check if we have events registered
		if (static::hasEvents($event))
		{
			$events = $reversed ? array_reverse(static::$_events[$event], true) : static::$_events[$event];

			// process them
			foreach ($events as $event)
			{
				// call the callback event
				if (is_callable($event))
				{
					if (is_string($event) and strpos($event, '::') !== false)
					{
						$event = explode('::', $event);
					}

					$calls[] = $event($data);
				}
			}
		}

		switch ($return_type)
		{
			case 'array':
				return $calls;

			case 'json':
				return json_encode($calls);

			case 'none':
				return;

			case 'serialized':
				return serialize($calls);

			case 'string':
				$str = '';
				foreach ($calls as $call)
				{
					$str .= $call;
				}
				return $str;

			default:
				return $calls;
		}
	}

	/**
	 * Has Listeners
	 *
	 * Checks if the event has listeners
	 *
	 * @access public
	 * @param string $event The name of the event
	 * @return bool Whether the event has listeners
	 */
	public static function hasEvents($event)
	{
		if (isset(static::$_events[$event]) and count(static::$_events[$event]) > 0)
		{
			return true;
		}
		return false;
	}
}