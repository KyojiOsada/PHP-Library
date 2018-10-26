<?php

class AppArray
{

	public static function forwardKey(array $_sources, array $_filters)
	{
		$results = array();
		foreach ($_sources as $key => $value) {
			if (array_key_exists($key, $_filters)) {
				if (is_array($_filters[$key])) {
					foreach ($_filters[$key] as $i => $v) {
						$results[$v] = $value;
					}
					continue;
				}
				$results[$_filters[$key]] = $value;
				continue;
			}
			$results[$key] = $value;
		}
		
		return $results;
	}

	public static function reverseKey(array $_sources, array $_filters)
	{
		$results = array();
		foreach ($_sources as $key => $value) {
			if (is_array($value)) {
				$results[$key] = self::reverseKey($value, $_filters);
				continue;
			}
			if (false !== ($back_key = array_search($key, $_filters))) {
				if (is_int($back_key)) {
					continue;
				}
				$results[$back_key] = $value;
				continue;
			} else {
				$is_set = 0;
				foreach ($_filters as $fkey => $fvalue) {
					if (is_array($fvalue) && in_array($key, $fvalue)) {
						$results[$fkey] = $value;
						$is_set = 1;
						break;
					}
				}
				if ($is_set === 0) {
					$results[$key] = $value;
				}
			}
		}

		return $results;
	}

}
