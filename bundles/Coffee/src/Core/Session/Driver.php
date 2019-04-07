<?php

namespace Core\Session;

abstract class Driver
{
	/**
	 * Load a session from storage by a given ID.
	 *
	 * If no session is found for the ID, null will be returned.
	 *
	 * @param string $id
	 * @return array
	 */
	abstract public function load($id);

	/**
	 * Save a given session to storage.
	 *
	 * @param array $session
	 * @param array $config
	 * @return void
	 */
	abstract public function save(array $session, array $config);

	/**
	 * Delete a session from storage by a given ID.
	 *
	 * @param string $id
	 * @return void
	 */
	abstract public function delete($id);

	/**
	 * Create a fresh session and return the payload array.
	 *
	 * @return array
	 */
	public function create()
	{
		// We will simply generate an empty session payload array, using an ID
		// that is not currently assigned to any existing session within the
		// application and return it to the driver.
		return array(
			'id' => $this->id(),
			'ip' => \Core\Input::ip(),
			'browser' => \Core\Input::userAgent(),
			'data' => array(
				':new:' => array(),
				':old:' => array(),
			),
		);
	}

	/**
	 * Get a new session ID that isn't assigned to any current session.
	 *
	 * @return string
	 */
	public function id()
	{
		// We'll containue generating random IDs until we find an ID that is
		// not currently assigned to a session. This is almost definitely
		// going to happen on the first iteration.
		do {

			$session = $this->load($id = \Core\Str::random('alnum', 40));

		} while ( ! is_null($session));

		return $id;
	}
}