<?php
/**
 * Soundcloud OAuth2 Provider
 *
 * @package    FuelPHP/OAuth2
 * @category   Provider
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

namespace Core\Auth\OAuth2\Provider;

class Soundcloud extends \Core\Auth\OAuth2\Provider
{
	/**
	 * Provider name
	 * @var string
	 */
	public $name = 'Soundcloud';

	/**
	 * The method to use when requesting tokens
	 * @var string
	 */
	protected $_method = 'POST';

	/**
	 * The authorization URL for the provider.
	 * @return string
	 */
	protected $_urlAuthorize = 'https://soundcloud.com/connect';

	/**
	 * The access token endpoint for the provider.
	 * @return string
	 */
	protected $_urlAccessToken = 'https://api.soundcloud.com/oauth2/token';

	/**
	 * Returns the info about the logged user.
	 *
	 * @return string
	 */
	public function getUserInfo(\Core\Auth\OAuth2\Token\Access $token)
	{
		$url = 'https://api.soundcloud.com/me.json?' . http_build_query(array(
			'oauth_token' => $token->accessToken,
		), null, '&');

		$user = json_decode(file_get_contents($url));

		// Create a response from the request
		return array(
			'uid' => $user->id,
			'nickname' => $user->username,
			'name' => $user->full_name,
			'location' => $user->country.' ,'.$user->country,
			'description' => $user->description,
			'image' => $user->avatar_url,
			'urls' => array(
				'MySpace' => $user->myspace_name,
				'Website' => $user->website,
			),
		);
	}
}