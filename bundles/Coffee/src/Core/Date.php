<?php

namespace Core;

class DateException extends \Exception {}

class Date
{
	/**
	 * Time constants (and only those that are constant, thus not MONTH/YEAR)
	 */
	const WEEK   = 604800;
	const DAY    = 86400;
	const HOUR   = 3600;
	const MINUTE = 60;
	const SECOND = 1;

	/**
	 * Default timezone of the app
	 * @var string
	 */
	protected static $_defaultTimezone = null;

	/**
	 * Datetime instance
	 * @var \DateTime
	 */
	public $datetime = null;

	/**
	 * Set initial default configs
	 */
	public static function _init()
	{
		static::$_defaultTimezone = Config::get('system.timezone', 'UTC');

		// Ugly temporary windows fix because windows doesn't support strptime()
		// Better fix will accept custom pattern parsing but only parse
		// numeric input on windows servers
		if ( ! function_exists('strptime'))
		{
			/**
			 * Implementation of strptime() for PHP on Windows.
			 *
			 * @param string $date
			 * @param string $format
			 * @return array Parsed date
			 */
			function strptime($date, $format)
			{
				// Translate some numeric strftime() format to date's
				$format = strtr($format, array(
					'%d' => 'd',
					'%e' => 'j',
					'%e' => 'j',
					'%w' => 'w',
					'%W' => 'W',
					'%m' => 'm',
					'%y' => 'y',
					'%Y' => 'Y',
					'%H' => 'H',
					'%I' => 'h',
					'%l' => 'g',
					'%M' => 'i',
					'%p' => 'A',
					'%P' => 'a',
					'%r' => 'h:i:s A',
					'%R' => 'h:i',
					'%S' => 's',
					'%T' => 'h:i:s',
					'%z' => 'T',
					'%Z' => 'T',
					'%D' => 'm/d/y',
					'%F' => 'Y-m-d',
					'%s' => 'U',
					'%n' => "\n",
					'%t' => "\t",
					'%%' => '%',
				));

				if ( ! $date = \DateTime::createFromFormat($format, $date))
				{
					return false;
				}

				return localtime($date->getTimestamp(), true);
			}
		}
	}

	/**
	 * Create a new Date instance
	 *
	 * @param null $time Current time
	 * @param int $time Unix timestamp
	 * @param string $time A date/time string. Valid formats are explained in Date and Time Formats.
	 * @param \Core\Date $time A existing Date instance
	 * @param \DateTime $time A native DateTime instance
	 * @param null $timezone Current timezone
	 * @param string $timezone A valid Timezone value
	 * @param \DateTimeZone $timezone A existing native \DateTimeZone instance
	 * @param \Core\Date $timezone A existing Date instance
	 */
	public function __construct($time = null, $timezone = null)
	{
		// Set the internal DateTimeZone instance
		$timezone = static::_getDateTimeZone($timezone);

		// Set the internal DateTime instance
		if ($time instanceof Date)
		{
			$this->datetime = $time->datetime;
		}
		elseif(is_string($time))
		{
			$this->datetime = new \DateTime($time, $timezone);
		}
		elseif(is_int($time))
		{
			$this->datetime = new \DateTime(null, $timezone);
			$this->datetime->setTimestamp($time);
		}
		elseif ($time instanceof \DateTime)
		{
			$this->datetime = clone $time;
		}
		elseif ($time === null)
		{
			$this->datetime = new \DateTime('now', $timezone);
		}
		else
		{
			throw new DateException('Date format type "' . Debug::getType($time) . '" not recognized!');
		}
	}

	/**
	 * Create a new Date instance, timezone is optional
	 *
	 * @param null $time Current time
	 * @param int $time Unix timestamp
	 * @param string $time A date/time string. Valid formats are explained in Date and Time Formats.
	 * @param \Core\Date $time A existing Date instance
	 * @param \DateTime $time A native DateTime instance
	 * @param null $timezone Current timezone
	 * @param string $timezone A valid Timezone value
	 * @param \DateTimeZone $timezone A existing native \DateTimeZone instance
	 * @param \Core\Date $timezone A existing Date instance
	 * @return Date $this
	 */
	public static function make($time = null, $timezone = null)
	{
		return new static($time, $timezone);
	}

	/**
	 * Uses the date config file to translate string input to timestamp
	 *
	 * @param string $input Date/time input
	 * @param string $pattern_key Either a named pattern from date config file or a pattern, defaults to 'local'
	 * @param null $timezone Current timezone
	 * @param string $timezone A valid Timezone value
	 * @param \DateTimeZone $timezone A existing native \DateTimeZone instance
	 * @param \Core\Date $timezone A existing Date instance
	 * @return \Core\Date
	 */
	public static function fromFormat($input, $pattern_key = 'local', $timezone = null)
	{
		$pattern = Config::get('date.patterns.' . $pattern_key, $pattern_key);

		if ( ! $time = strptime($input, $pattern))
		{
			throw new DateException('Invalid date format "' . $input . '" for the pattern "' . $pattern . '".');
		}

		$time = mktime($time['tm_hour'], $time['tm_min'], $time['tm_sec'], $time['tm_mon'] + 1, $time['tm_mday'], $time['tm_year'] + 1900);
		return new static($time, $timezone);
	}

