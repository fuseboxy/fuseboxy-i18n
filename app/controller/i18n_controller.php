
<?php
F::redirect('auth', !Auth::user());
F::error('Forbidden', !Auth::userInRole('SUPER,ADMIN'));


// all locale
$all = I18N::localeAll();
F::error(I18N::error(), $all === false);


// field layout
// ===> [en] has one column separately
// ===> all others grouped in another column
$others = implode('|', array_filter(array_map(function($locale){
	return ( $locale != 'en' ) ? str_replace('-', '_', $locale) : false;
}, $all)));
$listField = array('en' => !empty($others) ? '35%' : '70%');
if ( !empty($others) ) $listField[$others] = '35%';


// field config per locale
$fieldConfig = array();
foreach ( $all as $locale ) {
	$fieldName = str_replace('-', '_', $locale);
	$fieldConfig[$fieldName] = array(
		'format' => 'textarea',
		'label' => strtoupper($locale),
		'placeholder' => ( count($all) > 2 ) ? strtoupper($locale) : false,
		'style' => 'height: 5rem',
	);
}


// config
$scaffold = array(
	'beanType' => 'i18n',
	'editMode' => 'inline',
	'allowDelete' => Auth::userInRole('SUPER'),
	'layoutPath' => (dirname(__DIR__).'/view/i18n/layout.php'),
	'listOrder' => 'ORDER BY CASE IFNULL(alias, "") WHEN "" THEN 1 ELSE 0 END, alias, en',
	'listField' => array_merge([
		'id' => '60',
		'alias' => '10%',
	], $listField),
	'fieldConfig' => array_merge([
		'id',
		'alias',
	], $fieldConfig),
	'writeLog' => class_exists('Log'),
);


// run!
include F::appPath('controller/scaffold_controller.php');