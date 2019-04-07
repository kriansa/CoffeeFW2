<?php
/**
 * Instagram OAuth2 Provider
 *
 * @package    FuelPHP/OAuth2
 * @category   Provider
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

namespace Core\Auth\OAuth2\Provider;

class Instagram extends \Core\Auth\OAuth2\Provider
{
	/**
	 * Provider name
	 * @var string
	 */
	public $name = 'Instagram';

	/**
	 * Default scope
	 * @var string
	 */
	protected $_scope = array('basic');

	/**
	 * The method to use when requesting tokens
	 * @var string
	 */
	protected $_method = 'POST';

	/**
	 * Scope separator, most use "," but some like Google are spaces
	 * @var string
	 */
	protected $scopeSeperator = '+';

	/**
	 * The authorization URL for the provider.
	 * @return string
	 */
	protected $_urlAuthorize = 'https://api.instagram.com/oauth/authorize';

	/**
	 * The access token endpoint for the provider.
	 * @return string
	 */
	protected $_urlAccessToken = 'https://api.instagram.com/oauth/access_token';

	/**
	 * Returns the info about the logged user.
	 *
	 * @return string
	 */
	public function getUserInfo(\Core\Auth\OAuth2\Token\Access $token)
	{
		$user = $token->user;

		// Create a response from the request
		return array(
			'uid' => $user->id,
			'nickname' => $user->username,
			'name' => $user->full_name,
			'image' => $user->profile_picture,
			'urls' => array(
			  'website' => $user->website,
			),
		);
	}
}