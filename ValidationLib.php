<?php

class ValidationLib
{

	/*
	 * Kanji: \x{3005}\x{3007}\x{303B}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}\x{20000}-\x{2FFFF}
	 *     : incorrect \p{Han}, [一-龠]
	 * HIragana: \x{3041}-\x{3096}
	 * Fw-Kana: \x{30A1}-\x{30FF}
	 * ・: \x{30FB}
	 * ー: \x{30FC}
	 */

	public static $ascii = '\x20-\x7F';
	public static $kanji = '\x{3005}\x{3007}\x{303B}\x{3400}-\x{4DBF}\x{4E00}-\x{9FFF}\x{F900}-\x{FAFF}\x{20000}-\x{2FFFF}';
	public static $hiragana = '\x{3000}-\x{303F}\x{3041}-\x{3096}\x{30FB}-\x{30FF}';
	public static $fw_kana = '\x{3000}-\x{303F}\x{30A1}-\x{30FF}';
	public static $hw_kana = '\x{FF61}-\x{FF9F}';
	public static $fw_space = '\x{3000}';
	public static $roma_num = '\x{2160}-\x{2184}';
	public static $fw_mark = '\x{3220}-\x{33FE}';
	public static $fw_ascii = '\x{FF01}-\x{FF60}';

	public static function isStringInteger(&$_value)
	{
		$_value = mb_convert_kana($_value, 'n');
		return ! preg_match('/^(?:\d|[1-9][\d]+)$/', $_value) ? false : true;
	}

	public static function isStringIntegerRange(&$_value, $_min, $_max)
	{
		$_value = mb_convert_kana($_value, 'n');
		if (! preg_match('/^(?:\d|[1-9][\d]+)$/', $_value)) {
			return false;
		}
		$value = intval($_value);
		return ($_min > $value || $_max < $value) ? false : true;
	}

	public static function isJa($_value)
	{
		return preg_match('/[^' .
			self::$ascii .
			self::$kanji .
			self::$hiragana .
			self::$fw_kana .
			self::$hw_kana .
			self::$fw_space .
			self::$roma_num .
			self::$fw_mark .
			self::$fw_ascii .
		']/u', $_value) ? false : true;
	}

	public static function isKanjiHiragana($_value)
	{
		$_value = mb_convert_kana($_value, 'cHV');
		return preg_match('/[^' . self::$kanji . self::$hiragana . ']/u', $_value) ? false : true;
	}

	public static function isAsciiKanjiHiragana_FwKana(&$_value)
	{
		# Fw Ascii -> Hw, Hw Kana -> Fw
		$_value = mb_convert_kana($_value, 'aKV');
		return preg_match('/[^' . self::$ascii . self::$kanji . self::$hiragana . self::$fw_kana . ']/u', $_value) ? false : true;
	}

	public static function isAscii(&$_value)
	{
		# Fw Ascii -> Hw, Hw Kana -> Fw
		$_value = mb_convert_kana($_value, 'a');
		return preg_match('/[^' . self::$ascii . ']/u', $_value) ? false : true;
	}

	public static function isAlphaNum(&$_value)
	{
		# Fw Ascii -> Hw, Hw Kana -> Fw
		$_value = mb_convert_kana($_value, 'a');
		return preg_match('/[^a-z\d]/i', $_value) ? false : true;
	}

	public static function isBinary(&$_value)
	{
		return false === strpos($_value, '\0') ? false : true;
	}

