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
    // Chemin absolu/relatif vers le fichier de log (stream en termes Monolog).
    private string $stream;
    // Seuil de niveau de log Monolog.
    private int $level;

    public function __construct(?string $stream = null, int $level = Logger::INFO)
    {
        // Par defaut, on ecrit dans api/logs/app.log si aucun stream n'est fourni.
        $this->stream = $stream ?? __DIR__ . '/../../logs/app.log';
        $this->level = $level;
    }

    public function create(string $name = 'app'): LoggerInterface
    {
        // On s'assure que le dossier de logs existe avant de creer le handler.
        $this->ensureLogDirectoryExists();

        $logger = new Logger($name);
        // Ecrit les logs vers le stream configure au niveau indique.
        $handler = new StreamHandler($this->stream, $this->level);
        // Formatter JSON pour des logs structures.
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
