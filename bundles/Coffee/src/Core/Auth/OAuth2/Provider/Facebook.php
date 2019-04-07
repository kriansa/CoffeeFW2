<?php
/**
 * Facebook OAuth2 Provider
 *
 * @package    FuelPHP/OAuth2
 * @category   Provider
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

namespace Core\Auth\OAuth2\Provider;

class Facebook extends \Core\Auth\OAuth2\Provider
{
	/**
	 * Provider name
	 * @var string
	 */
	public $name = 'Facebook';

	/**
	 * Default scope
	 * @var string
	 */
	protected $_scope = array('email', 'read_stream');

	/**
	 * The authorization URL for the provider.
	 * @return string
	 */
	protected $_urlAuthorize = 'https://www.facebook.com/dialog/oauth';

	/**
	 * The access token endpoint for the provider.
	 * @return string
	 */
	protected $_urlAccessToken = 'https://graph.facebook.com/oauth/access_token';

	/**
	 * Returns the info about the logged user.
	 *
	 * @return string
	 */
	public function getUserInfo(\Core\Auth\OAuth2\Token\Access $token)
	{
		$url = 'https://graph.facebook.com/me?' . http_build_query(array(
			'access_token' => $token->accessToken,
		), null, '&');

		$user = json_decode(file_get_contents($url));

		// Create a response from the request
		return array(
			'uid' => $user->id,
			'name' => $user->name,
			'nickname' => isset($user->username) ? $user->username : null,
			'email' => isset($user->email) ? $user->email : null,
			'image' => 'https://graph.facebook.com/me/picture?type=normal&access_token=' . $token->accessToken,
			'urls' => array(
			  'Facebook' => $user->link,
			),
		);
	}
}