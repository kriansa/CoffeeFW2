<?php

namespace App\Controller;

use
Core\Arr, Core\Cache, Core\Config, Core\Cookie, Core\Crypter,
Core\DB, Core\Debug, Core\File, Core\Input, Core\Date,
Core\Profiler, Core\Redis, Core\Security, Core\Session,
Core\Str, Core\URL, Core\Asset, Core\Inflector, Core\Format,
Core\Num, Core\Lang;

class Index extends \Core\Controller\Basic
{

	//public $cacheEnabled = true;

	/**
	 * Action for the index page
	 *
	 * @param array $params
	 */
	public function indexAction(array $params = array())
	{
		//\Core\Debug::dump(Input::server('HTTP_HOST'));
		//Asset::css('bootstrap.min.css');
		//Asset::css('bootstrap-responsive.min.css');
		//Asset::js('jquery-1.7.1.min.js');
		//Asset::js('bootstrap.min.js');
		//$this->cache();
		//\Core\Debug::dump(Inflector::friendlyTitle('dashes-to-cá†®´`~amel-case'));
		//if(str)
		//$this->response->setCache('10 March 2012 13:00:00', '30 March 2012 13:00:00');
		//$lol = \Core\URL::getFull();
		//var_dump($lol);
		//return Str::convertEncoding(strftime('%A %B a mae sua'));

		//$this->view->tenso = 'A123 é á eae';
		//return 123;
		//$this->view->setGlobal('title', '<teste></teste>', false);
		//$this->layout->title = 'suck my dick';
		/*\Core\Debug::dump(DB::table('char')
				->where('name', '=', 'kriansa')
				->joinUsing('login', 'account_id')
				->get(array(
					'char.char_id',
					'login.email',
					)));*/
		//$query = DB::query('select * from login where userid = ?', array('kriansa_adm'));
		//\Core\Debug::dump($query->getRow());
		//\Core\Debug::dump($query->getAll());
		/*$table = DB::table('char AS c')
				->where('name', '<>', DB::expr('"teste"'))
				->where('name', '<>', 'teste')
				->where('name', '<>', null)
				->where('name', '<>', DB::expr('"teste"'))
				->where('name', 'not between', array(0,9))
				->where('name', '<>', 'm aconha s')
				->select('name AS nome do personagem', 'char_id AS id do personagem');
		\Core\Debug::dump($table->getRow(0));*/
		/*Profiler::markStart('oi');
		\Core\Debug::dump(Date::fromFormat('12/31/1s992', 'us')->toFormat('local'));
		Profiler::markEnd('oi');
		/*$this->layout->title = '>eae';
		$this->view->global->maconha = 123;
		//$this->view->dados = DB::table('char')->where('name', 'like', '%a%')->get();
		$this->view->logged = null;
		$this->view->chars = array();
		//$this->view->global->title = '<teste>Pênes</teste>';
		//$this->clearCache();
		/*$aid = $this->accountData();
		$this->view->logged = (bool) $aid;
		DB::table('usuarios', array('eae', 'eae'))->where(array(
			'user_id' => 1
		));*/

		//Cache::getInstance('database')->garbageCollector();
		//\Core\Debug::dump(Cache::getInstance('database')->get('teste'));

		Cache::getInstance('session')->garbageCollector();
		//echo \Core\Num::bytesToHuman(2342423);
		//DB::getInstance('lol');
		Session::set('sexo', 666);
		//Session::set('access_token', 'iaheioaeheaio');
		//Session::set('sexo2', new Date);
		echo \Core\URL::getRequestString('sex');

		//\Core\Session::set('key', 'blablabla');
		//\Core\Session::regenerate();
		//echo \Core\Session::get('key');
	}

	public function personagensAction(array $params)
	{
		Asset::css('bootstrap.min.css');
		Asset::css('bootstrap-responsive.min.css');
		Asset::less('styles.less');
		Asset::js('jquery-1.7.2.min.js');
		Asset::js('bootstrap.min.js');

		$this->view->chars = DB::table('char as c')
			->select();
	}

