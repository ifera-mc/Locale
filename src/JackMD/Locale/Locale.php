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
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use function array_diff;
use function array_merge;
use function is_dir;
use function mkdir;
use function scandir;
use function str_replace;
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
	private static $fallbackIdentifier = "en_US";

	/**
	 * Format: [
	 *      lang_id => [
	 *          message_id => translation
	 *      ]
	 * ]
	 *
	 * @var array
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
		if(!isset(LocaleUtils::ALLOWED_IDENTIFIERS[$fallbackIdentifier])){
			throw new InvalidLocaleIdentifierException("Locale $fallbackIdentifier is invalid.");
		}

		if(!isset(Config::$formats["ini"])){
			Config::$formats["ini"] = Config::YAML;
		}

		self::$fallbackIdentifier = $fallbackIdentifier;
		$pluginFilePath = LocaleUtils::getFile($plugin) . "resources" . DIRECTORY_SEPARATOR . "lang";

		if($saveFilesToPath){
			$path = $plugin->getDataFolder() . "lang";

			if(!is_dir($path)){
				mkdir($path);
			}

			foreach(array_diff(scandir($pluginFilePath), ["..", "."]) as $langFile){
				$plugin->saveResource("lang" . DIRECTORY_SEPARATOR . $langFile);
			}
		}else{
			$path = $pluginFilePath;
		}

		foreach(array_diff(scandir($path), ["..", "."]) as $langFile){
			$langPath = $path . DIRECTORY_SEPARATOR . $langFile;
			$config = new Config($langPath, Config::DETECT, [], $loaded);

			if(!$loaded){
				$plugin->getLogger()->debug("$langFile is not supported.");

				continue;
			}

			$data = $config->getAll();

			if(!isset($data["identifier"])){
				throw new ConfigException("identifier key does not exist in $langPath.");
			}

			$identifier = (string) $data["identifier"];

			if(!isset(LocaleUtils::ALLOWED_IDENTIFIERS[$identifier])){
				$plugin->getLogger()->debug("$langFile with identifier: $identifier, is not supported.");

				continue;
			}

			self::loadTranslations($identifier, $data);
		}

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
	 * @param array  $translations
	 */
	private static function loadTranslations(string $langIdentifier, array $translations): void{
		unset($translations[$langIdentifier]);

		$current = self::$translations[$langIdentifier] ?? [];

		if(empty($current)){
			self::$translations[$langIdentifier] = $translations;
		}else{
			self::$translations[$langIdentifier] = array_merge($current, $translations);
		}
	}

	/**
	 * Returns the translated message based on the identifiers.
	 *
	 * @param string $langIdentifier
	 * @param string $messageIdentifier
	 * @param array  $toFind
	 * @param array  $toReplace
	 * @return string
	 */
	public static function getTranslation(string $langIdentifier, string $messageIdentifier, array $toFind = [], array $toReplace = []): string{
		$translated = self::$translations[$langIdentifier][$messageIdentifier] ?? (self::$translations[self::$fallbackIdentifier][$messageIdentifier] ?? $messageIdentifier);
		$translated = TextFormat::colorize($translated, "&");

		if(!empty($toFind) && !empty($toReplace)){
			$translated = str_replace($toFind, $toReplace, $translated);
		}

		return $translated;
	}

	/**
	 * Send the translated message to the player by automatically finding their locale.
	 *
	 * @param CommandSender $sender
	 * @param string        $messageIdentifier
	 * @param array         $toFind
	 * @param array         $toReplace
	 */
	public static function sendTranslatedMessage(CommandSender $sender, string $messageIdentifier, array $toFind = [], array $toReplace = []): void{
		$sender->sendMessage(self::getTranslation(self::getLocale($sender), $messageIdentifier, $toFind, $toReplace));
	}

	/**
	 * Returns the land_id player has. Retrieved from login packet.
	 *
	 * @param CommandSender $sender
	 * @return string
	 */
	private static function getLocale(CommandSender $sender): string{
		return ($sender instanceof Player) ? $sender->getLocale() : self::$fallbackIdentifier;
	}
}