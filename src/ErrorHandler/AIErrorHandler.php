<?php


namespace ACSEO\AIErrorExplainedBundle\ErrorHandler;

use ACSEO\AIErrorExplainedBundle\ErrorHandler\ErrorHandler;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\ErrorHandler\DebugClassLoader;
use Symfony\Component\Runtime\Internal\BasicErrorHandler;

class AIErrorHandler
{
    public static function register(bool $debug): void
    {
        BasicErrorHandler::register($debug);

        if (class_exists(ErrorHandler::class)) {
            DebugClassLoader::enable();
            restore_error_handler();
            ErrorHandler::register(new ErrorHandler(new BufferingLogger(), $debug));
        }
    }
}
