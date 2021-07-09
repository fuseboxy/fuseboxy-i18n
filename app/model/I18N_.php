<?php /*
<fusedoc>
	<io>
		<in>
			<string name="I18N_LOCALE" comments="en|zh-hk|zh-cn|.." />
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
				<object name="$obj" />
				<string name="$property" />
				<string name="$locale" />
			</in>
			<out>
				<mixed name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function convertObjectValue($object, $property, $locale) {

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
				<array name="$array" />
				<string name="$key" />
				<string name="$locale" />
			</in>
			<out>
				<mixed name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function convertArrayValue($array, $key, $locale) {

	}




	/**
	<fusedoc>
		<description>
			convert input to specified language
		</description>
		<io>
			<in>
				<string name="$string" />
				<string name="$locale" />
			</in>
			<out>
				<mixed name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function convertString($string, $locale) {

	}


} // class