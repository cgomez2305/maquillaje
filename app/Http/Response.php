<?php
declare(strict_types=1);

namespace App\Http;

final class Response
{
    /** @var array<string,string> */
    private array $headers = [];

    public function __construct(
        private string $body = '',
        private int $status = 200,
        array $headers = [],
    ) {
        $this->headers = $headers + ['Content-Type' => 'text/html; charset=UTF-8'];
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-store'],
        );
    }

    public static function text(string $body, string $type = 'text/plain', int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => $type . '; charset=UTF-8']);
    }

    /**
     * Redirección solo a rutas internas o a la lista blanca (§14.3).
     */
    public static function redirect(string $to, int $status = 302): self
    {
        if (!self::isAllowedTarget($to)) {
            $to = '/';
        }
        return new self('', $status, ['Location' => $to]);
    }

    public static function isAllowedTarget(string $to): bool
    {
        if (str_starts_with($to, '/') && !str_starts_with($to, '//') && !str_contains($to, '\\')) {
            return true;
        }
        $host = strtolower((string) parse_url($to, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($to, PHP_URL_SCHEME));
        $allowed = ['wa.me', 'api.whatsapp.com', 'checkout.wompi.co', parse_url((string) config('app.url'), PHP_URL_HOST)];
        return $scheme === 'https' && in_array($host, $allowed, true);
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /** @return array<string,string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            header_remove('X-Powered-By');
            foreach ($this->headers as $k => $v) {
                header($k . ': ' . $v);
            }
        }
        echo $this->body;
    }
}
