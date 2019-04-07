<?php
/**
 * Github OAuth2 Provider
 *
 * @package    FuelPHP/OAuth2
 * @category   Provider
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

namespace Core\Auth\OAuth2\Provider;

class Github extends \Core\Auth\OAuth2\Provider
{
	/**
	 * Provider name
	 * @var string
	 */
	public $name = 'Github';

	/**
	 * The authorization URL for the provider.
	 * @return string
	 */
	protected $_urlAuthorize = 'https://github.com/login/oauth/authorize';

	/**
	 * The access token endpoint for the provider.
	 * @return string
	 */
	protected $_urlAccessToken = 'https://github.com/login/oauth/access_token';

	/**
	 * Returns the info about the logged user.
	 *
	 * @return string
	 */
	public function getUserInfo(\Core\Auth\OAuth2\Token\Access $token)
	{
		$url = 'https://api.github.com/user?'.http_build_query(array(
			'access_token' => $token->accessToken,
		), null, '&');

		$user = json_decode(file_get_contents($url));

		// Create a response from the request
		return array(
			'uid' => $user->id,
			'nickname' => $user->login,
			'name' => $user->name,
			'email' => $user->email,
			'urls' => array(
			  'GitHub' => 'http://github.com/'.$user->login,
			  'Blog' => $user->blog,
			),
		);
	}
}