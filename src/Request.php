<?php

namespace Mgx\Router;

/**
 * HTTP istek bilgilerini temsil eden sınıf.
 * Router tarafından otomatik olarak oluşturulur ve route/middleware handler'larına geçilir.
 */
class Request
{
    /**
     * HTTP methodu (GET, POST, vs.)
     */
    protected string $method;

    /**
     * İstek URI (query string'siz)
     */
    protected string $uri;

    /**
     * Query string parametreleri ($_GET)
     */
    protected array $query = [];

    /**
     * Form post veya JSON verisi
     */
    protected array $body = [];

    /**
     * HTTP header bilgileri
     */
    protected array $headers = [];

    /**
     * Route tarafından atanacak path parametreleri (ör: /users/{id})
     */
    protected array $routeParams = [];

    /**
     * Kullanıcı tanımlı ekstra alanlar (auth middleware gibi)
     */
    protected array $attributes = [];

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $this->parseUri();
        $this->query = $_GET ?? [];
        $this->headers = $this->parseHeaders();
        $this->body = $this->parseBody();
    }

    /**
     * URI'yi query string'ten temizleyerek getirir.
     */
    protected function parseUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return parse_url($uri, PHP_URL_PATH) ?? '/';
    }

    /**
     * Header bilgilerini PHP global'lerinden alır.
     */
    protected function parseHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * Body verisini (form veya JSON) parse eder.
     */
    protected function parseBody(): array
    {
        if ($this->method === 'POST') {
            return $_POST ?? [];
        }

        // JSON body mi?
        $contentType = $this->header('content-type');

        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            return json_decode($input, true) ?? [];
        }

        return [];
    }

    // -------------------------------
    // Erişim metodları
    // -------------------------------

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function query(string $key = null, $default = null): mixed
    {
        return $key ? ($this->query[$key] ?? $default) : $this->query;
    }

    public function input(string $key = null, $default = null): mixed
    {
        return $key ? ($this->body[$key] ?? $default) : $this->body;
    }

    public function header(string $key, $default = null): mixed
    {
        return $this->headers[strtolower($key)] ?? $default;
    }

    public function ip(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    // -------------------------------
    // Route parametreleri
    // -------------------------------

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key, $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function allParams(): array
    {
        return $this->routeParams;
    }

    // -------------------------------
    // Custom attribute (middleware içi veri taşıma)
    // -------------------------------

    public function set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    // Örnek özel method: user()
    public function user(): mixed
    {
        return $this->get('user');
    }
}
