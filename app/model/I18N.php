<?php /*
<fusedoc>
	<io>
		<in>
			<string name="I18N_LOCALE" />
			<list_or_array name="I18N_LOCALE_ALL" />
		</in>
		<out />
	</io>
</fusedoc>
*/
class I18N {


	// get (latest) error message
	private static $error;
	public static function error() { return self::$error; }




	/**
	<fusedoc>
		<description>
			obtain all locales (if specified)
		</description>
		<io>
			<in>
				<string name="I18N_LOCALE_ALL" optional="yes" />
			</in>
			<out>
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function all() {
		$result = defined('I18N_LOCALE_ALL') ? I18N_LOCALE_ALL : self::locale();
		$result = array_map('strtolower', explode(',', $result));
		if ( array_search('en', $result) === false ) array_unshift($result, 'en');
		return $result;
	}




	/**
	<fusedoc>
		<description>
			facade method to convert language
			===> last argument is always [locale]
		</description>
		<io>
			<in>
				<mixed name="$arg1" comments="object|array|string" />
				<mixed name="$arg2" optional="yes" comments="locale|objectProperty|arrayElement" />
				<mixed name="$arg3" optional="yes" comments="locale" />
			</in>
			<out>
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function convert($arg1, $arg2=null, $arg3=null) {
		if ( is_object($arg1) ) return self::convertObjectProperty($arg1, $arg2, $arg3);
		if ( is_array($arg1)  ) return self::convertArrayElement($arg1, $arg2, $arg3);
		if ( is_string($arg1) ) return self::convertStringValue($arg1, $arg2);
		return $arg1;
	}




	/**
	<fusedoc>
		<description>
			access array element with key of specified locale
			===> or convert value of array element to specified language
			===> use [en] as fallback when element empty or not exists
		</description>
		<io>
			<in>
				<!-- constant -->
				<string name="I18N_LOCALE" />
				<!-- parameter -->
				<array name="$arr" />
				<string name="$key" />
				<string name="$lang" default="~I18N_LOCALE~" />
			</in>
			<out>
				<mixed name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function convertArrayElement($arr, $key, $lang) {
		$lang = $lang ?: self::locale();
		// look for key with locale suffix (no suffix for [en])
		// ===> (e.g.) $product['title'] / $product['title__zh_hk'] / $product['title__zh_cn']
		for ( $i=1; $i<=3; $i++ ) {
			$key_lang = ( $lang == 'en' ) ? $key : ( $key.str_repeat('_', $i).str_replace('-', '_', strtolower($lang)) );
			if ( !empty($arr->{$key_lang}) ) return $arr->{$key_lang};
		}
		// otherwise, convert from [en] element
		if ( !empty($arr->{$key}) ) return self::convertStringValue($arr->{$key}, $lang);
		// not found...
		return '';
	}




	/**
	<fusedoc>
		<description>
			access object property of specified locale
			===> or convert value of property to specified language
			===> use [en] as fallback when property empty or not exists
		</description>
		<io>
			<in>
				<!-- constant -->
				<string name="I18N_LOCALE" />
				<!-- parameter -->
				<object name="$obj" />
				<string name="$prop" />
				<string name="$lang" default="~I18N_LOCALE~" />
			</in>
			<out>
				<mixed name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function convertObjectProperty($obj, $prop, $lang) {
		$lang = $lang ?: self::locale();
		// look for property name with locale suffix (no suffix for [en])
		// ===> (e.g.) $student->name / $student->name__zh_hk / $student->name__zh_cn
		for ( $i=1; $i<=3; $i++ ) {
			$prog_lang = ( $lang == 'en' ) ? $prop : ( $prop.str_repeat('_', $i).str_replace('-', '_', strtolower($lang)) );
			if ( !empty($obj->{$prog_lang}) ) return $obj->{$prog_lang};
		}
		// otherwise, convert from [en] property
		if ( !empty($obj->{$prop}) ) return self::convertStringValue($obj->{$prop}, $lang);
		// not found...
		return '';
	}




	/**
	<fusedoc>
		<description>
			convert input to specified language
		</description>
		<io>
			<in>
				<!-- constant -->
				<string name="I18N_LOCALE" />
				<!-- parameter -->
				<string name="$str" />
				<string name="$locale" default="~I18N_LOCALE~" />
			</in>
			<out>
				<mixed name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function convertStringValue($str, $lang) {
		$lang = $lang ?: self::locale();
		// do nothing when type not match
		if ( !is_string($str) ) return $str;
/*
// only load once for every request
$enumType = 'LOCALE ('.strtoupper($lang).')';
if ( !isset($GLOBALS[$enumType]) ) {
	$GLOBALS[$enumType] = Enum::getArray($enumType);
}
// auto-translate when necessary
if ( $lang == 'zh-cn' and empty($GLOBALS[$enumType][$str]) and !empty(locale__simpleValue($str, 'zh-hk')) ) {
	$GLOBALS[$enumType][$str] = LangConverter::tc2sc(locale__simpleValue($str, 'zh-hk'));
}
// done!
return isset($GLOBALS[$enumType][$str]) ? $GLOBALS[$enumType][$str] : $str;
*/
		// convert nothing...
		return $str;
	}




	/**
	<fusedoc>
		<description>
			obtain current locale (default [en] when not specified)
		</description>
		<io>
			<in>
				<string name="I18N_LOCALE" optional="yes" />
			</in>
			<out>
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function locale() {
		return define('I18N_LOCALE') ? strtolower(I18N_LOCALE) : 'en';
	}




	/**
	<fusedoc>
		<description>
			convert all traditional chinese characters into simplified chinese
		</description>
		<io>
			<in>
				<string name="$input" />
			</in>
			<out>
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function tc2sc($input) {
		$result = '';
		// load mapping
		$map = include 'tc2sc.php';
		// go through each character of input
		$length = mb_strlen($input);
		for ($i=0; $i<$length; $i++) {
			$char = mb_substr($input, $i, 1);
			$result .= isset($map[$char]) ? $map[$char] : $char;
		}
		// done!
		return $result;
	}


} // class