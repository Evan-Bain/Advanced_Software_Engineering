<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function siteUrl(string $path = ''): string
{
    return rtrim(EXTERNAL_SITE_BASE_PATH, '/') . '/' . ltrim($path, '/');
}

function apiCall(string $method, string $endpoint, array $params = []): array
{
    $url = rtrim(API_BASE_URL, '/') . '/' . ltrim($endpoint, '/');
    $body = '';
    $headers = [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
        'Origin: ' . EXTERNAL_SITE_ORIGIN,
    ];

    if ($method === 'GET' && count($params) > 0) {
        $url .= '?' . http_build_query($params);
    } elseif ($method !== 'GET') {
        $body = http_build_query($params);
    }

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        if ($method !== 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $headerSize = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);

        if ($response === false) {
            return [
                'success' => false,
                'message' => 'API request failed: ' . ($error !== '' ? $error : 'cURL could not reach the API.'),
                'data' => [],
            ];
        }

        $response = substr($response, $headerSize);
    } else {
        $options = [
            'http' => [
                'method' => $method,
                'ignore_errors' => true,
                'follow_location' => 1,
                'max_redirects' => 5,
                'header' => implode("\r\n", $headers) . "\r\n",
                'timeout' => 10,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];

        if ($method !== 'GET') {
            $options['http']['content'] = $body;
        }

        $response = @file_get_contents($url, false, stream_context_create($options));
        if ($response === false) {
            $error = error_get_last();
            return [
                'success' => false,
                'message' => 'API request failed: ' . ($error['message'] ?? 'Check API_BASE_URL and server availability.'),
                'data' => [],
            ];
        }

        $statusCode = 0;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches) === 1) {
                    $statusCode = (int) $matches[1];
                }
            }
        }
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        $detail = trim(strip_tags((string) $response));
        if ($detail !== '') {
            $detail = ' Response: ' . substr($detail, 0, 160);
        }

        return [
            'success' => false,
            'message' => 'API returned invalid JSON' . ($statusCode > 0 ? " with HTTP $statusCode." : '.') . $detail,
            'data' => [],
        ];
    }

    return $decoded;
}

function selected(string $actual, string $expected): string
{
    return $actual === $expected ? ' selected' : '';
}

function responseMessage(?array $response): string
{
    if ($response === null) {
        return '';
    }

    $class = !empty($response['success']) ? 'success' : 'error';
    return '<div class="message ' . $class . '">' . h($response['message'] ?? 'No message returned.') . '</div>';
}
