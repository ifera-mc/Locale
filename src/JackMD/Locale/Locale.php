<?php
declare(strict_types = 1);

/**
 * Locale
 * 
 * Locale, a virion for PocketMine-MP.
 * Copyright (c) 2019 JackMD  < https://github.com/JackMD/Locale >
 *
 * Website: https://jacktaylor.cc
 * Discord: JackMD#3717
 * Twitter: JackMTaylor_
 *
 * This software is distributed under "GNU General Public License v3.0".
 * This license allows you to use it and/or modify it but you are not at
 * all allowed to sell this plugin at any cost. If found doing so the
 * necessary action required would be taken.
 *
 * Locale is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License v3.0 for more details.
 *
 * You should have received a copy of the GNU General Public License v3.0
 * along with this program. If not, see
 * <https://opensource.org/licenses/GPL-3.0>.
 * -----------------------------------------------------------------------
 */

namespace JackMD\Locale;

use JackMD\Locale\Exceptions\ConfigException;
use JackMD\Locale\Exceptions\InvalidLocaleIdentifierException;
use JackMD\Locale\Utils\LocaleUtils;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use function array_diff;
use function is_dir;
use function is_null;
use function mkdir;
use function scandir;
use function strtr;
use const DIRECTORY_SEPARATOR;

class Locale{

	/**
	 * NOTE:
	 *
	 * BUNDLE THIS VIRION INTO THE PLUGIN WHEN USED IN PRODUCTION.
	 * NOT BUNDLING IT WILL LEAD TO UNWANTED SIDE AFFECTS.
	 *
	 * YOU HAVE BEEN WARNED!
	 */

	/** @var string */
	public static $fallbackIdentifier = "en_US";

	/**
	 * Format: [
	 *      lang_id => Config
	 * ]
	 *
	 * @var Config[]
	 */
	private static $translations = [];

	/**
	 * Locale constructor.
	 *
	 * @see Locale::init()
	 */
	private function __construct(){
	}

	/**
	 * This will load all the **valid** lang config files in the lang folder in plugins data path.
	 *
	 * @param PluginBase $plugin
	 * @param string     $fallbackIdentifier - The identifier which is to be used for fallback language for player. Default to en_US.
	 * @param bool       $saveFilesToPath - Save the lang files to the plugin data folder?
	 *
	 * @throws InvalidLocaleIdentifierException
	 * @throws \ReflectionException
	 */
	public static function init(PluginBase $plugin, string $fallbackIdentifier = "en_US", bool $saveFilesToPath = true): void{
		// check if the $fallbackIdentifier is a valid one. we only want the ones shipped by mojang to load.
		if(!isset(LocaleUtils::ALLOWED_IDENTIFIERS[$fallbackIdentifier])){
			throw new InvalidLocaleIdentifierException("Locale $fallbackIdentifier is invalid.");
		}

		// simple fix for ini configs.
		if(!isset(Config::$formats["ini"])){
			Config::$formats["ini"] = Config::YAML;
		}

		self::$fallbackIdentifier = $fallbackIdentifier;

		// get the path to the resources folder inside the plugins folder. not the plugin_data one.
		$pluginFilePath = LocaleUtils::getFile($plugin) . "resources" . DIRECTORY_SEPARATOR . "lang";

		// if files are to be saved then use the plugin_data path else use the plugins path.
		if($saveFilesToPath){
			$path = $plugin->getDataFolder() . "lang";

			if(!is_dir($path)){
				mkdir($path);
			}

			// save all the lang files inside the resources folder in plugins path.
			foreach(array_diff(scandir($pluginFilePath), ["..", "."]) as $langFile){
				$plugin->saveResource("lang" . DIRECTORY_SEPARATOR . $langFile);
			}
		}else{
			$path = $pluginFilePath;
		}

		// try loading all the lang files
		foreach(array_diff(scandir($path), ["..", "."]) as $langFile){
			$langPath = $path . DIRECTORY_SEPARATOR . $langFile;
			$config = new Config($langPath, Config::DETECT, [], $loaded);

			// if the lang file could not be loaded then skip it.
			if(!$loaded){
				$plugin->getLogger()->debug("$langFile is not supported.");

				continue;
			}

			if(!$config->exists("identifier")){
				throw new ConfigException("identifier key does not exist in $langPath.");
			}

			$identifier = (string) $config->get("identifier");

			// identifier in the lang file should be one from the allowed identifiers.
			if(!isset(LocaleUtils::ALLOWED_IDENTIFIERS[$identifier])){
				$plugin->getLogger()->debug("$langFile with identifier: $identifier, is not supported.");

				continue;
			}

			// everything seems ok. load the translations.
			self::loadTranslations($identifier, $config);
		}

		// if the files are not to be saved from the plugins resource folder then remove the lang folder in plugin_data.
		if(!$saveFilesToPath){
			LocaleUtils::deleteTree($plugin->getDataFolder() . "lang");
		}

		if(!isset(self::$translations[$fallbackIdentifier])){
			throw new ConfigException("$fallbackIdentifier does not exist in the langFiles.");
		}
	}

	/**
	 * Loads all the translations provided.
	 *
	 * @param string $langIdentifier
	 * @param Config $config
	 */
	private static function loadTranslations(string $langIdentifier, Config $config): void{
		self::$translations[$langIdentifier] = $config;
	}

	/**
	 * Returns the translated message based on the identifiers.
	 *
	 * @param string $langIdentifier
	 * @param string $messageIdentifier
	 * @param array  $args
	 * @return string
	 */
	public static function getTranslation(string $langIdentifier, string $messageIdentifier, array $args = []): string{
		$config = self::$translations[$langIdentifier] ?? (self::$translations[self::$fallbackIdentifier] ?? null);

		if(is_null($config)){
			throw new ConfigException("Required lang id config and fallback config not found.");
		}

		$translated = $config->getNested($messageIdentifier, $messageIdentifier);
		$translated = TextFormat::colorize($translated, "&");

		if(!empty($args)){
			$translated = strtr($translated, $args);
		}

		return $translated;
	}

	/**
	 * Send the translated message to the player by automatically finding their locale.
	 *
	 * @param CommandSender $sender
	 * @param string        $messageIdentifier
	 * @param array         $args
	 */
	public static function sendTranslatedMessage(CommandSender $sender, string $messageIdentifier, array $args = []): void{
		$sender->sendMessage(self::getTranslation(LocaleUtils::getLocale($sender), $messageIdentifier, $args));
	}
}