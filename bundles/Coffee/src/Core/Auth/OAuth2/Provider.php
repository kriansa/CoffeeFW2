<?php
/**
 * OAuth Provider
 *
 * @package    FuelPHP/OAuth2
 * @category   Provider
 * @author     Phil Sturgeon
 * @copyright  (c) 2012 HappyNinjas Ltd
 * @license    http://philsturgeon.co.uk/code/dbad-license
 */

namespace Core\Auth\OAuth2;

class ProviderException extends \Exception {}

abstract class Provider
{
	/**
	 * Provider name
	 * @var string
	 */
	public $name;

	/**
	 * Additional request parameters to be used for remote requests
	 * @var string
	 */
	public $callback = null;

	/**
	 * Client ID
	 * @var string
	 */
	protected $clientId = null;

	/**
	 * Client Secret
	 * @var string
	 */
	protected $clientSecret = null;

	/**
	 * The URL to be redirected to
	 * @var string
	 */
	protected $redirectUri = null;

	/**
	 * The method to use when requesting tokens
	 * @var string
	 */
	protected $_method = 'GET';

	/**
	 * Default scope (useful if a scope is required for user info)
	 * @var string
	 */
	protected $_scope;

	/**
	 * Scope separator, most use "," but some like Google are spaces
	 * @var string
	 */
	protected $_scopeSeperator = ',';

	/**
	 * The authorization URL for the provider.
	 * @var string
	 */
	protected $_urlAuthorize = null;

	/**
	 * The access token endpoint for the provider.
	 * @var string
	 */
	protected $_urlAccessToken = null;

	/**
	 * Create a new provider.
	 *
	 *     // Load the Twitter provider
	 *     $provider = Provider::make('twitter');
	 *
	 * @param string $name Provider name
	 * @param array $options Provider options
	 * @return \Core\Auth\OAuth2\Provider
	 */
	public static function make($name, array $options = null)
	{
		$class = 'Core\\Auth\\OAuth2\\Provider\\' . ucfirst($name);
		return new $class($options);
	}

	/**
	 * Overloads default class properties from the options.
	 *
	 * Any of the provider options can be set here, such as app_id or secret.
	 *
	 * @param array $options Provider options
	 * @throws \Core\Auth\OAuth2\ProviderException
	 */
	protected function __construct(array $options = array())
	{
		if ( ! $this->name)
		{
			// Attempt to guess the name from the class name
			$this->name = strtolower(substr(get_class($this), strlen('Core\\Auth\\OAuth2\\Provider\\')));
		}

		if ( ! $this->clientId = \Core\Arr::get($options, 'id'))
		{
			throw new ProviderException('Required option not provided: id');
		}

		$this->callback = \Core\Arr::get($options, 'callback');
		$this->clientSecret = \Core\Arr::get($options, 'secret');
		\Core\Arr::get($options, 'scope') !== null and $this->_scope = \Core\Arr::merge($this->_scope, \Core\Arr::get($options, 'scope'));

		$this->redirectUri = \Core\Arr::get($options, 'redirect_uri', \Core\URL::create());
	}

	/**
	 * Return the value of any protected class variable.
	 *
	 *     // Get the provider signature
	 *     $signature = $provider->signature;
	 *
	 * @param string $key Variable name
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->$key;
	}

	/**
	 * Return a boolean if the property is set
	 *
	 * @param string $key Variable name
	 * @return bool
	 */
	public function __isset($key)
	{
		return isset($this->$key);
	}

	/**
	 * Get an authorization code from Facebook.  Redirects to Facebook,
	 * which this redirects back to the app using the redirect address you've set.
	 *
	 * @param array $options
	 */
	public function authorize(array $options = array())
	{
		$state = md5(uniqid(rand(), true));
		\Core\Session::setFlash('oauth2.state.' . $this->name, $state);

		$url = $this->_urlAuthorize . '?' . http_build_query(array(
			'client_id' => $this->clientId,
			'redirect_uri' => \Core\Arr::get($options, 'redirect_uri', $this->redirectUri),
			'state' => $state,
			'scope' => is_array($this->_scope) ? implode($this->_scopeSeperator, $this->_scope) : $this->_scope,
			'response_type' => 'code',
		), null, '&');

		\Core\Request::redirect($url);
	}

	/**
	 * Get access to the API
	 *
	 * @param string $code The access code
	 * @param string $state The state code received from provider
	 * @param array $options Options
	 * @return \Core\Auth\OAuth2\Token Success or failure along with the response details
	 * @throws \OutOfBoundsException
	 * @throws \Core\Auth\OAuth2\ProviderException
	 */
	public function access($code, $state, array $options = array())
	{
		if ($code === null)
		{
			throw new ProviderException('Expected Authorization Code from ' . $this->name . ' is missing');
		}

		if (strcmp($state, \Core\Session::get('oauth2.state.' . $this->name)) != 0)
		{
			throw new ProviderException('Expected state from ' . ucfirst($this->name) . ' is invalid. Possible bruteforce attempt.');
		}

		$params = array(
			'client_id' 	=> $this->clientId,
			'client_secret' => $this->clientSecret,
			'grant_type' 	=> \Core\Arr::get($options, 'grant_type', 'authorization_code'),
		);

		switch ($params['grant_type'])
		{
			case 'authorization_code':
				$params['code'] = $code;
				$params['redirect_uri'] = \Core\Arr::get($options, 'redirect_uri', $this->redirectUri);
			break;

			case 'refresh_token':
				$params['refresh_token'] = $code;
			break;
		}


		$response = null;
		$url = $this->_urlAccessToken;

		switch ($this->_method)
		{
			case 'GET':
				// Need to switch to Request library, but need to test it on one that works
				$url .= '?' . http_build_query($params, null, '&');
				$response = file_get_contents($url);

				$return = array();
				parse_str($response, $return);
				break;

			case 'POST':
				$postdata = http_build_query($params, null, '&');
				$opts = array(
					'http' => array(
						'method'  => 'POST',
						'header'  => 'Content-type: application/x-www-form-urlencoded',
						'content' => $postdata,
					)
				);
				$context  = stream_context_create($opts);
				$response = file_get_contents($url, false, $context);

				$return = get_object_vars(json_decode($response));
				break;

			default:
				throw new \OutOfBoundsException('Method "' . $this->_method . '" must be either GET or POST');
		}

		if (isset($return['error']))
		{
			throw new ProviderException($return);
		}

		switch ($params['grant_type'])
		{
			case 'authorization_code':
				return Token::make('access', $return);
			break;

			case 'refresh_token':
				return Token::make('refresh', $return);
			break;
		}
	}

	/**
	 * Returns the info about the logged user.
	 *
	 * @param \Core\Auth\OAuth2\Token\Access $token
	 * @return string
	 */
	abstract public function getUserInfo(\Core\Auth\OAuth2\Token\Access $token);
}