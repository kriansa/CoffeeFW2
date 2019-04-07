<?php
/**
 * OAuth2 Token
 *
 * @package    FuelPHP/OAuth2
 * @category   Token
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 */

namespace Core\Auth\OAuth2;

class TokenException extends \Exception {}

abstract class Token
{
	/**
	 * Create a new token object.
	 *
	 *     $token = Token::make('access', $name);
	 *
	 * @param string $type Token type
	 * @param array $options Token options
	 * @return \Core\Auth\OAuth2\Token
	 */
	public static function make($type = 'access', array $options = null)
	{
		$class = 'Core\\Auth\\OAuth2\\Token\\' . ucfirst($type);

		return new $class($options);
	}

	/**
	 * Returns the token key.
	 *
	 * @return string
	 */
	abstract public function __toString();
}