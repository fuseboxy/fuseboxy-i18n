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
			obtain all texts from database
			===> cache for this web request
		</description>
		<io>
			<in>
				<!-- cache -->
				<structure name="__i18n__" scope="$GLOBALS" optional="yes">
					<structure name="byAlias">
						<structure name="~alias~">
							<string name="en|~locale~" />
						</structure>
					</structure>
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
			$GLOBALS['__i18n__'] = array('byAlias' => [], 'byValue' => []);
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
				// map by alias (when alias specified)
				if ( !empty($item->alias) ) {
					$GLOBALS['__i18n__']['byAlias'][$item->alias] = array();
					foreach ( $locales as $locale ) {
						$fieldName = str_replace('-', '_', $locale);
						$GLOBALS['__i18n__']['byAlias'][$item->alias][$locale] = $item->{$fieldName};
					}
				}
				// map by value (when [en] not empty)
				if ( !empty($item->en) ) {
					$GLOBALS['__i18n__']['byValue'][$item->en] = array();
					foreach ( $locales as $locale ) if ( $locale != 'en' ) {
						$fieldName = str_replace('-', '_', $locale);
						$GLOBALS['__i18n__']['byValue'][$item->en][$locale] = $item->{$fieldName};
					}
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
				<array name="$arr" />
				<string name="$key" />
				<string name="$lang" optional="yes" />
			</in>
			<out>
				<mixed name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function convertArrayElement($arr, $key, $lang=null) {
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
				<!-- cache -->
				<structure name="~self::all()~">
					<structure name="byAlias">
						<structure name="~alias~">
							<string name="en|~locale~" />
						</structure>
					</structure>
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
		// ===> perform [tc2sc] when necessary
		$match = isset($cache['byValue'][$str]) ? $cache['byValue'][$str] : array();
		if ( !empty($match[$lang]) ) return $match[$lang];
		if ( $lang == 'zh-cn' and !empty($match['zh-hk']) ) return self::tc2sc($match['zh-hk']);
		// no match...
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