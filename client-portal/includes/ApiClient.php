<?php

declare(strict_types=1);

final class ApiClient
{
    public function __construct(
        private string $baseUrl,
        private string $token,
    ) {
        $this->baseUrl = rtrim($this->baseUrl, '/');
    }

    /** @return array{status: int, body: mixed, error: ?string} */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, null, $query);
    }

    /** @return array{status: int, body: mixed, error: ?string} */
    public function patch(string $path, array $data): array
    {
        return $this->request('PATCH', $path, $data);
    }

    /** @return array{status: int, body: mixed, error: ?string} */
    public function post(string $path, array $data): array
    {
        return $this->request('POST', $path, $data);
    }

    /** @return array{status: int, body: mixed, error: ?string} */
    public function put(string $path, array $data): array
    {
        return $this->request('PUT', $path, $data);
    }

    /** @return array{status: int, body: mixed, error: ?string} */
    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /** @return array{status: int, body: mixed, error: ?string, raw: ?string} */
    private function request(string $method, string $path, ?array $data = null, array $query = []): array
    {
        $url = $this->baseUrl.'/'.ltrim($path, '/');
        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return ['status' => 0, 'body' => null, 'error' => 'cURL başlatılamadı'];
        }

        $headers = [
            'Accept: application/json',
            'Authorization: Bearer '.$this->token,
        ];

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
        ];

        if ($data !== null) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_HTTPHEADER] = $headers;
            $opts[CURLOPT_POSTFIELDS] = $json;
        }

        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL) ?: null;
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['status' => 0, 'body' => null, 'error' => $err ?: 'Bağlantı hatası', 'raw' => null, 'redirect_url' => null];
        }

        $body = json_decode($raw, true);

        return [
            'status' => $status,
            'body' => is_array($body) ? $body : null,
            'error' => is_array($body) ? null : ($err ?: (is_string($raw) && $raw !== '' ? 'JSON olmayan yanıt' : null)),
            'raw' => is_array($body) ? null : (is_string($raw) ? substr($raw, 0, 300) : null),
            'redirect_url' => is_string($redirectUrl) && $redirectUrl !== '' ? $redirectUrl : null,
        ];
    }
}
