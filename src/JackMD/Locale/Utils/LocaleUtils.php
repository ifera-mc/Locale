<?php
declare(strict_types = 1);

namespace JackMD\Locale\Utils;

use pocketmine\plugin\PluginBase;
use ReflectionProperty;
use function file_exists;
use function is_file;
use function is_link;
use function rmdir;
use function unlink;

class LocaleUtils{

	/**
	 * Accepted locales. Please PR in more if I forgot any.
	 * Data retrieved from vanilla resource pack.
	 *
	 * @var array
	 */
	public const ALLOWED_IDENTIFIERS = [
		"en_US" => "English (US)",
		"en_GB" => "English (UK)",
		"de_DE" => "Deutsch (Deutschland)",
		"es_ES" => "Español (España)",
		"es_MX" => "Español (México)",
		"fr_FR" => "Français (France)",
		"fr_CA" => "Français (Canada)",
		"it_IT" => "Italiano (Italia)",
		"ja_JP" => "日本語 (日本)",
		"ko_KR" => "한국어 (대한민국)",
		"pt_BR" => "Português (Brasil)",
		"pt_PT" => "Português (Portugal)",
		"ru_RU" => "Русский (Россия)",
		"zh_CN" => "简体中文 (中国)",
		"zh_TW" => "繁體中文 (台灣)",
		"nl_NL" => "Nederlands (Nederland)",
		"bg_BG" => "Български (BG)",
		"cs_CZ" => "Čeština (Česká republika)",
		"da_DK" => "Dansk (DA)",
		"el_GR" => "Ελληνικά (Ελλάδα)",
		"fi_FI" => "Suomi (Suomi)",
		"hu_HU" => "Magyar (HU)",
		"id_ID" => "Bahasa Indonesia (Indonesia)",
		"nb_NO" => "Norsk bokmål (Norge)",
		"pl_PL" => "Polski (PL)",
		"sk_SK" => "Slovensky (SK)",
		"sv_SE" => "Svenska (Sverige)",
		"tr_TR" => "Türkçe (Türkiye)",
		"uk_UA" => "Українська (Україна)"
	];

	/**
	 * @param PluginBase $pluginBase
	 * @return string
	 * @throws \ReflectionException
	 */
	public static function getFile(PluginBase $pluginBase): string{
		$pathReflection = new ReflectionProperty(PluginBase::class, 'file');
		$pathReflection->setAccessible(true);

		return $pathReflection->getValue($pluginBase);
	}

	/**
	 * Recursively deletes a directory tree.
	 *
	 * @param string $folder         The directory path.
	 * @param bool   $keepRootFolder Whether to keep the top-level folder.
	 *
	 * @return bool TRUE on success, otherwise FALSE.
	 */
	public static function deleteTree($folder, $keepRootFolder = false){
		if(empty($folder) || !file_exists($folder)){
			return true;
		}elseif(is_file($folder) || is_link($folder)){
			return @unlink($folder);
		}

		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

		foreach($files as $fileinfo){
			$action = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
			if(!@$action($fileinfo->getRealPath())){
				return false;
			}
		}

		return (!$keepRootFolder ? @rmdir($folder) : true);
	}
}