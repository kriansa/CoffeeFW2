<?php

namespace Core;

abstract class Inflector
{

	/**
	 * Translate string to 7-bit ASCII
	 * Only works with UTF-8.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function toAscii($str)
	{
		// Translate unicode characters to their simpler counterparts
		static $foreign_characters = array(
			// Latin-1 Supplement
			'©' => '(c)', '«' => '<<',  '®' => '(R)', '»' => '>>',  '¼' => '1/4',
			'½' => '1/2', '¾' => '3/4', 'À' => 'A',   'Á' => 'A',   'Â' => 'A',
			'Ã' => 'A',   'Ä' => 'A',   'Å' => 'A',   'Æ' => 'AE',  'Ç' => 'C',
			'È' => 'E',   'É' => 'E',   'Ê' => 'E',   'Ë' => 'E',   'Ì' => 'I',
			'Í' => 'I',   'Î' => 'I',   'Ï' => 'I',   'Ñ' => 'N',   'Ò' => 'O',
			'Ó' => 'O',   'Ô' => 'O',   'Õ' => 'O',   'Ö' => 'O',   'Ø' => 'O',
			'Ù' => 'U',   'Ú' => 'U',   'Û' => 'U',   'Ü' => 'U',   'Ý' => 'Y',
			'à' => 'a',   'á' => 'a',   'â' => 'a',   'ã' => 'a',   'ä' => 'a',
			'å' => 'a',   'æ' => 'ae',  'ç' => 'c',   'è' => 'e',   'é' => 'e',
			'ê' => 'e',   'ë' => 'e',   'ì' => 'i',   'í' => 'i',   'î' => 'i',
			'ï' => 'i',   'ñ' => 'n',   'ò' => 'o',   'ó' => 'o',   'ô' => 'o',
			'õ' => 'o',   'ö' => 'o',   'ø' => 'o',   'ù' => 'u',   'ú' => 'u',
			'û' => 'u',   'ü' => 'u',   'ý' => 'y',   'ÿ' => 'y',
			// Latin Extended-A
			'Ā' => 'A',   'ā' => 'a',   'Ă' => 'A',   'ă' => 'a',   'Ą' => 'A',
			'ą' => 'a',   'Ć' => 'C',   'ć' => 'c',   'Ĉ' => 'C',   'ĉ' => 'c',
			'Ċ' => 'C',   'ċ' => 'c',   'Č' => 'C',   'č' => 'c',   'Ď' => 'D',
			'ď' => 'd',   'Đ' => 'D',   'đ' => 'd',   'Ē' => 'E',   'ē' => 'e',
			'Ĕ' => 'E',   'ĕ' => 'e',   'Ė' => 'E',   'ė' => 'e',   'Ę' => 'E',
			'ę' => 'e',   'Ě' => 'E',   'ě' => 'e',   'Ĝ' => 'G',   'ĝ' => 'g',
			'Ğ' => 'G',   'ğ' => 'g',   'Ġ' => 'G',   'ġ' => 'g',   'Ģ' => 'G',
			'ģ' => 'g',   'Ĥ' => 'H',   'ĥ' => 'h',   'Ħ' => 'H',   'ħ' => 'h',
			'Ĩ' => 'I',   'ĩ' => 'i',   'Ī' => 'I',   'ī' => 'i',   'Ĭ' => 'I',
			'ĭ' => 'i',   'Į' => 'I',   'į' => 'i',   'İ' => 'I',   'ı' => 'i',
			'Ĳ' => 'IJ',  'ĳ' => 'ij',  'Ĵ' => 'J',   'ĵ' => 'j',   'Ķ' => 'K',
			'ķ' => 'k',   'Ĺ' => 'L',   'ĺ' => 'l',   'Ļ' => 'L',   'ļ' => 'l',
			'Ľ' => 'L',   'ľ' => 'l',   'Ŀ' => 'L',   'ŀ' => 'l',   'Ł' => 'L',
			'ł' => 'l',   'Ń' => 'N',   'ń' => 'n',   'Ņ' => 'N',   'ņ' => 'n',
			'Ň' => 'N',   'ň' => 'n',   'ŉ' => "'n",  'Ŋ' => 'N',   'ŋ' => 'n',
			'Ō' => 'O',   'ō' => 'o',   'Ŏ' => 'O',   'ŏ' => 'o',   'Ő' => 'O',
			'ő' => 'o',   'Œ' => 'OE',  'œ' => 'oe',  'Ŕ' => 'R',   'ŕ' => 'r',
			'Ŗ' => 'R',   'ŗ' => 'r',   'Ř' => 'R',   'ř' => 'r',   'Ś' => 'S',
			'ś' => 's',   'Ŝ' => 'S',   'ŝ' => 's',   'Ş' => 'S',   'ş' => 's',
			'Š' => 'S',   'š' => 's',   'Ţ' => 'T',   'ţ' => 't',   'Ť' => 'T',
			'ť' => 't',   'Ŧ' => 'T',   'ŧ' => 't',   'Ũ' => 'U',   'ũ' => 'u',
			'Ū' => 'U',   'ū' => 'u',   'Ŭ' => 'U',   'ŭ' => 'u',   'Ů' => 'U',
			'ů' => 'u',   'Ű' => 'U',   'ű' => 'u',   'Ų' => 'U',   'ų' => 'u',
			'Ŵ' => 'W',   'ŵ' => 'w',   'Ŷ' => 'Y',   'ŷ' => 'y',   'Ÿ' => 'Y',
			'Ź' => 'Z',   'ź' => 'z',   'Ż' => 'Z',   'ż' => 'z',   'Ž' => 'Z',
			'ž' => 'z',
			// Latin Extended-B
			'ƀ' => 'b',   'Ɓ' => 'B',   'Ƃ' => 'B',   'ƃ' => 'b',   'Ɔ' => 'O',
			'Ƈ' => 'C',   'ƈ' => 'c',   'Ɖ' => 'D',   'Ɗ' => 'D',   'Ƌ' => 'D',
			'ƌ' => 'd',   'Ǝ' => 'E',   'Ɛ' => 'E',   'Ƒ' => 'F',   'ƒ' => 'f',
			'Ɠ' => 'G',   'Ɨ' => 'I',   'Ƙ' => 'K',   'ƙ' => 'k',   'ƚ' => 'l',
			'Ɯ' => 'M',   'Ɲ' => 'N',   'ƞ' => 'n',   'Ɵ' => 'O',   'Ơ' => 'O',
			'ơ' => 'o',   'Ƣ' => 'OI',  'ƣ' => 'oi',  'Ƥ' => 'P',   'ƥ' => 'p',
			'ƫ' => 't',   'Ƭ' => 'T',   'ƭ' => 't',   'Ʈ' => 'T',   'Ư' => 'U',
			'ư' => 'u',   'Ʋ' => 'V',   'Ƴ' => 'Y',   'ƴ' => 'y',   'Ƶ' => 'Z',
			'ƶ' => 'z',   'ƻ' => '2',   'Ǆ' => 'DZ',  'ǅ' => 'Dz',  'ǆ' => 'dz',
			'Ǉ' => 'LJ',  'ǈ' => 'Lj',  'ǉ' => 'lj',  'Ǌ' => 'Nj',  'ǋ' => 'Nj',
			'ǌ' => 'nj',  'Ǎ' => 'A',   'ǎ' => 'a',   'Ǐ' => 'I',   'ǐ' => 'i',
			'Ǒ' => 'O',   'ǒ' => 'o',   'Ǔ' => 'U',   'ǔ' => 'u',   'Ǖ' => 'U',
			'ǖ' => 'u',   'Ǘ' => 'U',   'ǘ' => 'u',   'Ǚ' => 'U',   'ǚ' => 'u',
			'Ǜ' => 'U',   'ǜ' => 'u',   'ǝ' => 'e',   'Ǟ' => 'A',   'ǟ' => 'a',
			'Ǡ' => 'A',   'ǡ' => 'a',   'Ǣ' => 'AE',  'ǣ' => 'ae',  'Ǥ' => 'G',
			'ǥ' => 'g',   'Ǧ' => 'G',   'ǧ' => 'g',   'Ǩ' => 'K',   'ǩ' => 'k',
			'Ǫ' => 'O',   'ǫ' => 'o',   'Ǭ' => 'O',   'ǭ' => 'o',   'ǰ' => 'j',
			'Ǳ' => 'DZ',  'ǲ' => 'Dz',  'ǳ' => 'dz',  'Ǵ' => 'G',   'ǵ' => 'g',
			'Ǹ' => 'N',   'ǹ' => 'n',   'Ǻ' => 'A',   'ǻ' => 'a',   'Ǽ' => 'AE',
			'ǽ' => 'ae',  'Ǿ' => 'O',   'ǿ' => 'o',   'Ȁ' => 'A',   'ȁ' => 'a',
			'Ȃ' => 'A',   'ȃ' => 'a',   'Ȅ' => 'E',   'ȅ' => 'e',   'Ȇ' => 'E',
			'ȇ' => 'e',   'Ȉ' => 'I',   'ȉ' => 'i',   'Ȋ' => 'I',   'ȋ' => 'i',
			'Ȍ' => 'O',   'ȍ' => 'o',   'Ȏ' => 'O',   'ȏ' => 'o',   'Ȑ' => 'R',
			'ȑ' => 'r',   'Ȓ' => 'R',   'ȓ' => 'r',   'Ȕ' => 'U',   'ȕ' => 'u',
			'Ȗ' => 'U',   'ȗ' => 'u',   'Ș' => 'S',   'ș' => 's',   'Ț' => 'T',
			'ț' => 't',   'Ȟ' => 'H',   'ȟ' => 'h',   'Ƞ' => 'N',   'ȡ' => 'd',
			'Ȥ' => 'Z',   'ȥ' => 'z',   'Ȧ' => 'A',   'ȧ' => 'a',   'Ȩ' => 'E',
			'ȩ' => 'e',   'Ȫ' => 'O',   'ȫ' => 'o',   'Ȭ' => 'O',   'ȭ' => 'o',
			'Ȯ' => 'O',   'ȯ' => 'o',   'Ȱ' => 'O',   'ȱ' => 'o',   'Ȳ' => 'Y',
			'ȳ' => 'y',   'ȴ' => 'l',   'ȵ' => 'n',   'ȶ' => 't',   'ȷ' => 'j',
			'ȸ' => 'db',  'ȹ' => 'qp',  'Ⱥ' => 'A',   'Ȼ' => 'C',   'ȼ' => 'c',
			'Ƚ' => 'L',   'Ⱦ' => 'T',   'ȿ' => 's',   'ɀ' => 'z',   'Ƀ' => 'B',
			'Ʉ' => 'U',   'Ʌ' => 'V',   'Ɇ' => 'E',   'ɇ' => 'e',   'Ɉ' => 'J',
			'ɉ' => 'j',   'Ɋ' => 'Q',   'ɋ' => 'q',   'Ɍ' => 'R',   'ɍ' => 'r',
			'Ɏ' => 'Y',   'ɏ' => 'y',
			// IPA Extensions
			'ɐ' => 'a',   'ɓ' => 'b',   'ɔ' => 'o',   'ɕ' => 'c',   'ɖ' => 'd',
			'ɗ' => 'd',   'ɘ' => 'e',   'ɛ' => 'e',   'ɜ' => 'e',   'ɝ' => 'e',
			'ɞ' => 'e',   'ɟ' => 'j',   'ɠ' => 'g',   'ɡ' => 'g',   'ɢ' => 'G',
			'ɥ' => 'h',   'ɦ' => 'h',   'ɨ' => 'i',   'ɪ' => 'I',   'ɫ' => 'l',
			'ɬ' => 'l',   'ɭ' => 'l',   'ɯ' => 'm',   'ɰ' => 'm',   'ɱ' => 'm',
			'ɲ' => 'n',   'ɳ' => 'n',   'ɴ' => 'N',   'ɵ' => 'o',   'ɶ' => 'OE',
			'ɹ' => 'r',   'ɺ' => 'r',   'ɻ' => 'r',   'ɼ' => 'r',   'ɽ' => 'r',
			'ɾ' => 'r',   'ɿ' => 'r',   'ʀ' => 'R',   'ʁ' => 'R',   'ʂ' => 's',
			'ʇ' => 't',   'ʈ' => 't',   'ʉ' => 'u',   'ʋ' => 'v',   'ʌ' => 'v',
			'ʍ' => 'w',   'ʎ' => 'y',   'ʏ' => 'Y',   'ʐ' => 'z',   'ʑ' => 'z',
			'ʗ' => 'C',   'ʙ' => 'B',   'ʚ' => 'e',   'ʛ' => 'G',   'ʜ' => 'H',
			'ʝ' => 'j',   'ʞ' => 'k',   'ʟ' => 'L',   'ʠ' => 'q',   'ʣ' => 'dz',
			'ʥ' => 'dz',  'ʦ' => 'ts',  'ʨ' => 'tc',  'ʪ' => 'ls',  'ʫ' => 'lz',
			'ʮ' => 'h',   'ʯ' => 'h',
			// Latin Extended Additional
			'Ḁ' => 'A',   'ḁ' => 'a',   'Ḃ' => 'B',   'ḃ' => 'b',   'Ḅ' => 'B',
			'ḅ' => 'b',   'Ḇ' => 'B',   'ḇ' => 'b',   'Ḉ' => 'C',   'ḉ' => 'c',
			'Ḋ' => 'D',   'ḋ' => 'd',   'Ḍ' => 'D',   'ḍ' => 'd',   'Ḏ' => 'D',
			'ḏ' => 'd',   'Ḑ' => 'D',   'ḑ' => 'd',   'Ḓ' => 'D',   'ḓ' => 'd',
			'Ḕ' => 'E',   'ḕ' => 'e',   'Ḗ' => 'E',   'ḗ' => 'e',   'Ḙ' => 'E',
			'ḙ' => 'e',   'Ḛ' => 'E',   'ḛ' => 'e',   'Ḝ' => 'E',   'ḝ' => 'e',
			'Ḟ' => 'F',   'ḟ' => 'f',   'Ḡ' => 'G',   'ḡ' => 'g',   'Ḣ' => 'H',
			'ḣ' => 'h',   'Ḥ' => 'H',   'ḥ' => 'h',   'Ḧ' => 'H',   'ḧ' => 'h',
			'Ḩ' => 'H',   'ḩ' => 'h',   'Ḫ' => 'H',   'ḫ' => 'h',   'Ḭ' => 'I',
			'ḭ' => 'i',   'Ḯ' => 'I',   'ḯ' => 'i',   'Ḱ' => 'K',   'ḱ' => 'k',
			'Ḳ' => 'K',   'ḳ' => 'k',   'Ḵ' => 'K',   'ḵ' => 'k',   'Ḷ' => 'L',
			'ḷ' => 'l',   'Ḹ' => 'L',   'ḹ' => 'l',   'Ḻ' => 'L',   'ḻ' => 'l',
			'Ḽ' => 'L',   'ḽ' => 'l',   'Ḿ' => 'M',   'ḿ' => 'm',   'Ṁ' => 'M',
			'ṁ' => 'm',   'Ṃ' => 'M',   'ṃ' => 'm',   'Ṅ' => 'N',   'ṅ' => 'n',
			'Ṇ' => 'N',   'ṇ' => 'n',   'Ṉ' => 'N',   'ṉ' => 'n',   'Ṋ' => 'N',
			'ṋ' => 'n',   'Ṍ' => 'O',   'ṍ' => 'o',   'Ṏ' => 'O',   'ṏ' => 'o',
			'Ṑ' => 'O',   'ṑ' => 'o',   'Ṓ' => 'O',   'ṓ' => 'o',   'Ṕ' => 'P',
			'ṕ' => 'p',   'Ṗ' => 'P',   'ṗ' => 'p',   'Ṙ' => 'R',   'ṙ' => 'r',
			'Ṛ' => 'R',   'ṛ' => 'r',   'Ṝ' => 'R',   'ṝ' => 'r',   'Ṟ' => 'R',
			'ṟ' => 'r',   'Ṡ' => 'S',   'ṡ' => 's',   'Ṣ' => 'S',   'ṣ' => 's',
			'Ṥ' => 'S',   'ṥ' => 's',   'Ṧ' => 'S',   'ṧ' => 's',   'Ṩ' => 'S',
			'ṩ' => 's',   'Ṫ' => 'T',   'ṫ' => 't',   'Ṭ' => 'T',   'ṭ' => 't',
			'Ṯ' => 'T',   'ṯ' => 't',   'Ṱ' => 'T',   'ṱ' => 't',   'Ṳ' => 'U',
			'ṳ' => 'u',   'Ṵ' => 'U',   'ṵ' => 'u',   'Ṷ' => 'U',   'ṷ' => 'u',
			'Ṹ' => 'U',   'ṹ' => 'u',   'Ṻ' => 'U',   'ṻ' => 'u',   'Ṽ' => 'V',
			'ṽ' => 'v',   'Ṿ' => 'V',   'ṿ' => 'v',   'Ẁ' => 'W',   'ẁ' => 'w',
			'Ẃ' => 'W',   'ẃ' => 'w',   'Ẅ' => 'W',  'ẅ' => 'w',   'Ẇ' => 'W',
			'ẇ' => 'w',   'Ẉ' => 'W',   'ẉ' => 'w',   'Ẋ' => 'X',   'ẋ' => 'x',
			'Ẍ' => 'X',   'ẍ' => 'x',   'Ẏ' => 'Y',   'ẏ' => 'y',   'Ẑ' => 'Z',
			'ẑ' => 'z',   'Ẓ' => 'Z',   'ẓ' => 'z',   'Ẕ' => 'Z',   'ẕ' => 'z',
			'ẖ' => 'h',   'ẗ' => 't',   'ẘ' => 'w',   'ẙ' => 'y',   'ẚ' => 'a',
			'Ạ' => 'A',   'ạ' => 'a',   'Ả' => 'A',   'ả' => 'a',   'Ấ' => 'A',
			'ấ' => 'a',   'Ầ' => 'A',   'ầ' => 'a',   'Ẩ' => 'A',   'ẩ' => 'a',
			'Ẫ' => 'A',   'ẫ' => 'a',   'Ậ' => 'A',   'ậ' => 'a',   'Ắ' => 'A',
			'ắ' => 'a',   'Ằ' => 'A',   'ằ' => 'a',   'Ẳ' => 'A',   'ẳ' => 'a',
			'Ẵ' => 'A',   'ẵ' => 'a',   'Ặ' => 'A',   'ặ' => 'a',   'Ẹ' => 'E',
			'ẹ' => 'e',   'Ẻ' => 'E',   'ẻ' => 'e',   'Ẽ' => 'E',   'ẽ' => 'e',
			'Ế' => 'E',   'ế' => 'e',   'Ề' => 'E',   'ề' => 'e',   'Ể' => 'E',
			'ể' => 'e',   'Ễ' => 'E',   'ễ' => 'e',   'Ệ' => 'E',   'ệ' => 'e',
			'Ỉ' => 'I',   'ỉ' => 'i',   'Ị' => 'I',   'ị' => 'i',   'Ọ' => 'O',
			'ọ' => 'o',   'Ỏ' => 'O',   'ỏ' => 'o',   'Ố' => 'O',   'ố' => 'o',
			'Ồ' => 'O',   'ồ' => 'o',   'Ổ' => 'O',   'ổ' => 'o',   'Ỗ' => 'O',
			'ỗ' => 'o',   'Ộ' => 'O',   'ộ' => 'o',   'Ớ' => 'O',   'ớ' => 'o',
			'Ờ' => 'O',   'ờ' => 'o',   'Ở' => 'O',   'ở' => 'o',   'Ỡ' => 'O',
			'ỡ' => 'o',   'Ợ' => 'O',   'ợ' => 'o',   'Ụ' => 'U',   'ụ' => 'u',
			'Ủ' => 'U',   'ủ' => 'u',   'Ứ' => 'U',   'ứ' => 'u',   'Ừ' => 'U',
			'ừ' => 'u',   'Ử' => 'U',   'ử' => 'u',   'Ữ' => 'U',   'ữ' => 'u',
			'Ự' => 'U',   'ự' => 'u',   'Ỳ' => 'Y',   'ỳ' => 'y',   'Ỵ' => 'Y',
			'ỵ' => 'y',   'Ỷ' => 'Y',   'ỷ' => 'y',   'Ỹ' => 'Y',   'ỹ' => 'y',
			// General Punctuation
			' ' => ' ',   ' ' => ' ',   ' ' => ' ',   ' ' => ' ',   ' ' => ' ',
			' ' => ' ',   ' ' => ' ',   ' ' => ' ',   ' ' => ' ',   ' ' => ' ',
			' ' => ' ',   '​' => '',    '‌' => '',    '‍' => '',    '‐' => '-',
			'‑' => '-',   '‒' => '-',   '–' => '-',   '—' => '-',   '―' => '-',
			'‖' => '||',  '‘' => "'",   '’' => "'",   '‚' => ',',   '‛' => "'",
			'“' => '"',   '”' => '"',   '‟' => '"',   '․' => '.',   '‥' => '..',
			'…' => '...', ' ' => ' ',   '′' => "'",   '″' => '"',   '‴' => '\'"',
			'‵' => "'",   '‶' => '"',   '‷' => '"\'', '‹' => '<',   '›' => '>',
			'‼' => '!!',  '‽' => '?!',  '⁄' => '/',   '⁇' => '?/',  '⁈' => '?!',
			'⁉' => '!?',
			// Letterlike Symbols
			'℠' => 'SM',  '™' => 'TM',
			// Number Forms
			'⅓' => '1/3', '⅔' => '2/3', '⅕' => '1/5', '⅖' => '2/5', '⅗' => '3/5',
			'⅘' => '4/5', '⅙' => '1/6', '⅚' => '5/6', '⅛' => '1/8', '⅜' => '3/8',
			'⅝' => '5/8', '⅞' => '7/8', 'Ⅰ' => 'I',   'Ⅱ' => 'II',  'Ⅲ' => 'III',
			'Ⅳ' => 'IV',  'Ⅴ' => 'V',   'Ⅵ' => 'Vi',  'Ⅶ' => 'VII', 'Ⅷ' => 'VIII',
			'Ⅸ' => 'IX',  'Ⅹ' => 'X',   'Ⅺ' => 'XI',  'Ⅻ' => 'XII', 'Ⅼ' => 'L',
			'Ⅽ' => 'C',   'Ⅾ' => 'D',   'Ⅿ' => 'M',   'ⅰ' => 'i',   'ⅱ' => 'ii',
			'ⅲ' => 'iii', 'ⅳ' => 'iv',  'ⅴ' => 'v',   'ⅵ' => 'vi',  'ⅶ' => 'vii',
			'ⅷ' => 'viii','ⅸ' => 'ix',  'ⅹ' => 'x',   'ⅺ' => 'xi',  'ⅻ' => 'xii',
			'ⅼ' => 'l',   'ⅽ' => 'c',   'ⅾ' => 'd',   'ⅿ' => 'm'
		);

		if (preg_match('#[^\x00-\x7F]#', $str) === false)
		{
			return $str;
		}

		$str = strtr($str, $foreign_characters);

		// remove any left over non 7bit ASCII
		return preg_replace('#[^\x00-\x7F]#', '', $str);
	}

	/**
	 * Converts your text to a URL-friendly title so it can be used in the URL.
	 * Only works with UTF8 input and and only outputs 7 bit ASCII characters.
	 *
	 * @param string $str The text
	 * @param string $sep The separator (either - or _)
	 * @param bool $lowercase Whether to lowercase the string or not
	 * @return string The new title
	 */
	public static function friendlyTitle($str, $sep = '-', $lowercase = true)
	{
		// Allow underscore, otherwise default to dash
		$sep = $sep === '_' ? '_' : '-';

		// Remove tags
		$str = Security::stripTags($str);

		// Decode all entities to their simpler forms
		$str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');

		// Remove all quotes.
		$str = preg_replace("#[\"\']#", '', $str);

		// Only allow 7bit characters
		$str = static::toAscii($str);

		// Strip unwanted characters
		$str = preg_replace("#[^a-z0-9]#i", $sep, $str);
		$str = preg_replace("#[/_|+ -]+#", $sep, $str);
		$str = trim($str, $sep);

		return $lowercase ? strtolower($str) : $str;
	}

