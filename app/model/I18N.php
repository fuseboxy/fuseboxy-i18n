<?php
class I18N {


	// default comparison mode
	// ===> CS : case-sensitive
	// ===> CI : case-insensitive
	private static $mode = 'CI';
	// current locale (optional)
	private static $locale;
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
					<structure name="CS|CI">
						<structure name="~en_value~">
							<string name="~locale~" />
						</structure>
					</structure>
				</structure>
			</in>
			<out>
				<structure name="~return~">
					<structure name="CS|CI">
						<structure name="~en_value~">
							<string name="~locale~" />
						</structure>
					</structure>
				</structure>
			</out>
		</io>
	</fusedoc>
	*/
	public static function all() {
		// build cache (when necessary)
		if ( !isset($GLOBALS['__i18n__']) ) {
			$GLOBALS['__i18n__'] = array('CS' => [], 'CI' => []);
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
				// ===> always supposed [en] not empty
				$GLOBALS['__i18n__']['CS'][$item->en] = array();
				$GLOBALS['__i18n__']['CI'][strtolower($item->en)] = array();
				foreach ( $locales as $locale ) {
					$fieldName = str_replace('-', '_', $locale);
					// case-sensitive
					$GLOBALS['__i18n__']['CS'][$item->en][$locale] = $item->{$fieldName};
					// case-insensitive (by reference to save memory)
					$GLOBALS['__i18n__']['CI'][strtolower($item->en)][$locale] = &$GLOBALS['__i18n__']['CS'][$item->en][$locale];
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
				<!-- setting -->
				<string name="$mode" scope="self" comments="CS|CI" />
				<!-- cache -->
				<structure name="~self::all()~">
					<structure name="CS|CI">
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
		// ===> apply comparison mode
		$mode = self::$mode;
		$strKey = ( $mode == 'CI' ) ? strtolower($str) : $str;
		if ( isset($cache[$mode][$strKey]) ) $match = $cache[$mode][$strKey];
		// return right away if already has match (lucky~)
		if ( !empty($match[$lang]) ) return $match[$lang];
		// if no match & language is simplified chinese
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
				<list name="$langList" delim=",">
					<string name="~locale~" />
				</list>
			</in>
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function is($langList) {
		if ( !is_array($langList) ) $langList = explode(',', $langList);
		return in_array(self::locale(), $langList);
	}




	/**
	<fusedoc>
		<description>
			obtain current locale
			===> use property setting (self::$locale)
			===> otherwise, use env setting (fusebox-config or constant)
			===> otherwise, default using [en]
		</description>
		<io>
			<in>
				<string name="$locale" scope="self" optional="yes" />
				<structure name="i18n" scope="$fusebox->config">
					<string name="current" optional="yes" />
				</structure>
				<string name="FUSEBOXY_I18N_LOCALE" optional="yes" />
			</in>
			<out>
				<string name="~return~" default="en" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function locale() {
		// adhoc setting
		if ( !empty(self::$locale) ) return self::$locale;
		// env setting
		if ( class_exists('F') ) return F::config('i18n')['current'] ?? null;
		if ( defined('FUSEBOXY_I18N_LOCALE') ) return FUSEBOXY_I18N_LOCALE;
		// default
		return 'en';
	}




	/**
	<fusedoc>
		<description>
			obtain all locales (if specified)
		</description>
		<io>
			<in>
				<structure name="i18n" scope="$fuesbox->config">
					<list_or_array name="locales|all" delim="," optional="yes" />
				</structure>
				<list_or_array name="FUSEBOXY_I18N_LOCALE_ALL" delim="," optional="yes" />
			</in>
			<out>
				<array name="~return~">
					<string name="~locale~" />
				</array>
			</out>
		</io>
	</fusedoc>
	*/
	public static function localeAll() {
		// env setting or default
		if ( class_exists('F') ) $locales = F::config('i18n')['locales'] ?? F::config('i18n')['all'] ?? null;
		elseif ( defined('FUSEBOXY_I18N_LOCALE_ALL') ) $locales = FUSEBOXY_I18N_LOCALE_ALL;
		else $locales = self::locale();
		// convert into array
		if ( !is_array($locales) ) $locales = array_filter(explode(',', $locales));
		$locales = array_map('strtolower', $locales);
		// force english available
		if ( array_search('en', $locales) === false ) array_unshift($locales, 'en');
		// done!
		return $locales;
	}




	/**
	<fusedoc>
		<description>
			get or set comparison mode
			===> CS : case-sensitive
			===> CI : case-insensitive (default)
		</description>
		<io>
			<in>
				<string name="$input" optional="yes" comments="CS|CI" />
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
	public static function mode($input=null) {
		// getter
		if ( empty($input) ) return self::$mode;
		// setter
		self::$mode = strtoupper($input);
		return true;
	}




	/**
	<fusedoc>
		<description>
			clear locale set locally (and fallback to use env setting)
		</description>
		<io>
			<in />
			<out>
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function reset() {
		return self::set(false);
	}




	/**
	<fusedoc>
		<description>
			convert string from simplified chinese to traditional chinese
		</description>
		<io>
			<in>
				<string name="$str" />
			</in>
			<out>
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function sc2tc($str) {
		$result = '';
		// load mapping
		$map = array_flip( include 'tc2sc.php' );
		// go through each character of input
		$length = mb_strlen($str);
		for ($i=0; $i<$length; $i++) {
			$char = mb_substr($str, $i, 1);
			$result .= isset($map[$char]) ? $map[$char] : $char;
		}
		// done!
		return $result;
	}




	/**
	<fusedoc>
		<description>
			set locale (to override env setting)
			===> set to null (or false) to clear
		</description>
		<io>
			<in>
				<string name="$lang" />
			</in>
			<out>
				<!-- property -->
				<string name="$locale" scope="self" />
				<!-- return value -->
				<boolean name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function set($lang) {
		self::$locale = ( $lang === false ) ? null : $lang;
		return true;
	}




	/**
	<fusedoc>
		<description>
			convert string from traditional chinese to simplified chinese
		</description>
		<io>
			<in>
				<string name="$str" />
			</in>
			<out>
				<string name="~return~" />
			</out>
		</io>
	</fusedoc>
	*/
	public static function tc2sc($str) {
		$result = '';
		// load mapping
		$map = include 'tc2sc.php';
		// go through each character of input
		$length = mb_strlen($str);
		for ($i=0; $i<$length; $i++) {
			$char = mb_substr($str, $i, 1);
			$result .= isset($map[$char]) ? $map[$char] : $char;
		}
		// done!
		return $result;
	}


} // class


// alias method
function __(...$args) { return I18N::convert(...$args); }