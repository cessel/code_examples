<?php

/**
 * Problem: https://leetcode.com/problems/two-sum/description/
 * Submission: https://leetcode.com/problems/two-sum/submissions/936510510/
 */
class SolutionTwoSum {

	/**
	 * @param Integer[] $nums
	 * @param Integer   $target
	 *
	 * @return Integer[]
	 */
	function twoSum( array $nums, int $target ): array {

		foreach ( $nums as $i1 => $d1 ) {
			unset( $nums[ $i1 ] );
			$i2 = array_search( $target - $d1, $nums );
			if ( $i2 ) {
				return [ $i1, $i2 ];
			}
		}

		return [];

	}
}

/**
 * Problem: https://leetcode.com/problems/valid-parentheses/description/
 * Submission: https://leetcode.com/problems/valid-parentheses/submissions/961674921/
 */
class SolutionIsValidParentheses {

	/**
	 * @param String $s
	 *
	 * @return Boolean
	 */
	function isValid( string $s ): bool {

		$map = [
			'{' => '}',
			'[' => ']',
			'(' => ')',

		];

		$i          = 0;
		$char       = $s[ $i ];
		$close_flag = [];
		while ( $char != '' ) {
			if ( isset( $map[ $char ] ) ) {
				$close_flag[] = $map[ $char ];
			} else {
				$current = array_pop( $close_flag );
				if ( $current != $char ) {
					return false;
				}
			}

			$char = $s[ ++ $i ];
		}

		return empty( $close_flag );
	}
}