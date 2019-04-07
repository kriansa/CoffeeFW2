<?php

namespace Core;

class FormatException extends \Exception {}

class Format
{
	/**
	 * Input data to convert
	 * @var array
	 */
	protected $_data = array();

	/**
	 * Returns an instance of the Format object.
	 *
	 *     echo Format::make(array('foo' => 'bar'))->toXml();
	 *
	 * @param mixed General date to be converted
	 * @param string Data format the file was provided in
	 * @return \Core\Format
	 */
	public static function make($data, $from_type = 'array')
	{
		return new static($data, $from_type);
	}

	/**
	 * Constructor
	 *
	 * @param mixed General date to be converted
	 * @param string Data format the file was provided in
	 * @throws Core\FormatException
	 */
	public function __construct($data, $from_type = 'array')
	{
		if (method_exists($this, '_from' . ucfirst($from_type)))
		{
			$data = $this->{'_from' . ucfirst($from_type)}($data);
		}
		else
		{
			throw new FormatException('Format class does not support conversion from "' . $from_type . '".');
		}

		$this->_data = $data;
	}
	
	/**
	 * Convert the data to array
	 * 
	 * @param mixed $data
	 * @return array 
	 */
	public function toArray($data = null)
	{
		return $data === null ? $this->_data : static::_fromArray($data);
	}

	/**
	 * To XML conversion
	 *
	 * @param string $encoding
	 * @param string $basenode
	 * @param mixed $data
	 * @return string
	 */
	public function toXml($pretty_print = false, $encoding = null, $basenode = 'response', $data = null)
	{
		$data === null and $data = $this->_data;
		$encoding === null and $encoding = Config::get('system.encoding');
		
		// creating object of SimpleXMLElement
		$simple_xml_element = new \SimpleXMLElement('<?xml version="1.0" encoding="' . $encoding . '"?><' . $basenode . ' />');

		// function call to convert array to xml
		static::_toXml($this->toArray($data), $simple_xml_element);

		if($pretty_print)
		{
			$dom = dom_import_simplexml($simple_xml_element)->ownerDocument;
			$dom->formatOutput = true;
			return $dom->saveXML();
		}
		else
		{
			return $simple_xml_element->asXML();
		}
	}
	
	/**
	 * Convert the input data to the SimpleXMLElement pointer
	 * 
	 * @param array $data
	 * @param \SimpleXMLElement $simple_xml_element
	 */
	protected static function _toXml($data, &$simple_xml_element)
	{
		foreach($data as $key => $value)
		{
			// Replace anything not alpha numeric
			$key = preg_replace('/[^a-z_\-0-9]/i', '', $key);
			
			if(is_array($value))
			{
				
				if( ! is_numeric($key))
				{
					$subnode = $simple_xml_element->addChild($key);
					static::_toXml($value, $subnode);
				}
				else
				{
					static::_toXml($value, $simple_xml_element);
				}
			}
			else
			{
				// Numeric tags are not allowed
				if(is_numeric($key))
				{
					$key = 'value';
				}

				$simple_xml_element->addChild($key, $value);
			}
		}
	}

	/**
	 * To CSV conversion
	 *
	 * @param mixed $data
	 * @return string
	 */
	public function toCsv($data = null)
	{
		$data === null and $data = $this->_data;

		// Multi-dimentional array
		if (is_array($data) and isset($data[0]))
		{
			if (Arr::isAssoc($data[0]))
			{
				$headings = array_keys($data[0]);
			}
			else
			{
				$headings = array_shift($data);
			}
		}

		// Single array
		else
		{
			$headings = array_keys((array) $data);
			$data = array($data);
		}

		$output = implode(',', $headings) . "\n";
		foreach ($data as &$row)
		{
			$output .= '"' . implode('","', (array) $row) . "\"\n";
		}

		return rtrim($output, "\n");
	}

	/**
	 * To JSON conversion
	 *
	 * @param bool Whether to make the json pretty
	 * @param mixed $data
	 * @return string
	 */
	public function toJson($pretty = false, $data = null)
	{
		$data === null and $data = $this->_data;

		// To allow exporting ArrayAccess objects like Orm\Model instances they need to be
		// converted to an array first
		(is_array($data) or is_object($data)) and $data = $this->toArray($data);

		return $pretty ? static::_prettyJson($data) : json_encode($data);
	}

	/**
	 * To JSONP conversion
	 *
	 * @param string Callback name
	 * @param bool $pretty Whether to make the json pretty
	 * @param mixed $data
	 * @return string
	 */
	public function toJsonp($callback, $pretty = false, $data = null)
	{
		 return $callback . '(' . $this->toJson($pretty, $data) . ')';
	}

	/**
	 * Serialize
	 *
	 * @param mixed $data
	 * @return string
	 */
	public function toSerialized($data = null)
	{
		$data === null and $data = $this->_data;

		return serialize($data);
	}

