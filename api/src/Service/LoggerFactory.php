<?php

declare(strict_types=1);

namespace App\Service;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerFactory
{
    private string $stream;
    private int $level;

    public function __construct(string $stream = 'php://stdout', int $level = Logger::INFO)
    {
        $this->stream = $stream;
        $this->level = $level;
    }

    public function create(string $name = 'app'): Logger
    {
        $logger = new Logger($name);
        $handler = new StreamHandler($this->stream, $this->level);
        $handler->setFormatter(new JsonFormatter());
        $logger->pushHandler($handler);

        return $logger;
    }
}
