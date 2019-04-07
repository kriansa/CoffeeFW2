<?php

namespace App\Model;

class Char extends \Core\Model
{
	function teste($param) {
		DB::select('texto.cs_nome, cs_texto')
			->from('texto')
			->left_join('blablabla', 'cs_nome = cs_teste')
			->left_join('coisa2');
		DB::get('tabela1', 'tabela2', 'tabela3')
				->relate('tabela2')
				->relate('tabela3')
				->relate('tabela4')
				->where('tabela1.xxx = ?', $valor)
				->and_where('tabela2.xxx = tabela1.xxx')
				->open()
				->where('eae')
				->or_where(array('eaew2'))
				->close();
	}
	
}