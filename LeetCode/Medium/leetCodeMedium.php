<?php
/**
 * Problem: https://leetcode.com/problems/longest-substring-without-repeating-characters/description/
 * Submission: https://leetcode.com/problems/longest-substring-without-repeating-characters/submissions/961626790/
 */
class SolutionLengthOfLongestSubstring {

	/**
	 * @param String $s
	 *
	 * @return Integer
	 */
	function lengthOfLongestSubstring( string $s ): int {

		if ( $s == '' ) {
			return 0;
		}

		$i      = 0;
		$temp   = '';
		$substr = $s[0];
		$l      = 0;
		$result = 0;

		while ( $substr !== "" ) {

			if ( ! str_contains( $temp, $substr ) ) {
				$temp .= $substr;
				$i ++;
				$result_new = $i - $l;
				$result     = ( $result_new > $result ) ? $result_new : $result;
			} else {
				$l ++;
				$temp = substr( $temp, 1 );
			}

			$substr = $s[ $i ];
		}

		return $result;
	}
}


/**
 * Problem: https://leetcode.com/problems/longest-substring-without-repeating-characters/
 * Submission alternate: * https://leetcode.com/problems/longest-substring-without-repeating-characters/submissions/961480008/
 */

class SolutionAlternateLengthOfLongestSubstring {

	protected array $string_array = [];

	/**
	 * @param String $s
	 *
	 * @return Integer
	 */
	function lengthOfLongestSubstring( string $s ): int {

		if ( empty( $s ) ) {
			return 0;
		}
		$chars_generator = $this->_splitString( $s );
		$result          = 0;
		$temp = [];
		while ( true ) {
			$current_letter = $chars_generator->current();
			if ( ! isset( $current_letter ) ) {
				break;
			}
			$temp[] = $current_letter;
			$chars_generator->next();
			$next_letter = $chars_generator->current();

			foreach ( $temp as $index => $temp_letter ) {
				if ( $temp_letter === $next_letter ) {
					$result_new = count( $temp );
					$result     = ( $result_new > $result ) ? $result_new : $result;
					$temp       = array_slice( $temp, $index + 1 );
				}
			}
		}

		$result_new = count( $temp );

		return ( $result_new > $result ) ? $result_new : $result;
	}

	private function _splitString( $s ): Generator {
		foreach ( mb_str_split( $s ) as $char ) {
			yield $char;
		}
	}

}
