
<?php
F::redirect('auth', !Auth::user());
F::error('Forbidden', !Auth::userInRole('SUPER,ADMIN'));


// all locales
$all = I18N::localeAll();
F::error(I18N::error(), $all === false);
F::error('Please define more language by [I18N_LOCALE_ALL]', count($all) <= 1);


// retain selected (use first non-EN as default)
$_SESSION['i18nController__lang'] = $arguments['lang'] ?? $_SESSION['i18nController__lang'] ?? call_user_func(function() use ($all){
	foreach ( $all as $lang ) if ( $lang != 'en' ) return $lang;
});


// field name of another language
$fieldName = str_replace('-', '_', $_SESSION['i18nController__lang']);


// config
$scaffold = array(
	'beanType' => 'i18n',
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
		$fieldName => array('format' => 'textarea', 'label' => strtoupper($_SESSION['i18nController__lang']), 'style' => 'height: 5rem'),
	),
	'writeLog' => class_exists('Log'),
);


// run!
include F::appPath('controller/scaffold_controller.php');