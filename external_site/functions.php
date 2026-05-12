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
    $options = [
        'http' => [
            'method' => $method,
            'ignore_errors' => true,
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        ],
    ];

    if ($method === 'GET' && count($params) > 0) {
        $url .= '?' . http_build_query($params);
    } elseif ($method !== 'GET') {
        $options['http']['content'] = http_build_query($params);
    }

    $response = @file_get_contents($url, false, stream_context_create($options));
    if ($response === false) {
        return [
            'success' => false,
            'message' => 'API request failed. Check API_BASE_URL and server availability.',
            'data' => [],
        ];
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
