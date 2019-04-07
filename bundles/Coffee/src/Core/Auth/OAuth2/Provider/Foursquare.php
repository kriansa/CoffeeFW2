<?php
/**
 * Foursquare OAuth2 Provider
 *
 * @package    FuelPHP/OAuth2
 * @category   Provider
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

namespace Core\Auth\OAuth2\Provider;

class Foursquare extends \Core\Auth\OAuth2\Provider
{
	/**
	 * Provider name
	 * @var string
	 */
	public $name = 'Foursquare';

	/**
	 * The method to use when requesting tokens
	 * @var string
	 */
	protected $_method = 'POST';

	/**
	 * The authorization URL for the provider.
	 * @return string
	 */
	protected $_urlAuthorize = 'https://foursquare.com/oauth2/authenticate';

	/**
	 * The access token endpoint for the provider.
	 * @return string
	 */
	protected $_urlAccessToken = 'https://foursquare.com/oauth2/access_token';

	/**
	 * Returns the info about the logged user.
	 *
	 * @return string
	 */
	public function getUserInfo(\Core\Auth\OAuth2\Token\Access $token)
	{
		$url = 'https://api.foursquare.com/v2/users/self?' . http_build_query(array(
			'oauth_token' => $token->accessToken,
		), null, '&');

		$response = json_decode(file_get_contents($url));

		$user = $response->response->user;

		// Create a response from the request
		return array(
			'uid' => $user->id,
			//'nickname' => $user->login,
			'name' => $user->firstName . ' ' . $user->lastName,
			'email' => $user->contact->email,
			'image' => $user->photo,
			'location' => $user->homeCity,
		);
	}
}