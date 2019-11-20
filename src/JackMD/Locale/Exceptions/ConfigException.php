<?php
declare(strict_types = 1);

namespace JackMD\Locale\Exceptions;

use InvalidArgumentException;
use Throwable;

class ConfigException extends InvalidArgumentException{

	/**
	 * ConfigException constructor.
	 *
	 * @param string         $message
	 * @param int            $code
	 * @param Throwable|null $previous
	 */
	public function __construct($message = "", $code = 0, Throwable $previous = null){
		parent::__construct("Config Problem: " . $message, $code, $previous);
	}
}