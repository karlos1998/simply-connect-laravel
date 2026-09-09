<?php

declare(strict_types=1);

$path = $argv[1] ?? null;

if (! is_string($path) || ! is_file($path)) {
    fwrite(STDERR, "Usage: php scripts/verify-openapi.php <openapi.json>\n");
    exit(2);
}

$contents = file_get_contents($path);
$document = is_string($contents) ? json_decode($contents, true) : null;

if (! is_array($document)) {
    fwrite(STDERR, "The OpenAPI document is not valid JSON.\n");
    exit(2);
}

$requirements = [
    ['paths', '/api/v1/external/messages', 'post', 'responses', '202'],
    ['paths', '/api/v1/external/messages/{messageId}', 'get'],
    ['paths', '/api/v1/external/endpoints', 'get'],
    ['components', 'schemas', 'ExternalSmsRequest'],
    ['components', 'schemas', 'SmsSendResult'],
    ['components', 'schemas', 'ExternalMessageDetails'],
];

$missing = [];

foreach ($requirements as $segments) {
    $value = $document;

    foreach ($segments as $segment) {
        if (! is_array($value) || ! array_key_exists($segment, $value)) {
            $missing[] = implode(' → ', $segments);

            continue 2;
        }

        $value = $value[$segment];
    }
}

$parameters = $document['paths']['/api/v1/external/messages']['post']['parameters'] ?? [];
$hasIdempotencyKey = false;

if (is_array($parameters)) {
    foreach ($parameters as $parameter) {
        if (is_array($parameter)
            && ($parameter['name'] ?? null) === 'Idempotency-Key'
            && ($parameter['in'] ?? null) === 'header') {
            $hasIdempotencyKey = true;

            break;
        }
    }
}

if (! $hasIdempotencyKey) {
    $missing[] = 'POST /api/v1/external/messages → Idempotency-Key header';
}

if ($missing !== []) {
    fwrite(STDERR, "The canonical External API contract is missing:\n- ".implode("\n- ", $missing)."\n");
    exit(1);
}

fwrite(STDOUT, "Simply Connect External API contract is compatible.\n");
