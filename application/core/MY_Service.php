<?php
class MY_Service
{
    public function __construct()
    {
    }

    function __get($key)
    {
        $CI = & get_instance();
        return $CI->$key;
    }
}
 
