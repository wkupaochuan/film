<?php
$config = array(
	'smarty_pc' => array(
		'theme' => 'default',
		'template_dir' => APPPATH . 'views',
		'compile_dir' => FCPATH . 'templates_c',
		'cache_dir' =>  FCPATH . 'cache',
		'template_ext' => '.tpl',
		'config_dir' => FCPATH . 'configs',
		'caching' => false,
		'lefttime' => 60,
	),
	'smarty_h5' => array(
		'theme' => 'default',
		'template_dir' => APPPATH . 'viewsh5',
		'compile_dir' => FCPATH . 'templates_m',
		'cache_dir' =>  FCPATH . 'cache',
		'template_ext' => '.tpl',
		'config_dir' => FCPATH . 'configs',
		'caching' => false,
		'lefttime' => 60,
	),
);