<?php

declare(strict_types=1);

namespace App\Service;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use RuntimeException;

class LoggerFactory
{
    private string $stream;
    private int $level;

    public function __construct(?string $stream = null, int $level = Logger::INFO)
    {
        $this->stream = $stream ?? __DIR__ . '/../../logs/app.log';
        $this->level = $level;
    }

    public function create(string $name = 'app'): LoggerInterface
    {
        $this->ensureLogDirectoryExists();

        $logger = new Logger($name);
        $handler = new StreamHandler($this->stream, $this->level);
        $handler->setFormatter(new JsonFormatter());
        $logger->pushHandler($handler);

        return $logger;
    }

    private function ensureLogDirectoryExists(): void
    {
        $directory = \dirname($this->stream);

        if (\is_dir($directory)) {
            return;
        }

        if (!@mkdir($directory, 0775, true) && !\is_dir($directory)) {
            throw new RuntimeException(sprintf('Unable to create log directory: %s', $directory));
        }
     }
}
