<?php
/**
 * OAuth2 Token (Access)
 *
 * @package    FuelPHP/OAuth2
 * @category   Token
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 */

namespace Core\Auth\OAuth2\Token;

class Access extends \Core\Auth\OAuth2\Token
{
	/**
	 * Access token
	 * @var string
	 */
	public $accessToken;

	/**
	 * Expires
	 * @var int
	 */
	public $expires;

	/**
	 * Refresh token
	 * @var string
	 */
	public $refreshToken;

	/**
	 * UID
	 * @var string
	 */
	public $uid;

	/**
	 * User
	 * @var mixed
	 */
	public $user;

	/**
	 * Sets the token, expiry, etc values.
	 *
	 * @param array $options Token options
	 * @return void
	 */
	public function __construct(array $options)
	{
		if ( ! isset($options['access_token']))
		{
			throw new \Core\Auth\OAuth2\TokenException('Required option not passed: access_token' . PHP_EOL . var_export($options, true));
		}

		// if ( ! isset($options['expires_in']) and ! isset($options['expires']))
		// {
		// 	throw new Exception('We do not know when this access_token will expire');
		// }

		$this->accessToken = $options['access_token'];

		// Some providers (not many) give the uid here, so lets take it
		isset($options['uid']) and $this->uid = $options['uid'];

		// Some providers (not many) give the user here, so lets take it
		isset($options['user']) and $this->user = $options['user'];

		// We need to know when the token expires, add num. seconds to current time
		isset($options['expires_in']) and $this->expires = time() + ((int) $options['expires_in']);

		// Facebook is just being a spec ignoring jerk
		isset($options['expires']) and $this->expires = time() + ((int) $options['expires']);

		// Grab a refresh token so we can update access tokens when they expires
		isset($options['refresh_token']) and $this->refreshToken = $options['refresh_token'];
	}

	/**
	 * Returns the token key.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) $this->accessToken;
	}
}