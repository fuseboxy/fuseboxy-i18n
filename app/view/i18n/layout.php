<?php
// breadcrumb
$arguments['breadcrumb'] = array('Multi-Language');


// tab config (show all languages except EN)
$tabLayout = array(
	'style' => 'tab',
	'position' => 'left',
	'header' => 'Multi-Language',
	'nav' => array_map(function($lang){
		return ( $lang == 'en' ) ? false : array(
			'name'   => strtoupper($lang),
			'url'    => F::url(F::command().'&lang='.$lang),
			'active' => ( $_SESSION['i18nController__lang'] == $lang ),
		);
	}, I18N::localeAll()),
);


// display
ob_start();
include F::appPath('view/tab/layout.php');
$layout['content'] = ob_get_clean();


// global layout
$layout['width'] = 'full';
include F::appPath('view/global/layout.php');