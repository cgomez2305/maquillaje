<?php
declare(strict_types=1);

namespace App\Http;

final class Request
{
    /** @var array<string,string> parámetros de ruta */
    public array $params = [];

    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed> $post
     * @param array<string,mixed> $server
     * @param array<string,mixed> $cookies
     * @param array<string,mixed> $files
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly array $server,
        public readonly array $cookies,
        public readonly array $files = [],
        private readonly ?string $rawBody = null,
    ) {
    }

    public static function capture(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        // Ruta = todo lo anterior a «?» o «#». parse_url() devuelve false con rutas como «/a:1/» y caería en «/»
        $rawPath = (string) strtok($uri, '?#');
        if ($rawPath === '' || $rawPath[0] !== '/') {
            $rawPath = '/' . ltrim((string) preg_replace('#^[a-z][a-z0-9+.-]*://[^/]*#i', '', $rawPath), '/');
        }
        $path = rawurldecode($rawPath);
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        return new self($method, $path, $_GET, $_POST, $_SERVER, $_COOKIE, $_FILES);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    /** Texto recortado y con longitud máxima (lista blanca de caracteres de control). */
    public function str(string $key, int $max = 255, string $default = ''): string
    {
        $v = $this->input($key, $default);
        if (!is_string($v)) {
            return $default;
        }
        $v = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '');
        return mb_substr($v, 0, $max);
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->input($key);
        return is_scalar($v) && preg_match('/^-?\d{1,10}$/', (string) $v) ? (int) $v : $default;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function ip(): string
    {
        // Hostinger / Cloudflare: confiar solo en REMOTE_ADDR salvo que se configure un proxy de confianza
        $ip = (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
        $cf = (string) ($this->server['HTTP_CF_CONNECTING_IP'] ?? '');
        if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP) && (bool) config('app.trust_cloudflare', false)) {
            return $cf;
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public function userAgent(): string
    {
        return mb_substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function referer(): string
    {
        return mb_substr((string) ($this->server['HTTP_REFERER'] ?? ''), 0, 500);
    }

    public function isHttps(): bool
    {
        return (($this->server['HTTPS'] ?? '') !== '' && $this->server['HTTPS'] !== 'off')
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return (string) ($this->server[$key] ?? '');
    }

    public function body(): string
    {
        return $this->rawBody ?? (string) file_get_contents('php://input');
    }

    public function wantsJson(): bool
    {
        return str_contains($this->header('Accept'), 'application/json') || $this->header('X-Requested-With') === 'fetch';
    }

    public function param(string $key): string
    {
        return $this->params[$key] ?? '';
    }
}
