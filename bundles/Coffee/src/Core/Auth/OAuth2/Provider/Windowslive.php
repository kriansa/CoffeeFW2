<?php
/**
 * Windowslive OAuth2 Provider
 *
 * @package    FuelPHP/OAuth2
 * @category   Provider
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

namespace Core\Auth\OAuth2\Provider;

class Windowslive extends \Core\Auth\OAuth2\Provider
{
	/**
	 * Provider name
	 * @var string
	 */
	public $name = 'Windowslive';

	/**
	 * Default scope
	 * @var string
	 */
	protected $_scope = array('wl.basic', 'wl.emails');

	/**
	 * The authorization URL for the provider.
	 * @return string
	 */
	protected $_urlAuthorize = 'https://oauth.live.com/authorize';

	/**
	 * The access token endpoint for the provider.
	 * @return string
	 */
	protected $_urlAccessToken = 'https://oauth.live.com/token';

	/**
	 * Returns the info about the logged user.
	 *
	 * This can be extended through the
	 * use of scopes, check out the document at
	 * http://msdn.microsoft.com/en-gb/library/hh243648.aspx#user
	 *
	 * @return string
	 */
	public function getUserInfo(\Core\Auth\OAuth2\Token\Access $token)
	{
		// define the get user information token
		$url = 'https://apis.live.net/v5.0/me?' . http_build_query(array(
			'access_token' => $token->accessToken,
		), null, '&');

		// perform network request
		$user = json_decode(file_get_contents($url));

		// create a response from the request and return it
		return array(
			'uid' 			=> $user->id,
			'name' 			=> $user->name,
			'emial'			=> isset($user->emails->preferred) ? $user->emails->preferred : null,
			'nickname' 		=> \Core\Inflector::friendlyTitle($user->name, '-', true),
			// 'location' 	=> $user->location,
			// 	requires scope wl.postal_addresses and docs here: http://msdn.microsoft.com/en-us/library/hh243648.aspx#user
			'locale' 		=> $user->locale,
			'urls' 			=> array(
				'Windows Live' => $user->link
			),
		);
	}
}