	/**
	 * Returns the date formatted according to the current locale
	 *
	 * @param string Either a named pattern from date config file or a pattern, defaults to 'local'
	 * @param null $timezone Current timezone
	 * @param string $timezone A valid Timezone value
	 * @param \DateTimeZone $timezone A existing native \DateTimeZone instance
	 * @param \Core\Date $timezone A existing Date instance
	 * @param bool $strftime Whether to use stftime instead, to produce locale aware dates
	 * @return string Date formatted
	 */
	public function toFormat($pattern_key = 'local', $timezone = null)
	{
		$pattern = Config::get('date.patterns.' . $pattern_key, $pattern_key);

		$timestamp = $this->datetime->getTimestamp();

		if($timezone !== null)
		{
			$timestamp = $timestamp + static::getTimezoneDifference($timezone);
		}

		// Create output
		$output = strftime($pattern, $timestamp);

		return $output;
	}

	/**
	 * Get a DateTimeZone object from a string, int DateTimeZone or Date object
	 *
	 * @param null $timezone Current timezone
	 * @param string $timezone A valid Timezone value
	 * @param \DateTimeZone $timezone A existing native \DateTimeZone instance
	 * @param \Core\Date $timezone A existing Date instance
	 * @return \DateTimeZone
	 * @throws DateException
	 */
	protected static function _getDateTimeZone($timezone)
	{
		if ($timezone === null)
		{
			$timezone = new \DateTimeZone(static::$_defaultTimezone);
		}
		elseif($timezone instanceof \DateTimeZone)
		{
			// Do nothing
		}
		elseif(is_string($timezone))
		{
			$timezone = new \DateTimeZone($timezone);
		}
		elseif ($timezone instanceof Date)
		{
			$timezone = $timezone->datetime->getTimezone();
		}
		else
		{
			throw new DateException('Timezone format type "' . Debug::getType($timezone) . '" not recognized!');
		}

		return $timezone;
	}

	/**
	 * Set the current timezone
	 *
	 * @param null $timezone Current timezone
	 * @param string $timezone A valid Timezone value
	 * @param \DateTimeZone $timezone A existing native \DateTimeZone instance
	 * @param \Core\Date $timezone A existing Date instance
	 * @return Date $this
	 */
	public function setTimezone($timezone = null)
	{
		$this->datetime->setTimezone(static::_getDateTimeZone($timezone));

		return $this;
	}

	/**
	 * Get the current timezone string
	 *
	 * @return string
	 */
	public function getTimezone()
	{
		return $this->datetime->getTimezone()->getName();
	}

	/**
	 * Returns the current timestamp
	 *
	 * @param int $timestamp
	 * @return Date $this
	 */
	public function setTimestamp($timestamp)
	{
		$this->datetime->setTimestamp($timestamp);
		return $this;
	}

	/**
	 * Returns the current timestamp
	 *
	 * @return int
	 */
	public function getTimestamp()
	{
		return $this->datetime->getTimestamp();
	}

	/**
	 * Alter the timestamp of a DateTime object by incrementing or decrementing
	 * in a format accepted by strtotime().
	 *
	 * @param string $modify
	 * @return \Core\Date $this
	 */
	public function modify($modify)
	{
		$this->datetime->modify($modify);
		return $this;
	}

	/**
	 * Return a DateInterval instance of the difference between these dates
	 *
	 * @param \Core\Date $date
	 * @param \DateTime $date
	 * @param bool $absolute
	 * @return \DateInterval
	 */
	public function diff($date, $absolute = false)
	{
		if ($date instanceof Date)
		{
			$date = $date->datetime;
		}
		elseif ($date instanceof \DateTime)
		{
			// Already a DateTime instance
		}
		else
		{
			throw new DateException('Date format type "' . Debug::getType($date) . '" not recognized!');
		}

		return $this->datetime->diff($date, $absolute);
	}

