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


	// default comparison mode
	// ===> cs : case-sensitive
	// ===> ci : case-insensitive
	private static $mode = 'ci';
	// get (latest) error message
	private static $error;
	public static function error() { return self::$error; }




	/**
	<fusedoc>
		<description>
			obtain all texts from database
			===> cache for this web request
		</description>
		<io>
			<in>
				<!-- cache -->
				<structure name="__i18n__" scope="$GLOBALS" optional="yes">
					<structure name="byValue">
						<structure name="~en_value~">
							<string name="~locale~" />
						</structure>
					</structure>
				</structure>
			</in>
			<out>
			</out>
		</io>
	</fusedoc>
	*/
	public static function all() {
		// build cache (when necessary)
		if ( !isset($GLOBALS['__i18n__']) ) {
			$GLOBALS['__i18n__'] = array('byValue' => []);
			// get all data
			$data = ORM::get('i18n', 'disabled = 0');
			if ( $data === false ) {
				self::$error = ORM::error();
				return false;
			}
			// move to cache
			foreach ( $data as $item ) {
				// get all locales
				$locales = self::localeAll();
				if ( $locales === false ) return false;
				// map by value
				$GLOBALS['__i18n__']['byValue'][$item->en] = array();
				foreach ( $locales as $locale ) {
					$fieldName = str_replace('-', '_', $locale);
					// map by value
					// ===> always supposed [en] not empty
					$GLOBALS['__i18n__']['byValue'][$item->en][$locale] = $item->{$fieldName};
				}
			} // foreach-data
		} // if-isset
		// done!
		return $GLOBALS['__i18n__'];
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
		if ( is_array($arg1)  ) return self::convertObjectProperty((object) $arg1, $arg2, $arg3);
		if ( is_object($arg1) ) return self::convertObjectProperty($arg1, $arg2, $arg3);
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
				<string name="$prop" />
				<string name="$lang" optional="yes" />
			</in>
			<out>
				<mixed name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function convertObjectProperty($obj, $prop, $lang=null) {
		$lang = $lang ?: self::locale();
		// look for property with locale-suffix (no suffix for [en])
		// ===> (e.g.) $student->name / $student->name__zh_hk / $student->name__zh_cn
		// ===> return from non-empty property with correct locale-suffix
		for ( $i=1; $i<=3; $i++ ) {
			$prop_lang = ( $lang == 'en' ) ? $prop : ( $prop.str_repeat('_', $i).str_replace('-', '_', strtolower($lang)) );
			if ( !empty($obj->{$prop_lang}) ) return $obj->{$prop_lang};
		}
		// if locale is [zh-cn] & empty or not-found
		// ===> look for traditional chinese field
		// ===> perform [tc2sc] accordingly
		if ( $lang == 'zh-cn' ) {
			for ( $i=1; $i<=3; $i++ ) {
				$prop_hk = $prop.str_repeat('_', $i).'zh_hk';
				$prop_tw = $prop.str_repeat('_', $i).'zh_tw';
				if ( !empty($obj->{$prop_hk}) ) return self::tc2sc($obj->$prop_hk);
				if ( !empty($obj->{$prop_tw}) ) return self::tc2sc($obj->$prop_tw);
			}
		}
		// if no key with locale-suffix
		// ===> simply convert from [en] element
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
				<!-- cache -->
				<structure name="~self::all()~">
					<structure name="byValue">
						<structure name="~en_value~">
							<string name="~locale~" />
						</structure>
					</structure>
				</structure>
				<!-- parameter -->
				<string name="$str" comments="input with [en] language" />
				<string name="$lang" optional="yes" />
			</in>
			<out>
				<mixed name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function convertStringValue($str, $lang=null) {
		$lang = $lang ?: self::locale();
		// do nothing 
		// ===> when type not match
		// ===> when convert to [en]
		if ( !is_string($str) or $lang == 'en' ) return $str;
		// load cache
		$cache = self::all();
		if ( $cache === false ) return false;
		// check if any match
		// ===> return specific language
		$match = isset($cache['byValue'][$str]) ? $cache['byValue'][$str] : array();
		if ( !empty($match[$lang]) ) return $match[$lang];
		// if no match & language simplified chinese
		// ===> perform [tc2sc] from traditional chinese (if any)
		if ( $lang == 'zh-cn' and !empty($match['zh-hk']) ) return self::tc2sc($match['zh-hk']);
		if ( $lang == 'zh-cn' and !empty($match['zh-tw']) ) return self::tc2sc($match['zh-tw']);
		// no match...
		return $str;
	}




	/**
	<fusedoc>
		<description>
			shorthand to check current locale
		</description>
		<io>
			<in>
				<string name="$lang" />
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function is($lang) {
		return ( self::locale() == $lang );
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
		return defined('I18N_LOCALE') ? strtolower(I18N_LOCALE) : 'en';
	}




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
	public static function localeAll() {
		$result = defined('I18N_LOCALE_ALL') ? I18N_LOCALE_ALL : self::locale();
		$result = array_map('strtolower', is_array($result) ? $result : explode(',', $result));
		if ( array_search('en', $result) === false ) array_unshift($result, 'en');
		return array_filter($result);
	}




	/**
	<fusedoc>
		<description>
			get or set comparison mode
			===> cs : case-sensitive
			===> ci : case-insensitive (default)
		</description>
		<io>
			<in>
				<string name="$val" optional="yes" comments="cs|ci" />
			</in>
			<out>
				<!-- getter -->
				<string name="~return~" optional="yes" />
				<!-- setter -->
				<boolean name="~return~" optional="yes" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function mode($val=null) {
		// getter
		if ( empty($mode) ) return self::$mode;
		// setter
		self::$mode = strtolower($val);
		return true;
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




// alias method
function __($arg1, $arg2=null, $arg3=null) {
	return I18N::convert($arg1, $arg2, $arg3);
}