<?php /*
<fusedoc>
	<io>
		<in>
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
	public static function convertObjectValue($obj, $prop, $lang) {
		$lang = define('I18N_LOCALE') ? I18N_LOCALE : 'en';
		// look for property name with locale suffix
		// ===> no suffix for [en] locale
		$prog_lang = ( $lang == 'en' ) ? $prop : ( $prop.'_'.str_replace('-', '_', $lang) );
		if ( !empty($obj->{$prog_lang}) ) return $obj->{$prog_lang};
		// otherwise, convert from [en] property
		if ( !empty($obj->{$prop}) ) return self::convertStringValue($obj->{$prop}, $lang);
		// not found...
		return '';
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
	public static function convertArrayValue($arr, $key, $lang) {
		$lang = define('I18N_LOCALE') ? I18N_LOCALE : 'en';
		// look for key with locale suffix
		// ===> no suffix for [en] locale
		$key_lang = ( $lang == 'en' ) ? $key : ( $key.'_'.str_replace('-', '_', $lang) );
		if ( !empty($arr->{$key_lang}) ) return $arr->{$key_lang};
		// otherwise, convert from [en] element
		if ( !empty($arr->{$key}) ) return self::convertStringValue($arr->{$key}, $lang);
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
	public static function convertString($str, $locale) {
		$locale = define('I18N_LOCALE') ? I18N_LOCALE : 'en';
	}


} // class