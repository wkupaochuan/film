<?php

function f_array_append($desc, $append){
	if(empty($append)){
		return;
	}

	foreach($append as $tmp){
		array_push($desc, $tmp);
	}

	return $desc;
}