	/**
	 * Returns any string, converted to using dashes with only lowercase
	 * alphanumerics.
	 *
	 * 'foo bar Is TheAwesomeThing-EverISaid' to 'foo-bar-is-the-awesome-thing-ever-i-said'
	 *
	 * @param string $str The string to convert.
	 * @param string $encoding Encoding of the string, null to use the app's default
	 * @return string The converted string.
	 */
	public static function toDashes($str, $encoding = null)
	{
		$str = static::camelCaseToDashes(preg_replace('/[^\w _-]/i' . static::_regexModifier($encoding), '', $str), $encoding);
		return preg_replace('/[ _-]+/', '-', $str);
	}

	/**
	 * Turns an underscore or dash separated word and turns it into a human looking string.
	 *
	 * @param string $str The word
	 * @param string $encoding Encoding of the string, null to use the app's default
	 * @param bool $only_first_upper Whether to uppercase only the first letter or all words
	 * @return string The human version of given string
	 */
	public static function toHuman($str, $only_first_upper = true, $encoding = null)
	{
		$str = str_replace('-', " ", static::toDashes($str, $encoding));
		return $only_first_upper ? Str::ucfirst($str, $encoding) : Str::ucwords($str, $encoding);
	}

	/**
	 * Returns "foo_bar_baz" as "fooBarBaz".
	 *
	 * @param string $str The underscore word.
	 * @param string $encoding Encoding of the string, null to use the app's default
	 * @return string The word in camel-caps.
	 */
	public static function underLinesToCamelCase($str, $encoding = null)
	{
		$str = Str::ucwords(str_replace('_', ' ', $str), $encoding);
		$str = str_replace(' ', '', $str);
		return Str::lcfirst($str, $encoding);
	}

