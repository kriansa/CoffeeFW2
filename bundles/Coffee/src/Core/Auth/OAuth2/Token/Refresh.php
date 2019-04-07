<?php
/**
 * OAuth2 Token
 *
 * @package    OAuth2
 * @category   Token
 * @author     Phil Sturgeon
 * @copyright  (c) 2011 HappyNinjas Ltd
 */

namespace Core\Auth\OAuth2\Token;

class Refresh extends \Core\Auth\OAuth2\Token
{
	/**
	 * Code
	 * @var string
	 */
	public $code;

	/**
	 * Sets the token, expiry, etc values.
	 *
	 * @param array $options Token options
	 * @return void
	 */
	public function __construct(array $options)
	{
		if ( ! isset($options['code']))
		{
			throw new \Core\Auth\OAuth2\TokenException('Required option not passed: code');
		}

		$this->code = $options['code'];
	}

	/**
	 * Returns the token key.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return (string) $this->code;
	}
}