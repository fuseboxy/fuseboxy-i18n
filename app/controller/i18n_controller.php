<?php
F::redirect('auth', !Auth::user());
F::error('Forbidden', !Auth::userInRole('SUPER,ADMIN'));


// config
$scaffold = array(
	'beanType' => 'i18n',
	'editMode' => 'inline',
	'allowDelete' => Auth::userInRole('SUPER'),
	'layoutPath' => F::appPath('view/i18n/layout.php'),
	'listOrder' => 'ORDER BY alias',
	'listField' => array(
		'id' => '60',
		'alias' => '10%',
		'en' => '25%',
	),
	'fieldConfig' => array(
		'id',
		'alias',
		'en',
	),
	'writeLog' => class_exists('Log'),
);


// run!
include F::appPath('controller/scaffold_controller.php');