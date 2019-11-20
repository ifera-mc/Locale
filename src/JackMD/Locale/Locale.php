<?php
declare(strict_types = 1);

namespace JackMD\Locale;

use JackMD\Locale\Exceptions\ConfigException;
use JackMD\Locale\Exceptions\InvalidLocaleIdentifierException;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use function array_diff;
use function array_merge;
use function in_array;
use function is_dir;
use function mkdir;
use function scandir;
use function str_replace;
use function strtolower;
use const DIRECTORY_SEPARATOR;

class Locale implements Listener{

	/*
	 * NOTE:
	 *
	 * BUNDLE THIS VIRION INTO THE PLUGIN WHEN USED IN PRODUCTION.
	 * NOT BUNDLING IT WILL LEAD TO UNWANTED SIDE AFFECTS.
	 *
	 * YOU HAVE BEEN WARNED!
	 */

	/**
	 * Accepted locales. Please PR in more if I forgot any.
	 * Data retrieved from vanilla resource pack.
	 *
	 * @var array
	 */
	private const ALLOWED_IDENTIFIERS = [
		"en_US", "en_GB", "de_DE", "es_ES", "es_MX", "fr_FR", "fr_CA", "it_IT", "ja_JP", "ko_KR", "pt_BR", "pt_PT", "ru_RU", "zh_CN", "zh_TW", "nl_NL", "bg_BG", "cs_CZ", "da_DK",
		"el_GR", "fi_FI", "hu_HU", "id_ID", "nb_NO", "pl_PL", "sk_SK", "sv_SE", "tr_TR", "uk_UA"
	];

	/** @var Plugin */
	private static $plugin;

	/** @var string */
	private static $fallbackIdentifier = "en_US";

	/**
	 * Format: [
	 *      lang_id => [
	 *          translations
	 *      ]
	 * ]
	 *
	 * @var array
	 */
	private static $translations = [];

	/**
	 * An array consisting of players lowercase name => land_id
	 *
	 * @var array
	 */
	private static $players = [];

	/**
	 * Locale constructor.
	 *
	 * Yea.. You construct using init.
	 *
	 * @see Locale::init()
	 */
	private function __construct(){
	}

	/**
	 * This will load all the **valid** lang config files in the lang folder in plugins data path.
	 *
	 * @param Plugin $plugin
	 * @param string $fallbackIdentifier - The identifier which is to be used for fallback language for player. Default to en_US.
	 */
	public static function init(Plugin $plugin, string $fallbackIdentifier = "en_US"): void{
		self::$plugin = $plugin;

		if(!in_array($fallbackIdentifier, self::ALLOWED_IDENTIFIERS)){
			throw new InvalidLocaleIdentifierException("Locale $fallbackIdentifier is invalid.");
		}

		self::$fallbackIdentifier = $fallbackIdentifier;

		$path = $plugin->getDataFolder() . "lang";

		if(!is_dir($path)){
			mkdir($path);
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
				throw new ConfigException("identifier key does not exist in " . $path . $langFile);
			}

			self::loadTranslations((string) $data["identifier"], $data);
		}

		if(!isset(self::$translations[$fallbackIdentifier])){
			throw new ConfigException("$fallbackIdentifier does not exist in the langFiles.");
		}

		$plugin->getServer()->getPluginManager()->registerEvents(new self(), $plugin);
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

		$translated = str_replace("&", TextFormat::ESCAPE, $translated);
		$translated = str_replace($toFind, $toReplace, $translated);

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
		$sender->sendMessage(self::getTranslation(self::getPlayerLocale($sender), $messageIdentifier, $toFind, $toReplace));
	}

	/**
	 * Returns the land_id player has. Retrieved from login packet.
	 *
	 * @param CommandSender $sender
	 * @return string
	 * @internal
	 */
	private static function getPlayerLocale(CommandSender $sender): string{
		$langIdentifier = self::$fallbackIdentifier;

		if($sender instanceof Player){
			$playerName = strtolower($sender->getName());

			return self::$players[$playerName] ?? $langIdentifier;
		}

		return $langIdentifier;
	}


	# LISTENER STUFF IN HERE

	/**
	 * @param DataPacketReceiveEvent $event
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		$packet = $event->getPacket();

		if($packet instanceof LoginPacket){
			$playerName = strtolower($packet->username);

			if($playerName === ""){
				return;
			}

			self::$players[$playerName] = $packet->locale;
		}
	}

	/**
	 * @param PlayerQuitEvent $event
	 */
	public function onQuit(PlayerQuitEvent $event){
		unset(self::$players[strtolower($event->getPlayer()->getName())]);
	}
}