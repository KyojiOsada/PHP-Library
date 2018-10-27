<?php
/**
 * @author Kyoji Osada at WARP-WG
 * @copyright 2017 WARP-WG
 * @license Apache-2.0
 */
namespace WarpWg;

use WarpWg\WarpException;

/**
 * for WARP Query Parser
 *
 * @version 0.1.0
 * @date 2018-04:20 UTC
 */
class WarpQuery
{

	protected static $operators = array(';', '&', '|', '^', '==', '!=', '><', '<<', '>>', '<>', '.ij.', '.lj.', '.rj.', '.cj.', '>=', '<=', '>', '<', '?=', ':=', '.ge.', '.le.', '.gt.', '.lt.', '%3E%3E', '%3C%3C', '%3E%3C', '%3C%3E', '%3E=', '%3C=', '%3E', '%3C', '=');
	protected static $central_operators = array('==', '!=', '><', '<<', '>>', '<>', '>=', '<=', '>', '<', '?=', ':=', '=');
	protected static $compare_operators = array('==', '!=', '>=', '<=', '>', '<', '?=');
	protected static $logical_operators = array('&', '|', '^');
	protected static $join_operators = array('><', '<<', '>>', '<>');


	/**
	 * decode Pion Query to Pion Object
	 *
	 * @param string $_query Pion Query
	 * @return array $queries Pion Object
	 */
	public static function decode($_query = null)
	{
		# check Empty Query String
		if (empty($_query)) {
			return array();
		}

		# form Query String for Parsing
		$query_string = '&' . $_query . ';';

		$queries = array();
		# for Proxy Parameters
		while (true) {
			## check Curly Brackets
			if (! preg_match('/\A(?:|(.*?)([&|]))({.*?[^%]})(.*)\z/', $query_string, $matches)) {
				break;
			}

			## to Semantics Variables
			list($all_match, $pre_match, $process, $proxy, $post_match) = $matches;

			## delete Curly Bracket
			$proxy = preg_replace('/\A{(.*)}\z/', '$1', $proxy);

			## for Virtical Proxy Module
			if (0 === strpos($proxy, '/')) {
				$location = 'self';
			## for Horizontal Proxy Module
			} else if (preg_match('/http(?:|s):\/\//', $proxy)) {
				if (! preg_match('{^(http(?:|s):\/\/.+?)\/}i', $proxy, $matches)) {
					throw new WarpException('The proxy is having invalid domain part: ' . $proxy, 400);
				}
				$location = $matches[1];
				$proxy = str_replace($location, '', $proxy);
			## for Others
			} else {
				throw new WarpException(WarpException::SYNTAX_ERROR . 'The Proxy Parameters are having unknown URL scheme: ' . $proxy, 400);
			}

			### to Objects
			$queries[] = array(
				$process,
				$location,
				'{}',
				$proxy,
			);

			## reform Query String for Parsing
			$query_string = $pre_match . $post_match;
		}

		# escape Operators
		$esc_operators = array();
		foreach (self::$operators as $i => $operator) {
			$esc_operators[$i] = preg_quote($operator);
		}

		# form Operators Regex
		$operators_regex = '{\A(.*?)(' . implode('|', $esc_operators) . ')(.*?)\z}';

		# Query to Parts
		$query_parts = array();
		while (true) {
			## matching Operators
			if (! preg_match($operators_regex, $query_string, $matches)) {
				break;
			}

			## to Semantics Variables
			list($all_match, $operand, $operator, $post_match) = $matches;

			## from Alias Operators to Master Operators
			if ($operator === '.ge.' || $operator === '%3E=') {
				$operator = '>=';
			} else if ($operator === '.le.' || $operator === '%3C=') {
				$operator = '<=';
			} else if ($operator === '.gt.' || $operator === '%3E') {
				$operator = '>';
			} else if ($operator === '.lt.' || $operator === '%3C') {
				$operator = '<';
			} else if ($operator === '.ij.' || $operator === '%3E%3C') {
				$operator = '><';
			} else if ($operator === '.lj.' || $operator === '%3C%3C') {
				$operator = '<<';
			} else if ($operator === '.rj.' || $operator === '%3E%3E') {
				$operator = '>>';
			} else if ($operator === '.cj.' || $operator === '%3C%3E') {
				$operator = '<>';
			}

			## map to Query Parts
			if ($operand !== '') {
				$query_parts[] = $operand;
			}
			$query_parts[] = $operator;

			## from Post Matcher to Query String
			$query_string = $post_match;
		}

		# check Data-Type-Head Module
		$data_type_id = array_search('data-type', $query_parts);
		$data_type = null;
		if ((false !== $data_type_id) && ($query_parts[$data_type_id + 1] === ':=')) {
			$data_type = $query_parts[$data_type_id + 2];
		}

		# map to Queries
		foreach ($query_parts as $i => $query_part) {

			## not NV Operators
			if (! in_array($query_part, self::$central_operators)) {
				continue;
			}

			## to Semantics Variables
			$logical_operator = $query_parts[$i - 2];
			$left_operand = $query_parts[$i - 1];
			$central_operator = $query_part;
			$right_operand = $query_parts[$i + 1];

			## for Data-Type-Head Module
			### for Strict Data Type
			if ($data_type === 'true') {
				$regex = '/\A%(?:22|27|["\'])(.*?)%(?:22|27|["\'])\z/';
				### delete first and last quotes for String Data Type
				if (preg_match($regex, $right_operand)) {
					$right_operand = preg_replace($regex, '$1', $right_operand);
				### for Not String Type
				} else {
					#### to Boolean
					if ($right_operand === 'true') {
						$right_operand = true;
					#### to Boolean
					} else if ($right_operand === 'false') {
						$right_operand = false;
					#### to Null
					} else if ($right_operand === 'null') {
						$right_operand = null;
					#### to Integer
					} else if (preg_match('/\A\d\z|\A[1-9]\d+\z/', $right_operand)) {
						$right_operand = intval($right_operand);
					#### to Float
					} else if (preg_match('/\A\d\.\d+\z|\A[1-9]\d+\.\d+\z/', $right_operand)) {
						$right_operand = floatval($right_operand);
					}
				}
			}

			## validate Left Operand
			if (in_array($left_operand, self::$operators)) {
				throw new WarpException(WarpException::SYNTAX_ERROR . 'The parameter is having invalid left operands: ' . $_query, 400);
			}

			## validate Right Operand
			### to Empty String
			# Notice: if $right_operand is true, in_array() function always returns true.
			if ($right_operand !== true && in_array($right_operand, self::$logical_operators) || $right_operand === ';') {
				$right_operand = '';
			### for Double NV Operators
			# Notice: if $right_operand is true, in_array() function always returns true.
			} else if ($right_operand !== true && in_array($right_operand, self::$central_operators)) {
				throw new WarpException(WarpException::SYNTAX_ERROR .  'The parameter is having double comparing operators: ' . $_query, 400);
			}

			## map to Queries
			switch (true) {
				### for Head Parameters
				case $central_operator === ':=':
					#### validate Logical Part
					if ($logical_operator !== '&') {
						throw new WarpException(WarpException::SYNTAX_ERROR . 'The Head Parameters must be a “and” logical operator: ' . $_query, 400);
					}

					break;
				### for Assign Parameters
				case $central_operator === '=':
					#### validate Logical Part
					if ($logical_operator !== '&') {
						throw new WarpException(WarpException::SYNTAX_ERROR . 'The Assign Parameters must be a “&” logical operator: ' . $_query, 400);
					}

					break;
				### for Join Parameters
				case in_array($central_operator, self::$join_operators):
					#### validate Logical Part
					if ($logical_operator !== '&') {
						throw new WarpException(WarpException::SYNTAX_ERROR . 'The Join Parameters must be a “&” logical operator: ' . $_query, 400);
					}

					break;
				### for Search Parameters
				case in_array($central_operator, self::$compare_operators):
					#### validate Logical Part
					if (! in_array($logical_operator, self::$logical_operators)) {
						throw new WarpException(WarpException::SYNTAX_ERROR . 'The Search Parameters are having invalid logical operators: ' . $_query, 400);
					}

					break;
				## for Others
				default:
					continue;
			}

			#### to Queries
			$queries[] = array(
				$logical_operator,
				$left_operand,
				$central_operator,
				$right_operand,
			);
		}

		# init Searches 1st Logical Operator
		if (! empty($queries[0][0])) {
			$queries[0][0] = '';
		}

		# return
		return $queries;
	}


