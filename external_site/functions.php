<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function apiCall(string $method, string $endpoint, array $params = []): array
{
    $url = rtrim(API_BASE_URL, '/') . '/' . ltrim($endpoint, '/');
    $body = '';

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
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 10,
        ]);

        if ($method !== 'GET') {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            return [
                'success' => false,
                'message' => 'API request failed: ' . ($error !== '' ? $error : 'cURL could not reach the API.'),
                'data' => [],
            ];
        }
    } else {
        $options = [
        'http' => [
            'method' => $method,
            'ignore_errors' => true,
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'timeout' => 10,
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
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return [
            'success' => false,
            'message' => 'API returned invalid JSON.',
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
