<?php
declare(strict_types = 1);

namespace JackMD\Locale\Exceptions;

use Exception;
use Throwable;

class InvalidLocaleIdentifierException extends Exception{

	public function __construct($message = "", $code = 0, Throwable $previous = null){
		parent::__construct("Invalid Locale: " . $message, $code, $previous);
	}
}