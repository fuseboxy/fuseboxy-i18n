<?php
// config
$tabLayout = array(
	'style' => 'tab',
	'position' => 'left',
	'header' => 'Multi-Language',
	'nav' => array([ 'name' => 'All', 'url' => $fusebox->controller, 'active' => true ]),
);


// tab layout
ob_start();
include F::appPath('view/tab/layout.php');
$layout['content'] = ob_get_clean();


// global layout
$layout['width'] = 'full';
include F::appPath('view/global/layout.php');