	/**
	 * encode Pion Object to Pion Query
	 *
	 * @param array $_object Pion Object
	 * @return string $query Pion Query
	 */
	public static function encode(array $_object)
	{
		# check Empty Object
		if (empty($_object)) {
			return '';
		}

		# drop First Logical Operator
		unset($_object[0][0]);

		# check Data Type Flag
		$data_type_flag = false;
		foreach ($_object as $i => $list) {
			foreach ($list as $j => $value) {
				## for Not Data Type
				if ($value !== 'data-type') {
					continue;
				}

				## for Data Type
				# Notice: Processing must be not breaked because there ware multiple the value of “data-type”.
				if ($_object[$i][$j + 2] === true) {
					$data_type_flag = true;
				}
			}
		}

		# to Query String
		$query = '';
		foreach ($_object as $i => $list) {

			if ($list[2] === '{}') {
				$list[1] = $list[1] === 'self' ? '' : $list[1];
				$list[1] = '{' . $list[1];
				$list[2] = '';
				$list[3] = $list[3] . '}';
			}

			foreach ($list as $j => $value) {
				## for Stric Data Type
				if ($data_type_flag) {
					### for Value of Strint Type
					if ($j === 3) {
						if (is_string($value)) {
							$value = "'" . $value . "'";
						}
					}
				}

				## for Value of Boolean and Null Type
				if ($j === 3) {
					if ($value === true) {
						$value = 'true';
					} else if ($value === false) {
						$value = 'false';
					} else if ($value === null) {
						$value = 'null';
					}
				}

				### to Query String
				$query .= $value;
			}
		}

		# return
		return $query;
	}

}
