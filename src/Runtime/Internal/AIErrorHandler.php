<?php


namespace ACSEO\AIErrorExplainedBundle\Runtime\Internal;

use ACSEO\AIErrorExplainedBundle\ErrorHandler\ErrorHandler;
use ACSEO\AIErrorExplainedBundle\Solution\OpenAiSolution;
use Symfony\Component\Runtime\Internal\BasicErrorHandler;
use Symfony\Component\ErrorHandler\BufferingLogger;
use Symfony\Component\ErrorHandler\DebugClassLoader;

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
