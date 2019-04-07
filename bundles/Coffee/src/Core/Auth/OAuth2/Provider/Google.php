<?php
/**
 * Google OAuth2 Provider
 *
 * @package    FuelPHP/OAuth2
 * @category   Provider
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

namespace Core\Auth\OAuth2\Provider;

class Google extends \Core\Auth\OAuth2\Provider
{
	/**
	 * Provider name
	 * @var string
	 */
	public $name = 'Google';

	/**
	 * The method to use when requesting tokens
	 * @var string
	 */
	protected $_method = 'POST';

	/**
	 * Default scope
	 * We need this default feed to get the authenticated users basic information
	 * array('https://www.googleapis.com/auth/plus.me');
	 * @var string
	 */
	protected $_scope = array('https://www.google.com/m8/feeds');

	/**
	 * Scope separator, most use "," but some like Google are spaces
	 * @var string
	 */
	public $_scopeSeperator = ' ';

	/**
	 * The authorization URL for the provider.
	 * @return string
	 */
	protected $_urlAuthorize = 'https://accounts.google.com/o/oauth2/auth';

	/**
	 * The access token endpoint for the provider.
	 * @return string
	 */
	protected $_urlAccessToken = 'https://accounts.google.com/o/oauth2/token';

	/**
	 * Get an authorization code from Facebook.  Redirects to Facebook,
	 * which this redirects back to the app using the redirect address you've set.
	 *
	 * @return void
	 */
	public function authorize(array $options = array())
	{
		$state = md5(uniqid(rand(), true));
		\Core\Session::set('state', $state);

		$url = $this->_urlAuthorize . '?' . http_build_query(array(
			'client_id' => $this->clientId,
			'redirect_uri' => \Core\Arr::get($options, 'redirect_uri', $this->redirectUri),
			'state' => $state,
			'scope' => is_array($this->_scope) ? implode($this->_scopeSeperator, $this->_scope) : $this->_scope,
			'response_type' => 'code',
			'access_type' => 'offline',
			'approval_prompt' => 'force',
		), null, '&');

		\Core\Request::redirect($url);
	}

	/**
	 * Returns the info about the logged user.
	 *
	 * @param \Core\Auth\OAuth2\Token\Access $token
	 * @return string
	 */
	public function getUserInfo(\Core\Auth\OAuth2\Token\Access $token)
	{
		$url = 'https://www.google.com/m8/feeds/contacts/default/full?max-results=1&alt=json&' . http_build_query(array(
			'access_token' => $token->accessToken,
		));

		$response = json_decode(file_get_contents($url), true);

		// Fetch data parts
		$email = \Core\Arr::get($response, 'feed.id.$t');
		$name = \Core\Arr::get($response, 'feed.author.0.name.$t');
		$name == '(unknown)' and $name = $email;

		return array(
			'uid' => $email,
			'nickname' => \Core\Inflector::friendlyTitle($name),
			'name' => $name,
			'email' => $email,
			'location' => null,
			'image' => null,
			'description' => null,
			'urls' => array(),
		);
	}
}