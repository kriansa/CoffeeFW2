<?php

namespace Core;

class Html
{
	/**
	 * Takes an array of attributes and turns it into a string for an html tag
	 *
	 * @param array $attr
	 * @return string
	 */
	public static function arrayToAttr($attr)
	{
		$attr_str = '';

		if ( ! is_array($attr))
		{
			$attr = (array) $attr;
		}

		foreach ($attr as $property => $value)
		{
			// Ignore null values
			if (is_null($value))
			{
				continue;
			}

			// If the key is numeric then it must be something like selected="selected"
			if (is_numeric($property))
			{
				$property = $value;
			}

			$attr_str .= $property . '="' . $value . '" ';
		}

		// We strip off the last space for return
		return trim($attr_str);
	}

	/**
	* Create a XHTML tag
	*
	* @param string The tag name
	* @param array|string The tag attributes
	* @param string|bool The content to place in the tag, or false for no closing tag
	* @return string
	*/
	public static function tag($tag, $attr = array(), $content = false)
	{
		$has_content = (bool) ($content !== false and $content !== null);
		$html = '<' . $tag;

		$html .= ( ! empty($attr)) ? ' ' . (is_array($attr) ? static::arrayToAttr($attr) : $attr) : '';
		$html .= $has_content ? '>' : ' />';
		$html .= $has_content ? $content.'</' . $tag . '>' : '';

		return $html;
	}

	/**
	 * Creates an html link
	 *
	 * @param string The url
	 * @param string The text value
	 * @param array The attributes array
	 * @param bool Ttrue to force https, false to force http
	 * @return string The html link
	 */
	public static function anchor($href, $text = null, $attr = array(), $secure = null)
	{
		if ( ! preg_match('#^(\w+://|javascript:|\#)# i', $href))
		{
			$urlparts = explode('?', $href, 2);
			$href = \Uri::create($urlparts[0], array(), isset($urlparts[1])?$urlparts[1]:array(), $secure);
		}
		elseif ( ! preg_match('#^(javascript:|\#)# i', $href) and  is_bool($secure))
		{
			$href = http_build_url($href, array('scheme' => $secure ? 'https' : 'http'));
		}

		// Create and display a URL hyperlink
		is_null($text) and $text = $href;

		$attr['href'] = $href;

		return static::tag('a', $attr, $text);
	}

	/**
	 * Creates an html image tag
	 *
	 * Sets the alt atribute to filename of it is not supplied.
	 *
	 * @param string The source
	 * @param array The attributes array
	 * @return string The image tag
	 */
	public static function img($src, $attr = array())
	{
		if ( ! preg_match('#^(\w+://)# i', $src))
		{
			$src = URL::getBase() . $src;
		}
		$attr['src'] = $src;
		$attr['alt'] = (isset($attr['alt'])) ? $attr['alt'] : pathinfo($src, PATHINFO_FILENAME);
		return static::tag('img', $attr);
	}

	/**
	 * Adds the given schema to the given URL if it is not already there.
	 *
	 * @param string The url
	 * @param string The schema
	 * @return string Url with schema
	 */
	public static function prepUrl($url, $schema = 'http')
	{
		if ( ! preg_match('#^(\w+://|javascript:)# i', $url))
		{
			$url = $schema.'://'.$url;
		}

		return $url;
	}

	/**
	 * Creates a mailto link.
	 *
	 * @param string $email The email address
	 * @param string $text The text value
	 * @param string $subject The subject
	 * @param array $attr The atributtes
	 * @return string The mailto link
	 */
	public static function mailTo($email, $text = null, $subject = null, $attr = array())
	{
		$text or $text = $email;

		$subject and $subject = '?subject='.$subject;

		return static::tag('a', array(
			'href' => 'mailto:'.$email.$subject,
		) + $attr, $text);
	}

	/**
	 * Creates a mailto link with Javascript to prevent bots from picking up the
	 * email address.
	 *
	 * @param string The email address
	 * @param string The text value
	 * @param string The subject
	 * @param array Attributes for the tag
	 * @return string The javascript code containg email
	 */
	public static function mailToSafe($email, $text = null, $subject = null, $attr = array())
	{
		$text or $text = str_replace('@', '[at]', $email);

		$email = explode("@", $email);

		$subject and $subject = '?subject='.$subject;

		$attr = array_to_attr($attr);
		$attr = ($attr == '' ? '' : ' ').$attr;

		$output = '<script type="text/javascript">';
		$output .= '(function() {';
		$output .= 'var user = "'.$email[0].'";';
		$output .= 'var at = "@";';
		$output .= 'var server = "'.$email[1].'";';
		$output .= "document.write('<a href=\"' + 'mail' + 'to:' + user + at + server + '$subject\"$attr>$text</a>');";
		$output .= '})();';
		$output .= '</script>';
		return $output;
	}

	/**
	 * Generates a html meta tag
	 *
	 * @param string|array Multiple inputs or name/http-equiv value
	 * @param string Content value
	 * @param string Name or http-equiv
	 * @return string
	 */
	public static function meta($name = '', $content = '', $type = 'name')
	{
		if( ! is_array($name))
		{
			$result = static::tag('meta', array($type => $name, 'content' => $content));
		}
		elseif(is_array($name))
		{
			$result = "";
			foreach($name as $array)
			{
				$meta = $array;
				$result .= "\n" . static::tag('meta', $meta);
			}
		}
		return $result;
	}

	/**
	 * Generates a html un-ordered list tag
	 *
	 * @param array List items, may be nested
	 * @param array|string Outer list attributes
	 * @return string
	 */
	public static function ul(array $list = array(), $attr = false)
	{
		return static::_buildList('ul', $list, $attr);
	}

	/**
	 * Generates a html ordered list tag
	 *
	 * @param array List items, may be nested
	 * @param array|string Outer list attributes
	 * @return string
	 */
	public static function ol(array $list = array(), $attr = false)
	{
		return static::_buildList('ol', $list, $attr);
	}

	/**
	 * Generates the html for the list methods
	 *
	 * @param string List type (ol or ul)
	 * @param array	List items, may be nested
	 * @param array	Tag attributes
	 * @param string Indentation
	 * @return string
	 */
	protected static function _buildList($type = 'ul', array $list = array(), $attr = false, $indent = '')
	{
		if ( ! is_array($list))
		{
			$result = false;
		}

		$out = '';
		foreach ($list as $key => $val)
		{
			if ( ! is_array($val))
			{
				$out .= $indent . "\t" . static::tag('li', false, $val) . PHP_EOL;
			}
			else
			{
				$out .= $indent . "\t" . static::tag('li', false, $key . PHP_EOL . static::_buildList($type, $val, '', $indent . "\t\t") . $indent . "\t") . PHP_EOL;
			}
		}
		$result = $indent.static::tag($type, $attr, PHP_EOL . $out . $indent) . PHP_EOL;
		return $result;
	}
}