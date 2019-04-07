<?php
/**
 * Paypal OAuth2 Provider
 *
 * @package    FuelPHP/OAuth2
 * @category   Provider
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

namespace Core\Auth\OAuth2\Provider;

class Paypal extends \Core\Auth\OAuth2\Provider
{
	/**
	 * Provider name
	 * @var string
	 */
	public $name = 'Paypal';

	/**
	 * The method to use when requesting tokens
	 * @var string
	 */
	protected $_method = 'POST';

	/**
	 * Default scope
	 * @var string
	 */
	protected $_scope = array('https://identity.x.com/xidentity/resources/profile/me');

	/**
	 * The authorization URL for the provider.
	 * @return string
	 */
	protected $_urlAuthorize = 'https://identity.x.com/xidentity/resources/authorize';

	/**
	 * The access token endpoint for the provider.
	 * @return string
	 */
	protected $_urlAccessToken = 'https://identity.x.com/xidentity/oauthtokenservice';

	/**
	 * Returns the info about the logged user.
	 *
	 * @return string
	 */
	public function getUserInfo(\Core\Auth\OAuth2\Token\Access $token)
	{
        $url = 'https://identity.x.com/xidentity/resources/profile/me?' . http_build_query(array(
            'oauth_token' => $token->accessToken
        ), null, '&');

        $user = json_decode(file_get_contents($url));
		$user = $user->identity;

		return array(
            'uid' => $user['userId'],
            'nickname' => \Inflector::friendly_title($user['fullName'], '_', true),
            'name' => $user['fullName'],
            'first_name' => $user['firstName'],
            'last_name' => $user['lastName'],
            'email' => $user['emails'][0],
            'location' => $user->addresses[0],
            'image' => null,
            'description' => null,
            'urls' => array(
				'PayPal' => null
			)
        );
    }
}