	/**
	 * Convert to YAML
	 *
	 * @param mixed $data
	 * @return string
	 */
	public function toYaml($data = null)
	{
		$data === null and $data = $this->_data;

		if ( ! function_exists('spyc_load'))
		{
			require_once COREPATH . 'Vendor' . DS . 'spyc' . DS . 'spyc.php';
		}

		return \Spyc::YAMLDump($data);
	}
	
	/**
	 * To array conversion
	 *
	 * Goes through the input and makes sure everything is either a scalar value or array
	 *
	 * @param mixed $data
	 * @return array
	 */
	protected static function _fromArray($data)
	{
		$array = array();

		if (is_object($data) and ! $data instanceof \Iterator)
		{
			$data = get_object_vars($data);
		}

		if (empty($data))
		{
			return array();
		}

		foreach ($data as $key => $value)
		{
			if (is_object($value) or is_array($value))
			{
				$array[$key] = static::_fromArray($value);
			}
			else
			{
				$array[$key] = $value;
			}
		}

		return $array;
	}

	/**
	 * Import XML data
	 *
	 * @param string $string
	 * @return array
	 */
	protected function _fromXml($string)
	{
		$xmlobject = simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA);

		return static::toArray($xmlobject);
	}

	/**
	 * Import YAML data
	 *
	 * @param string $string
	 * @return array
	 */
	protected function _fromYaml($string)
	{
		if ( ! function_exists('spyc_load'))
		{
			require_once COREPATH . 'Vendor' . DS . 'spyc' . DS . 'spyc.php';
		}

		return \Spyc::YAMLLoadString($string);
	}

	/**
	 * Import CSV data
	 *
	 * Options includes: delimiter, enclosure and escape
	 *
	 * @param string $string
	 * @param array $options
	 * @return array
	 */
	protected function _fromCsv($string, array $options = array())
	{
		$options = array_merge(array(
			'delimiter' => ',',
			'enclosure' => '"',
			'escape' => '\\',
		), $options);

		$data = str_getcsv($string, "\n"); //parse the rows

		$header = null;

		foreach($data as $key => &$row)
		{
			$row = str_getcsv($row, $options['delimiter'], $options['enclosure'], $options['escape']);

			if($header === null)
			{
				$header = $row;
				unset($data[$key]);
			}
			else
			{
				$row = array_combine($header, $row);
			}
		}

		return array_values($data);
	}

	/**
	 * Import JSON data
	 *
	 * @param string $string
	 * @return mixed
	 */
	protected function _fromJson($string)
	{
		return json_decode(trim($string));
	}

	/**
	 * Import Serialized data
	 *
	 * @param string $string
	 * @return mixed
	 */
	protected function _fromSerialized($string)
	{
		return unserialize(trim($string));
	}

	/**
	 * Makes json pretty the json output.
	 * Barrowed from http://www.php.net/manual/en/function.json-encode.php#80339
	 *
	 * @param string $json JSON encoded array
	 * @return string|false Pretty json output or false when the input was not valid
	 */
	protected static function _prettyJson($data)
	{
		$json = json_encode($data);
		if ( ! $json)
		{
			return false;
		}

		$tab = "\t";
		$newline = "\n";
		$new_json = '';
		$indent_level = 0;
		$in_string = false;
		$len = strlen($json);

		for ($c = 0; $c < $len; $c++)
		{
			$char = $json[$c];
			switch($char)
			{
				case '{':
				case '[':
					if ( ! $in_string)
					{
						// Don't indent empty values
						if ($json[$c+1] == ']' or $json[$c+1] == '}'){
							$new_json .= '[]';
							break;
						}

						$new_json .= $char.$newline.str_repeat($tab, $indent_level+1);
						$indent_level++;
					}
					else
					{
						$new_json .= $char;
					}
					break;
				case '}':
				case ']':
					if ( ! $in_string)
					{
						// Don't indent empty values
						if ($json[$c-1] == '[' or $json[$c-1] == '{'){
							break;
						}
						
						$indent_level--;
						$new_json .= $newline.str_repeat($tab, $indent_level).$char;
					}
					else
					{
						$new_json .= $char;
					}
					break;
				case ',':
					if ( ! $in_string)
					{
						$new_json .= ','.$newline.str_repeat($tab, $indent_level);
					}
					else
					{
						$new_json .= $char;
					}
					break;
				case ':':
					if ( ! $in_string)
					{
						$new_json .= ': ';
					}
					else
					{
						$new_json .= $char;
					}
					break;
				case '"':
					if ($c > 0 and $json[$c-1] !== '\\')
					{
						$in_string = ! $in_string;
					}
				default:
					$new_json .= $char;
					break;
			}
		}

		return $new_json;
	}
}