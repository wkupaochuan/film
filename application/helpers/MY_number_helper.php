<?php

/**
 * 获取最近的质数
 * @param $start
 * @return int|null
 */
function get_closest_prime($start){
	$ret = null;
	if($start < 2){
		return 2;
	}

	while(true){
		$start++;

		if($start%2 == 0){
			continue;
		}

		$sqrtn = intval(sqrt($start));
		$flag = true;
		for($i = 3; $i <= $sqrtn; $i++) {
			if ($start % $i == 0) {
				$flag = false;
				break;
			}
		}

		if($flag){
			$ret = $start;
			break;
		}
	}

	return $ret;
}