	/**
	 * Get the difference between two timezones in seconds
	 *
	 * @param null $remote_tz Current timezone
	 * @param string $remote_tz A valid Timezone value
	 * @param \DateTimeZone $remote_tz A existing native \DateTimeZone instance
	 * @param \Core\Date $remote_tz A existing Date instance
	 * @param null $origin_tz Current timezone
	 * @param string $origin_tz A valid Timezone value
	 * @param \DateTimeZone $origin_tz A existing native \DateTimeZone instance
	 * @param \Core\Date $origin_tz A existing Date instance
	 * @return int
	 */
	public static function getTimezoneDifference($remote_tz, $origin_tz = null)
	{
		$origin_dtz = static::_getDateTimeZone($remote_tz);
		$remote_dtz = static::_getDateTimeZone($origin_tz);

		return $origin_dtz->getOffset(new \DateTime('now', $origin_dtz)) - $remote_dtz->getOffset(new \DateTime('now', $remote_dtz));
	}

	/**
	 * Fetches an array of Date objects per interval within a range
	 *
	 * @param null $start Current time
	 * @param int $start Unix timestamp
	 * @param string $start A date/time string. Valid formats are explained in Date and Time Formats.
	 * @param \Core\Date $start A existing Date instance
	 * @param \DateTime $start A native DateTime instance
	 * @param null $end Current time
	 * @param int $end Unix timestamp
	 * @param string $end A date/time string. Valid formats are explained in Date and Time Formats.
	 * @param \Core\Date $end A existing Date instance
	 * @param \DateTime $end A native DateTime instance
	 * @param string $interval Length of the interval in seconds
	 * @param int $interval A valid strtotime time difference
	 * @return array Array of Date objects
	 */
	public static function rangeToArray($start, $end, $interval = '+1 Day')
	{
		if ( ! $start instanceof Date)
		{
			$start = new static($start);
		}

		if ( ! $end instanceof Date)
		{
			$end = new static($end);
		}

		is_int($interval) or $interval = strtotime($interval, $start->getTimestamp()) - $start->getTimestamp();

		if ($interval <= 0)
		{
			throw new \UnexpectedValueException('Input was not recognized by pattern.');
		}

		$range   = array();
		$current = $start;

		while ($current->getTimestamp() <= $end->getTimestamp())
		{
			$range[] = $current;
			$current = new static($current->getTimestamp() + $interval);
		}

		return $range;
	}

	/**
	 * Returns the number of days in the requested month
	 *
	 * @param int Month as a number (1-12)
	 * @param int The year, leave empty for current
	 * @return int The number of days in the month
	 */
	public static function daysInMonth($month, $year = null)
	{
		$year  = ! empty($year) ? (int) $year : (int) date('Y');
		$month = (int) $month;

		if ($month < 1 or $month > 12)
		{
			throw new \UnexpectedValueException('Invalid input for month given.');
		}
		elseif ($month == 2 and ($year % 400 == 0 or ($year % 4 == 0 and $year % 100 != 0)))
		{
			return 29;
		}

		$days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		return $days_in_month[$month-1];
	}

	/**
	 * Returns the time ago
	 *
	 * @param int $timestamp Unix timestamp
	 * @param string $timestamp A date/time string. Valid formats are explained in Date and Time Formats.
	 * @param \Core\Date $timestamp A existing Date instance
	 * @param \DateTime $timestamp A native DateTime instance
	 * @param int $from_timestamp Unix timestamp to compare against
	 * @param string $from_timestamp A date/time string. Valid formats are explained in Date and Time Formats.
	 * @param \Core\Date $from_timestamp A existing Date instance
	 * @param \DateTime $from_timestamp A native DateTime instance
	 * @return string Time ago
	 */
	public static function timeAgo($timestamp, $from_timestamp = null)
	{
		if ($timestamp === null)
		{
			return '';
		}

		! is_numeric($timestamp) and $timestamp = static::make($timestamp)->getTimestamp();

		if ($from_timestamp === null)
		{
			$from_timestamp = time();
		}
		elseif( ! is_numeric($from_timestamp))
		{
			$from_timestamp = static::make($from_timestamp)->getTimestamp();
		}

		// Get the difference between the timestamps
		$difference = $from_timestamp - $timestamp;

		if ($difference < 3)
		{
			return Lang::get('date.now');
		}

		$periods = array(
			12 * 30 * 24 * 60 * 60 => 'year',
			30 * 24 * 60 * 60 => 'month',
			24 * 60 * 60 => 'day',
			60 * 60 => 'hour',
			60 => 'minute',
			1 => 'second'
		);

		foreach ($periods as $secs => $str)
		{
			$time = $difference / $secs;
			if ($time >= 1)
			{
				$number = round($time);
				return $number . ' ' . Lang::get('date.' . ($number > 1 ? $str . 's' : $str));
			}
		}
	}

	/**
	 * Allows you to just put the object in a string and get it inserted in the default pattern
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toFormat();
	}

	/**
	 * Allows you to just put the object in a SQL query and get it inserted in the default pattern
	 *
	 * @return string
	 */
	public function __toSql($driver)
	{
		return $this->toFormat('sql.' . $driver);
	}
}