<?php

namespace Core;

class Model {
	
	/**
	 * Nome da tabela
	 * @var string 
	 */
	protected $_tableName = null;
	
	/**
	 * Chaves primárias da tabela
	 * @var array 
	 */
	protected $_primaryKeys = array();
	
	/**
	 * Conexão com o DB que a tabela irá usar
	 * @var \Core\DB 
	 */
	protected $_databaseConnection = null;
	
	/**
	 * Todos os campos da tabela
	 * @var array 
	 */
	protected $_tableFields = array(
		'st_nome' => array(
			'desc' => 'Nome',
			'type' => 'string', // int, string, text, float, preset, date, time, datetime
			'size' => '255',
			'fixed' => true
		),
		'id' => array(
			'desc' => 'Nome',
			'type' => 'string', // int, string, text, float, preset, date, time, datetime
			'size' => '255',
			'fixed' => true
		)
	);
	
	/**
	 * Método construtor 
	 */
	public function __construct()
	{
		/**
		 * Define automaticamente as configurações da tabela
		 * Nome da tabela = Nome do model em minúsculo 
		 */
		if( ! static::$_tableName)
			static::$_tableName = strtolower(substr(__CLASS__, strrpos(__CLASS__, '\\') + 1));
		
		if( ! static::$_primaryKeys)
			static::$_primaryKeys[] = static::$_tableName . '_id';
		
		if( ! static::$_databaseConnection)
			static::$_databaseConnection = null;
	}
	
	/**
	 * Cria uma nova instância da classe
	 * 
	 * @return \Core\Model 
	 */
	public static function make()
	{
		return new static;
	}
}