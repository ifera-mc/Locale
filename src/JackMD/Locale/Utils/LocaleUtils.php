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