	/**
	 * Returns "foo-bar-baz" as "fooBarBaz".
	 *
	 * @param string $str The dashed word.
	 * @param string $encoding Encoding of the string, null to use the app's default
	 * @return string The word in camel-caps.
	 */
	public static function dashesToCamelCase($str, $encoding = null)
	{
		$str = Str::ucwords(str_replace('-', ' ', $str), $encoding);
		$str = str_replace(' ', '', $str);
		$str[0] = Str::lower($str[0], $encoding);
		return $str;
	}

	/**
	 * Returns "foo_bar_baz" as "FooBarBaz".
	 *
	 * @param string $str The underscore word.
	 * @param string $encoding Encoding of the string, null to use the app's default
	 * @return string The word in studly-caps.
	 */
	public static function underLinesToStudlyCase($str, $encoding = null)
	{
		return Str::ucfirst(static::underLinesToCamelCase($str), $encoding);
	}

	/**
	 * Returns "foo-bar-baz" as "FooBarBaz".
	 *
	 * @param string $str The dashed word.
	 * @param string $encoding Encoding of the string, null to use the app's default
	 * @return string The word in studly-caps.
	 */
	public static function dashesToStudlyCase($str, $encoding = null)
	{
		return Str::ucfirst(static::dashesToCamelCase($str), $encoding);
	}

