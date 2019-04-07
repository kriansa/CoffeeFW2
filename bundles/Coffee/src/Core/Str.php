<?php

namespace Core;

class StrException extends \Exception {}

class Str
{
	/**
	 * Truncates a string to the given length.  It will optionally preserve
	 * HTML tags if $is_html is set to true.
	 *
	 * @param string $text The string to truncate
	 * @param int $length The number of characters to truncate too
	 * @param string $suffix The string to use to denote it was truncated
	 * @param bool $isHTML Whether the string has HTML
	 * @return string The truncated string
	 */
	public static function truncate($text, $length, $suffix = '&hellip;', $isHTML = true)
	{
		$i = 0;
		$tags = array();

		if ($isHTML)
		{
			preg_match_all('/<[^>]+>([^<]*)/', $text, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
			foreach($m as $o)
			{
				if($o[0][1] - $i >= $length)
					break;

				$t = static::sub(strtok($o[0][0], " \t\n\r\0\x0B>"), 1);

				if($t[0] != '/')
					$tags[] = $t;
				elseif(end($tags) == static::sub($t, 1))
					array_pop($tags);

				$i += $o[1][1] - $o[0][1];
			}
		}

		$output = static::sub($text, 0, $length = min(static::length($text),  $length + $i)) . (count($tags = array_reverse($tags)) ? '</' . implode('></', $tags) . '>' : '');

		// Get everything until last space
		$one = static::sub($output, 0, strrpos($output, " "));
		// Get the rest
		$two = static::sub($output, strrpos($output, " "), (static::length($output) - strrpos($output, " ")));
		// Extract all tags from the last bit
		preg_match_all('/<(.*?)>/s', $two, $tags);
		// Add suffix if needed
		if (static::length($text) > $length) { $one .= $suffix; }
		// Re-attach tags
		$output = $one . implode($tags[0]);

		return $output;
	}

	/**
	 * Check if a string starts with a substring
	 *
	 * @param string $string
	 * @param string $substring
	 * @param string $encoding
	 * @return bool
	 * @throws StrException
	 */
	public static function startsWith($string, $substring, $encoding = null)
	{
		if( ! MBSTRING)
			throw new StrException('mbstring not supported!');

		$encoding or $encoding = Config::get('system.encoding');

		return (mb_substr($string, 0, mb_strlen($substring, $encoding), $encoding) === $substring);
	}

	/**
	 * Check if a string ends with a substring
	 *
	 * @param string $string
	 * @param string $substring
	 * @param string $encoding
	 * @return bool
	 * @throws StrException
	 */
	public static function endsWith($string, $substring, $encoding = null)
	{
		if( ! MBSTRING)
			throw new StrException('mbstring not supported!');

		$encoding or $encoding = Config::get('system.encoding');

		if (!$length = mb_strlen($substring, $encoding))
		{
			return true;
		}

		return (mb_substr($string, -$length, $encoding) === $substring);
	}

	/**
	 * Add's _1 to a string or increment the ending number to allow _2, _3, etc
	 *
	 * @param string $str
	 * @param int $first
	 * @param string $separator
	 * @return string
	 */
	public static function increment($str, $first = 1, $separator = '_')
	{
		preg_match('/(.+)'.$separator.'([0-9]+)$/', $str, $match);

		return isset($match[2]) ? $match[1].$separator.($match[2] + 1) : $str.$separator.$first;
	}

	/**
	 * substr
	 *
	 * @param string $str
	 * @param int $start
	 * @param int $length
	 * @param string $encoding
	 * @return string
	 * @throws StrException
	 */
	public static function sub($str, $start, $length = null, $encoding = null)
	{
		if( ! MBSTRING)
			throw new StrException('mbstring not supported!');

		$encoding or $encoding = Config::get('system.encoding');

		// substr functions don't parse null correctly
		$length = is_null($length) ? (mb_strlen($str, $encoding) - $start) : $length;

		return mb_substr($str, $start, $length, $encoding);
	}

	/**
	 * Checks if the specified byte stream is valid for the specified encoding.
	 * It is useful to prevent so-called "Invalid Encoding Attack".
	 *
	 * @param $string
	 * @param null $encoding
	 * @return bool
	 * @throws StrException
	 */
	public static function check($string, $encoding = null)
	{
		if( ! MBSTRING)
			throw new StrException('mbstring not supported!');

		$encoding or $encoding = Config::get('system.encoding');

		return mb_check_encoding($string, $encoding);
	}

	/**
	 * strlen
	 *
	 * @param string $str
	 * @param string $encoding
	 * @return int
	 * @throws StrException
	 */
	public static function length($str, $encoding = null)
	{
		if( ! MBSTRING)
			throw new StrException('mbstring not supported!');

		$encoding or $encoding = Config::get('system.encoding');

		return mb_strlen($str, $encoding);
	}

	/**
	 * lower
	 *
	 * @param string $str
	 * @param string $encoding
	 * @return string
	 * @throws StrException
	 */
	public static function lower($str, $encoding = null)
	{
		if( ! MBSTRING)
			throw new StrException('mbstring not supported!');

		$encoding or $encoding = Config::get('system.encoding');

		return mb_strtolower($str, $encoding);
	}

	/**
	 * upper
	 *
	 * @param string $str
	 * @param string $encoding
	 * @return string
	 * @throws StrException
	 */
	public static function upper($str, $encoding = null)
	{
		if( ! MBSTRING)
			throw new StrException('mbstring not supported!');

		$encoding or $encoding = Config::get('system.encoding');

		return mb_strtoupper($str, $encoding);
	}

	/**
	 * lcfirst
	 *
	 * Does not strtoupper first
	 *
	 * @param string $str
	 * @param string $encoding
	 * @return string
	 * @throws StrException
	 */
	public static function lcfirst($str, $encoding = null)
	{
		if( ! MBSTRING)
			throw new StrException('mbstring not supported!');

		$encoding or $encoding = Config::get('system.encoding');

		return mb_strtolower(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
	}

	/**
	 * ucfirst
	 *
	 * Does not strtolower first
	 *
	 * @param string $str
	 * @param string $encoding
	 * @return string
	 * @throws StrException
	 */
	public static function ucfirst($str, $encoding = null)
	{
		if( ! MBSTRING)
			throw new StrException('mbstring not supported!');

		$encoding or $encoding = Config::get('system.encoding');

		return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
	}

	/**
	 * ucwords
	 *
	 * First strtolower then ucwords
	 *
	 * ucwords normally doesn't strtolower first
	 * but MB_CASE_TITLE does, so ucwords now too
	 *
	 * @param string $str
	 * @param string $encoding
	 * @return string
	 * @throws StrException
	 */
	public static function ucwords($str, $encoding = null)
	{
		if( ! MBSTRING)
			throw new StrException('mbstring not supported!');

		$encoding or $encoding = Config::get('system.encoding');

		return mb_convert_case($str, MB_CASE_TITLE, $encoding);
	}

	/**
	  * Creates a random string of characters
	  *
	  * @param string $type The type of string
	  * @param int $length The number of characters
	  * @return string The random string
	  */
	public static function random($type = 'alnum', $length = 16)
	{
		switch($type)
		{
			case 'basic':
				return mt_rand();
				break;

			default:
			case 'alnum':
			case 'numeric':
			case 'nozero':
			case 'alpha':
			case 'distinct':
			case 'hexdec':
				switch ($type)
				{
					case 'alpha':
						$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						break;

					default:
					case 'alnum':
						$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
						break;

					case 'numeric':
						$pool = '0123456789';
						break;

					case 'nozero':
						$pool = '123456789';
						break;

					case 'distinct':
						$pool = '2345679ACDEFHJKLMNPRSTUVWXYZ';
						break;

					case 'hexdec':
						$pool = '0123456789abcdef';
						break;
				}

				$str = '';
				for ($i = 0; $i < $length; $i++)
				{
					$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
				}
				return $str;
				break;

			case 'unique':
				return md5(uniqid(mt_rand()));
				break;

			case 'sha1':
				return sha1(uniqid(mt_rand(), true));
				break;
		}
	}

	/**
	 * Returns a closure that will alternate between the args which to return.
	 * If you call the closure with false as the arg it will return the value without
	 * alternating the next time.
	 *
	 * @param string ...
	 * @return Closure
	 */
	public static function alternator()
	{
		// the args are the values to alternate
		$args = func_get_args();

		return function ($next = true) use ($args)
		{
			static $i = 0;
			return $args[($next ? $i++ : $i) % count($args)];
		};
	}

	/**
	 * Parse the params from a string using strtr()
	 *
	 * @param string $string
	 * @param array $array
	 * @return string
	 */
	public static function tr($string, $array = array())
	{
		if (is_string($string))
		{
			$tr_arr = array();

			foreach ($array as $from => $to)
			{
				$tr_arr[':'.$from] = $to;
			}
			unset($array);

			return strtr($string, $tr_arr);
		}
		else
		{
			return $string;
		}
	}

	/**
	 * Get the encoding of the given string
	 *
	 * @param string $string
	 * @param string|array $encodeList Possible encoding values of the string
	 * @return string
	 * @throws StrException
	 */
	public static function getEncoding($string, $encodeList = 'UTF-8, ISO-8859-1')
	{
		if( ! MBSTRING)
			throw new StrException('mbstring not supported!');

		return mb_detect_encoding($string, $encodeList, true);
	}

	/**
	 * Convert the string between different encodings
	 *
	 * @param string $string
	 * @param string $encoding
	 * @param string|array $encodeList Possible origin encoding values of the string
	 * @return string
	 * @throws StrException
	 */
	public static function convertEncoding($string, $encoding = 'HTML-ENTITIES', $encodeList = 'UTF-8, ISO-8859-1')
	{
		if( ! MBSTRING)
			throw new StrException('mbstring not supported!');

		return mb_convert_encoding($string, $encoding, static::getEncoding($string, $encodeList));
	}
}