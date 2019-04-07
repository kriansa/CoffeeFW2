<?php

namespace Core\Session;

class Cache extends Driver
{
	/**
	 * The Cache driver instance.
	 * @var \Core\Cache
	 */
	protected $_cache;

	/**
	 * Create a new cache session driver instance.
	 *
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$this->_cache = \Core\Cache::getInstance($config['adapter']);
	}

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
		if( ! $this->_cache->has($id))
		{
			return null;
		}

		return $this->_cache->get($id);
	}

	/**
	 * Save a given session to storage.
	 *
	 * @param array $session
	 * @param array $config
	 */
	public function save(array $session, array $config)
	{
		$this->_cache->set($session['id'], $session, $config['lifetime']);
	}

	/**
	 * Delete a session from storage by a given ID.
	 *
	 * @param string $id
	 * @return void
	 */
	public function delete($id)
	{
		$this->_cache->drop($id);
	}
}