	/**
	 * Returns "camelCapsWord" and "CamelCapsWord" as "Camel_Caps_Word".
	 *
	 * @param string $str The camel-caps word.
	 * @param string $encoding Encoding of the string, null to use the app's default
	 * @return string The word with underscores in place of camel caps.
	 */
	public static function camelCaseToUnderLines($str, $encoding = null)
	{
		return Str::lower(preg_replace('/(?<=\\w)([[:upper:]])/' . static::_regexModifier($encoding), '_\\1', $str), $encoding);
	}

	/**
	 * Returns "camelCapsWord" and "CamelCapsWord" as "camel-caps-word".
	 *
	 * @param string $str The camel-caps word.
	 * @param string $encoding Encoding of the string, null to use the app's default
	 * @return string The word with dashes in place of camel caps.
	 */
	public static function camelCaseToDashes($str, $encoding = null)
	{
		return Str::lower(preg_replace('/(?<=\\w)([[:upper:]])/' . static::_regexModifier($encoding), '-\\1', $str), $encoding);
	}

	/**
	 * Create a properly modifier for regex with/out support to UTF-8
	 *
	 * @param $encoding
	 * @return string
	 */
	protected static function _regexModifier($encoding)
	{
		$encoding or $encoding = Config::get('system.encoding');

		// @TODO Check which encodings are supportable by PREG
		if (strtoupper($encoding) == 'UTF-8')
		{
			return 'u';
		}

		return '';
	}
}