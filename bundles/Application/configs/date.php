<?php

return array(

	/**
	 * A couple of named patterns that are often used
	 */
	'patterns' => array(
		'local'		 => '%c',

		'sql' => array(
			'mysql'		 => '%Y-%m-%d %H:%M:%S',
			'mysql_date' => '%Y-%m-%d',
		),

		'us'		 => '%m/%d/%Y',
		'us_short'	 => '%m/%d',
		'us_named'	 => '%B %d %Y',
		'us_full'	 => '%I:%M %p, %B %d %Y',
		'eu'		 => '%d/%m/%Y',
		'eu_short'	 => '%d/%m',
		'eu_named'	 => '%d %B %Y',
		'eu_full'	 => '%H:%M, %d %B %Y',

		'24h'		 => '%H:%M',
		'12h'		 => '%I:%M %p'
	)
);