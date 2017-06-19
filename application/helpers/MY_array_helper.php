<?php

function f_array_append($desc, $append){
	if(empty($append)){
		return $desc;
	}

	foreach($append as $tmp){
		array_push($desc, $tmp);
	}

	return $desc;
}

function f_array_columns($source_array, $attrs){
    $ret = array();
    foreach($source_array as $row){
        foreach($row as $k => $v){
            if(!in_array($k, $attrs)){
                unset($row[$k]);
            }
        }
        $ret[] = $row;
    }

    return $ret;
}