	public function facebookLoginAction(array $params)
	{
		/*$provider = new \Core\Auth\OAuth2\Provider\Facebook(array(
			'id' => '160582154038100',
			'secret' => 'e3cdd48ec27c1b2441d5e82b3382af51',
			'redirect_uri' => 'http://127.0.0.2/index/facebook-login',
		));*/

		/**
		 * Se não enviou o code, significa que estamos enviando o usuário
		 * para o provider, para depois ele reenviar o usuário para esta
		 * página com o code, isso significa que a resposta veio
		 * Mas se já tivermos o token, não precisamos repetir o processo.
		 * OBS: Podemos salvar o token onde quiser-mos!!
		 */
		if ( ! Input::get('code') and ($token = Session::get('token')) === null)
		{
			// Redireciona o usuário para a página de autorização do provider
			$provider->authorize();
		}
		/**
		 * Recebemos a resposta do provider com o code, então vamos fazer
		 * a autenticação do usuário em nosso sistema
		 */
		else
		{
			try
			{
				/**
				 * Vamos ver se salvamos já anteriormente
				 */
				if ($token  !== null)
				{
					Debug::dump($token);
					Debug::dump(Date::make($token->expires));
					return;
				}

				/**
				 * Aqui obtemos o token do usuário, com ele podemos fazer
				 * as solicitaçõs no provider, como userinfo e outras
				 * OBS: Devemos salvar este token no banco pra futuras requisições
				 */
				$token = $provider->access(Input::get('code'), Input::get('state'));

				/**
				 * Salvando o token do usuário, não precisamos fazer a solicitação
				 * deste usando o método Provider::access toda vez.
				 */
				Session::set('token', $token);

				// Usando o token, conseguimos capturar as informações do usuário
				$user = $provider->getUserInfo($token);

				// Here you should use this information to A) look for a user B) help a new user sign up with existing data.
				// If you store it all in a cookie and redirect to a registration page this is crazy-simple.
				Debug::dump($user, $token);
			}
			catch (\Exception $e)
			{
				echo 'Ocorreu um erro!';
			}
		}
	}

	public function oauth2fAction(array $params)
	{
		/*
		 * $params[0] - O primeiro parâmetro será o nome do provider
		 * Ex: facebook, twitter, etc
		 */
		$provider_name = $params[0];

		$provider = \Core\Auth\OAuth2\Provider::make($provider_name, array(
			'id' => '160582154038100',
			'secret' => 'e3cdd48ec27c1b2441d5e82b3382af51',
			'redirect_uri' => 'http://127.0.0.2/index/oauth2/' . $provider_name,
		));

		/**
		 * Se não enviou o code, significa que estamos enviando o usuário
		 * para o provider, para depois ele reenviar o usuário para esta
		 * página com o code, isso significa que a resposta veio
		 */
		if ( ! Input::get('code'))
		{
			// Redireciona o usuário para a página de autorização do provider
			$provider->authorize();
		}
		/**
		 * Recebemos a resposta do provider com o code, então vamos fazer
		 * a autenticação do usuário em nosso sistema
		 */
		else
		{
			try
			{
				if (($token = Session::get('token')) !== null)
				{
					\Core\Debug::dump($token);
					\Core\Debug::dump(Date::make($token->expires));
					return;
				}

				/**
				 * Aqui obtemos o token do usuário, com ele podemos fazer
				 * as solicitaçõs no provider, como userinfo e outras
				 * OBS: Devemos salvar este token no banco pra futuras requisições
				 */
				$token = $provider->access(Input::get('code'), Input::get('state'));

				/**
				 * Salvando o token do usuário, não precisamos fazer a solicitação
				 * deste usando o método Provider::access toda vez.
				 */
				Session::set('token', $token);

				// Optional: Use the token object to grab user data from the API
				$user = $provider->getUserInfo($token);

				// Here you should use this information to A) look for a user B) help a new user sign up with existing data.
				// If you store it all in a cookie and redirect to a registration page this is crazy-simple.
				\Core\Debug::dump($user, $token);
			}
			catch (\Core\Auth\OAuth2\ProviderException $e)
			{
				Debug::dump($e);
			}
			catch (\Core\Auth\OAuth2\TokenException $e)
			{
				Debug::dump($e);
			}
		}
	}

	/**
	 * Ação de cadastro
	 *
	 * @param array $params
	 */
	public function cadastroAction($params = null)
	{
		$aid = $this->accountData();
		$this->view->logged = (bool) $aid;

		if( ! $aid)
		{

		}

	}

	/**
	 * Ação de login
	 */
	public function loginAction()
	{
		$userid = \Core\Input::post('userid');
		$passwd = \Core\Input::post('passwd');

		Debug::dump(DB::table('login')->where('userid', '=', $userid)->get());
	}
}