<?php

namespace Core\Session;

class Cookie extends Driver
{
	/**
	 * The name of the cookie used to store the session payload.
	 * @var string
	 */
	const PAYLOAD = 'sess_data';

	/**
	 * Load a session from storage by a given ID.
	 *
	 * If no session is found for the ID, null will be returned.
	 *
	 * @param string $id
	 * @return array
	 */
	public function load($id)
	{
		if ($cookie = \Core\Cookie::get(static::PAYLOAD))
		{
			return unserialize(\Core\Crypter::decrypt($cookie));
		}

		return null;
	}

	/**
	 * Save a given session to storage.
	 *
	 * @param array $session
	 * @param array $config
	 */
	public function save(array $session, array $config)
	{
		$payload = \Core\Crypter::encrypt(serialize($session));

		$minutes = $config['expire_on_close'] ? 0 : time() + $config['lifetime'] * 60;

		\Core\Request::getInstance()->setCookie(array(
			'name' => static::PAYLOAD,
			'value' => $payload,
			'expire' => $minutes,
			'path' => $config['cookie']['path'],
			'domain' => $config['cookie']['domain'],
			'secure' => $config['cookie']['secure'],
			'http_only' => $config['cookie']['http_only'],
		));
	}

	/**
	 * Delete a session from storage by a given ID.
	 *
	 * @param string $id
	 */
	public function delete($id)
	{
		\Core\Cookie::delete(static::PAYLOAD);
	}

	/**
	 * Get a new session ID that isn't assigned to any current session.
	 *
	 * @return string
	 */
	public function id()
	{
		// If the driver is an instance of the Cookie driver, we are able to
		// just return any string since the Cookie driver has no real idea
		// of a server side persisted session with an ID.
		return \Core\Str::random('alnum', 40);
	}
}