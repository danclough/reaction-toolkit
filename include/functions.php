<?php
require_once('config/config.php');

/**
 * Calculate median of all values in an array.
 * @param	array	$array	A 1D array of numbers
 * @return	number		Median of all array values
 */
function calculate_median($array) {
	sort($array);
	$count = count($array); //total numbers in array
	if($count > 1) {
		$middleval = floor(($count-1)/2); // find the middle value, or the lowest middle value
		if($count % 2) { // odd number, middle is the median
			$median = $array[$middleval];
		} else { // even number, calculate avg of 2 medians
			$low = $array[$middleval];
			$high = $array[$middleval+1];
			$median = (($low+$high)/2);
		}
	}
	elseif ($count >0) {
		return $array[0];
	}
	else {
		$median = 0;
	}
	return $median;
}
