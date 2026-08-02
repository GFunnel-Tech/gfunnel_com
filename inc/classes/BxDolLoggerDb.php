<?php

declare(strict_types=1);

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

final class BxDolLoggerDb extends AbstractLogger
{
    private const LEVELS = [
        LogLevel::DEBUG     => 0,
        LogLevel::INFO      => 1,
        LogLevel::NOTICE    => 2,
        LogLevel::WARNING   => 3,
        LogLevel::ERROR     => 4,
        LogLevel::CRITICAL  => 5,
        LogLevel::ALERT     => 6,
        LogLevel::EMERGENCY => 7,
    ];

    private \PDO $pdo;
    private \PDOStatement $stmt;
    private int $minimumLevel;
    private string $channel;

    public function __construct(string $channel = 'system')
    {
        $this->channel      = $channel;
        $this->minimumLevel = self::LEVELS[LogLevel::INFO];

        $this->pdo = BxDolDb::getInstance()->getLink();

        $this->stmt = $this->pdo->prepare(
            'INSERT INTO `sys_logger` (level, message, context, channel, created_at)
             VALUES (:level, :message, :context, :channel, :created_at)'
        );
    }

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $level = (string) $level;

        if (!isset(self::LEVELS[$level])) {
            throw new \Psr\Log\InvalidArgumentException("Unknown log level: {$level}");
        }

        if (self::LEVELS[$level] < $this->minimumLevel) {
            return;
        }

        try {
            $this->stmt->execute([
                ':level'      => $level,
                ':message'    => $this->interpolate((string) $message, $context),
                ':context'    => $this->contextToString($context),
                ':channel'    => $this->channel,
                ':created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.v'),
            ]);
        } catch (Throwable) {
            // Swallow — logging must never crash the application
        }
    }

    private function contextToString(array $context): ?string
    {
        if (empty($context)) {
            return null;
        }

        $parts = [];

        if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
            $e = $context['exception'];
            $parts[] = sprintf(
                "[exception] %s(%d): %s\nFile: %s:%d\nTrace:\n%s",
                $e::class, $e->getCode(), $e->getMessage(),
                $e->getFile(), $e->getLine(), $e->getTraceAsString()
            );
            unset($context['exception']);
        }

        foreach ($context as $key => $value) {
            $parts[] = sprintf('[%s] %s', $key, match (true) {
                is_null($value)                       => 'null',
                is_bool($value)                       => $value ? 'true' : 'false',
                is_scalar($value)                     => (string) $value,
                $value instanceof Stringable          => (string) $value,
                is_array($value) || is_object($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                default                               => gettype($value),
            });
        }

        return implode("\n", $parts);
    }

    private function interpolate(string $message, array $context): string
    {
        if (!str_contains($message, '{')) {
            return $message;
        }

        $replace = [];
        foreach ($context as $key => $value) {
            if (!is_array($value) && (!is_object($value) || method_exists($value, '__toString'))) {
                $replace['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replace);
    }
}