	public static function isMailAddress(&$_value)
	{
		$mails = explode('@', $_value);

		# @ check
		if (2 !== count($mails)) {
			return false;
		}

		# @ OK Local none
		if (0 === strlen($mails[0])) {
			return false;
		}

		# @ OK Domain none
		if (0 === strlen($mails[1])) {
			return false;
		}

		# Local Part
		if (preg_match('/^"([\d!#$%&\'*+-.\/=?^_`{|}~a-z()<>\[\]:;,@\s]*?)([^\d!"#$%&\'*+-.\/=?^_`{|}~\\a-z()<>\[\]:;,@\s]+?|(?<!\\\)")([\d!#$%&\'*+-.\/=?^_`{|}~a-z()<>\[\]:;,@\s]*?)"$|^(?<!")([\d!#$%&\'*+\-.\/=?^_`{|}~a-z]*?)([^\d!#$%&\'*+\-.\/=?^_`{|}~a-z]+?)([\d!#$%&\'*+\-.\/=?^_`{|}~a-z]*?)(?!")$|^"[^"]+?(?!")$|^(?<!")[^"]+?"$|^\.|\.$|^(?<!")\.{2,}(?!")$/i', $mails[0])) {
			return false;
		}

		# Domain Part
		if (preg_match('/^\[(?!(\d|[1-9]\d|1[\d][\d]|2[0-5][0-5])\.(\d|[1-9]\d|1[\d][\d]|2[0-5][0-5])\.(\d|[1-9]\d|1[\d][\d]|2[0-5][0-5])\.(\d|[1-9]\d|1[\d][\d]|2[0-5][0-5])\]$)|^[^\[].+?\.[a-z]*?[^a-z.]+?[a-z]*?(?!\])$|^(?<!\[)[\d\-.a-z]*?[^\d\-.a-z]+?[\d\-.a-z]*?(?!\])$|^\.|\.$|\.{2,}|^-|-$|-{2,}|-\.|\.-/i', $mails[1])) {
			return false;
		}

		if (64 < strlen($mails[0])) {
			return false;
		}

		if (253 < strlen($mails[1])) {
			return false;
		}

		if (254 < strlen($_value)) {
			return false;
		}

		return true;
	}

	public static function isHost(&$_value)
	{
		# Domain Part
		if (preg_match('/^\[(?!(\d|[1-9]\d|1[\d][\d]|2[0-5][0-5])\.(\d|[1-9]\d|1[\d][\d]|2[0-5][0-5])\.(\d|[1-9]\d|1[\d][\d]|2[0-5][0-5])\.(\d|[1-9]\d|1[\d][\d]|2[0-5][0-5])\]$)|^[^\[].+?\.[a-z]*?[^a-z.]+?[a-z]*?(?!\])$|^(?<!\[)[\d\-.a-z]*?[^\d\-.a-z]+?[\d\-.a-z]*?(?!\])$|^\.|\.$|\.{2,}|^-|-$|-{2,}|-\.|\.-/i', $_val)) {
			return false;
		}

		if (253 < strlen($_value)) {
			return false;
		}

		return true;
	}

	public static function isIpv4(&$_value)
	{
		return preg_match('/^(?:(?:\d|\d\d|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d|\d\d|1\d\d|2[0-4]\d|25[0-5])$/', $_value) ? true : false;
	}

	public static function isIpv4Cidr(&$_value)
	{
		return preg_match('/^(?:(?:\d|\d\d|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d|\d\d|1\d\d|2[0-5][0-5])\/(?:\d|[1-2]\d|3[0-2])$/', $_value) ? true : false;
	}

	public static function isPort(&$_value)
	{
		if (! is_int($_value)) {
			return false;
		}

		if ($_value < 0 || $_value > 65535) {
			return false;
		}

		return true;
	}

	public static function isDate(&$_value)
	{
		# Format Check
		if (! preg_match('/^(?:[1-2]\d\d\d)-(?:0[1-9]|1[0-2])-(?:0[1-9]|[1-2]\d|3[0-1])$/', $_value)) {
			return false;
		}

		# Date Check
		$parts = explode('-', $_value);
		if (! checkdate(intval($parts[1]), intval($parts[2]), intval($parts[0]))) {
			return false;
		}

		return true;
	}

	public static function isDatetime(&$_value)
	{
		# Format Check
		if (!preg_match('/^[\dT\-:+ ]+$/', $_value)) {
			return false;
		}

		if (!preg_match('/^(\d{1,4})-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $_value)) {
			return true;
		}

		$time = mb_substr($_value, 0, -3);

		if ($time !== date("Y-m-d H:i", strtotime($time))) {
			return false;
		}

		# Leap Seconds
		if (!preg_match('/^(\d{1,4})-\d\d-\d\d\s\d\d:\d\d:([0-5][0-9]|60)$/', $_value)) {
			return false;
		}

		return true;
	}

	public static function isXOrO(&$_value)
	{
		$_value = preg_replace(array('/\x{00D7}/u', '/\x{25CB}|\x{3007}|\x{25CE}|\x{25CF}/u'), array('x', 'o'), $_value);
		$_value = mb_convert_kana($_value, 'a');
		$_value = strtolower($_value);
		$lists = array('x', 'o');
		return ! in_array($_value, $lists) ? false : true;
	}

	public static function isYesOrNo(&$_value)
	{
		$_value = mb_convert_kana($_value, 'a');
		$_value = strtolower($_value);
		$lists = array('yes', 'no');
		return ! in_array($_value, $lists) ? false : true;
	}

}
