<?php
F::redirect('auth&callback='.base64_encode($_SERVER['REQUEST_URI']), !Auth::user());
F::error('Forbidden', !Auth::userInRole('SUPER,ADMIN'));


// all locales
$all = I18N::localeAll();
F::error(I18N::error(), $all === false);
F::error('Please define more language by [i18n] fuseboxy config', count($all) <= 1);


// default language (use first non-EN as default)
$arguments['lang'] = $arguments['lang'] ?? call_user_func(function() use ($all){
	foreach ( $all as $lang ) if ( $lang != 'en' ) return $lang;
});


// field name of another language
$fieldName = str_replace('-', '_', $arguments['lang']);


// config
$scaffold = array_merge([
	'beanType' => 'i18n',
	'retainParam' => array('lang' => $arguments['lang']),
	'editMode' => 'inline',
	'allowDelete' => Auth::userInRole('SUPER'),
	'layoutPath' => (dirname(__DIR__).'/view/i18n/layout.php'),
	'listOrder' => 'ORDER BY en',
	'listField' => array(
		'id' => '60',
		'en' => '40%',
		$fieldName => '40%',
	),
	'fieldConfig' => array(
		'en' => array('format' => 'textarea', 'label' => 'EN', 'style' => 'height: 5rem'),
		$fieldName => array('format' => 'textarea', 'label' => strtoupper($arguments['lang']), 'style' => 'height: 5rem'),
	),
	'writeLog' => class_exists('Log'),
], $i18nScaffold ?? $i18n_scaffold ?? []);


// run!
include F::appPath('controller/scaffold_controller.php');