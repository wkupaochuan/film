<?php

function c_echo($str)  {
	echo $str . PHP_EOL;
}

function log_error($function, $line, $msg){
	echo 'user error :on function ' . $function . ' line ' . $line . PHP_EOL . $msg . PHP_EOL;
}