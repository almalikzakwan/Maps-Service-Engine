<?php
declare(strict_types=1);

class EnvManager
{
    private static ?EnvManager $instance = null;
    private array $variables = [];
    private string $envPath;
    private bool $loaded = false;

    private function __construct(string $envPath = '')
    {
        $this->envPath = $envPath ?: $this->getDefaultEnvPath();
        $this->loadEnvironment();
    }

    public static function getInstance(string $envPath = ''): EnvManager
    {
        if (self::$instance === null) {
            self::$instance = new self($envPath);
        }
        return self::$instance;
    }

    private function getDefaultEnvPath(): string
    {
        // Default to project root (one level up from classes directory)
        return dirname(__DIR__) . '/.env';
    }

    private function loadEnvironment(): void
    {
        if ($this->loaded) {
            return;
        }

        if (!file_exists($this->envPath)) {
            throw new Exception("Environment file not found: {$this->envPath}");
        }

        if (!is_readable($this->envPath)) {
            throw new Exception("Environment file is not readable: {$this->envPath}");
        }

        $lines = file($this->envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new Exception("Failed to read environment file: {$this->envPath}");
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || $line[0] === '#') {
                continue;
            }

            $this->parseLine($line);
        }

        $this->loaded = true;
    }

    private function parseLine(string $line): void
    {
        // Handle quoted values and special characters
        if (strpos($line, '=') === false) {
            return;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Remove quotes if present
        if (
            ($value[0] === '"' && substr($value, -1) === '"') ||
            ($value[0] === "'" && substr($value, -1) === "'")
        ) {
            $value = substr($value, 1, -1);
        }

        // Handle escape sequences
        $value = str_replace(['\\n', '\\r', '\\t'], ["\n", "\r", "\t"], $value);

        $this->variables[$key] = $value;

        // Also set as environment variable if not already set
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    public function get(string $key, $default = null)
    {
        return $this->variables[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    public function getString(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        return (float) $this->get($key, $default);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = strtolower($this->getString($key));

        if (in_array($value, ['true', '1', 'yes', 'on'])) {
            return true;
        }

        if (in_array($value, ['false', '0', 'no', 'off', ''])) {
            return false;
        }

        return $default;
    }

    public function getArray(string $key, array $default = []): array
    {
        $value = $this->getString($key);
        if (empty($value)) {
            return $default;
        }

        // Handle JSON arrays
        if ($value[0] === '[' || $value[0] === '{') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : $default;
        }

        // Handle comma-separated values
        return array_map('trim', explode(',', $value));
    }

    public function has(string $key): bool
    {
        return isset($this->variables[$key]) ||
            array_key_exists($key, $_ENV) ||
            getenv($key) !== false;
    }

    public function require(string $key)
    {
        if (!$this->has($key)) {
            throw new Exception("Required environment variable '{$key}' is not set");
        }
        return $this->get($key);
    }

    public function all(): array
    {
        return array_merge($_ENV, $this->variables);
    }

    public function getSecure(string $key, $default = null)
    {
        $value = $this->get($key, $default);

        // Don't log sensitive values
        if ($this->isSensitiveKey($key)) {
            error_log("Accessing sensitive environment variable: {$key}");
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $sensitivePatterns = [
            'PASSWORD',
            'SECRET',
            'KEY',
            'TOKEN',
            'API_KEY',
            'DB_PASS',
            'REDIS_PASS',
            'JWT_SECRET',
            'ENCRYPTION_KEY'
        ];

        $upperKey = strtoupper($key);
        foreach ($sensitivePatterns as $pattern) {
            if (strpos($upperKey, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    public function isProduction(): bool
    {
        return strtolower($this->getString('APP_ENV', 'production')) === 'production';
    }

    public function isDevelopment(): bool
    {
        return strtolower($this->getString('APP_ENV', 'production')) === 'development';
    }

    public function isDebug(): bool
    {
        return $this->getBool('APP_DEBUG', false);
    }

    public function reload(): void
    {
        $this->variables = [];
        $this->loaded = false;
        $this->loadEnvironment();
    }

    // Security: Prevent cloning and serialization
    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize EnvManager singleton");
    }
}