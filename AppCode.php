<?php

class AppCode {

	private static $iv = '4rgaP3pKm+hP9jie5o2z4J+SNVzrQE90xgo6tX49KNYnkZlkSiPt0a+jfZ3JRg5P518eP4wdBbnOIFLin9piJQ==';
	private static $key = 'kt87R7CiVBoU0pPZxWMTVPb4HGUd1IVRUFu9rink5NBGFUum1aDPqm/V8FdQJdgzoNZ3fsGNvruzuAf0l+QpiA==';

	public static function getMktime()
	{
		return mktime(date('H'), 0, 0, date('m'), date('d'), date('Y'));
	}


	public static function encode($_plain)
	{
		$td = mcrypt_module_open(MCRYPT_BLOWFISH, '', MCRYPT_MODE_CBC, '');
		$iv_size = mcrypt_enc_get_iv_size($td);
		$iv = substr(hash('sha512', self::$iv), 0, $iv_size);
		$key_size = mcrypt_enc_get_key_size($td);
		$key = substr(hash('sha512', self::$key), 0, $key_size);
		mcrypt_generic_init($td, $key, $iv);
		$encoded = mcrypt_generic($td, $_plain);
		$base64 = base64_encode($encoded);
		$for_url = str_replace(array('+', '/', '='), array('.', '_', '-'), $base64);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $for_url;
	}


	public static function decode($_encode)
	{
		$td = mcrypt_module_open(MCRYPT_BLOWFISH, '', MCRYPT_MODE_CBC, '');
		$iv_size = mcrypt_enc_get_iv_size($td);
		$iv = substr(hash('sha512', self::$iv), 0, $iv_size);
		$key_size = mcrypt_enc_get_key_size($td);
		$key = substr(hash('sha512', self::$key), 0, $key_size);
		mcrypt_generic_init($td, $key, $iv);
		$base64 = str_replace(array('.', '_', '-'), array('+', '/', '='), $_encode);
		$bin = base64_decode($base64);
		$decoded = mdecrypt_generic($td, $bin);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return trim($decoded);
	}

}
